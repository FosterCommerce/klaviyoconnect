<?php

namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;
use craft\commerce\models\ProductType;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\web\View;
use fostercommerce\klaviyoconnect\services\Onsite;
use Twig\Error\SyntaxError;
use Twig\Source;

class Settings extends Model
{
	public string $klaviyoSiteId = '';

	public string $klaviyoApiKey = '';

	/**
	 * List IDs, or `*` for every list in the account.
	 *
	 * @var string[]|string
	 */
	public array|string $klaviyoAvailableLists = [];

	public bool $klaviyoListsAll = false;

	/**
	 * @var string[]
	 */
	public array $klaviyoAvailableGroups = [];

	public string $cartUrl = '/shop/cart';

	public string $productImageField = 'productImage';

	public string $productImageFieldTransformation = 'productThumbnail';

	/**
	 * `none`, `craft`, `imagerx` or `smallpics`. Null until the settings are saved, so an install from before 7.3.0 keeps its transform.
	 */
	public ?string $imageEngine = null;

	/**
	 * A string until validated, so the integer rule sees the posted value.
	 */
	public int|string|null $imageWidth = null;

	/**
	 * A string until validated, so the integer rule sees the posted value.
	 */
	public int|string|null $imageHeight = null;

	public string $imageFit = 'crop';

	/**
	 * Mappings keyed by product type UID, or by handle in a config file, with `catalogItemId`, `productImageField`, `variantImageField`, `categoryField` and `brandField`.
	 *
	 * @var array<string, array<string, string>>
	 */
	public array $productTypeFields = [];

	public string $eventPrefix = '';

	public bool $injectOnsiteScript = false;

	/**
	 * URI patterns where the tracking script isn't added, in the same shape as Blitz's excluded URI patterns.
	 *
	 * @var array<int, array{enabled?: bool|string, siteUid?: string, uriPattern?: mixed}>
	 */
	public array $excludedUriPatterns = [];

	// Tracking Event Options
	/**
	 * Kept so a pre-7.3.0 install with user syncing switched off stays off until its settings are saved.
	 */
	public bool $trackSaveUser = true;

	public bool $trackCommerceStartedCheckout = true;

	public bool $trackCommerceAddedToCart = true;

	public bool $trackCommerceCartUpdated = true;

	public bool $trackCommerceOrderCompleted = true;

	public bool $trackCommerceStatusUpdated = true;

	public bool $trackCommerceRefunded = true;

	/**
	 * Order status handles that send Klaviyo's Fulfilled Order event.
	 *
	 * @var string[]
	 */
	public array $fulfilledOrderStatuses = [];

	/**
	 * Order status handles that send Klaviyo's Cancelled Order event.
	 *
	 * @var string[]
	 */
	public array $cancelledOrderStatuses = [];

	// Event Data Options
	public bool $sendAddresses = true;

	public bool $sendCategories = true;

	public bool $sendPricingDetail = true;

	public bool $sendSiteContext = true;

	/**
	 * Sources keyed by Klaviyo profile attribute: a user field handle, or `__twig__` with a `twig` template.
	 *
	 * @var array<string, array{userField?: string, twig?: string}>
	 */
	public array $profileAttributeFields = [
		// Send the user ID as External ID by default, matching versions before 7.3.0
		'external_id' => [
			'userField' => '__userId__',
		],
	];

	/**
	 * Custom profile properties, each with a `name` and a `userField` handle, or `__twig__` with a `twig` template.
	 *
	 * @var array<int|string, array{name?: string, userField?: string, twig?: string}>
	 */
	public array $profileCustomProperties = [];

