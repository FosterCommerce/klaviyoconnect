<?php

namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use fostercommerce\klaviyoconnect\Plugin;
use GuzzleHttp\Exception\ClientException;

class ListsField extends Field
{
    /**
     * displayName.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public static
     * @return	mixed
     */
    public static function displayName(): string
    {
        return Craft::t('klaviyoconnect', 'Klaviyo Lists');
    }

    /**
     * getInputHtml.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed               $values
     * @param	elementinterface	$element	Default: null
     * @return	mixed
     */
    public function getInputHtml($values, ElementInterface $element = null): string
    {
        try {
            $lists = Plugin::getInstance()->api->getLists();
        } catch (ClientException $e) {
            $lists = [];
        }

        $allLists = Plugin::getInstance()->settings->klaviyoListsAll;
        $availableLists = Plugin::getInstance()->settings->klaviyoAvailableLists;

        $listOptions = [];

        foreach ($lists as $list) {
            if ($allLists || in_array($list->id, $availableLists, true)) {
                $listOptions[$list->id] = $list->name;
            }
        }

        $ids = [];
        if ($values !== null) {
            $values = is_array($values) ? $values : [];
            $ids = ArrayHelper::getColumn($values, 'id');
        }

        return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/checkboxgroup', [
            'name' => $this->handle,
            'options' => $listOptions,
            'values' => $ids,
        ]);
    }

    /**
     * normalizeValue.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed               $values
     * @param	elementinterface	$element	Default: null
     */
    public function normalizeValue($values, ElementInterface $element = null): mixed
    {
        if ($values && ! is_array($values)) {
            $o = json_decode($values);
            $newValues = [];
            if (is_array($o)) {
                foreach ($o as $val) {
                    $newValues[] = $val->id;
                }
                $values = $newValues;
            }
        }
        $modified = [];

        try {
            $lists = Plugin::getInstance()->api->getLists();
        } catch (ClientException $e) {
            $lists = [];
        }

        if (! empty($values)) {
            foreach ($lists as $list) {
                if (in_array($list->id, $values, true)) {
                    $modified[] = $list;
                }
            }
        }
        return $modified;
    }
}
