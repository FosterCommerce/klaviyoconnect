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

    public function getInputHtml($value)
    {
        $lists = Plugin::getInstance()->api->getLists();
        $listOptions = [];

        $availableLists = Plugin::getInstance()->settings->klaviyoAvailableLists;

        foreach ($lists as $list) {
            if (in_array($list->id, $availableLists)) {
                $listOptions[$list->id] = $list->name;
            }
        }

        return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/select', array(
            'name' => $this->handle,
            'options' => $listOptions,
            'value' => $value['id'],
        ));
    }

    public function normalizeValue($value)
    {
        $lists = Plugin::getInstance()->api->getLists();
        foreach ($lists as $list) {
            if ($list->id === $value) {
                return [
                    'id' => $list->id,
                    'name' => $list->name
                ];
            }
        }
        return null;
    }
}
