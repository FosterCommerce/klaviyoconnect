<?php

namespace fostercommerce\klaviyoconnect\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use fostercommerce\klaviyoconnect\Plugin;
use GuzzleHttp\Exception\ClientException;

class ListField extends Field
{
    public static function displayName(): string
    {
        return Craft::t('klaviyoconnect', 'Klaviyo List');
    }

    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        try {
            $lists = Plugin::getInstance()->api->getLists();
        } catch (ClientException) {
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

        return Craft::$app->getView()->renderTemplate('klaviyoconnect/fieldtypes/select', [
            'name' => $this->handle,
            'options' => $listOptions,
            'value' => $value ? $value->id : null,
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value) {
            $o = json_decode($value);
            if ($o) {
                $value = $o->id;
            }
        }

        try {
            $lists = Plugin::getInstance()->api->getLists();
        } catch (ClientException) {
            $lists = [];
        }

        if ($value) {
            foreach ($lists as $list) {
                if ($list->id === $value) {
                    return $list;
                }
            }
        }

        return $value;
    }
}
