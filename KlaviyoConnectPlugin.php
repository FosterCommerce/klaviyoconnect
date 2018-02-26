<?php
namespace Craft;

class KlaviyoConnectPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Klaviyo Connect');
    }

    public function getDescription()
    {
        return Craft::t('Klaviyo integration for Craft CMS');
    }

    public function getVersion()
    {
        return '0.1';
    }

    public function getDeveloper()
    {
        return 'Shoe Shine Design & Development';
    }

    public function getDeveloperUrl()
    {
        return 'http://shoeshinedesign.com/';
    }

    public function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';

        craft()->on('users.onSaveUser', function($event) {
            craft()->klaviyoConnect->onSaveUser($event);
        });

        craft()->on('userGroups.onAssignUserToGroups', function($event) {
            craft()->klaviyoConnect->onAssignUserToGroups($event);
        });
    }

    protected function defineSettings()
    {
        return array(
            'klaviyoSiteId' => array(AttributeType::String, 'default' => ''),
            'klaviyoApiKey' => array(AttributeType::String, 'default' => ''),
            'klaviyoDefaultProfileMapping' => array(AttributeType::String, 'default' => 'formdata_mapping'),
            'klaviyoAvailableLists' => array(AttributeType::Mixed, 'default' => array()),
            'klaviyoListsAll' => array(AttributeType::Bool, 'default' => false),
            'klaviyoAvailableGroups' => array(AttributeType::Mixed, 'default' => array()),
        );
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('klaviyoconnect/settings', array(
            'settings' => $this->getSettings(),
        ));
    }
}
