<?php

namespace Craft;

class KlaviyoConnect_ListsFieldType extends BaseFieldType
{

    public function getName()
    {
        return Craft::t('Klaviyo Lists');
    }

    public function defineContentAttribute()
    {
        return array(AttributeType::Mixed);
    }

    public function getInputHtml($name, $values)
    {
        $lists = craft()->klaviyoConnect_api->getLists();
        $listOptions = array();

        $plugin = craft()->plugins->getPlugin('klaviyoconnect');
        $availableLists = $plugin->getSettings()['klaviyoAvailableLists'];

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

        return craft()->templates->render('klaviyoconnect/fieldtypes/checkboxgroup', array(
            'name'    => $name,
            'options' => $listOptions,
            'values'   => $ids,
        ));
    }

    public function prepValueFromPost($values)
    {
        $modified = array();
        $lists = craft()->klaviyoConnect_api->getLists();
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
