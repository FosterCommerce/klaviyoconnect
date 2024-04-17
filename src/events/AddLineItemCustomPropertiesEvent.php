<?php

namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddLineItemCustomPropertiesEvent extends Event
{
    public $properties = [];

    public $order = null;

    public $lineItem = null;

    public $event = null;
}
