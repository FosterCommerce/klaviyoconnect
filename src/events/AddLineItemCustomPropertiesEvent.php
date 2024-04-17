<?php

namespace fostercommerce\klaviyoconnect\events;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use yii\base\Event;

class AddLineItemCustomPropertiesEvent extends Event
{
    public array $properties = [];

    public Order $order;

    public LineItem $lineItem;

    public string $event;
}
