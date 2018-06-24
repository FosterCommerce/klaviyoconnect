<?php
namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class AddProfileMappingEvent extends Event
{
    public $mappings = array();
}