	/**
	 * Each site's keys, lists, cart URL and event prefix, keyed by site UID.
	 * A blank value uses the setting of the same name, set by a config file or saved before 7.3.0.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $siteSettings = [];

	/**
	 * Returns each mapped Klaviyo profile attribute's source, without blank rows.
	 *
	 * @return array<string, array{userField: string, twig: string}>
	 */
	public function getProfileAttributeFields(): array
	{
		$attributeFields = [];
		foreach ($this->profileAttributeFields as $attribute => $row) {
			$userField = $row['userField'] ?? '';
			if (is_string($userField) && $userField !== '') {
				$attributeFields[$attribute] = [
					'userField' => $userField,
					'twig' => is_string($row['twig'] ?? null) ? trim($row['twig']) : '',
				];
			}
		}

		return $attributeFields;
	}

	/**
	 * Returns each custom property's source keyed by property name, without blank rows.
	 *
	 * @return array<string, array{userField: string, twig: string}>
	 */
	public function getProfileCustomProperties(): array
	{
		$customProperties = [];
		foreach ($this->profileCustomProperties as $profileCustomProperty) {
			$name = is_string($profileCustomProperty['name'] ?? null) ? trim($profileCustomProperty['name']) : '';
			$userField = $profileCustomProperty['userField'] ?? '';
			if ($name !== '' && is_string($userField) && $userField !== '') {
				$customProperties[$name] = [
					'userField' => $userField,
					'twig' => is_string($profileCustomProperty['twig'] ?? null) ? trim($profileCustomProperty['twig']) : '',
				];
			}
		}

		return $customProperties;
	}

	public function getProductTypeField(?ProductType $productType, string $column): string
	{
		$productTypeSettings = $this->productTypeFields[(string) $productType?->uid] ?? $this->productTypeFields[(string) $productType?->handle] ?? [];
		$fieldHandle = $productTypeSettings[$column] ?? '';

		// Fall back to the pre-7.3.0 image field, which applied to products and variants alike
		if ($fieldHandle === '' && in_array($column, ['productImageField', 'variantImageField'], true)) {
			return $this->productImageField;
		}

		return $fieldHandle;
	}

	public function getImageEngine(): string
	{
		if ($this->imageEngine !== null && $this->imageEngine !== '') {
			return $this->imageEngine;
		}

		// Keep a pre-7.3.0 transform working until the settings are saved, and send original images when it doesn't exist
		return $this->productImageFieldTransformation !== '' && Craft::$app->getImageTransforms()->getTransformByHandle($this->productImageFieldTransformation) !== null ? 'craft' : 'none';
	}

	public function getApiKey(?int $siteId = null): string
	{
		return $this->stringSetting('klaviyoApiKey', $siteId);
	}

	public function getPublicApiKey(?int $siteId = null): string
	{
		return $this->stringSetting('klaviyoSiteId', $siteId);
	}

	public function getCartUrl(?int $siteId = null): string
	{
		return $this->stringSetting('cartUrl', $siteId);
	}

	public function getEventPrefix(?int $siteId = null): string
	{
		return $this->stringSetting('eventPrefix', $siteId);
	}

	/**
	 * @return string[]
	 */
	public function getAvailableLists(?int $siteId = null): array
	{
		$lists = $this->siteSetting('klaviyoAvailableLists', $siteId);

		return is_array($lists) ? array_values(array_filter($lists, static fn (mixed $listId): bool => is_string($listId) && $listId !== '')) : [];
	}

	public function getListsAll(?int $siteId = null): bool
	{
		$siteLists = $this->settingsForSite($siteId)['klaviyoAvailableLists'] ?? null;
		if (in_array($siteLists, [null, '', []], true)) {
			return $this->klaviyoListsAll || $this->klaviyoAvailableLists === '*';
		}

		return $siteLists === '*';
	}

	public function beforeValidate(): bool
	{
		foreach ($this->siteSettings as $siteUid => $siteSettings) {
			if (! is_array($siteSettings)) {
				continue;
			}

			// Trim pasted keys and paths, since a stray space keeps an env var name from resolving
			$siteSettings = array_map(static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $siteSettings);

			$this->siteSettings[$siteUid] = $siteSettings;
		}

		return parent::beforeValidate();
	}

