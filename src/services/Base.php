<?php

namespace fostercommerce\klaviyoconnect\services;

use craft\helpers\App;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

abstract class Base extends Component
{
    private mixed $settings = null;

    /**
     * getSetting.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	protected
     * @param	mixed   $name
     */
    protected function getSetting(string $name): mixed
    {
        if ($this->settings === null) {
            $this->settings = Plugin::getInstance()->settings;
        }

        $value = $this->settings->{$name};

        if (is_string($value)) {
            $value = App::parseEnv($value);
        }

        return $value;
    }
}
