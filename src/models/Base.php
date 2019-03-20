<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;
use yii\base\UnknownPropertyException;

abstract class Base extends Model
{
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            $this->$name = $value;
        }
    }

    public function getSpecialProperties(): Array
    {
        $specialProps = (new \ReflectionClass($this))->getProperties();
        $specialProps = array_map(function ($item) {
            return $item->name;
        }, $specialProps);

        return $specialProps;
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

        $specialProps = $this->getSpecialProperties();

        $mapped = [];
        foreach ($arr as $key => $value) {
            if ($value) {
                if (in_array($key, $specialProps)) {
                    $mapped["\${$key}"] = $value;
                } else {
                    $mapped[$key] = $value;
                }
            }
        }

        return $mapped;
    }
}
