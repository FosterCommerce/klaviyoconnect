<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\events\RefundTransactionEvent;
use craft\helpers\ArrayHelper;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\events\AddCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddProfilePropertiesEvent;
use yii\base\Event;
use GuzzleHttp\Exception\RequestException;
use DateTime;

class Track extends Base
{
    public const ADD_CUSTOM_PROPERTIES = 'addCustomProperties';

    public const ADD_ORDER_CUSTOM_PROPERTIES = 'addOrderCustomProperties';

    public const ADD_LINE_ITEM_CUSTOM_PROPERTIES = 'addLineItemCustomProperties';

    public const ADD_PROFILE_PROPERTIES = 'addProfileProperties';

    /**
     * onSaveUser.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	event	$event	
     * @return	void
     */
    public function onSaveUser(Event $event): void
    {
        $user = $event->sender;
        $groups = $this->getSetting('klaviyoAvailableGroups');
        $userGroups = Craft::$app->getUserGroups()->getGroupsByUserId($user->id);

        if ($this->isInGroup($groups, $userGroups)) {
            $this->identifyUser(Plugin::getInstance()->map->mapUser($user));
        }
    }

    /**
     * identifyUser.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$params	
     * @return	void
     */
    public function identifyUser(array $params): void
    {
        Plugin::getInstance()->api->identify($this->createProfile($params));
    }

    /**
     * onCartUpdated.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	event	$event	
     * @return	void
     */
    public function onCartUpdated(Event $event): void
    {
        /** @var Order $order */
        $order = $event->sender;
        $this->trackOrder('Updated Cart', $order);
    }

    /**
     * onOrderCompleted.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	event	$event	
     * @return	void
     */
    public function onOrderCompleted(Event $event): void
    {
        $this->trackOrder('Placed Order', $event->sender);
    }

    /**
     * onStatusChanged.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	event	$event	
     * @return	void
     */
    public function onStatusChanged(Event $event): void
    {
        $order = $event->orderHistory->getOrder();
        $this->trackOrder("Status Changed", $order, null, null, $event);
    }

    /**
     * onOrderRefunded.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	refundtransactionevent	$event	
     * @return	void
     */
    public function onOrderRefunded(RefundTransactionEvent $event): void
    {
        $order = $event->transaction->getOrder();
        $this->trackOrder("Refunded Order", $order, null, null, $event);
    }

    /**
     * addToLists.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed  	$listIds             	
     * @param	mixed  	$profileParams       	
     * @param	boolean	$useSubscribeEndpoint	Default: false
     * @return	void
     */
    public function addToLists(array $listIds, array $profile, bool $subscribe = false): void
    {
        $profileId = Plugin::getInstance()->api->getProfileId($profile['email']);

        if ($profileId) {
            foreach ($listIds as $listId) {
                try {
                    if ($subscribe) {
                        Plugin::getInstance()->api->subscribeProfileToList($listId, $profile);
                    } else {
                        Plugin::getInstance()->api->addProfileToList($listId, $profileId);
                    }
                } catch (\Throwable $t) {
                    // TODO we need proper error handling
                    // Swallow. Klaviyo responds with a 200.
                }
            }
        }
    }

    /**
     * trackEvent.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$eventName      	
     * @param	mixed	$profileParams  	
     * @param	mixed	$eventProperties	
     * @param	mixed	$trackOnce      	
     * @param	mixed	$timestamp      	Default: null
     * @return	void
     */
    public function trackEvent(string $eventName, array $profileParams, EventProperties $eventProperties, ?string $timestamp = null): void
    {
        $profile = $this->createProfile($profileParams, $eventName);

        $addCustomPropertiesEvent = new AddCustomPropertiesEvent(['name' => $eventName]);
        Event::trigger(static::class, self::ADD_CUSTOM_PROPERTIES, $addCustomPropertiesEvent);

        if (sizeof($addCustomPropertiesEvent->properties) > 0) {
            $eventProperties->setCustomProperties($addCustomPropertiesEvent->properties);
        }

        try {
            Plugin::getInstance()->api->track($eventName, $profile, $eventProperties, $timestamp);
        } catch (RequestException $e) {
            // Swallow. Klaviyo responds with a 200.
        }
    }

