<?php

namespace fostercommerce\klaviyoconnect\services;

use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use KlaviyoAPI\ApiException;
use KlaviyoAPI\KlaviyoAPI;
use yii\base\Exception;

class Api extends Base
{
	private KlaviyoAPI|null $api = null;

	/**
	 * @var KlaviyoList[]|null
	 */
	private ?array $cachedLists = null;

	public function init(): void
	{
		parent::init();

		$this->api = new KlaviyoAPI($this->getSetting('klaviyoApiKey'));
	}

	public function track(string $event, array $profile, ?EventProperties $eventProperties = null, ?string $timestamp = null): void
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

	public function identify(array $profile): void
	{
		$profile = $this->profileArray($profile);
		if (! isset($profile['data']['attributes']['email'])) {
			throw new Exception('You must identify a user by email');
		}

		try {
			$this->api?->Profiles->createOrUpdateProfile($profile);
		} catch (ApiException $e) {
			// 409 for a duplicate profile error - if we try track the same person twice, Klaviyo will respond with this.
			if ($e->getCode() !== 409) {
				// TODO implement proper error handling
			}
		} catch (\Throwable) {
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

			$lists = array_map(static fn ($list): KlaviyoList => new KlaviyoList([
				'id' => $list['id'],
				'name' => $list['attributes']['name'],
			]), $lists);

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

	public function addProfileToList(string $listId, string $profileId): void
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

	public function subscribeProfileToList(string $listId, array $profile): void
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

		$this->api?->Profiles->subscribeProfiles([
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

	private function getPaginationCursor(array $response): ?string
	{
		$url = parse_url($response['links']['next'] ?? '');
		parse_str($url['query'] ?? '', $params);
		return $params['page']['cursor'] ?? null;
	}
}
