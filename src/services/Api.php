<?php

namespace fostercommerce\klaviyoconnect\services;

use fostercommerce\klaviyoconnect\models\Profile;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\models\EventProperties;
use yii\base\Exception;
use GuzzleHttp\Client;

class Api extends Base
{
    private $host = 'https://a.klaviyo.com/api';
    private $client = null;
    private $clientV2 = null;

    private $cachedLists = null;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => "{$this->host}/v1/",
        ]);

        $this->clientV2 = new Client([
            'base_uri' => "{$this->host}/v2/",
        ]);
    }

    public function track(
        $event,
        Profile $profile,
        EventProperties $properties = null,
        $trackOnce = false,
        $timestamp = null
    ) {
        if (!$profile->hasEmailOrId()) {
            throw new Exception('You must identify a user by email or ID.');
        }

        // Check if there's a prefix for events
        $eventPrefix = $this->getSetting('eventPrefix');

        if ($eventPrefix) {
            $event = $eventPrefix . ' ' . $event;
        }

        $params = array(
            'token' => $this->getSetting('klaviyoSiteId'),
            'event' => $event,
            'customer_properties' => $profile->toArray(),
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }

        $mappedProperties = $properties->toArray();
        if (isset($properties) && sizeof($mappedProperties) > 0) {
            $params['properties'] = $mappedProperties;
        }

        return $this->callServerApi($trackOnce ? 'track-once' : 'track', $params);
    }

    public function identify(Profile $profile)
    {
        if (!$profile->hasEmailOrId()) {
            throw new Exception('You must identify a user by email or ID.');
        }

        $params = array(
            'token' => $this->getSetting('klaviyoSiteId'),
            'properties' => $profile->toArray(),
        );

        return $this->callServerApi('identify', $params);
    }

    private function callServerApi($path, $params)
    {
        if($path === 'track' && $params['properties']) {
            $items = $params['properties']['Items'] ?? null;

            if($items && is_string($items)) {
                $params['properties']['Items'] = json_decode($items);
            }
        }
      
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
            $response = $this->clientV2->get('lists', [
                'query' => [
                    'api_key' => $this->getSetting('klaviyoApiKey'),
                ],
            ]);
            $content = $this->getObjectResponse($response);

            $lists = [];
            foreach ($content as $list) {
                $model = new KlaviyoList([
                    'id' => $list->list_id,
                    'name' => $list->list_name,
                ]);
                $lists[] = $model;
            }

            $this->cachedLists = $lists;
        }

        return $this->cachedLists;
    }

    public function profileInList($listId, $email)
    {
        $response = $this->clientV2->get("list/{$listId}/members", [
            'query' => [
                'api_key' => $this->getSetting('klaviyoApiKey'),
                'emails' => $email,
            ],
        ]);
        $content = $this->getObjectResponse($response);
        return sizeof($content->data) > 0;
    }

    public function addProfileToList(KlaviyoList &$list, Profile &$profile) {
        if (!$profile->hasEmail()) {
            throw new Exception('You must identify a user by email.');
        }

        $params = [
            'api_key' => $this->getSetting('klaviyoApiKey'),
            'profiles' => [],
        ];

        $mapped = $profile->toArray();
        $email = $mapped['$email'];
        unset($mapped['$email']);
        $mapped['email'] = $email;
        if (sizeof($mapped) > 0) {
            $params['profiles'][] = $mapped;
        }

        $response = $this->clientV2->post("list/{$list->id}/members", ['json' => $params]);
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
