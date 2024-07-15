<?php

namespace fostercommerce\klaviyoconnect\services;

use craft\helpers\App;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

abstract class Base extends Component
{
    private ?Settings $settings = null;

    protected function getSetting(string $name): mixed
    {
        if ($this->settings === null) {
            /** @var Settings $settings */
            $settings = Plugin::getInstance()->getSettings();
            $this->settings = $settings;
        }

        $value = $this->settings->{$name};

        if (is_string($value)) {
            return App::parseEnv($value);
        }

        return $value;
    }
}
