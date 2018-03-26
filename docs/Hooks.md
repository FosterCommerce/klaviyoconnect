# Hooks

## `klaviyoConnect_addProfileMapping`

Add a custom mapping for user profiles.

Requires an associative array be returned

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

Add a custom mapper to set extra properties on an event.

See the `extra` parameter in Event Form Parameters in [Actions](Actions.md)

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
