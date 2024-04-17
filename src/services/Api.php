<?php

namespace fostercommerce\klaviyoconnect\services;

use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\models\Profile;
use GuzzleHttp\Client;
use KlaviyoAPI\ApiException;
use KlaviyoAPI\KlaviyoAPI;
use yii\base\Exception;

class Api extends Base
{
    private $host = 'https://a.klaviyo.com/api';

    private $client = null;

    private $clientV2 = null;

    private KlaviyoAPI|null $api = null;

    private $cachedLists = null;

    public function init()
    {
        parent::init();
        $this->client = new Client([
            'base_uri' => "{$this->host}/v1/",
        ]);

        $this->clientV2 = new Client([
            'base_uri' => "{$this->host}/v2/",
        ]);

        $this->api = new KlaviyoAPI($this->getSetting('klaviyoApiKey'));
    }

    public function track(string $event, $profile, EventProperties $eventProperties = null, $timestamp = null)
    {
        if (! $profile['email']) {
            throw new Exception('You must identify a user by email.');
        }

        // Check if there's a prefix for events
        $eventPrefix = $this->getSetting('eventPrefix');
        if ($eventPrefix) {
            $event = $eventPrefix . ' ' . $event;
        }

        $properties = [
            'type' => 'event',
            'attributes' => [
                'metric' => [
                    'data' => [
                        'type' => 'metric',
                        'attributes' => [
                            'name' => $event,
                        ],
                    ],
                ],
                'profile' => $this->profileArray($profile),
            ],
        ];

        if ($timestamp !== null) {
            $properties['attributes']['time'] = $timestamp;
        }

        $mappedProperties = $eventProperties?->toArray();
        if (isset($eventProperties) && count($mappedProperties) > 0) {
            $properties['attributes']['properties'] = $mappedProperties;
        }

        $this->api?->Events->createEvent([
            'data' => $properties,
        ]);
    }

    public function identify(array $profile, bool $update = false): void
    {
        if (! $profile['email']) {
            throw new Exception('You must identify a user by email');
        }

        try {
            if ($update) {
                $this->api?->Profiles->createOrUpdateProfile($this->profileArray($profile));
            } else {
                $this->api?->Profiles->createProfile($this->profileArray($profile));
            }
        } catch (ApiException $e) {
            // 409 for a duplicate profile error - if we try track the same person twice, Klaviyo will respond with this.
            if ($e->getCode() !== 409) {
                // TODO implement proper error handling
            }
        } catch (\Throwable $t) {
            // TODO implement proper error handling
            // Swallow error.
        }
    }

    public function getLists(): mixed
    {
        if ($this->cachedLists === null) {
            $lists = [];
            $cursor = null;

            do {
                $result = $this->api?->Lists->getLists(['name'], page_cursor: $cursor);
                $lists = [
                    ...$lists,
                    ...$result['data'],
                ];

                $cursor = $this->getPaginationCursor($result);
            } while ($cursor !== null);

            $lists = array_map(static function($list) {
                return new KlaviyoList([
                    'id' => $list['id'],
                    'name' => $list['attributes']['name'],
                ]);
            }, $lists);

            $this->cachedLists = $lists;
        }

        return $this->cachedLists;
    }

    public function getProfileId(string $profileEmail): ?string
    {
        $result = $this->api?->Profiles->getProfiles(filter: "equals(email,\"{$profileEmail}\")");
        if (count($result['data']) > 0) {
            return $result['data'][0]['id'];
        }

        return null;
    }

    public function addProfileToList(string $listId, string $profileId)
    {
        $this->api?->Lists->createListRelationships(
            $listId,
            [
                'data' => [
                    [
                        'type' => 'profile',
                        'id' => $profileId,
                    ],
                ],
            ],
        );
    }

    public function subscribeProfileToList(string $listId, array $profile)
    {
        $profileData = [];
        $consent = [];

        if (array_key_exists('email', $profile)) {
            $profileData['email'] = $profile['email'];
            $consent['email'] = [
                'marketing' => [
                    'consent' => 'SUBSCRIBED',
                ],
            ];
        }
        if (array_key_exists('phone_number', $profile)) {
            $profileData['phone_number'] = $profile['phone_number'];
            $consent['sms'] = [
                'marketing' => [
                    'consent' => 'SUBSCRIBED',
                ],
            ];
        }

        $res = $this->api?->Profiles->subscribeProfiles([
            'data' => [
                'type' => 'profile-subscription-bulk-create-job',
                'attributes' => [
                    'profiles' => [
                        'data' => [
                            [
                                'type' => 'profile',
                                'attributes' => [
                                    ...$profileData,
                                    'subscriptions' => $consent,
                                ],
                            ],
                        ],
                    ],
                ],
                'relationships' => [
                    'list' => [
                        'data' => [
                            'type' => 'list',
                            'id' => $listId,
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function profileArray(array $profile): array
    {
        return [
            'data' => [
                'type' => 'profile',
                'attributes' => $profile,
            ],
        ];
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
     */
    private function callServerApi($path, $params): bool
    {
        if ($path === 'track' && $params['properties']) {
            $items = $params['properties']['Items'] ?? null;

            if ($items && is_string($items)) {
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
     */
    private function encode($params): string
    {
        return urlencode(base64_encode(json_encode($params)));
    }

    private function getPaginationCursor(array $response): ?string
    {
        $url = parse_url($response['links']['next'] ?? '');
        parse_str($url['query'] ?? '', $params);
        return $params['page']['cursor'] ?? null;
    }

    /**
     * getObjectResponse.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @param	mixed	$response
     */
    private function getObjectResponse($response): mixed
    {
        $content = $response->getBody()->getContents();
        if (isset($content)) {
            return json_decode($content);
        }
        return null;
    }
}
