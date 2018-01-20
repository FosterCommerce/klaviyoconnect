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

### Klaviyo List

Single-select dropdown to select a Klaviyo list.

The selected list's ID is saved.

### Klaviyo Lists

Group checkbox for multi-selection of Klaiyo lists.

An array of selected list IDs are stored.

## Actions

### `POST lists/addToLists`

Adds a profile to a list or multiple lists.

Either `list` or `lists[]` is required.

#### Parameters

`list` _Required_

A Klaviyo list ID.

`lists[]` _Required_

Array of Klaviyo list IDs

`confirmOptIn` _Optional_ [Default: `"1"`]

Whether or not Klaviyo should send an email to the person confirming opt-in.

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

`forward`

Indicates that the controller should forward requests to the specified action once complete.

If `forward` is not set, the POST will follow the `redirect` value if present.

#### Example: Add a Profile to a Single List

```
<form method="POST">
    <input type="hidden" name="action" value="klaviyoConnect/lists/addToLists">

    <!-- Klaviyo list ID -->
    <input type="hidden" name="list" value="FOO123">

    <label>Title</label><input type="text" name="title" />
    <label>First Name</label><input type="text" name="first_name" />
    <label>Last Name</label><input type="text" name="last_name" />
    <label>Email</label><input type="email" name="email" />

    <input type="submit" value="Submit"/>
</form>
```

#### Example: Add a Profile to Multiple Lists

```
<form method="POST">
    <input type="hidden" name="action" value="klaviyoConnect/lists/addToLists">
    <input type="hidden" name="forward" value="somePlugin/someController/someAction">

    <!-- Explicitly set confirmOptIn to false -->
    <input type="hidden" name="confirmOptIn" value="0">

    <!-- Klaviyo list IDs -->
    <input type="hidden" name="lists[]" value="FOO123">
    <input type="hidden" name="lists[]" value="BAR456">

    <label for="">Email</label>
    <input type="email" name="email" />

    <!-- Send extra data to Klaviyo -->
    <input type="hidden" name="extra[Foo]" value="Bar">

    <input type="submit" value="Submit"/>
</form>
```

## Notes

Klaviyo only knows about events which have already been tracked. I.e. if the "Completed Order" event hasn't been tracked yet, you will not be able to set up a Flow which depends on that event.