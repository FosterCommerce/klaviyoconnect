<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\events\AddProfileMappingEvent;
use fostercommerce\klaviyoconnect\models\Profile;
use fostercommerce\klaviyoconnect\models\ProfileMapping;
use yii\base\Event;

class Map extends Base
{
    const EVENT_ADD_PROFILE_MAPPING = 'addProfileMapping';

    public function getProfileMappings()
    {
        $mappings = [
            new ProfileMapping([
                'name' => 'UserModel Profile Mapping',
                'handle' => 'usermodel_mapping',
                'description' => 'Simple UserModel profile mapping',
                'method' => function ($params) {
                    return Plugin::getInstance()->map->userModelMap();
                },
            ]),
            new ProfileMapping([
                'name' => 'Form-Data Profile Mapping',
                'handle' => 'formdata_mapping',
                'description' => 'Simple form-data profile mapping',
                'method' => function ($params) {
                    return Plugin::getInstance()->map->formDataMap($params);
                },
            ]),
        ];

        $addProfileMappingEvent = new AddProfileMappingEvent();
        Event::trigger(static::class, self::EVENT_ADD_PROFILE_MAPPING, $addProfileMappingEvent);

        foreach ($addProfileMappingEvent->mappings as $mapping) {
            $mappings[] = $result;
        }

        return $mappings;
    }

    public function getProfileMapping($handle = '')
    {
        if ($handle === '') {
            $handle = $this->getSetting('klaviyoDefaultProfileMapping');
        }
        $mappings = $this->getProfileMappings();
        if ($handle !== '') {
            foreach ($mappings as $mapping) {
                if ($mapping['handle'] === $handle) {
                    return $mapping;
                }
            }
        }
        return null;
    }

    public function map($handle='', $params)
    {
        $mapping = $this->getProfileMapping($handle);
        $res = ($mapping->method)($params);
        return $res;
    }

    public function userModelMap()
    {
        $user = Craft::$app->user->getIdentity();
        $model = new Profile([
            'email' => $user->email,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
        ]);
        return $model;
    }

    public function formDataMap($params)
    {
        return Plugin::getInstance()->populateModel(Profile::class, $params);
    }
}
