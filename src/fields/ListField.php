<?php
namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\Field;
use fostercommerce\klaviyoconnect\Plugin;

class ListField extends Field
{

    public static function displayName(): string
    {
        return Craft::t('klaviyoconnect', 'Klaviyo List');
    }

    public function getInputHtml($value, \craft\base\ElementInterface $element = NULL): string
    {
        $lists = Plugin::getInstance()->api->getLists();
        $listOptions = [];

        $allLists = Plugin::getInstance()->settings->klaviyoListsAll;
        $availableLists = Plugin::getInstance()->settings->klaviyoAvailableLists;

        foreach ($lists as $list) {
            if ($allLists || in_array($list->id, $availableLists)) {
                $listOptions[$list->id] = $list->name;
            }
        }

        return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/select', array(
            'name' => $this->handle,
            'options' => $listOptions,
            'value' => $value->id,
        ));
    }

    public function normalizeValue($value, \craft\base\ElementInterface $element = NULL)
    {
        if ($value) {
            $o = json_decode($value);
            if ($o) {
                $value = $o->id;
            }
        }
        $modified = array();
        $lists = Plugin::getInstance()->api->getLists();
        if ($value) {
            foreach ($lists as $list) {
                if ($list->id === $value) {
                    return $list;
                }
            }
        }

        return null;
    }
}
