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
        return AttributeType::Mixed;
    }

    public function getInputHtml($name, $values)
    {
        $lists = craft()->klaviyoConnect_api->getLists();
        $listOptions = [];

        foreach($lists as $list) {
            $listOptions[$list->id] = $list->name;
        }

        return craft()->templates->render('klaviyoconnect/fieldtypes/checkboxgroup', array(
            'name'    => $name,
            'options' => $listOptions,
            'values'   => $values,
        ));
    }

}
