<?php
namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddOrderDetailsEvent extends Event
{
    public $properties = array();
    public $order = null;
}
