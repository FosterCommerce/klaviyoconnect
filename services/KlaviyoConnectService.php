<?php

namespace Craft;

use \Klaviyo;

class KlaviyoConnectService extends BaseApplicationComponent
{
    var $settings = null;

    function __construct()
    {
        $plugin = craft()->plugins->getPlugin('klaviyoconnect');
        $this->settings = $plugin->getSettings();
    }

    // Add guests to mailing list
    public function onSaveOrder($event)
    {
        $order = $event->params['order'];

        if (!$order->isCompleted) {
            $customer = $order->customer;

            $email = $order->email;
            if (empty($email)) {
                if (!is_null($customer->user)) {
                    $email = $customer->user->email;
                }
            }

            if (!empty($email)) {
                $customerProperties = ['$email' => $email];

                // Use shipping/billing details to get extra customer info if they're already set
                if (is_null($customer->user)) {
                    $subscriptionHandle = $this->getSetting('subscribeFieldHandle');
                    if (isset($order[$subscriptionHandle])) {
                        $withSubscription = $order[$subscriptionHandle];
                        $customerProperties['WithSubscription'] = $withSubscription ? 'yes' : 'no';
                    }

                    $address = null;
                    if (!is_null($order->shippingAddress)) {
                        $address = $order->shippingAddress;
                    } else if (!is_null($order->billingAddress)) {
                        $address = $order->billingAddress;
                    }

                    if (!is_null($address)) {
                        $customerProperties = array_merge($customerProperties, [
                            '$first_name' => $address->firstName,
                            '$last_name' => $address->lastName,
                            '$phone_number' => $address->phone,
                        ]);
                    }
                } else { // Set details from UserModel
                    $user = $customer->user;
                    $customerProperties = array_merge($customerProperties, [
                        '$first_name' => $user->firstName,
                        '$last_name' => $user->lastName,
                        'IsRegistered' => 'yes',
                    ]);
                }

                $lineItemsProperties = array();

                foreach ($order->lineItems as $lineItem) {
                    $product = $lineItem->purchasable->product;

                    $lineItemProperties = [
                        'Title' => $product->title,
                        'URL' => '/'.$product->uri,
                        'Price' => $lineItem->price,
                        'Line Price' => $lineItem->subtotal,
                        'Quantity' => $lineItem->qty,
                    ];

                    if (isset($product->productImages)) {
                        $images = $product->productImages->find();

                        if (sizeof($images) > 0) {
                            $image = $images[0];
                            $lineItemProperties['Image'] = $image->getUrl('productThumbnail');
                        }
                    }

                    $lineItemsProperties[] = $lineItemProperties;
                }

                $extraProperties = [
                    'Order ID' => $order->id,
                    'Item Total' => $order->itemTotal,
                    'Total Price' => $order->totalPrice,
                    'Item Count' => $order->totalQty,
                    'Line Items' => $lineItemsProperties,
                ];

                $klaviyo = new Klaviyo($this->getSetting('klaviyoSiteId'));
                $klaviyo->track('Updated Cart', $customerProperties, $extraProperties);
            }
        }
    }

    public function onOrderComplete($event)
    {
        $order = $event->params['order'];
        $klaviyo = new Klaviyo($this->getSetting('klaviyoSiteId'));
        $klaviyo->track(
            'Completed Order',
            ['$email' => $order->email],
            ['Order ID' => $order->id]
        );
    }

    public function onSaveUser($event)
    {
        $user = $event->params['user'];
        $isCustomer = $user->isInGroup('customers');

        if ($isCustomer) {
            $isNewUser = $event->params['isNewUser'];

            $klaviyo = new Klaviyo($this->getSetting('klaviyoSiteId'));
            $klaviyo->identify([
                '$email' => $user->email,
                '$first_name' => $user->firstName,
                '$last_name' => $user->lastName,
                'IsRegistered' => 'yes',
                'WasUpdated' => ($isNewUser ? 'no' : 'yes'),
            ]);
        }
    }

    public function getSetting($name)
    {
        return $this->settings[$name];
    }
}
