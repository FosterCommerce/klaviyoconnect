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

        foreach($lists as $list) {
            $listOptions[$list->id] = $list->name;
        }

        return craft()->templates->render('klaviyoconnect/fieldtypes/select', array(
            'name'    => $name,
            'options' => $listOptions,
            'value'   => $value,
        ));
    }

}
