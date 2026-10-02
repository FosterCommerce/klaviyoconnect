<?php

namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\Plugin;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;

class ListsField extends Field
{
	/**
	 * displayName.
	 *
	 * @author	Unknown
	 * @since	v0.0.1
	 * @version	v1.0.0	Monday, May 23rd, 2022.
	 * @access	public static
	 */
	public static function displayName(): string
	{
		return Craft::t('klaviyoconnect', 'Klaviyo Lists');
	}

	public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
	{
		try {
			$lists = Plugin::getInstance()->api->getLists();
		} catch (ClientException) {
			$lists = [];
		}

		/** @var Settings $settings */
		$settings = Plugin::getInstance()->getSettings();
		$allLists = $settings->klaviyoListsAll;
		$availableLists = $settings->klaviyoAvailableLists;

		$listOptions = [];

		foreach ($lists as $list) {
			if ($allLists || in_array($list->id, $availableLists, true)) {
				$listOptions[$list->id] = $list->name;
			}
		}

		$ids = [];
		if ($value !== null) {
			$value = is_array($value) ? $value : [];
			$ids = ArrayHelper::getColumn($value, 'id');
		}

		return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/checkboxgroup', [
			'name' => $this->handle,
			'options' => $listOptions,
			'values' => $ids,
		]);
	}

	public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
	{
		try {
			$lists = collect(Plugin::getInstance()->api->getLists());
		} catch (ClientException) {
			$lists = collect([]);
		}

		$value = collect(is_array($value) ? $value : []);
		$value = $value
			->when(
				is_string($value->first()),
				fn ($collection): Collection => $collection,
				fn ($collection): Collection => $collection->pluck('id'),
			);

		return $lists->filter(fn ($list) => $value->contains($list->id))->toArray();
	}
}
