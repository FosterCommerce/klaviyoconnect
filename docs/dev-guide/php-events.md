# Add properties with PHP events

How to add your own properties to the profiles and events Klaviyo Connect sends, from a Craft module or plugin.

## The events

Klaviyo Connect triggers four events on `fostercommerce\klaviyoconnect\services\Track`. Each one fires before the data is queued for Klaviyo, so a listener's changes are part of the send.

| Constant | Event class | Fires when |
|---|---|---|
| `Track::ADD_PROFILE_PROPERTIES` | `AddProfilePropertiesEvent` | Klaviyo Connect builds a profile, for any event or identify call |
| `Track::ADD_ORDER_CUSTOM_PROPERTIES` | `AddOrderCustomPropertiesEvent` | Klaviyo Connect builds an order event's properties |
| `Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES` | `AddLineItemCustomPropertiesEvent` | Klaviyo Connect builds one line item's properties for an order event |
| `Track::ADD_CUSTOM_PROPERTIES` | `AddCustomPropertiesEvent` | A form posts a custom event to `klaviyoconnect/api/track` |

The event classes are in the `fostercommerce\klaviyoconnect\events` namespace. The order and line item events need Craft Commerce.

Event names in these events do not include the site's **Event Prefix**.

## Example

This module adds the amount paid to every order event, and the customer's last order number to their profile when they place an order.

```php
<?php

declare(strict_types=1);

namespace modules\klaviyo;

use craft\commerce\elements\Order;
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddProfilePropertiesEvent;
use fostercommerce\klaviyoconnect\services\Track;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public function init(): void
    {
        parent::init();

        Event::on(
            Track::class,
            Track::ADD_ORDER_CUSTOM_PROPERTIES,
            static function (AddOrderCustomPropertiesEvent $event): void {
                $event->properties['TotalPaid'] = $event->order->getTotalPaid();
            },
        );

        Event::on(
            Track::class,
            Track::ADD_PROFILE_PROPERTIES,
            static function (AddProfilePropertiesEvent $event): void {
                $order = is_array($event->context) ? ($event->context['order'] ?? null) : null;
                if ($event->event === 'Placed Order' && $order instanceof Order) {
                    $event->properties['LastOrderNumber'] = $order->number;
                }
            },
        );
    }
}
```

