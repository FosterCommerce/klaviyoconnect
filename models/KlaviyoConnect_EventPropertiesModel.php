<?php

namespace Craft;

class KlaviyoConnect_EventPropertiesModel extends KlaviyoConnect_BaseModel
{

    public function __toString()
    {
        return $this->value;
    }

    public function defineAttributes()
    {
        return [
            'event_id' => AttributeType::String,
            'value' => AttributeType::String,
            'extra' => array(AttributeType::Mixed),
        ];
    }
}