	public function afterValidate(): void
	{
		foreach (['imageWidth', 'imageHeight'] as $attribute) {
			// Store an integer, and clear an invalid size from a hidden field, which the integer rule skips
			if (! $this->hasErrors($attribute)) {
				$size = filter_var($this->{$attribute}, FILTER_VALIDATE_INT, [
					'options' => [
						'min_range' => 1,
					],
				]);
				$this->{$attribute} = $size === false ? null : $size;
			}
		}

		parent::afterValidate();
	}

	/**
	 * Matches when the settings page shows the width and height.
	 */
	public function usesImageSize(): bool
	{
		$imageEngine = $this->getImageEngine();

		return $imageEngine !== 'none' && ! ($imageEngine === 'craft' && $this->productImageFieldTransformation !== '');
	}

	/**
	 * @return array<int, mixed>
	 */
	protected function defineRules(): array
	{
		return [
			...parent::defineRules(),
			[['profileAttributeFields'], function (): void {
				$this->validateTwigSources('profileAttributeFields', $this->getProfileAttributeFields());
			}],
			[['profileCustomProperties'], function (): void {
				$customProperties = $this->getProfileCustomProperties();
				foreach (array_keys($customProperties) as $name) {
					// Reject a numeric name, since merging profile properties renumbers integer keys
					if (is_numeric($name)) {
						$this->addError('profileCustomProperties', Craft::t('klaviyoconnect', 'settings.profileCustomProperties.numericName', [
							'name' => $name,
						]));
					}
				}

				$this->validateTwigSources('profileCustomProperties', $customProperties);
			}],
			[['imageEngine'],
				'in',
				'range' => ['none', 'craft', 'imagerx', 'smallpics']],
			[['imageWidth', 'imageHeight'],
				'integer',
				'min' => 1,
				'when' => fn (): bool => $this->usesImageSize()],
			[['imageFit'],
				'in',
				'range' => ['crop', 'fit']],
			[['productTypeFields'], function (): void {
				$this->validateTwigSources('productTypeFields', $this->getProductTypeTwigSources());
			}],
			[['siteSettings'], function (): void {
				foreach ($this->siteSettings as $siteUid => $siteSettings) {
					$this->validateSiteSettings((string) $siteUid, $siteSettings);
				}
			}],
			[['excludedUriPatterns'], function (): void {
				foreach ($this->excludedUriPatterns as $excludedUriPattern) {
					$uriPattern = $excludedUriPattern['uriPattern'] ?? '';
					if (! is_string($uriPattern) || @preg_match(Onsite::uriPatternRegex($uriPattern), '') === false) {
						$this->addError('excludedUriPatterns', Craft::t('klaviyoconnect', 'settings.excludedUriPatterns.invalid', [
							'pattern' => is_string($uriPattern) ? $uriPattern : '',
						]));
					}
				}
			}],
		];
	}

	/**
	 * @param array<string, array{userField: string, twig: string}> $sources
	 */
	private function validateTwigSources(string $attribute, array $sources): void
	{
		// Parse with the site Twig environment that renders the templates, so control panel-only functions fail here
		$view = Craft::$app->getView();
		$templateMode = $view->getTemplateMode();
		$view->setTemplateMode(View::TEMPLATE_MODE_SITE);
		$twig = $view->getTwig();

		foreach ($sources as $name => $source) {
			if ($source['userField'] !== '__twig__') {
				continue;
			}

			try {
				$twig->parse($twig->tokenize(new Source($source['twig'], (string) $name)));
			} catch (SyntaxError $syntaxError) {
				$this->addError($attribute, Craft::t('klaviyoconnect', 'settings.profileAttributes.invalidTwig', [
					'attribute' => $attribute === 'profileAttributeFields' ? Craft::t('klaviyoconnect', 'settings.profileAttributes.' . StringHelper::toCamelCase((string) $name)) : $name,
					'error' => $syntaxError->getRawMessage(),
				]));
			}
		}

		$view->setTemplateMode($templateMode);
	}

