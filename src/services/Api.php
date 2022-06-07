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

    /**
     * __construct.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	void
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => "{$this->host}/v1/",
        ]);

        $this->clientV2 = new Client([
            'base_uri' => "{$this->host}/v2/",
        ]);
    }

    /**
     * track.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed          	$event     	
     * @param	profile        	$profile   	
     * @param	eventproperties	$properties	Default: null
     * @param	boolean        	$trackOnce 	Default: false
     * @param	mixed          	$timestamp 	Default: null
     * @return	bool
     */
    public function track( $event, Profile $profile, EventProperties $properties = null, $trackOnce = false, $timestamp = null ): bool
     {
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

    /**
     * identify.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	profile	$profile	
     * @return	bool
     */
    public function identify(Profile $profile): bool
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

    /**
     * callServerApi.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @param	mixed	$path  	
     * @param	mixed	$params	
     * @return	bool
     */
    private function callServerApi($path, $params): bool
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

    /**
     * encode.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @param	mixed	$params	
     * @return	string
     */
    private function encode($params): string
    {
        return urlencode(base64_encode(json_encode($params)));
    }

    /**
     * getLists.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	mixed
     */
    public function getLists(): mixed
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

    /**
     * profileInList.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$listId	
     * @param	mixed	$email 	
     * @return	int
     */
    public function profileInList($listId, $email): int
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

    /**
     * addProfileToList.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	klaviyolist	&$list               	
     * @param	profile    	&$profile            	
     * @param	boolean    	$useSubscribeEndpoint	Default: false
     * @return	mixed
     */
    public function addProfileToList(KlaviyoList &$list, Profile &$profile, $useSubscribeEndpoint = false): mixed
    {
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

        $endpoint = $useSubscribeEndpoint ? 'subscribe' : 'members';
        $response = $this->clientV2->post("list/{$list->id}/${endpoint}", ['json' => $params]);
        $content = $this->getObjectResponse($response);
        return $content;
    }

    /**
     * getObjectResponse.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @param	mixed	$response	
     * @return	mixed
     */
    private function getObjectResponse($response): mixed
    {
        $content = $response->getBody()->getContents();
        if (isset($content)) {
            return json_decode($content);
        } else {
            return null;
        }
    }
}
