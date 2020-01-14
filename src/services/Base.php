<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

abstract class Base extends Component
{
    private $settings = null;

    protected function getSetting($name)
    {
        if (is_string($this->settings)) {
            $this->settings = Plugin::getInstance()->settings;
        }

        $value = $this->settings->$name;

        if (is_string($value)) {
            $value = Craft::parseEnv($value);
        }
        
        return $value;
    }
}
