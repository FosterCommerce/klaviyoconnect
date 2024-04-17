<?php

namespace fostercommerce\klaviyoconnect\services;

use craft\helpers\App;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

abstract class Base extends Component
{
    private mixed $settings = null;

    protected function getSetting(string $name): mixed
    {
        if ($this->settings === null) {
            $this->settings = Plugin::getInstance()->settings;
        }

        $value = $this->settings->{$name};

        if (is_string($value)) {
            return App::parseEnv($value);
        }

        return $value;
    }
}
