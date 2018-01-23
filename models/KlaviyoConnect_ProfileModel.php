<?php

namespace Craft;

class KlaviyoConnect_ProfileModel extends KlaviyoConnect_BaseModel
{
    const SPECIAL_PROPERTIES = [
        'id', 'email', 'first_name', 'last_name', 'phone_number',
        'title', 'organization', 'city', 'region', 'country', 'zip',
        'image',
    ];

    public function __toString()
    {
        return $this->id ? $this->id : $this->email;
    }

    public function hasEmail()
    {
        return isset($this->email) && !is_null($this->email);
    }

    public function hasId()
    {
        return isset($this->id) && !is_null($this->email);
    }

    public function hasEmailOrId()
    {
        return $this->hasEmail() || $this->hasId();
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
}
