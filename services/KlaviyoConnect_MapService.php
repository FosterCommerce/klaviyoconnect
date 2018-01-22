<?php

namespace Craft;

class KlaviyoConnect_MapService extends KlaviyoConnect_BaseService
{
    const REQUIRED_KEYS = ['name', 'handle', 'description', 'method'];

    const USERMODEL_PROFILE_MAPPING = [
        'name' => 'UserModel Mapping',
        'handle' => 'usermodel_mapping',
        'description' => 'Default UserModel profile mapping',
        'method' => 'klaviyoConnect_map.userModelMap',
    ];

    public function getProfileMappingProviders()
    {
        $allProviders = [self::USERMODEL_PROFILE_MAPPING];

        foreach (craft()->plugins->call('klaviyoConnect_addProfileMapping') as $key => $provider) {
            if (isset($provider[0])) {
                foreach ($provider as $o) {
                    $allProviders[] = $o;
                }
            } else {
                $allProviders[] = $provider;
            }
        }

        $providers = array();
        foreach ($allProviders as $provider) {
            if ($this->validateKeys($provider)) {
                $result = $this->generateServiceMethod($provider);
                if ($result) {
                    $providers[] = $result;
                }
            }
        }

        return $providers;
    }

    public function getProfileMappingProvider($handle = '')
    {
        if ($handle === '') {
            $handle = $this->getSetting('klaviyoDefaultProfileMapping');
        }
        $providers = $this->getProfileMappingProviders();
        if ($handle !== '') {
            foreach ($providers as $provider) {
                if ($provider['handle'] === $handle) {
                    return $provider;
                }
            }
        }
        return null;
    }

    private function validateKeys($provider)
    {
        foreach (self::REQUIRED_KEYS as $requiredKey) {
            if (!(array_key_exists($requiredKey, $provider) && $provider[$requiredKey] !== '')) {
                return false;
            }
        }
        return true;
    }

    private function generateServiceMethod($provider)
    {
        $methodTuple = explode('.', $provider['method']);
        if (sizeof($methodTuple) > 1) {
            $service = lcfirst($methodTuple[0]);
            $method = lcfirst($methodTuple[1]);

            if (isset(craft()->$service) && method_exists(craft()->$service, $method)) {
                $provider['service'] = $service;
                $provider['method'] = $method;
                return $provider;
            }
        }
        return false;
    }

    public function map($handle='', $params)
    {
        $provider = $this->getProfileMappingProvider($handle);
        $service = $provider['service'];
        $method = $provider['method'];
        return craft()->$service->$method($params);
    }

    public function userModelMap($params)
    {
        return KlaviyoConnect_ProfileModel::populateModel($params);
    }
}
