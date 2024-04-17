<?php

namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddProfilePropertiesEvent extends Event
{
    public $event = null;

    public $properties = [];

    public $profile = null;

    public $context = null;
}