Register the listeners in your module's `init()` method, as shown. For setting up a module, see [Craft's module guide](https://craftcms.com/docs/5.x/extend/module-guide.html).

## Add profile properties

`Track::ADD_PROFILE_PROPERTIES` fires each time Klaviyo Connect builds a profile to send:

- For an identify call, from the `klaviyoconnect/api/identify` and `klaviyoconnect/api/track` actions, or when a user in a selected user group is saved. `event` and `context` are `null`.
- For a custom event posted from a form. `event` is the event name and `context` is `null`.
- For an order event. `event` is the event name and `context` holds the order. A status change uses the name Klaviyo receives, such as `Fulfilled Order`. The Placed Order profile is also the profile for that order's Ordered Product events.

| Property | Type | Notes |
|---|---|---|
| `event` | `?string` | The event name, or `null` for an identify call. |
| `profile` | `array` | The Klaviyo profile attributes, such as `email`, `first_name` and `location`. Changes to this array are sent to Klaviyo. |
| `properties` | `array` | Custom profile properties to add. Starts empty. Klaviyo Connect merges these into the profile's other custom properties, and a key here replaces one with the same name. |
| `context` | `mixed` | For order events, an array with `order` (the `Order`) and `eventProperties` (the `EventProperties` model built so far). Otherwise `null`. |

Keep `email` in `profile`. Klaviyo Connect does not send an event or identify call for a profile without one.

## Add order properties

`Track::ADD_ORDER_CUSTOM_PROPERTIES` fires once for each order event: Started Checkout, Added to Cart, Updated Cart, Placed Order, order status changes and Refunded Order. It also fires for order events posted from forms and for [past orders](../user-guide/past-orders.md) sent from the utility or the `klaviyoconnect/orders/sync` command.

| Property | Type | Notes |
|---|---|---|
| `event` | `string` | The event name. Every status change uses `Status Changed`. |
| `order` | `craft\commerce\elements\Order` | The order the event is for. |
| `properties` | `array` | The order event's properties. Starts with the built-in properties. |

`properties` starts with the [order properties](../reference/event-properties.md#order-properties) Klaviyo Connect sends. Add keys to the array rather than assigning a new one, because a new array replaces the built-in properties. To remove a built-in property, `unset()` its key.

```php
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\services\Track;
use yii\base\Event;

Event::on(
    Track::class,
    Track::ADD_ORDER_CUSTOM_PROPERTIES,
    static function (AddOrderCustomPropertiesEvent $event): void {
        if ($event->event === 'Placed Order') {
            $event->properties['GiftMessage'] = $event->order->getFieldValue('giftMessage');
        }
    },
);
```

### Add to Started Checkout

This listener adds a purchase order number, from an order field with the handle `purchaseOrder`, to Started Checkout:

```php
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\services\Track;
use yii\base\Event;

Event::on(
    Track::class,
    Track::ADD_ORDER_CUSTOM_PROPERTIES,
    static function (AddOrderCustomPropertiesEvent $event): void {
        if (in_array($event->event, ['Started Checkout', 'Updated Cart'], true)) {
            $event->properties['PurchaseOrder'] = $event->order->getFieldValue('purchaseOrder');
        }
    },
);
```

Started Checkout is sent once per cart, as soon as the cart has an email and an item, which is often before the shopper fills in later checkout fields. The listener also adds the value to Updated Cart, which is sent again when the cart changes, so a purchase order number entered later still reaches Klaviyo.

## Add line item properties

`Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES` fires once per line item, each time Klaviyo Connect builds an order event's properties. It fires before the order event, for the same event names.

| Property | Type | Notes |
|---|---|---|
| `event` | `string` | The event name of the order event being built. |
| `order` | `craft\commerce\elements\Order` | The line item's order. |
| `lineItem` | `craft\commerce\models\LineItem` | The line item. |
| `properties` | `array` | The line item's properties. Starts with the built-in properties. |

`properties` starts with the [line item properties](../reference/event-properties.md#line-item-properties) Klaviyo Connect sends. As with order properties, add keys rather than assigning a new array.

The result becomes the line item's entry in the order's `Items` property, and the properties of its Ordered Product event. Changes to `ProductName` and `Categories` also change the order's `ItemNames` and `Categories`.

A custom line item, which has no purchasable, starts with an empty `properties` array. Klaviyo Connect leaves a line item out of `Items` and does not send an Ordered Product event for it unless a listener adds properties.

```php
use craft\commerce\elements\Variant;
use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\services\Track;
use yii\base\Event;

Event::on(
    Track::class,
    Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES,
    static function (AddLineItemCustomPropertiesEvent $event): void {
        $purchasable = $event->lineItem->getPurchasable();
        if ($purchasable instanceof Variant) {
            $event->properties['Color'] = $purchasable->getFieldValue('color')?->label;
        }
    },
);
```

## Add custom event properties

`Track::ADD_CUSTOM_PROPERTIES` fires when a form posts an event to `klaviyoconnect/api/track` with an `event[name]` and without `event[trackOrder]`. For the form parameters, see [actions](../reference/actions.md).

| Property | Type | Notes |
|---|---|---|
| `event` | `?string` | The event name from the form. |
| `properties` | `array` | Properties to add to the event. Starts empty. A key here replaces a form property with the same name. |

```php
use fostercommerce\klaviyoconnect\events\AddCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\services\Track;
use yii\base\Event;

Event::on(
    Track::class,
    Track::ADD_CUSTOM_PROPERTIES,
    static function (AddCustomPropertiesEvent $event): void {
        if ($event->event === 'Requested Quote') {
            $event->properties['SalesRegion'] = 'West';
        }
    },
);
```

## Errors in a listener

If a listener throws an exception, Klaviyo Connect logs it to the `klaviyoconnect` log category and does not send that event. The request that triggered the event continues, so a failing listener does not stop checkout.

Placed Order is the exception. It is built in a queue job, for live orders and for past orders, so an exception fails the job. After fixing the listener, retry the job from **Utilities -> Queue Manager**.
