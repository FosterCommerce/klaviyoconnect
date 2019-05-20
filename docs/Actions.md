# Actions

You can use Klaviyo Connect plugin actions (in forms and links, see Templating Examples) to perform various Klaviyo actions from your Craft templates.

## Identify `POST /klaviyoconnect/api/identify`

This action is used to track properties about an individual without tracking an associated event.

See the Profile Form Parameters in the Update Profile action.

## Update Profile `POST /klaviyoconnect/api/track`

This action is used to add a user to a list or multiple lists and/or track events from a user.

Calls the `identify` API too.

### Profile Form Parameters

These apply to both actions.

`email` or `profile[email]` _Required_

An email address to identify a person's profile on Klaviyo

If `profile[email]` is not present, `email` will be used.

```html
<input type="hidden" name="email" value="hi@mysite.com" />
<!-- or -->
<input type="hidden" name="profile[email]" value="hi@mysite.com" />
```

#### Custom Event Properties

If you'd like to pass through custom profile properties, add them into the `profile` array.

`profile[PropertyName]`

```twig
<input type="hidden" name="profile[LastLogin]" value="{{ currentUser.lastLoginDate|date('Y-m-d\TH:i:sP') }}" />
```

#### Klaviyo-specific Profile properties

See [Klaviyo Notes](./KlaviyoNotes.md#klaviyo-special-properties) and [Special Identify Properties](https://www.klaviyo.com/docs/http-api) in Klaviyo's API docs.

```twig
<input type="hidden" name="profile[first_name]" value="{{ currentUser.firstName }}" />
<input type="hidden" name="profile[last_name]" value="{{ currentUser.lastName }}" />
```

### List Parameters

List form parameters can be passed to Klaviyo as either a single list or multiple lists.

Either `list` or `lists[]` needs to be present to add a user to a list:

`list` - _Required_

Klaviyo list ID.

```html
<input type="hidden" name="list" value="{{ entry.listField.id }}" />
```

`lists[]` - _Required_

Array of Klaviyo List IDs

```twig
<select name="lists[]" multiple>
  {% for list in entry.listsField %}
    <option value="{{ list.id }}">{{ list.name }}</option>
  {% endfor %}
</select>
```

### Tracking Event Parameters

If event form parameters are present, Klaviyo's tracking API will be called to track the event and associate it to the user.

`event[name]` - _Required_

The name of the event to track.

```html
<input type="hidden" name="event[name]" value="Completed Order" />
```

`event[event_id]` - _Required_ See [Klaviyo Notes](./KlaviyoNotes.md)

The ID to associate with an event, e.g. Order ID.

```html
<input type="hidden" name="event[event_id]" value="{{ order.number }}" />
```

`event[value]` - _Required_ See [Klaviyo Notes](./KlaviyoNotes.md)

Value associated with an event, e.g. Total Cost.

```html
<input type="hidden" name="event[value]" value="{{ order.totalPrice }}" />
```

#### Custom Event Properties

If you'd like to pass through custom event properties, add them into the `event` array.

`event[PropertyName]` - _Optional_

Associative arrary of extra properties to be assigned to the event in Klaviyo.

```html
<input type="hidden" name="event[Foo]" value="Bar" />
```

#### Extra Form Parameters

The following extra parameters can be used in POST actions.

`event[trackOrder]`

When present, will trigger the order tracking logic as apposed to regular event tracking. If `event[orderId]` is set, that specific order will be used, otherwise the customer's current cart will be used.

This is useful in situations where the built-in order tracking is not sufficient, for example, tracking partial payments.

```html
<input type="hidden" name="event[name]" value="Partial Payment" />
<input type="hidden" name="event[trackOrder]" value="1" />
<input type="hidden" name="event[orderId]" value="543" />
```

`event[orderId]`

The ID of the order to track.

`forward` - _Optional_

Tells the plugin to forward the POST request to a specified action once complete. If the `forward` form parameter is not included, the POST will follow the Craft Commerce `redirect` field, if present.

```html
<input type="hidden" name="forward" value="/commerce/cart/update-cart" />
```

## Restore Cart `GET /klaviyoconnect/cart/restore`

Restores a previously active cart. Best used in a Klaviyo generated email to a specific customer.

### Parameters

`number` - _Required_

The cart number of the cart you wish to restore.

```html
<a href="https://mysite.com/actions/klaviyoconnect/cart/restore?number=10a6a60e178f6d19ad58b2184001217b">Restore your cart</a>
```

In Klaviyo, if you've set up a flow based on the Started Checkout event, for example, you could create an email template to restore users' carts:

```twig
<a href="https://mysite.com/actions/klaviyoconnect/cart/restore?number={{ event.OrderNumber }}">Go to your cart</a>
```

See Klaviyo's [Template Tags & Syntax](https://www.klaviyo.com/docs/email-tags) documentation.
