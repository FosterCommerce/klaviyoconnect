<?php

namespace Craft;

class KlaviyoConnect_MapService extends KlaviyoConnect_BaseService
{
    const REQUIRED_KEYS = ['name', 'handle', 'description', 'method'];

    const USERMODEL_PROFILE_MAPPING = [
        'name' => 'UserModel Profile Mapping',
        'handle' => 'usermodel_mapping',
        'description' => 'Simple UserModel profile mapping',
        'method' => 'klaviyoConnect_map.userModelMap',
    ];

    const FORMDATA_PROFILE_MAPPING = [
        'name' => 'Form-Data Profile Mapping',
        'handle' => 'formdata_mapping',
        'description' => 'Simple form-data profile mapping',
        'method' => 'klaviyoConnect_map.formDataMap',
    ];

    public function getProfileMappings()
    {
        $allMappings = [
            self::USERMODEL_PROFILE_MAPPING,
            self::FORMDATA_PROFILE_MAPPING,
        ];

        foreach (craft()->plugins->call('klaviyoConnect_addProfileMapping') as $key => $mapping) {
            if (isset($mapping[0])) {
                foreach ($mapping as $o) {
                    $allMappings[] = $o;
                }
            } else {
                $allMappings[] = $mapping;
            }
        }

        $mappings = array();
        foreach ($allMappings as $mapping) {
            if ($this->validateKeys($mapping)) {
                $result = $this->generateServiceMethod($mapping);
                if ($result) {
                    $mappings[] = $result;
                }
            }
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

    private function validateKeys($mapping)
    {
        foreach (self::REQUIRED_KEYS as $requiredKey) {
            if (!(array_key_exists($requiredKey, $mapping) && $mapping[$requiredKey] !== '')) {
                return false;
            }
        }
        return true;
    }

    private function generateServiceMethod($mapping)
    {
        $methodTuple = explode('.', $mapping['method']);
        if (sizeof($methodTuple) > 1) {
            $service = lcfirst($methodTuple[0]);
            $method = lcfirst($methodTuple[1]);

            if (isset(craft()->$service) && method_exists(craft()->$service, $method)) {
                $mapping['service'] = $service;
                $mapping['method'] = $method;
                return $mapping;
            }
        }
        return false;
    }

    public function map($handle='', $params)
    {
        $mapping = $this->getProfileMapping($handle);
        $service = $mapping['service'];
        $method = $mapping['method'];
        return craft()->$service->$method($params);
    }

    public function userModelMap($params)
    {
        $user = craft()->userSession->getUser();
        $model = new KlaviyoConnect_ProfileModel();
        $model->email = $user->email;
        $model->first_name = $user->firstName;
        $model->last_name = $user->lastName;
        return $model;
    }

    public function formDataMap($params)
    {
        return KlaviyoConnect_ProfileModel::populateModel($params);
    }
}
