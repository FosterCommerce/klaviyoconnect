<?php

namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddCustomPropertiesEvent extends Event
{
	/**
	 * The Klaviyo event name. Not `$name`, which Yii sets to `addCustomProperties` when it triggers the event.
	 */
	public ?string $event = null;

	/**
	 * @var array<string, mixed>
	 */
	public $properties = [];
}
