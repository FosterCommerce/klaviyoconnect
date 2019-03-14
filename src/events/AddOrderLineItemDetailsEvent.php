<?php
namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddOrderLineItemDetailsEvent extends Event
{
    public $properties = array();
    public $order = null;
    public $lineItem = null;
}
