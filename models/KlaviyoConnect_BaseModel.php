<?php

namespace Craft;

abstract class KlaviyoConnect_BaseModel extends BaseModel
{
    public function map()
    {
        $className = get_class($this);
        $specialProperties = array();
        $mapped = array();

        if (defined("$className::SPECIAL_PROPERTIES")) {
            $specialProperties = $className::SPECIAL_PROPERTIES;

            foreach ($specialProperties as $property) {
                $value = $this->getAttribute($property);
                if (isset($value)) {
                    $mapped["\$$property"] = $this->getAttribute($property);
                }
            }
        }

        $attributes = $this->getAttributes();
        foreach ($attributes as $key => $value) {
            if (!in_array($key, $specialProperties)) {
                if ($key !== 'extra' && isset($value)) {
                    $mapped[$key] = $value;
                }
            }
        }
        if (isset($this->extra) && sizeof($this->extra) > 0) {
            foreach ($this->extra as $key => $value) {
                $mapped[$key] = $value;
            }
        }
        return $mapped;
    }
}
