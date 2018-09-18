<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\models\Profile;
use fostercommerce\klaviyoconnect\models\EventProperties;
use yii\base\Event;
use Klaviyo;
use GuzzleHttp\Exception\RequestException;

class Events extends Base
{
    public function onAssignUserToGroups($event)
    {
        $userId = $event->params['userId'];
        $user = Craft::$app->users->getUserById((int) $userId);
        $this->identifyUser($user);
    }

    public function onSaveUser($event)
    {
        $this->identifyUser($event->sender);
    }

    private function identifyUser ($user)
    {
        $groups = $this->getSetting('klaviyoAvailableGroups');
        $isInGroups = false;
        foreach ($groups as $group) {
            if ($user->isInGroup((Int) $group)) {
                $isInGroups = true;
            }
        }

        if ($isInGroups) {
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
        $this->trackOrder('Completed Order', $event->sender);
    }

    private function trackOrder($eventName, $order)
    {
        if (Craft::$app->user) {
            $profile = Plugin::getInstance()->map->map('usermodel_mapping', array());
        }
        else {
            if ($order->email) {
                $profile = Plugin::getInstance()->populateModel(Profile::class, ['email' => $order->email]);
            }
        }

        if (isset($profile)) {
            $event = [
                'event_id' => $order->id,
                'extra' => $this->getOrderDetails($order),
            ];
            $eventProperties = Plugin::getInstance()->populateModel(EventProperties::class, $event);
            try {
                Plugin::getInstance()->api->track($eventName, $profile, $eventProperties);
            } catch (RequestException $e) {
                // Swallow. Klaviyo responds with a 200.
            }
        }
    }

    private function getOrderDetails($order)
    {
        $settings = Plugin::getInstance()->settings;

        $lineItemsProperties = array();

        foreach ($order->lineItems as $lineItem) {
            $product = $lineItem->purchasable->product;

            $lineItemProperties = [
                'Title' => $product->title,
                'URL' => $product->getUrl(),
                'Price' => $lineItem->price,
                'Line_Price' => $lineItem->subtotal,
                'Quantity' => $lineItem->qty,
                'SKU' => $lineItem->purchasable->sku,
            ];


            $productImageField = $settings->productImageField;
            if (isset($product->$productImageField)) {
                $images = $product->$productImageField->find();
                if (sizeof($images) > 0) {
                    $image = $images[0];
                    $lineItemProperties['Image'] = $image->getUrl($settings->productImageFieldTransformation);
                }
            }

            $lineItemsProperties[] = $lineItemProperties;
        }

        $extraProperties = [
            'Order_ID' => $order->id,
            'Order_Number' => $order->number,
            'Item_Total' => $order->itemTotal,
            'Total_Price' => $order->totalPrice,
            'Item_Count' => $order->totalQty,
            'Line_Items' => $lineItemsProperties,
        ];

        return $extraProperties;
    }
}
