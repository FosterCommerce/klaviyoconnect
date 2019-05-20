<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;
use yii\base\UnknownPropertyException;

abstract class Base extends Model
{
    private $customAttributes = [];

    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            $this->$name = $value;
            $this->customAttributes[] = $name;
        }
    }

    public function setCustomProperties($properties)
    {
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $arr = parent::toArray($fields, $expand, $recursive);

        $mapped = [];
        foreach ($arr as $name => $value) {
            $mapped["\${$name}"] = $value;
        }

        foreach ($this->customAttributes as $name) {
            $mapped[$name] = $this->$name;
        }

        return $mapped;
    }
}
