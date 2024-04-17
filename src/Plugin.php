<?php

namespace fostercommerce\klaviyoconnect;

use Craft;
use craft\base\Model;
use craft\commerce\elements\Order;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\services\OrderHistories;
use craft\commerce\services\Payments;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use fostercommerce\klaviyoconnect\queue\jobs\TrackOrderComplete;
use fostercommerce\klaviyoconnect\utilities\KCUtilities;
use fostercommerce\klaviyoconnect\variables\Variable;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package fostercommerce\klaviyoconnect
 * @property \fostercommerce\klaviyoconnect\services\Api $api
 * @property \fostercommerce\klaviyoconnect\services\Track $track
 * @property \fostercommerce\klaviyoconnect\services\Map $map
 * @property \fostercommerce\klaviyoconnect\services\Cart $cart
 */
class Plugin extends \craft\base\Plugin
{
    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'api' => \fostercommerce\klaviyoconnect\services\Api::class,
            'track' => \fostercommerce\klaviyoconnect\services\Track::class,
            'map' => \fostercommerce\klaviyoconnect\services\Map::class,
            'cart' => \fostercommerce\klaviyoconnect\services\Cart::class,
        ]);

        $settings = $this->getSettings();

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = KCUtilities::class;
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['klaviyoconnect/sync-orders'] = 'klaviyoconnect/api/sync-orders';
            }
        );

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = \fostercommerce\klaviyoconnect\fields\ListField::class;
            $event->types[] = \fostercommerce\klaviyoconnect\fields\ListsField::class;
        });

        if ($settings->trackSaveUser) {
            Event::on(User::class, User::EVENT_AFTER_SAVE, function(Event $event) {
                self::getInstance()->track->onSaveUser($event);
            });
        }

        if (Craft::$app->plugins->isPluginEnabled('commerce')) {
            if ($settings->trackCommerceCartUpdated) {
                Event::on(Order::class, Order::EVENT_AFTER_SAVE, function(Event $e) {
                    self::getInstance()->track->onCartUpdated($e);
                });
            }

            if ($settings->trackCommerceOrderCompleted) {
                Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, function(Event $e) {
                    Craft::$app->getQueue()->delay(10)->push(new TrackOrderComplete([
                        'name' => $e->name,
                        'orderId' => $e->sender->id,
                    ]));
                });
            }

            if ($settings->trackCommerceStatusUpdated) {
                Event::on(OrderHistories::class,
                    OrderHistories::EVENT_ORDER_STATUS_CHANGE,
                    function(OrderStatusEvent $e) {
                        self::getInstance()->track->onStatusChanged($e);
                    }
                );
            }

            if ($settings->trackCommerceRefunded) {
                Event::on(Payments::class,
                    Payments::EVENT_AFTER_REFUND_TRANSACTION,
                    function(RefundTransactionEvent $e) {
                        self::getInstance()->track->onOrderRefunded($e);
                    }
                );
            }
        }

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('klaviyoConnect', Variable::class);
        });
    }

    public function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('klaviyoconnect/settings', [
            'settings' => $this->getSettings(),
        ]);
    }

    protected function createSettingsModel(): ?Model
    {
        return new \fostercommerce\klaviyoconnect\models\Settings();
    }
}
