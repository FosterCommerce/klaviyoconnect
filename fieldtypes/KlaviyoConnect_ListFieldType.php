<?php

namespace Craft;

class KlaviyoConnect_ListFieldType extends BaseFieldType
{

    public function getName()
    {
        return Craft::t('Klaviyo List');
    }

    public function defineContentAttribute()
    {
        return AttributeType::Mixed;
    }

    public function getInputHtml($name, $value)
    {
        $lists = craft()->klaviyoConnect_api->getLists();
        $listOptions = [];

        $plugin = craft()->plugins->getPlugin('klaviyoconnect');
        $availableLists = $plugin->getSettings()['klaviyoAvailableLists'];

        foreach ($lists as $list) {
            if (in_array($list->id, $availableLists)) {
                $listOptions[$list->id] = $list->name;
            }
        }

        return craft()->templates->render('klaviyoconnect/fieldtypes/select', array(
            'name' => $name,
            'options' => $listOptions,
            'value' => $value['id'],
        ));
    }

    public function prepValueFromPost($value)
    {
        $lists = craft()->klaviyoConnect_api->getLists();
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
