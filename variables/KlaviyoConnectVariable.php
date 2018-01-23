<?php
namespace Craft;

use \GuzzleHttp\Exception\RequestException;

class KlaviyoConnectVariable
{
    private $error = null;
    private $lists = null;

    public function lists()
    {
        if (is_null($this->lists)) {
            try {
                $lists = craft()->klaviyoConnect_api->getLists();
                if (sizeof($lists) > 0) {
                    $this->lists = $lists;
                }
            } catch (RequestException $e) {
                $response = json_decode($e->getResponse()->getBody()->getContents());
                $this->error = [ $response->status => $response->message];
            }
        }
        return $this->lists;
    }

    public function error()
    {
        return $this->error;
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