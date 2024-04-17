<?php

namespace fostercommerce\klaviyoconnect\models;

use craft\base\Model;
use yii\base\UnknownPropertyException;

abstract class Base extends Model
{
    private array $customAttributes = [];

    public function __set(mixed $name, mixed $value): void
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException) {
            $this->{$name} = $value;
            $this->customAttributes[] = $name;
        }
    }

    public function setCustomProperties(array $properties): void
    {
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): mixed
    {
        $arr = parent::toArray($fields, $expand, $recursive);

        $mapped = [];
        foreach ($arr as $name => $value) {
            $mapped["\${$name}"] = $value;
        }

        foreach ($this->customAttributes as $customAttribute) {
            $mapped[$customAttribute] = $this->{$customAttribute};
        }

        return $mapped;
    }
}
