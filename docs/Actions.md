# Actions

## [`POST`] klaviyoConnect/api/identify

The Identify API is used to track properties about an individual without tracking an associated event.

### Form Parameters

- `email` _Required_

An email address to identify a person's profile on Klaviyo

- `extra[]` _Optional_

Associative arrary of extra properties to be assigned to a profile in Klaviyo.

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

## [`POST`] klaviyoConnect/api/updateProfile

Adds a profile to a list or multiple lists and/or tracks an event.

Calls the `identify` API too.

### Profile Parameters

- `email` _Required_

An email address to identify a person's profile on Klaviyo

- `extra[]` _Optional_

Associative arrary of extra properties to be assigned to a profile in Klaviyo.

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

### List Form Parameters

If either `list` or `lists[]` is present, the profile will be added to the specified lists.

`list` _Required_ - A Klaviyo list ID.

`lists[]` _Required_- Array of Klaviyo list IDs.

`confirmOptIn` _Optional_ [Default: `"1"`] - Whether or not Klaviyo should send an email to the person confirming opt-in.

### Event Form Parameters

If event parameters are present, Klaviyos tracking API will be called.

`event[name]` - The name of the event to track.

`event[event_id]` - The ID to associate with an event, e.g. Order ID.

`event[value]` - Value associated with an event, e.g. Total Cost.

`event[extra][]` - Associative arrary of extra properties to be assigned to the event in Klaviyo.

## Extra Form Parameters

`klaviyoProfileMapping`

Set the profile mapping function to set profile data. Default mappings include:

See [Mapping](Mapping.md) for built-in custom mappings.

See [Hooks](Hooks.md) for custom profile mapping.

`forward`

Indicates that the controller should forward requests to the specified action once complete.

If `forward` is not set, the POST will follow the `redirect` value if present.
