<?php

namespace fostercommerce\klaviyoconnect\events;

use craft\commerce\elements\Order;
use yii\base\Event;

class AddOrderCustomPropertiesEvent extends Event
{
	public array $properties = [];

	public Order $order;

	public string $event;
}
