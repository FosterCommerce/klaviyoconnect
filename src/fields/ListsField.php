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

    public function getInputHtml($values)
    {
        $lists = Plugin::getInstance()->api->getLists();
        $listOptions = array();

        $availableLists = Plugin::getInstance()->settings->klaviyoAvailableLists;

        foreach ($lists as $list) {
            if (in_array($list->id, $availableLists)) {
                $listOptions[$list->id] = $list->name;
            }
        }

        $ids = array();
        $values = array();
        if (!is_null($values)) {
            foreach ($values as $key => $value) {
                $ids[] = $key;
            }
        }

        return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/checkboxgroup', array(
            'name' => $this->handle,
            'options' => $listOptions,
            'values'   => $ids,
        ));
    }

    public function normalizeValue($values)
    {
        $modified = array();
        $lists = Plugin::getInstance()->api->getLists();
        if (!empty($values)) {
            foreach ($lists as $list) {
                if (in_array($list->id, $values)) {
                    $modified[$list->id] = $list->name;
                }
            }
        }
        return $modified;
    }
}
