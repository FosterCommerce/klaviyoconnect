<?php
namespace Craft;

class KlaviyoConnectPlugin extends BasePlugin
{
    function getName()
    {
         return Craft::t('Klaviyo Connect');
    }

    function getDescription()
    {
        return Craft::t('Klaviyo integration for Craft CMS');
    }

    function getVersion()
    {
        return '0.1';
    }

    function getDeveloper()
    {
        return 'Shoe Shine Design & Development';
    }

    function getDeveloperUrl()
    {
        return 'http://shoeshinedesign.com/';
    }

    function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    protected function defineSettings()
    {
        return array(
          'klaviyoSiteId' => array(AttributeType::String, 'default' => ''),
          'klaviyoApiKey' => array(AttributeType::String, 'default' => ''),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('klaviyoconnect/settings', array(
          'settings' => $this->getSettings()
        ));
    }
}