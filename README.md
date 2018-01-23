# Klaviyo Connect

A [Craft CMS](https://craftcms.com/) plugin for [Klaviyo](https://www.klaviyo.com).

## Installation

- Download and unzip, rename to `klaviyoconnect`
- Copy the `klaviyoconnect` folder to your `craft/plugins` directory
- From inside `klaviyoconnect` directory, run `composer install`
- Install the plugin from Craft CP

## Configure (Settings -> Plugins -> Klaviyo Connect -> Settings)

Site ID and Private API Key can be found here: [https://www.klaviyo.com/account#api-keys-tab](https://www.klaviyo.com/account#api-keys-tab)

## Field Types

### Klaviyo Lists

Group checkbox for multi-selection of Klaiyo lists.

An array of selected list IDs are stored.

## Actions

### `POST klaviyoConnect/api/identify`

#### Profile Form Parameters

`email` _Required_

An email address to identify a person's profile on Klaviyo

`extra[]` _Optional_

Associative arrary of extra properties to be assigned to a profile in Klaviyo.

```
[
  'LastLogin' => '2018-01-20T10:24:44',
  ...
]
```

**Klaviyo-specific Profile properties** _Optional_

See [Special Identify Properties](https://www.klaviyo.com/docs/http-api) in Klaviyo's API docs.

The following properties can be passed to controllers to populate a persons profile:

- `id`
- `first_name`
- `last_name`
- `phone_number`
- `title`
- `organization`
- `city`
- `region`
- `country`
- `zip`
- `image`

### `POST klaviyoConnect/api/updateProfile`

Adds a profile to a list or multiple lists and/or tracks an event.

#### Profile Parameters

Same as `identify` action.

#### List Form Parameters

If either `list` or `lists[]` is present, the profile will be added to the specified lists.

`list` _Required_

A Klaviyo list ID.

`lists[]` _Required_

Array of Klaviyo list IDs.

`confirmOptIn` _Optional_ [Default: `"1"`]

Whether or not Klaviyo should send an email to the person confirming opt-in.

#### Event Form Parameters

If event parameters are present, Klaviyos tracking API will be called.

`event[name]`

The name of the event to track.

`event[event_id]`

The ID to associate with an event, e.g. Order ID.

`event[value]`

Value associated with an event, e.g. Total Cost.

`event[extra][]`

Associative arrary of extra properties to be assigned to the event in Klaviyo.

```
[
  'ShippingTotal' => '$230.00',
  'TotalItems' => 5,
  ...
]
```

#### Other Form Parameters

`klaviyoProfileMapping`

Set the profile mapping function to set profile data. Default mappings include:

 - `usermodel_mapping` Map logged in user from user session, no profile parameters are required in POST
 - `formdata_mapping` Map from POST data

 Use the `klaviyoConnect_addProfileMapping` hook to configure new mapping functions:

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

 An array of associative arrays can be returned too.

`forward`

Indicates that the controller should forward requests to the specified action once complete.

If `forward` is not set, the POST will follow the `redirect` value if present.

#### Example

```
<form method="POST">
    <input type="hidden" name="action" value="klaviyoConnect/api/updateProfile">

    <!-- Profile Mapping handle -->
    <input type="hidden" name="klaviyoProfileMapping" value="formdata_mapping">

    <!-- Profile Details -->
    <label>Title</label><input type="text" name="title" />
    <label>First Name</label><input type="text" name="first_name" />
    <label>Last Name</label><input type="text" name="last_name" />
    <label>Email</label><input type="email" name="email" />

    <!-- List to add profile to -->
    <input type="hidden" name="list" value="FOO123">
    <input type="hidden" name="confirmOptIn" value="0" />

    <!-- Event to track -->
    <input type="hidden" name="event[name]" value="Event Foo" />
    <input type="hidden" name="event[event_id]" value="a1b2c3" />
    <input type="hidden" name="event[value]" value="Foobar">
    <input type="hidden" name="event[extra][FooBar]" value="Foo Bar">

    <input type="submit" value="Submit"/>
</form>
```

## Notes

Klaviyo only knows about events which have already been tracked. I.e. if the "Completed Order" event hasn't been tracked yet, you will not be able to set up a Flow which depends on that event.