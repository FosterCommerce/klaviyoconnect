<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class EventProperties extends Base
{
    const SPECIAL_PROPERTIES = ['event_id', 'value'];

    public $name;
    public $event_id;
    public $value;
    public $extra;

    protected function getSpecialProperties(): Array
    {
        return self::SPECIAL_PROPERTIES;
    }

    public function __toString()
    {
        return "[{$this->name}] {$this->value}";
    }
}
