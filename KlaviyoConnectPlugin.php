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
        return Craft::t('Klaviyo integration for Craft Commerce');
    }

    function getVersion()
    {
        return '1.0';
    }

    function getDeveloper()
    {
        return 'Shoe Shine Design';
    }

    function getDeveloperUrl()
    {
        return 'http://shoeshinedesign.com/';
    }

    function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';

        craft()->on('commerce_orders.onSaveOrder', function($event) {
            craft()->klaviyoConnect->onSaveOrder($event);
        });

        craft()->on('commerce_orders.onOrderComplete', function($event) {
            craft()->klaviyoConnect->onOrderComplete($event);
        });

        craft()->on('users.onSaveUser', function($event) {
            craft()->klaviyoConnect->onSaveUser($event);
        });
    }

    protected function defineSettings()
    {
        return array(
          'klaviyoSiteId' => array(AttributeType::String, 'default' => ''),
          'klaviyoApiKey' => array(AttributeType::String, 'default' => ''),
          'subscribeFieldHandle' => array(AttributeType::String, 'default' => 'newsletter'),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('klaviyoconnect/settings', array(
          'settings' => $this->getSettings()
        ));
    }
}