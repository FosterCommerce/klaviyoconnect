# Hooks

## `klaviyoConnect_addProfileMapping`

Add a custom mapping for user profiles.

Requires an associative array be returned

- `name`
- `description`
- `handle` - A handle which Klaviyo can associate the mapping with
- `method` - The method in your plugin to call to execute the mapping

## Example

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