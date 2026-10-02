<?php

namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddProfilePropertiesEvent extends Event
{
	public ?string $event = null;

	/**
	 * @var array<string, mixed>
	 */
	public array $properties = [];

	/**
	 * @var array<string, mixed>
	 */
	public array $profile;

	public mixed $context;
}
