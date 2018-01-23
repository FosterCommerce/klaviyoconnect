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

    public function profileMappings()
    {
        return craft()->klaviyoConnect_map->getProfileMappings();
    }

    public function profileMapping($handle = '')
    {
        return craft()->klaviyoConnect_map->getProfileMapping($handle);
    }

    public function defaultProfileMapping()
    {
      return $this->profileMapping();
    }
}