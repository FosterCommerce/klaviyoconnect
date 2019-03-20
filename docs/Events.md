# Events

Klaviyo Connect provides the following events you can use in your own custom plugin.

### `AddCustomPropertiesEvent`

Add custom properties on to tracking events.

- `name` - Event name
- `properties` - Custom properties to pass through to Klaviyo with the tracked event.

### Example

```php
use fostercommerce\klaviyoconnect\controllers\ApiController;
use fostercommerce\klaviyoconnect\events\AddCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\models\EventProperties;

// ...

Event::on(
  ApiController::class,
  ApiController::EVENT_ADD_EVENT_DETAILS,
  function (AddCustomPropertiesEvent $e) {
    $eventName = $e->name;

    // Add your custom event properties to the tracking data
    $e->properties = [
      'Foo' => 'Bar',
    ];
  }
);
```

## `AddOrderCustomPropertiesEvent`

Add custom properties onto order tracking events.

- `event` - Event name
- `order` - The Commerce Order object
- `properties` - Custom properties to pass through to Klaviyo with the tracked event.

### Example

```php
use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\models\EventProperties;

// ...

Event::on(
  Track::class,
  Track::ADD_ORDER_CUSTOM_PROPERTIES,
  function (AddCustomPropertiesEvent $e) {
    $eventName = $e->event;
    $order = $e->order;

    // Add your custom event properties to the tracking data
    $e->properties = [
      'TotalPaid' => $order->getTotalPaid(),
    ];
  }
);
```

## `AddLineItemCustomPropertiesEvent`

Add custom properties onto the individual line items which form part of the order tracking events.

- `event` - Event name
- `order` - The Commerce Order object
- `lineItem` - The Commerce LineItem object
- `properties` - Custom properties to pass through to Klaviyo with the tracked event.

### Example

```php
use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\models\EventProperties;

// ...

Event::on(
  Track::class,
  Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES,
  function (AddLineItemCustomPropertiesEvent $e) {
    $eventName = $e->event;
    $order = $e->order;
    $lineItem = $e->lineItem;

    // Add your custom event properties to the tracking data
    $e->properties = [
      'SubTotal' => $lineItem->purchasable->product->title,
    ];
  }
);
```

## `AddLineItemCustomPropertiesEvent`

Add custom properties onto the individual line items which form part of the order tracking events.

- `event` - Event name
- `profile` - Associative array of the users profile data
- `properties` - Custom properties to pass through to Klaviyo with the tracked event.

### Example

```php
use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\events\AddProfilePropertiesEvent;
use fostercommerce\klaviyoconnect\models\EventProperties;

// ...

Event::on(
  Track::class,
  Track::ADD_PROFILE_PROPERTIES,
  function (AddProfilePropertiesEvent $e) {
    $eventName = $e->event;
    $profile = $e->profile;
    $context = $e->context;

    if (array_key_exists('order', $context)) {
      $eventProperties = $context['eventProperties'];
      // Add your custom event properties to the tracking data
      $e->properties = [
        'LastOrderId' => $eventProperties->OrderId,
      ];
    }
  }
);
```

