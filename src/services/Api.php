<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\Plugin;
use InvalidArgumentException;
use KlaviyoAPI\ApiException;
use KlaviyoAPI\KlaviyoAPI;
use KlaviyoAPI\Model\EventCreateQueryV2;
use KlaviyoAPI\Model\ListMembersAddQuery;
use KlaviyoAPI\Model\ProfileUpsertQuery;
use KlaviyoAPI\Model\SubscriptionCreateJobCreateQuery;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\caching\CacheInterface;

class Api extends Component
{
	private const LAST_ERROR_CACHE_KEY = 'klaviyoconnect:last-error';

	/**
	 * Profile attributes Klaviyo accepts at the top level.
	 */
	private const PROFILE_KEYS = ['email', 'phone_number', 'external_id', '_kx', 'first_name', 'last_name', 'organization', 'locale', 'title', 'image', 'location'];

	/**
	 * @var array<string, KlaviyoAPI>
	 */
	private array $clientsByApiKey = [];

	/**
	 * @var array<string, KlaviyoList[]>
	 */
	private array $listsByApiKey = [];

	/**
	 * @param array<string, mixed> $profile
	 * @throws Exception
	 * @throws ApiException
	 */
	public function track(string $event, array $profile, ?EventProperties $eventProperties = null, ?string $timestamp = null, ?int $siteId = null): void
	{
		$this->sendEvent($this->buildEvent($event, $profile, $eventProperties, $timestamp, $siteId), $siteId);
	}

