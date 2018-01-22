<?php

namespace Craft;

abstract class KlaviyoConnect_BaseService extends BaseApplicationComponent
{
    private $settings = null;

    protected function getSetting($name)
    {
        if (is_null($this->settings)) {
            $plugin = craft()->plugins->getPlugin('klaviyoconnect');
            $this->settings = $plugin->getSettings();
        }
        return $this->settings[$name];
    }
}
