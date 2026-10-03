<?php

namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\web\View;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\Plugin;
use KlaviyoAPI\ApiException;

class ListsField extends Field
{
	public static function displayName(): string
	{
		return Craft::t('klaviyoconnect', 'fields.lists.displayName');
	}

	public function getStaticHtml(mixed $value, ElementInterface $element): string
	{
		return (string) Html::disableInputs(fn (): string => $this->listsInputHtml($value, $element->siteId));
	}

	/**
	 * Renders the list input from the stored lists.
	 */
	public function listsInputHtml(mixed $value, ?int $siteId): string
	{
		try {
			$lists = Plugin::getInstance()->api->getAvailableLists($siteId);
		} catch (ApiException) {
			$lists = [];
		}

		$listOptions = [];
		foreach ($lists as $list) {
			$listOptions[$list->id] = $list->name;
		}

		/** @var string[] $listIds */
		$listIds = ArrayHelper::getColumn(is_array($value) ? $value : [], 'id');

		// Offer saved lists from another Klaviyo account, so saving here doesn't remove them
		foreach ($listIds as $listId) {
			$listOptions[$listId] ??= Craft::t('klaviyoconnect', 'fields.list.notInAccount', [
				'id' => $listId,
			]);
		}

		return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/checkboxgroup', [
			'name' => $this->handle,
			'options' => $listOptions,
			'values' => $listIds,
		]);
	}

	public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
	{
		if (! is_array($value) || $value === []) {
			return [];
		}

		try {
			$listsById = ArrayHelper::index(Plugin::getInstance()->api->getLists($element?->siteId), 'id');
		} catch (ApiException) {
			$listsById = [];
		}

		$listIds = array_filter(
			array_map(static fn (mixed $list): mixed => is_array($list) || is_object($list) ? ArrayHelper::getValue($list, 'id') : $list, $value),
			static fn (mixed $listId): bool => is_string($listId) && $listId !== '',
		);

		// Keep IDs missing from this account's lists, since they may belong to another site's account or Klaviyo may be unreachable
		return array_values(array_map(
			static fn (string $listId): KlaviyoList => $listsById[$listId] ?? new KlaviyoList([
				'id' => $listId,
				'name' => $listId,
			]),
			$listIds,
		));
	}

	/**
	 * Keeps `$element` optional for callers written for 7.2.5.
	 */
	public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
	{
		return parent::getInputHtml($value, $element);
	}

	protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline = false): string
	{
		return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/refreshable', [
			'field' => $this,
			'siteId' => $element?->siteId,
			'inputHtml' => $this->listsInputHtml($value, $element?->siteId),
		], View::TEMPLATE_MODE_CP);
	}
}
