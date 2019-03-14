<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\helpers\ArrayHelper;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\models\Profile;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\events\AddOrderDetailsEvent;
use fostercommerce\klaviyoconnect\events\AddOrderLineItemDetailsEvent;
use yii\base\Event;
use Klaviyo;
use GuzzleHttp\Exception\RequestException;

class Events extends Base
{
    const ADD_ORDER_DETAILS = 'addOrderDetails';

    const ADD_ORDER_LINE_ITEM_DETAILS = 'addOrderLineItemDetails';

    public function onSaveUser(Event $event)
    {
        $this->identifyUser($event->sender);
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

    private function identifyUser($user)
    {
        $groups = $this->getSetting('klaviyoAvailableGroups');
        $userGroups = Craft::$app->getUserGroups()->getGroupsByUserId($user->id);

        if ($this->isInGroup($groups, $userGroups)) {
            Plugin::getInstance()->api->identify(new Profile([
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
            ]));
        }
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
            if (Craft::$app->user->getIdentity()) {
                $profile = Plugin::getInstance()->map->map('usermodel_mapping', array());
            } else {
                if ($order->email) {
                    $profile = Plugin::getInstance()->populateModel(Profile::class, ['email' => $order->email]);
                }
            }
        }

        if ($profile) {
            $orderDetails = $this->getOrderDetails($order);
            $event = [
                'event_id' => $order->id,
                'value' => $order->getTotalPrice(),
                'extra' => $orderDetails,
            ];
            $eventProperties = Plugin::getInstance()->populateModel(EventProperties::class, $event);

            try {
                Plugin::getInstance()->api->track($eventName, $profile, $eventProperties);

                if ($eventName === 'Placed Order') {
                    foreach ($orderDetails['Items'] as $item) {
                        $event = [
                            'event_id' => $order->id.'_'.$item['Slug'],
                            'value' => $item['RowTotal'],
                            'extra' => $item,
                        ];

                        $eventProperties = Plugin::getInstance()->populateModel(EventProperties::class, $event);

                        Plugin::getInstance()->api->track('Ordered Product', $profile, $eventProperties);
                    }
                }

            } catch (RequestException $e) {
                // Swallow. Klaviyo responds with a 200.
            }
        }
    }

    protected function getOrderDetails($order)
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

            $addLineItemDetailsEvent = new AddOrderLineItemDetailsEvent([
                'properties' => $lineItemProperties,
                'order' => $order,
                'lineItem' => $lineItem,
            ]);
            Event::trigger(static::class, self::ADD_ORDER_LINE_ITEM_DETAILS, $addLineItemDetailsEvent);

            $lineItemsProperties[] = $addLineItemDetailsEvent->properties;
        }

        $extraProperties = [
            'OrderID' => $order->id,
            'OrderNumber' => $order->number,
            'TotalPrice' => $order->totalPrice,
            'TotalQuantity' => $order->totalQty,
            'Items' => $lineItemsProperties,
        ];

        $addOrderDetailsEvent = new AddOrderDetailsEvent([
            'properties' => $extraProperties,
            'order' => $order,
        ]);
        Event::trigger(static::class, self::ADD_ORDER_DETAILS, $addOrderDetailsEvent);

        return $addOrderDetailsEvent->properties;
    }
}
