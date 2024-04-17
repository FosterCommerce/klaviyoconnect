<?php

namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddOrderCustomPropertiesEvent extends Event
{
    public $properties = [];

    public $order = null;

    public $event = null;
}
