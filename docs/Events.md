# Events

Klaviyo Connect provides the following events you can use in your own custom plugin, to define your own custom mappings.

## `klaviyoConnect_addProfileMapping`

Add a custom mapping for user profiles. Requires an associative array be returned.

- `name`
- `description`
- `handle` - A handle which Klaviyo can associate the mapping with
- `method` - The method in your plugin to call to execute the mapping

### Example

```
function klaviyoConnect_addProfileMapping()
{
    return [
        'name' => 'My Custom Mapping',
        'handle' => 'my_mapping',
        'description' => 'My custom mapping',
        'method' => 'myPlugin.myMapping'
    ];
}
```

## `klaviyoConnect_trackEventMapping($eventName)`

Add a custom mapper to set extra properties on an event. See the `extra` parameter in Event Form Parameters in [Actions](Actions.md)

### Example

```
function klaviyoConnect_addProfileMapping()
{
    return [
        'Order_ID' => $order->id,
        'Order_Number' => $order->number,
        'Item_Total' => $order->itemTotal,
        'Total_Price' => $order->totalPrice,
        'Item_Count' => $order->totalQty,
    ];
}
```

## Klaviyo Special Properties

The Klaviyo API includes the following special properties via HTTP.

### User Profile Properties

- `$id` - your unique identifier for a person
- `$email` - email address
- `$first_name` - first name
- `$last_name` - last name
- `$phone_number` - phone number
- `$title` - title at their business or organization
- `$organization` - business or organization they belong to
- `$city` - city they live in
- `$region` - region or state they live in
- `$country` - country they live in
- `$zip` - postal code where they live
- `$image` - url to a photo of the person

### Event Properties

- `$event_id` - an unique identifier for an event
- `$value` - a numeric value to associate with this event (e.g. the dollar value of a purchase)
