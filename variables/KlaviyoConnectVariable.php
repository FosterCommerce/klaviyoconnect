<?php
namespace Craft;

class KlaviyoConnectVariable
{
    private $lists = null;

    public function lists()
    {
        if (is_null($this->lists)) {
            $this->lists = craft()->klaviyoConnect_api->getLists();
        }
        return $this->lists;
    }

    public function profileMappingProviders()
    {
        return craft()->klaviyoConnect_map->getProfileMappingProviders();
    }

    public function profileMappingProvider($handle = '')
    {
        return craft()->klaviyoConnect_map->getProfileMappingProvider($handle);
    }

    public function defaultProfileMappingProvider()
    {
      return $this->profileMappingProvider();
    }
}