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
use fostercommerce\klaviyoconnect\queue\jobs\TrackOrderComplete;
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

        if ($settings->trackSaveUser) {
            Event::on(User::class, User::EVENT_AFTER_SAVE, function (Event $event) {
                Plugin::getInstance()->track->onSaveUser($event);
            });
        }

        if(Craft::$app->plugins->isPluginEnabled('commerce')) {
            if ($settings->trackCommerceCartUpdated) {
                Event::on(Order::class, Order::EVENT_AFTER_SAVE, function (Event $e) {
                    Plugin::getInstance()->track->onCartUpdated($e);
                });
            }

            if ($settings->trackCommerceOrderCompleted) {
                Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, function (Event $e) {
                    Craft::$app->getQueue()->delay(10)->push(new TrackOrderComplete([
                        'name' => $e->name,
                        'orderId' => $e->sender->id,
                    ]));
                });
            }
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
