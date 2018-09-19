<?php
namespace fostercommerce\klaviyoconnect\events;

use yii\base\Event;

class TrackEventMappingEvent extends Event
{
    public $name;
    public $eventId;
    public $extraProps = array();
}
