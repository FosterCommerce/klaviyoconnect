<?php
namespace fostercommerce\klaviyoconnect\variables;

use fostercommerce\klaviyoconnect\Plugin;
use GuzzleHttp\Exception\RequestException;

class Variable
{
    private $error = null;
    private $lists = null;

    public function lists()
    {
        if (is_null($this->lists)) {
            try {
                $lists = Plugin::getInstance()->api->getLists();
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
        return Plugin::getInstance()->map->getProfileMappings();
    }

    public function profileMapping($handle = '')
    {
        return Plugin::getInstance()->map->getProfileMapping($handle);
    }

    public function defaultProfileMapping()
    {
      return $this->profileMapping();
    }
}