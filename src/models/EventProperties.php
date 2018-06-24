<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class EventProperties extends Model
{
    public $name;
    public $event_id;
    public $value;
    public $extra;

    public function __toString()
    {
        return "[{$this->name}] {$this->value}";
    }
}
