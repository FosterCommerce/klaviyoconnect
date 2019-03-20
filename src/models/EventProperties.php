<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class EventProperties extends Base
{
    public $event_id;
    public $value;

    public function __toString()
    {
        return "[{$this->name}] {$this->value}";
    }
}
