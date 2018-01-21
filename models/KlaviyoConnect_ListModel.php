<?php

namespace Craft;

class KlaviyoConnect_ListModel extends BaseModel
{

    public function __toString()
    {
        return $this->name;
    }

    public function defineAttributes()
    {
        return [
            'id' => AttributeType::String,
            'name' => AttributeType::String,
        ];
    }
}