	/**
	 * @param array<string, mixed> $profile
	 * @return array{type: string, attributes: array<string, mixed>}
	 * @throws Exception
	 */
	public function buildEvent(string $event, array $profile, ?EventProperties $eventProperties = null, ?string $timestamp = null, ?int $siteId = null): array
	{
		if (! isset($profile['email'])) {
			throw new Exception('You must identify a user by email.');
		}

		$eventPrefix = Plugin::getInstance()->getSettings()->getEventPrefix($siteId);
		if ($eventPrefix !== '') {
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

		// Send these as attributes, because same-second events without a top-level unique_id are deduped
		if ($eventProperties instanceof EventProperties) {
			$properties['attributes'] += array_filter([
				'unique_id' => $eventProperties->unique_id,
				'value' => $eventProperties->value === null ? null : (float) $eventProperties->value,
				'value_currency' => $eventProperties->value_currency,
			], static fn (string|float|null $attribute): bool => $attribute !== null);
		}

		$mappedProperties = $eventProperties?->toArray() ?? [];
		if ($mappedProperties !== []) {
			$properties['attributes']['properties'] = $mappedProperties;
		}

		return $properties;
	}

	/**
	 * @param array{type: string, attributes: array<string, mixed>} $eventData
	 * @throws ApiException
	 */
	public function sendEvent(array $eventData, ?int $siteId = null): void
	{
		$this->client($siteId)->Events->createEvent(new EventCreateQueryV2([
			'data' => $eventData,
		]));

		// Log the unique_id to match against the profile's activity feed in Klaviyo
		Craft::info('Sent Klaviyo event with unique_id ' . Json::encode($eventData['attributes']['unique_id'] ?? null), 'klaviyoconnect');
	}

	/**
	 * Logs Klaviyo errors instead of throwing them, matching versions before 7.3.0.
	 *
	 * @param array<string, mixed> $profile
	 * @throws Exception
	 */
	public function identify(array $profile, ?int $siteId = null): void
	{
		try {
			$this->upsertProfile($profile, $siteId);
		} catch (ApiException $apiException) {
			Craft::warning('Klaviyo profile update failed: ' . $this->errorMessage($apiException), 'klaviyoconnect');
		}
	}

	/**
	 * Throws Klaviyo errors, so a queued send can retry them.
	 *
	 * @param array<string, mixed> $profile
	 * @throws Exception
	 * @throws ApiException
	 */
	public function upsertProfile(array $profile, ?int $siteId = null): void
	{
		if (! isset($profile['email'])) {
			throw new Exception('You must identify a user by email');
		}

		$this->client($siteId)->Profiles->createOrUpdateProfile(new ProfileUpsertQuery($this->profileArray($profile)));
	}

	/**
	 * @param string[] $listIds
	 * @param array<string, mixed> $profile
	 * @param string[]|null $consentChannels `email` and/or `sms`; null subscribes every channel the profile has
	 * @throws ApiException
	 */
	public function addToLists(array $listIds, array $profile, bool $subscribe = false, ?int $siteId = null, ?array $consentChannels = null): void
	{
		// Subscribing creates the profile, so only adding needs an existing one
		$profileId = null;
		if (! $subscribe) {
			$email = $profile['email'] ?? null;
			$profileId = is_string($email) ? $this->getProfileId($email, $siteId) : null;
			if ($profileId === null) {
				return;
			}
		}

		// Try every list before failing, so one bad list ID doesn't keep the profile off the others
		$listException = null;
		foreach ($listIds as $listId) {
			try {
				if ($profileId === null) {
					$this->subscribeProfileToList($listId, $profile, $siteId, $consentChannels);
				} else {
					$this->addProfileToList($listId, $profileId, $siteId);
				}
			} catch (ApiException $apiException) {
				$listException ??= $apiException;
			}
		}

		if ($listException instanceof ApiException) {
			throw $listException;
		}
	}

	/**
	 * @return KlaviyoList[]
	 */
	public function getLists(?int $siteId = null, bool $refresh = false): array
	{
		return $this->getListsForApiKey(Plugin::getInstance()->getSettings()->getApiKey($siteId), $refresh);
	}

	/**
	 * @return KlaviyoList[]
	 */
	public function getListsForApiKey(string $apiKey, bool $refresh = false): array
	{
		// A site without a key has no Klaviyo account to read lists from
		if ($apiKey === '') {
			return [];
		}

		if (! $refresh && isset($this->listsByApiKey[$apiKey])) {
			return $this->listsByApiKey[$apiKey];
		}

		// Cache lists until refreshed, so only a cold cache fetches from Klaviyo
		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		$cacheKey = 'klaviyoconnect:lists:' . hash('sha256', $apiKey);
		$failureCacheKey = $cacheKey . ':failure';

		/** @var array<int, array{id: string, name: string}>|false $cachedLists */
		$cachedLists = $refresh ? false : $cache->get($cacheKey);
		if ($cachedLists === false) {
			// Remember a failed fetch briefly, so each element load doesn't call Klaviyo again
			/** @var array{message: string, code: int, body: string|null}|false $failure */
			$failure = $refresh ? false : $cache->get($failureCacheKey);
			if ($failure !== false) {
				throw new ApiException($failure['message'], $failure['code'], null, $failure['body']);
			}

			try {
				$cachedLists = $this->fetchLists($apiKey);
			} catch (InvalidArgumentException $invalidArgumentException) {
				if (! $this->isUnsendableKeyError($invalidArgumentException)) {
					throw $invalidArgumentException;
				}

				// Replace the header error, since its message repeats the private key
				$apiException = new ApiException(Craft::t('klaviyoconnect', 'settings.testConnection.unsendableKey'), 400);
				$cache->set($failureCacheKey, [
					'message' => $apiException->getMessage(),
					'code' => $apiException->getCode(),
					'body' => null,
				], 300);

				throw $apiException;
			} catch (ApiException $apiException) {
				$responseBody = $apiException->getResponseBody();
				$cache->set($failureCacheKey, [
					'message' => $apiException->getMessage(),
					'code' => $apiException->getCode(),
					'body' => is_string($responseBody) ? $responseBody : null,
				], 300);

				throw $apiException;
			}

			$cache->set($cacheKey, $cachedLists, 0);
			$cache->delete($failureCacheKey);
		}

		return $this->listsByApiKey[$apiKey] = array_map(static fn (array $list): KlaviyoList => new KlaviyoList($list), $cachedLists);
	}

	/**
	 * @return KlaviyoList[]
	 */
	public function getAvailableLists(?int $siteId = null, bool $refresh = false): array
	{
		$settings = Plugin::getInstance()->getSettings();
		if ($settings->getListsAll($siteId)) {
			return $this->getLists($siteId, $refresh);
		}

		$availableListIds = $settings->getAvailableLists($siteId);

		return array_values(array_filter(
			$this->getLists($siteId, $refresh),
			static fn (KlaviyoList $list): bool => in_array($list->id, $availableListIds, true),
		));
	}

	public function getProfileId(string $profileEmail, ?int $siteId = null): ?string
	{
		$escapedEmail = addcslashes($profileEmail, '"\\');
		/** @var array{data: list<array{id: string}>} $result */
		$result = $this->client($siteId)->Profiles->getProfiles(filter: "equals(email,\"{$escapedEmail}\")");

		return $result['data'][0]['id'] ?? null;
	}

	public function addProfileToList(string $listId, string $profileId, ?int $siteId = null): void
	{
		$this->client($siteId)->Lists->addProfilesToList(
			$listId,
			new ListMembersAddQuery([
				'data' => [
					[
						'type' => 'profile',
						'id' => $profileId,
					],
				],
			]),
		);
	}

	/**
	 * @param array<string, mixed> $profile
	 * @param string[]|null $consentChannels
	 */
	public function subscribeProfileToList(string $listId, array $profile, ?int $siteId = null, ?array $consentChannels = null): void
	{
		$profileData = [];
		$consent = [];

		// Send the email to identify the profile, but mark it subscribed only when the shopper gave it for this signup
		if (($profile['email'] ?? '') !== '') {
			$profileData['email'] = $profile['email'];
			if ($consentChannels === null || in_array('email', $consentChannels, true)) {
				$consent['email'] = [
					'marketing' => [
						'consent' => 'SUBSCRIBED',
					],
				];
			}
		}

		// Skip a blank phone field, since an empty number with SMS consent would fail the email signup too
		if (($profile['phone_number'] ?? '') !== '' && ($consentChannels === null || in_array('sms', $consentChannels, true))) {
			$profileData['phone_number'] = $profile['phone_number'];
			$consent['sms'] = [
				'marketing' => [
					'consent' => 'SUBSCRIBED',
				],
			];
		}

		// Skip a signup that gave no channel, since Klaviyo needs at least one subscription
		if ($consent === []) {
			return;
		}

		$this->client($siteId)->Profiles->bulkSubscribeProfiles(new SubscriptionCreateJobCreateQuery([
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
		]));
	}

	public function recordError(Throwable $throwable): void
	{
		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();

		// Keep the error until a connection check clears it, rather than for the cache's default duration
		$cache->set(self::LAST_ERROR_CACHE_KEY, [
			'message' => match (true) {
				$throwable instanceof ApiException => $this->errorMessage($throwable),
				// Replace the header error, since its message repeats the private key
				$this->isUnsendableKeyError($throwable) => Craft::t('klaviyoconnect', 'settings.testConnection.unsendableKey'),
				default => $throwable->getMessage(),
			},
			'date' => DateTimeHelper::currentUTCDateTime()->format(DATE_ATOM),
		], 0);
	}

	public function clearLastError(): void
	{
		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		$cache->delete(self::LAST_ERROR_CACHE_KEY);
	}

	/**
	 * @return array{message: string, date: string}|null
	 */
	public function getLastError(): ?array
	{
		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		/** @var array{message: string, date: string}|false $lastError */
		$lastError = $cache->get(self::LAST_ERROR_CACHE_KEY);

		return $lastError === false ? null : $lastError;
	}

	public function errorMessage(ApiException $apiException): string
	{
		$responseBody = $apiException->getResponseBody();
		/** @var array{errors?: list<array{detail?: string}>}|null $response */
		$response = is_string($responseBody) && Json::isJsonObject($responseBody) ? Json::decode($responseBody) : null;

		return $response['errors'][0]['detail'] ?? $apiException->getMessage();
	}

	/**
	 * A key with a line break or another control character can't be sent as a request header.
	 */
	private function isUnsendableKeyError(Throwable $throwable): bool
	{
		return $throwable instanceof InvalidArgumentException && str_contains($throwable->getMessage(), 'header value');
	}

	/**
	 * @return array<int, array{id: string, name: string}>
	 */
	private function fetchLists(string $apiKey): array
	{
		$lists = [];
		$cursor = null;

		do {
			/** @var array{data: list<array{id: string, attributes: array{name: string}}>, links: array{next?: string|null}} $result */
			$result = $this->clientForApiKey($apiKey)->Lists->getLists(fields_list: ['name'], page_cursor: $cursor);

			$lists = [
				...$lists,
				...$result['data'],
			];

			$cursor = $result['links']['next'] ?? null;
		} while ($cursor !== null);

		return array_map(static fn (array $list): array => [
			'id' => $list['id'],
			'name' => $list['attributes']['name'],
		], $lists);
	}

	private function client(?int $siteId): KlaviyoAPI
	{
		return $this->clientForApiKey(Plugin::getInstance()->getSettings()->getApiKey($siteId));
	}

	private function clientForApiKey(string $apiKey): KlaviyoAPI
	{
		return $this->clientsByApiKey[$apiKey] ??= new KlaviyoAPI(
			$apiKey,
			num_retries: 0,
			guzzle_options: [
				'connect_timeout' => 5,
				'timeout' => 10,
			],
		);
	}

	/**
	 * @param array<string, mixed> $profile
	 * @return array{data: array{type: string, attributes: array<string, mixed>}}
	 */
	private function profileArray(array $profile): array
	{
		// Move keys and value types Klaviyo rejects at the top level into custom properties
		$properties = is_array($profile['properties'] ?? null) ? $profile['properties'] : [];
		unset($profile['properties']);
		foreach ($profile as $key => $value) {
			$isAcceptedType = $key === 'location' ? is_array($value) : is_scalar($value) || $value === null;
			if (! in_array($key, self::PROFILE_KEYS, true) || ! $isAcceptedType) {
				$properties[$key] = $value;
				unset($profile[$key]);
			} elseif (is_scalar($value)) {
				$profile[$key] = (string) $value;
			}
		}

		if ($properties !== []) {
			// Send an object, since numeric property names would otherwise encode as a JSON list
			$profile['properties'] = (object) $properties;
		}

		return [
			'data' => [
				'type' => 'profile',
				'attributes' => $profile,
			],
		];
	}
}
