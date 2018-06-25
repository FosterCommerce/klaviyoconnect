<?php
namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\Field;
use fostercommerce\klaviyoconnect\Plugin;

class ListsField extends Field
{

    public static function displayName(): string
    {
        return Craft::t('klaviyoconnect', 'Klaviyo Lists');
    }

    public function getInputHtml($values, \craft\base\ElementInterface $element = NULL): string
    {
        $lists = Plugin::getInstance()->api->getLists();
        $listOptions = array();

        $allLists = Plugin::getInstance()->settings->klaviyoListsAll;
        $availableLists = Plugin::getInstance()->settings->klaviyoAvailableLists;

        foreach ($lists as $list) {
            if ($allLists || in_array($list->id, $availableLists)) {
                $listOptions[$list->id] = $list->name;
            }
        }

        $ids = array();
        $values = is_array($values) ? $values : array();
        if (!is_null($values)) {
            foreach ($values as $value) {
                $ids[] = $value->id;
            }
        }

        return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/checkboxgroup', array(
            'name' => $this->handle,
            'options' => $listOptions,
            'values'   => $ids,
        ));
    }

    public function normalizeValue($values, \craft\base\ElementInterface $element = NULL)
    {
        if ($values && !is_array($values)) {
            $o = json_decode($values);
            $newValues = [];
            if (is_array($o)) {
                foreach ($o as $val) {
                    $newValues[] = $val->id;
                }
                $values = $newValues;
            }
        }
        $modified = array();
        $lists = Plugin::getInstance()->api->getLists();
        if (!empty($values)) {
            foreach ($lists as $list) {
                if (in_array($list->id, $values)) {
                    $modified[] = $list;
                }
            }
        }
        return $modified;
    }
}
