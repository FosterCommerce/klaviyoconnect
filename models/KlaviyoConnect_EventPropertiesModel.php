<?php

namespace Craft;

class KlaviyoConnect_EventPropertiesModel extends BaseModel
{

  public function __toString()
  {
    return $this->name;
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
