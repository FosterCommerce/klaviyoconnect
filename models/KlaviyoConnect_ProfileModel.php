<?php

namespace Craft;

class KlaviyoConnect_ProfileModel extends BaseModel
{
    const SPECIAL_PROPERTIES = [
        'id', 'email', 'first_name', 'last_name', 'phone_number',
        'title', 'organization', 'city', 'region', 'country', 'zip',
        'image',
    ];

    public function __toString()
    {
        return $this->name;
    }

    public function defineAttributes()
    {
        return [
        'id' => AttributeType::String,
        'email' => AttributeType::String,
        'first_name' => AttributeType::String,
        'last_name' => AttributeType::String,
        'phone_number' => AttributeType::String,
        'title' => AttributeType::String,
        'organization' => AttributeType::String,
        'city' => AttributeType::String,
        'region' => AttributeType::String,
        'country' => AttributeType::String,
        'zip' => AttributeType::String,
        'image' => AttributeType::String,
        'extra' => array(AttributeType::Mixed),
        ];
    }

    public function map()
    {
        $mapped = [];
        foreach (KlaviyoConnect_ProfileModel::SPECIAL_PROPERTIES as $property) {
            $value = $this->getAttribute($property);
            if (isset($value)) {
                $mapped["\$$property"] = $this->getAttribute($property);
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


/*

*/