    public function trackOrder(string $eventName, Order $order, ?array $profile = null, ?string $timestamp = null, ?Event $fullEvent = null): void
    {
        if ($order->email) {
            $profile = [
                'email'      => $order->email,
                'first_name' => $order->billingAddress->firstName ?? null,
                'last_name'  => $order->billingAddress->lastName ?? null,
            ];
        }

        if (!$profile && $currentUser = Craft::$app->user->getIdentity()) {
            $profile = Plugin::getInstance()->map->mapUser($currentUser);
        }

        if ($profile) {
            $orderDetails = $this->getOrderDetails($order, $eventName);
            $dateTime = new DateTime();

            $event = [
                'unique_id' => $order->id . '_' . $dateTime->getTimestamp(),
                'value' => $order->totalPaid,
                'value_currency' => $order->currency,
            ];
            $eventProperties = new EventProperties($event);
            $eventProperties->setCustomProperties($orderDetails);
            $success = true;

            if($eventName === 'Refunded Order') {
                $children = $fullEvent->transaction->childTransactions;
                $child    = $children[count($children) - 1];

                if($child->status === 'success') {
                    $message = $child->note;
                    $eventProperties->setCustomProperties(['Reason' => $message]);

                    $eventName === 'Refund Issued';
                } else {
                    $success = false;
                }
            }

            if($eventName === 'Status Changed') {
                $orderHistory = $fullEvent->orderHistory;
                $status = $orderHistory->getNewStatus()->name;
                $eventProperties->setCustomProperties(['Reason' => $orderHistory->message]);

                $eventName = "$status Order";
            }

            if($success) {
                try {
                    $profile = $this->createProfile(
                        $profile,
                        $eventName,
                        [
                            'order' => $order,
                            'eventProperties' => $eventProperties,
                        ]
                    );

                    Plugin::getInstance()->api->track($eventName, $profile, $eventProperties, $timestamp);
                    
                    if ($eventName === 'Placed Order') {
                        foreach ($orderDetails['Items'] as $item) {
                            $event = [
                                'unique_id' => $order->id.'_'.$item['Slug'].'_'.$dateTime->getTimestamp(),
                                'value' => $order->totalPaid,
                                'value_currency' => $order->currency,
                            ];

                            $eventProperties = new EventProperties($event);
                            $eventProperties->setCustomProperties($item);

                            Plugin::getInstance()->api->track('Ordered Product', $profile, $eventProperties, $timestamp);
                        }
                    }
                } catch (RequestException $e) {
                    // Swallow. Klaviyo responds with a 200.
                }
            }
        } else {
            // Swallow.
            // This is likely a logged out user adding an item to their cart.
        }
    }

    protected function createProfile(array $profile, ?string $eventName = null, mixed $context = null): array
    {
        $event = new AddProfilePropertiesEvent([
            'profile' => $profile,
            'event' => $eventName,
            'context' => $context,
        ]);
        Event::trigger(static::class, self::ADD_PROFILE_PROPERTIES, $event);

        if ($event->properties !== []) {
            $profile['properties'] = $event->properties;
        }

        return $profile;
    }

    protected function getOrderDetails(Order $order, string $event = ''): array
    {
        $settings = Plugin::getInstance()->settings;

        $lineItemsProperties = array();

        foreach ($order->lineItems as $lineItem) {
            // set some defaults
            // This is a messy, temporary fix and needs refactoring
            // If a variant has been deleted since an order was placed the array assignment for Klaviyo event properties is skipped 
            // since there is no "product"
            // this means that when creating the event ID in trackOrder() (around line 317), there is no key named "Slug".
            // It was possible to get around this by ignoring "Slug", since it's always going to be null anyway,
            // but this also meant that no record of the product was sent to Kalviyo. 
            // This "fix" makes sure there is *some* data to send to Klaviyo if the Variant has been deleted
            // We should probably refactor this part to always use the lineitem snapshot
            $lineItemProperties = [
                'value' => $lineItem->price * $lineItem->qty,
                'ProductName' => $lineItem->snapshot['title'] ?? '',
                'Slug' => $lineItem->snapshot['slug'] ?? '',
                'ProductURL' => $lineItem->snapshot['url'] ?? '',
                'ProductType' => '',
                'ItemPrice' => $lineItem->snapshot['price'] ?? '',
                'RowTotal' => $lineItem->subtotal,
                'Quantity' => $lineItem->qty,
                'SKU' => $lineItem->snapshot['sku'] ?? '',
                'Note' => 'Variant no longer available',
                'Snapshot' =>  $lineItem->snapshot
            ];
            
            
            // Add regular Product purchasable properties
            $product = $lineItem->purchasable->product ?? [];
            if ($product) {
                $lineItemProperties = [
                    'value' => $lineItem->price * $lineItem->qty,
                    'ProductName' => $product->title,
                    'Slug' => $lineItem->purchasable->product->slug,
                    'ProductURL' => $product->getUrl(),
                    'ProductType' => $product->type->name,
                    'ItemPrice' => $lineItem->price,
                    'RowTotal' => $lineItem->subtotal,
                    'Quantity' => $lineItem->qty,
                    'SKU' => $lineItem->purchasable->sku,
                ];

                $variant = $lineItem->purchasable;
                
                $productImageField = $settings->productImageField;

                if ( isset($variant->$productImageField) && $variant->$productImageField->count() ) {
                    if ($image = $variant->$productImageField->one()) {
                        $lineItemProperties['ImageURL'] = $image->getUrl($settings->productImageFieldTransformation,true);
                    }
                } else if ( isset($product->$productImageField) && $product->$productImageField->count() ) {
                    if ($image = $product->$productImageField->one()) {
                        $lineItemProperties['ImageURL'] = $image->getUrl($settings->productImageFieldTransformation,true);
                    }
                }

            }

            // Add any additional user-defined properties
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

    private function isInGroup(array $selectedGroups, array $userGroups): bool
    {
        $groupIds = ArrayHelper::getColumn($userGroups, 'id');
        $groups = array_filter(array_map(static fn($group): ?int => $group ? (int) $group : null, $selectedGroups));
        foreach ($groups as $group) {
            $hasGroup = in_array($group, $groupIds, false);
            if ($hasGroup) {
                return true;
            }
        }

        return false;
    }
}