	/**
	 * @return array<string, array{userField: string, twig: string}>
	 */
	private function getProductTypeTwigSources(): array
	{
		$twigSources = [];
		$productTypesService = Craft::$app->getPlugins()->isPluginEnabled('commerce') ? Commerce::getInstance()?->getProductTypes() : null;
		foreach ($this->productTypeFields as $productTypeKey => $productTypeSettings) {
			$productType = $productTypesService?->getProductTypeByUid((string) $productTypeKey) ?? $productTypesService?->getProductTypeByHandle((string) $productTypeKey);
			foreach (['category', 'brand'] as $setting) {
				if (($productTypeSettings[$setting . 'Field'] ?? '') === '__twig__') {
					$twigSources[Craft::t('klaviyoconnect', 'settings.productTypeFields.' . $setting) . ' (' . ($productType->name ?? $productTypeKey) . ')'] = [
						'userField' => '__twig__',
						'twig' => is_string($productTypeSettings[$setting . 'Twig'] ?? null) ? trim($productTypeSettings[$setting . 'Twig']) : '',
					];
				}
			}
		}

		return $twigSources;
	}

	private function validateSiteSettings(string $siteUid, mixed $siteSettings): void
	{
		$site = ArrayHelper::firstWhere(Craft::$app->getSites()->getAllSites(true), 'uid', $siteUid);
		$siteLabel = $site instanceof Site ? $site->getUiLabel() : $siteUid;
		if (! is_array($siteSettings)) {
			$this->addError('siteSettings', Craft::t('klaviyoconnect', 'settings.sites.invalidValue', [
				'site' => $siteLabel,
			]));

			return;
		}

		foreach (['klaviyoSiteId', 'klaviyoApiKey', 'cartUrl', 'eventPrefix'] as $column) {
			$value = $siteSettings[$column] ?? '';
			if (! is_string($value)) {
				$this->addError('siteSettings', Craft::t('klaviyoconnect', 'settings.sites.invalidValue', [
					'site' => $siteLabel,
				]));

				return;
			}
		}

		// Guzzle can't send a key containing a line break or other control character as a header
		foreach (['klaviyoSiteId', 'klaviyoApiKey'] as $keyColumn) {
			if (preg_match('/[\x00-\x1F\x7F]/', $siteSettings[$keyColumn] ?? '') === 1) {
				$this->addError('siteSettings', Craft::t('klaviyoconnect', 'settings.sites.invalidKey', [
					'site' => $siteLabel,
				]));
			}
		}

		// Klaviyo limits metric names to 127 characters, and the longest built-in event name is 15
		$eventPrefix = $siteSettings['eventPrefix'] ?? '';
		if (mb_strlen((string) $eventPrefix) > 60 || preg_match('/[<>\x00-\x1F\x7F]/', (string) $eventPrefix) === 1) {
			$this->addError('siteSettings', Craft::t('klaviyoconnect', 'settings.sites.invalidEventPrefix', [
				'site' => $siteLabel,
			]));
		}

		if (preg_match('/[\s<>"]/', $siteSettings['cartUrl'] ?? '') === 1) {
			$this->addError('siteSettings', Craft::t('klaviyoconnect', 'settings.sites.invalidCartUrl', [
				'site' => $siteLabel,
			]));
		}
	}

	private function stringSetting(string $name, ?int $siteId): string
	{
		$value = $this->siteSetting($name, $siteId);

		// Trim the resolved value too, since an env var's trailing line break would make the key unsendable
		return is_string($value) ? trim((string) App::parseEnv($value)) : '';
	}

	private function siteSetting(string $name, ?int $siteId): mixed
	{
		$siteValue = $this->settingsForSite($siteId)[$name] ?? null;

		return in_array($siteValue, [null, '', []], true) ? $this->{$name} : $siteValue;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function settingsForSite(?int $siteId): array
	{
		$sitesService = Craft::$app->getSites();
		$site = $siteId === null ? $sitesService->getCurrentSite() : $sitesService->getSiteById($siteId, true);

		return $site === null ? [] : $this->siteSettings[$site->uid] ?? [];
	}
}
