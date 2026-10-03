<?php

namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Html;
use craft\web\View;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\Plugin;
use KlaviyoAPI\ApiException;

class ListField extends Field
{
	public static function displayName(): string
	{
		return Craft::t('klaviyoconnect', 'fields.list.displayName');
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

		$listOptions = [
			'' => '',
		];
		foreach ($lists as $list) {
			$listOptions[$list->id] = $list->name;
		}

		$selectedListId = $value instanceof KlaviyoList ? $value->id : (is_string($value) ? $value : '');

		// Offer a saved list from another Klaviyo account, so saving here doesn't replace it
		if (! isset($listOptions[$selectedListId])) {
			$listOptions[$selectedListId] = Craft::t('klaviyoconnect', 'fields.list.notInAccount', [
				'id' => $selectedListId,
			]);
		}

		return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/select', [
			'name' => $this->handle,
			'options' => $listOptions,
			'value' => $selectedListId,
		]);
	}

	public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
	{
		// Craft passes the normalized list back in when it applies a draft or duplicates an element
		if ($value instanceof KlaviyoList) {
			return $value;
		}

		if (is_array($value)) {
			$value = $value['id'] ?? null;
		}

		if (! is_string($value) || $value === '') {
			return null;
		}

		try {
			$lists = Plugin::getInstance()->api->getLists($element?->siteId);
		} catch (ApiException) {
			$lists = [];
		}

		foreach ($lists as $list) {
			if ($list->id === $value) {
				return $list;
			}
		}

		// Keep an ID missing from this account's lists, so templates can read its id like any other list
		return new KlaviyoList([
			'id' => $value,
			'name' => $value,
		]);
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
