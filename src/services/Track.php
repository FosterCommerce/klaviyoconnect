<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\helpers\ArrayHelper;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\models\Profile;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddProfilePropertiesEvent;
use yii\base\Event;
use Klaviyo;
use GuzzleHttp\Exception\RequestException;

class Track extends Base
{
    const ADD_ORDER_CUSTOM_PROPERTIES = 'addOrderCustomProperties';

    const ADD_LINE_ITEM_CUSTOM_PROPERTIES = 'addLineItemCustomProperties';

    const ADD_PROFILE_PROPERTIES = 'addProfileProperties';

    public function onSaveUser(Event $event)
    {
        $user = $event->sender;
        $groups = $this->getSetting('klaviyoAvailableGroups');
        $userGroups = Craft::$app->getUserGroups()->getGroupsByUserId($user->id);

        if ($this->isInGroup($groups, $userGroups)) {
            $this->identifyUser(Plugin::getInstance()->map->mapUser($user));
        }
    }

    private function isInGroup($selectedGroups, $userGroups)
    {
        foreach ($selectedGroups as $group) {
            $hasGroup = in_array($group, ArrayHelper::getColumn($userGroups, 'id'), false);
            if ($hasGroup) {
                return true;
            }
        }

        return false;
    }

    private function createProfile($param, $eventName = null, $context = null)
    {
        $profile = new Profile($params);

        $event = new AddProfilePropertiesEvent([
            'profile' => $profile,
            'event' => $eventName,
            'context' => $context,
        ]);
        Event::trigger(static::class, self::ADD_PROFILE_PROPERTIES, $event);

        $profile->setCustomProperties($event->properties);
        return $profile;
    }

    public function identifyUser($params)
    {
        Plugin::getInstance()->api->identify($this->createProfile($params));
    }

    public function onCartUpdated(Event $event)
    {
        $this->trackOrder('Updated Cart', $event->sender);
    }

    public function onOrderCompleted(Event $event)
    {
        $this->trackOrder('Placed Order', $event->sender);
    }

    public function trackOrder($eventName, $order, $profile = null)
    {
        if (!$profile) {
            if ($currentUser = Craft::$app->user->getIdentity()) {
                $profile = Plugin::getInstance()->map->mapUser($currentUser);
            } else {
                if ($order->email) {
                    $profile = ['email' => $order->email];
                }
            }
        }

        if ($profile) {
            $orderDetails = $this->getOrderDetails($order, $eventName);
            $event = [
                'event_id' => $order->id,
                'value' => $order->getTotalPrice(),
            ];
            $eventProperties = new EventProperties($event);
            $eventProperties->setCustomProperties($orderDetails);

            try {
                Plugin::getInstance()->api->track($eventName, $profile, $eventProperties);

                if ($eventName === 'Placed Order') {
                    foreach ($orderDetails['Items'] as $item) {
                        $event = [
                            'event_id' => $order->id.'_'.$item['Slug'],
                            'value' => $item['RowTotal'],
                        ];

                        $eventProperties = new EventProperties($event);
                        $eventProperties->setCustomProperties($item);

                        $profile = $this->createProfile(
                            $profile,
                            [
                                'order' => $order,
                                'eventProperties' => $eventProperties,
                            ]
                        );

                        Plugin::getInstance()->api->track('Ordered Product', $profile, $eventProperties);
                    }
                }

            } catch (RequestException $e) {
                // Swallow. Klaviyo responds with a 200.
            }
        }
    }

    protected function getOrderDetails($order, $event = '')
    {
        $settings = Plugin::getInstance()->settings;

        $lineItemsProperties = array();

        foreach ($order->lineItems as $lineItem) {
            $product = $lineItem->purchasable->product;

            $lineItemProperties = [
                'ProductName' => $product->title,
                'Slug' => $lineItem->purchasable->product->slug,
                'ProductURL' => $product->getUrl(),
                'ItemPrice' => $lineItem->price,
                'RowTotal' => $lineItem->subtotal,
                'Quantity' => $lineItem->qty,
                'SKU' => $lineItem->purchasable->sku,
            ];

            $productImageField = $settings->productImageField;
            if (isset($product->$productImageField)) {
                $images = $product->$productImageField->find();
                if (sizeof($images) > 0) {
                    $image = $images[0];
                    $lineItemProperties['ImageURL'] = $image->getUrl($settings->productImageFieldTransformation);
                }
            }

            $addLineItemCustomPropertiesEvent = new AddLineItemCustomPropertiesEvent([
                'properties' => $lineItemProperties,
                'order' => $order,
                'lineItem' => $lineItem,
                'event' => $event,
            ]);
            Event::trigger(static::class, self::ADD_LINE_ITEM_CUSTOM_PROPERTIES, $addLineItemCustomPropertiesEvent);

            $lineItemsProperties[] = $addLineItemCustomPropertiesEvent->properties;
        }

        $customProperties = [
            'OrderID' => $order->id,
            'OrderNumber' => $order->number,
            'TotalPrice' => $order->totalPrice,
            'TotalQuantity' => $order->totalQty,
            'Items' => $lineItemsProperties,
        ];

        $addOrderCustomPropertiesEvent = new AddOrderCustomPropertiesEvent([
            'properties' => $customProperties,
            'order' => $order,
            'event' => $event,
        ]);
        Event::trigger(static::class, self::ADD_ORDER_CUSTOM_PROPERTIES, $addOrderCustomPropertiesEvent);

        return $addOrderCustomPropertiesEvent->properties;
    }
}