<?php

namespace fostercommerce\klaviyoconnect\queue\jobs;

use craft\queue\BaseJob;
use fostercommerce\klaviyoconnect\Plugin;
use KlaviyoAPI\ApiException;
use Throwable;
use yii\queue\RetryableJobInterface;

class SendToKlaviyo extends BaseJob implements RetryableJobInterface
{
	public const ACTION_EVENT = 'event';

	public const ACTION_PROFILE = 'profile';

	public const ACTION_LISTS = 'lists';

	public string $action = self::ACTION_EVENT;

	public ?int $siteId = null;

	/**
	 * @var array{type: string, attributes: array<string, mixed>}
	 */
	public array $event = [
		'type' => 'event',
		'attributes' => [],
	];

	/**
	 * @var array<string, mixed>
	 */
	public array $profile = [];

	/**
	 * @var string[]
	 */
	public array $listIds = [];

	public bool $subscribe = false;

	/**
	 * @var string[]|null
	 */
	public ?array $consentChannels = null;

	public function execute($queue): void
	{
		$api = Plugin::getInstance()->api;

		if ($this->action === self::ACTION_EVENT) {
			$api->sendEvent($this->event, $this->siteId);
		} elseif ($this->action === self::ACTION_PROFILE) {
			$api->upsertProfile($this->profile, $this->siteId);
		} else {
			$api->addToLists($this->listIds, $this->profile, $this->subscribe, $this->siteId, $this->consentChannels);
		}
	}

	public function getTtr(): int
	{
		return 60;
	}

	/**
	 * @param Throwable $error
	 */
	public function canRetry($attempt, $error): bool
	{
		// Retry rate limits, Klaviyo outages and dropped connections; other errors fail the same way again
		if ($error instanceof ApiException && $attempt < 5 && in_array($error->getCode(), [0, 429, 500, 502, 503, 504, 524], true)) {
			return true;
		}

		// Record only final failures, so a retry that succeeds leaves no error in the settings
		Plugin::getInstance()->api->recordError($error);

		return false;
	}

	protected function defaultDescription(): string
	{
		return 'Sending data to Klaviyo';
	}
}
