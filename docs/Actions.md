# Actions

You can use Klaviyo Connect plugin actions (in forms and links, see Templating Examples) to perform various Klaviyo actions from your Craft templates.

## `POST` Actions

### `klaviyoconnect/api/identify`

This action is used to track properties about an individual without tracking an associated event.

### `klaviyoconnect/api/update-profile`

This action is used to add a user to a list or multiple lists and/or track events from a user.

Calls the `identify` API too.

#### Form Parameters

These apply to both actions.

`email` _Required_

An email address to identify a person's profile on Klaviyo

`extra[]` _Optional_

Associative arrary of extra properties to be assigned to a profile in Klaviyo.

**Klaviyo-specific Profile properties** - _Optional_

See [Special Identify Properties](https://www.klaviyo.com/docs/http-api) in Klaviyo's API docs.

The following properties can be passed to controllers to populate a persons profile:

`id`

`first_name`

`last_name`

`phone_number`

`title`

`organization`

`city`

`region`

`country`

`zip`

`image`

**List Form Parameters**

List form parameters can be passed to Klaviyo as either a single list or multiple lists.

One of the list fields needs to be present to add a user to a list and is required:

`list` - _Required_

Klaviyo list ID.

```html
<input type="hidden" name="list" value="{{ entry.listField.id }}" />
```

- OR -

`lists[]` - _Required_

Array of Klaviyo List IDs (Note: array syntax in field name)

```twig
<select name="lists[]" multiple>
  {% for list in entry.listsField %}
    <option value="{{ list.id }}">{{ list.name }}</option>
  {% endfor %}
</select>
```

`confirmOptIn` - _Optional_ [Default: `"1"`]

In Klaviyo, confirming an opt-in is similar to a double opt-in. This parameter tells Klaviyo if it should send an confirmation email to the person. Set it to 0 to prevent opt-in confirmation emails from being sent. Be smart about GDPR compliance.

**Event Form Parameters**

If event form parameters are present, Klaviyo's tracking API will be called to track the event and associate it to the user.

`event[name]` - _Required_

The name of the event to track.

```html
<input type="hidden" name="event[name]" value="Completed Order" />
```

`event[event_id]` - _Required_

The ID to associate with an event, e.g. Order ID.

```html
<input type="hidden" name="event[event_id]" value="{{ order.number }}" />
```

`event[value]` - _Required_

Value associated with an event, e.g. Total Cost.

```html
<input type="hidden" name="event[value]" value="{{ order.totalPrice }}" />
```

`event[extra][]` - _Optional_

Associative arrary of extra properties to be assigned to the event in Klaviyo.

```html
<input type="hidden" name="event[extra][Discount]" value="{{ order.totalDiscount }}" />
```

**Extra Form Parameters**

The following extra parameters can be used in POST actions.

`klaviyoProfileMapping` - _Optional_

Specify a profile mapping manually within your template. If this field is not present the default mapping set in the plugins configuration section will be used.

```html
<input type="hidden" name="klaviyoProfileMapping" value="formdata_mapping" />
```

`forward` - _Optional_

Tells the plugin to forward the POST request to a specified action once complete. If the `forward` form parameter is not included, the POST will follow the Craft Commerce `redirect` field, if present.

```html
<input type="hidden" name="forward" value="/commerce/cart/update-cart" />
```

## `GET` Actions

### `actions/klaviyoconnect/cart/restore?number=<cart number>`

Restores a previously active cart. Best used in a Klaviyo generated email to a specific customer.

```
<a href="https://mysite.com/actions/klaviyoconnect/cart/restore?number={{ cart.number }}">Get Your Cart</a>
```

