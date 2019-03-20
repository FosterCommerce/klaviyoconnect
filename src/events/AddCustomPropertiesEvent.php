<?php
namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddCustomPropertiesEvent extends Event
{
    public $name;
    public $properties = array();
}
