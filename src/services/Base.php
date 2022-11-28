<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\helpers\App;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

abstract class Base extends Component
{
    private $settings = null;

    /**
     * getSetting.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	protected
     * @param	mixed	$name	
     * @return	mixed
     */
    protected function getSetting($name) // no return type as mixed only works above PHP 8
    {
        if (is_null($this->settings)) {
            $this->settings = Plugin::getInstance()->settings;
        }

        $value = $this->settings->$name;

        if (is_string($value)) {
            if (method_exists('App', 'parseEnv')){
                $value = App::parseEnv($value);
            } else {
                $value = Craft::parseEnv($value);
            }
        }
        
        return $value;
    }
}
