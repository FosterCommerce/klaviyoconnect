<?php

namespace Craft;

use \GuzzleHttp\Client;

class KlaviyoConnect_ApiService extends KlaviyoConnect_BaseService
{
    private $host = 'https://a.klaviyo.com/api/v1/';
    private $client = null;

    private $cachedLists = null;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->host,
        ]);
    }

    public function track($event,
        KlaviyoConnect_ProfileModel $profile,
        KlaviyoConnect_EventPropertiesModel $properties = null,
        $trackOnce = false,
        $timestamp = null) {
        if (!$profile->hasEmailOrId()) {
            throw new Exception('You must identify a user by email or ID.');
        }

        $mappedProfile = $profile->map();
        $params = array(
            'token' => $this->getSetting('klaviyoSiteId'),
            'event' => $event,
            'customer_properties' => $mappedProfile,
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }

        $mappedProperties = $properties->map();
        if (isset($properties) && sizeof($mappedProperties) > 0) {
            $params['properties'] = $mappedProperties;
        }

        return $this->callServerApi($trackOnce ? 'track-once' : 'track', $params);
    }

    public function identify(KlaviyoConnect_ProfileModel $profile)
    {
        if (!$profile->hasEmailOrId()) {
            throw new Exception('You must identify a user by email or ID.');
        }

        $mapped = $profile->map();
        $params = array(
            'token' => $this->getSetting('klaviyoSiteId'),
            'properties' => $mapped,
        );

        return $this->callServerApi('identify', $params);
    }

    private function callServerApi($path, $params)
    {
        $response = $this->client->request('GET', "/api/{$path}?data={$this->encode($params)}");
        $body = (string) $response->getBody();
        return $response->getStatusCode() === 200 && $body === '1';
    }

    private function encode($params)
    {
        return urlencode(base64_encode(json_encode($params)));
    }

    public function getLists()
    {
        if (is_null($this->cachedLists)) {
            $this->cachedLists = $this->getListsPaged();
        }

        return $this->cachedLists;
    }

    private function getListsPaged($page = 0, $lists = array(), $totalLists = 0)
    {
        $response = $this->client->request('GET', 'lists', [
            'query' => [
                'api_key' => $this->getSetting('klaviyoApiKey'),
                'page' => $page,
            ],
        ]);
        $content = $this->getObjectResponse($response);
        foreach ($content->data as $list) {
            $totalLists++;
            if ($list->list_type === 'list') {
                $model = new KlaviyoConnect_ListModel();
                $model->id = $list->id;
                $model->name = $list->name;
                $lists[] = $model;
            }
        }
        if ($totalLists === $content->total) {
            return $lists;
        } else {
            return $this->getListsPaged($page + 1, $lists, $totalLists);
        }
    }

    public function profileInList($listId, $email)
    {
        $response = $this->client->request('GET', "list/{$listId}/members", [
            'query' => [
                'api_key' => $this->getSetting('klaviyoApiKey'),
                'email' => $email,
            ],
        ]);
        $content = $this->getObjectResponse($response);
        return sizeof($content->data) > 0;
    }

    public function addProfileToList(
        KlaviyoConnect_ListModel &$list,
        KlaviyoConnect_ProfileModel &$profile,
        $confirmOptIn = true) {
        if (!$profile->hasEmail()) {
            throw new Exception('You must identify a user by email.');
        }

        $params = [
            'api_key' => $this->getSetting('klaviyoApiKey'),
            'email' => $profile->email,
            'confirm_optin' => $confirmOptIn ? 'true' : 'false',
        ];

        $mapped = $profile->map();
        unset($mapped['$email']);
        if (sizeof($mapped) > 0) {
            $params['properties'] = json_encode($mapped);
        }

        $response = $this->client->request('POST', "list/{$list->id}/members", ['form_params' => $params]);
        $content = $this->getObjectResponse($response);
        return $content;
    }

    private function getObjectResponse($response)
    {
        $content = $response->getBody()->getContents();
        if (isset($content)) {
            return json_decode($content);
        } else {
            return null;
        }
    }
}
