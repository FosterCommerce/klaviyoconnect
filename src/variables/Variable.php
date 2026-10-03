<?php

namespace fostercommerce\klaviyoconnect\variables;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\OrderStatus;
use craft\commerce\Plugin as Commerce;
use craft\fields\Assets;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\fields\PlainText;
use fostercommerce\klaviyoconnect\helpers\ImageUrl;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\Plugin;
use KlaviyoAPI\ApiException;

class Variable
{
	private ?string $error = null;

	/**
	 * Returns the lists of the given site's Klaviyo account, or the current site's.
	 *
	 * @return KlaviyoList[]|null Declared `mixed`, so a subclass written for 7.2.5 still loads
	 */
	public function lists(?int $siteId = null): mixed
	{
		$this->error = null;

		return $this->listsForApiKey(Plugin::getInstance()->getSettings()->getApiKey($siteId));
	}

	/**
	 * @return string|null Declared `mixed`, so a subclass written for 7.2.5 still loads
	 */
	public function error(): mixed
	{
		return $this->error;
	}

	/**
	 * Tracks Viewed Product in Klaviyo for the product on this page, or the given variant.
	 */
	public function viewedProduct(Product $product, ?Variant $variant = null): void
	{
		Plugin::getInstance()->onsite->registerViewedProduct(Craft::$app->getView(), $product, $variant);
	}

	/**
	 * Returns every store's order statuses as options, one per handle.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public function orderStatusOptions(): array
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
			return [];
		}

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();

		$optionsByHandle = [];
		$stores = $commerce->getStores()->getAllStores();
		foreach ($stores as $store) {
			/** @var OrderStatus $orderStatus */
			foreach ($commerce->getOrderStatuses()->getAllOrderStatuses($store->id) as $orderStatus) {
				$optionsByHandle[(string) $orderStatus->handle] ??= [
					'label' => $orderStatus->getDisplayName(),
					'value' => (string) $orderStatus->handle,
				];
			}
		}

		return array_values($optionsByHandle);
	}

	/**
	 * Returns fields on product or variant layouts as options, filtered by `relation`, `categories`, `brands`, `assets` or `any`.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public function productFieldOptions(string $kind = 'any', ?string $level = null): array
	{
		$options = [[
			'label' => '',
			'value' => '',
		]];
		if (! Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
			return $options;
		}

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$fieldsByHandle = [];
		foreach ($commerce->getProductTypes()->getAllProductTypes() as $productType) {
			$layouts = array_filter([
				Craft::t('klaviyoconnect', 'settings.productField.product') => $level === 'variant' ? null : $productType->getFieldLayout(),
				Craft::t('klaviyoconnect', 'settings.productField.variant') => $level === 'product' ? null : $productType->getVariantFieldLayout(),
			]);
			foreach ($layouts as $layoutLabel => $fieldLayout) {
				foreach ($fieldLayout->getCustomFields() as $field) {
					$matchesKind = match ($kind) {
						'relation' => $field instanceof BaseRelationField && ! $field instanceof Assets,
						'categories' => ($field instanceof BaseRelationField && ! $field instanceof Assets) || $field instanceof BaseOptionsField,
						'brands' => ($field instanceof BaseRelationField && ! $field instanceof Assets) || $field instanceof BaseOptionsField || $field instanceof PlainText,
						'assets' => $field instanceof Assets,
						default => true,
					};
					if ($matchesKind) {
						$fieldsByHandle[(string) $field->handle][$layoutLabel] = in_array($field->name, [null, '', '__blank__'], true) ? (string) $field->handle : $field->name;
					}
				}
			}
		}

		foreach ($fieldsByHandle as $fieldHandle => $namesByLayout) {
			$options[] = [
				'label' => $level === null ? reset($namesByLayout) . ' (' . implode(', ', array_keys($namesByLayout)) . ')' : (string) reset($namesByLayout),
				'value' => $fieldHandle,
			];
		}

		return $options;
	}

	/**
	 * @return array<int, array{label: string, value: string}>
	 */
	public function imageTransformOptions(string $savedHandle = ''): array
	{
		$options = [[
			'label' => Craft::t('klaviyoconnect', 'settings.productImageTransform.customSize'),
			'value' => '',
		]];
		$imageTransforms = Craft::$app->getImageTransforms()->getAllTransforms();
		foreach ($imageTransforms as $imageTransform) {
			$options[] = [
				'label' => (string) $imageTransform->name,
				'value' => (string) $imageTransform->handle,
			];
		}

		// Offer a saved handle with no transform, so saving the page doesn't switch it to the original image
		if ($savedHandle !== '' && ! in_array($savedHandle, array_column($options, 'value'), true)) {
			$options[] = [
				'label' => Craft::t('klaviyoconnect', 'settings.productImageTransform.missing', [
					'handle' => $savedHandle,
				]),
				'value' => $savedHandle,
			];
		}

		return $options;
	}

	/**
	 * Offers Imager X and Small Pics only when installed.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public function imageEngineOptions(): array
	{
		$options = [];
		foreach (['none', 'craft', ...array_keys(ImageUrl::PLUGIN_ENGINES)] as $imageEngine) {
			$pluginHandle = ImageUrl::PLUGIN_ENGINES[$imageEngine][0] ?? null;
			if ($pluginHandle === null || Craft::$app->getPlugins()->isPluginEnabled($pluginHandle)) {
				$options[] = [
					'label' => Craft::t('klaviyoconnect', 'settings.imageEngine.' . $imageEngine),
					'value' => $imageEngine,
				];
			}
		}

		return $options;
	}

	/**
	 * @return array{message: string, date: string}|null
	 */
	public function lastError(): ?array
	{
		return Plugin::getInstance()->api->getLastError();
	}

	/**
	 * @return KlaviyoList[]|null
	 */
	private function listsForApiKey(string $apiKey): ?array
	{
		if ($apiKey === '') {
			return null;
		}

		try {
			$lists = Plugin::getInstance()->api->getListsForApiKey($apiKey);

			return $lists === [] ? null : $lists;
		} catch (ApiException $apiException) {
			$this->error = Plugin::getInstance()->api->errorMessage($apiException);

			return null;
		}
	}
}
