<?php
namespace fostercommerce\klaviyoconnect;

use Craft;
use craft\services\Users;
use craft\elements\User;
use craft\commerce\elements\Order;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\events\UserGroupsAssignEvent;
use craft\web\twig\variables\CraftVariable;
use fostercommerce\klaviyoconnect\variables\Variable;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public $hasCpSettings = true;

    public function init()
    {
        parent::init();

        $this->setComponents([
            'api' => \fostercommerce\klaviyoconnect\services\Api::class,
            'track' => \fostercommerce\klaviyoconnect\services\Track::class,
            'map' => \fostercommerce\klaviyoconnect\services\Map::class,
            'cart' => \fostercommerce\klaviyoconnect\services\Cart::class,
        ]);

        $settings = $this->getSettings();

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = \fostercommerce\klaviyoconnect\fields\ListField::class;
            $event->types[] = \fostercommerce\klaviyoconnect\fields\ListsField::class;
        });

        Event::on(User::class, User::EVENT_AFTER_SAVE, function (Event $event) {
            if ($settings->trackSaveUser) {
                Plugin::getInstance()->track->onSaveUser($event);
            }
        });

        if(Craft::$app->plugins->isPluginEnabled('commerce')) {
            Event::on(Order::class, Order::EVENT_AFTER_SAVE, function (Event $e) {
                if ($settings->trackCommerceCartUpdated) {
                    Plugin::getInstance()->track->onCartUpdated($e);
                }
            });

            Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, function (Event $e) {
                if ($settings->trackCommerceOrderCompleted) {
                    Plugin::getInstance()->track->onOrderCompleted($e);
                }
            });
        }

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            $variable = $event->sender;
            $variable->set('klaviyoConnect', Variable::class);
        });
    }

    protected function createSettingsModel()
    {
        return new \fostercommerce\klaviyoconnect\models\Settings();
    }

    public function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('klaviyoconnect/settings', [
            'settings' => $this->getSettings()
        ]);
    }
}
