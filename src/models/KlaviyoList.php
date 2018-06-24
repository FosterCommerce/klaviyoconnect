<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class KlaviyoList extends Model
{
    public $id;
    public $name;

    public function __toString()
    {
        return $this->name;
    }
}
