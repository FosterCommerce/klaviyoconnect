# Actions

The controller actions Klaviyo Connect adds to your site, and the parameters each one accepts. For complete forms, see [template examples](../dev-guide/template-examples.md).

## Requests

The identify and track actions require a POST request with Craft's CSRF token. In a form, add `{{ csrfInput() }}`. Logged-out visitors can post to either.

Any visitor can post any email address, and the plugin updates that profile in Klaviyo with the values posted.

## POST /actions/klaviyoconnect/api/identify

Creates or updates a Klaviyo profile without tracking an event.

Accepts the [profile parameters](#profile-parameters), plus `forward` and `redirect` from [response parameters](#response-parameters).

## POST /actions/klaviyoconnect/api/track

Creates or updates a Klaviyo profile, then tracks an event, adds the profile to lists, or both.

Accepts every parameter on this page. Without `event[name]`, the action does not track an event. Without `list` or `lists[]`, the action does not change list membership.

## Profile parameters

| Parameter | Description |
|---|---|
| `email` | The profile's email address. Used when `profile[email]` is absent or invalid. |
| `profile[email]` | The profile's email address. |
| `profile[first_name]`, `profile[last_name]` | The profile's name. |
| `profile[phone_number]` | Phone number in international format, such as `+15551234567`. |
| `profile[location][city]` | The profile's city. The other location keys are in [profile attributes](./profile-attributes.md). |
| `profile[properties][Name]` | A custom profile property named `Name`. |

Events and profile updates need an email Craft accepts for a user account, with a top-level domain of at least two characters that aren't all digits, such as `jane@example.com`. Klaviyo Connect skips an invalid email and logs a warning. A `subscribe` request can use `profile[phone_number]` alone. When a user is logged in, the plugin starts from their Craft user's name, email and mapped fields, and the posted values replace those. Without a logged-in user or a posted email, the plugin does not send the profile or the event to Klaviyo.

For the full list of profile keys Klaviyo accepts, see [profile attributes](./profile-attributes.md). Put any other property under `profile[properties]`.

## List parameters

| Parameter | Description |
|---|---|
| `list` | One Klaviyo list ID. |
| `lists[]` | Several Klaviyo list IDs. Ignored when `list` is present. |
| `subscribe` | `1` subscribes the profile to each list. Omit it to add the profile to each list without subscribing it. |

With `subscribe`, Klaviyo creates the profile if needed and marks the channels the form posted as subscribed: email when it posts a valid `email` or `profile[email]`, and SMS when it posts `profile[phone_number]`. A logged-in user's account email identifies the profile but isn't subscribed unless the form posts it. A list with double opt-in sends its confirmation first; see Klaviyo's [subscribe profiles](https://developers.klaviyo.com/en/reference/bulk_subscribe_profiles) reference. The plugin does not record consent wording, so collect the shopper's opt-in on your form before you post `subscribe`.

Without `subscribe`, the plugin looks up the profile by email and adds it to each list. If Klaviyo has no profile for the email when the job runs, the plugin does not add one.

## Event parameters

| Parameter | Description |
|---|---|
| `event[name]` | Required. The event name, which Klaviyo shows as the metric. The [event prefix](./configuration.md) is added in front. |
| `event[unique_id]` | An ID for the event. Klaviyo records one event per profile, metric and ID, so a repeated post with the same ID records once. |
| `event[value]` | A number, such as an order total. Left out when it isn't a number. |
| `event[value_currency]` | An ISO 4217 currency code, such as `USD`. Left out unless it's three capital letters. |
| `event[timestamp]` | When the event happened, in ISO 8601 format such as `2026-01-15T09:30:00Z`. Defaults to the time Klaviyo receives the event. Klaviyo Connect also uses that default for a value that isn't a date, or a time before 1990 or more than a year ahead. |
| `event[PropertyName]` | A custom event property. Every other key under `event` becomes one. |
| `event[Items][0][SKU]` | A nested key sends an array or object, here an `Items` array whose first entry has a `SKU`. A JSON string in a single field is sent as text. See [send an array of items](../dev-guide/template-examples.md#send-an-array-of-items). |

### Order events

| Parameter | Description |
|---|---|
| `event[trackOrder]` | Any value sends an order's details with the event, in the same shape as the [built-in order events](./event-properties.md#order-properties). Requires Craft Commerce. |
| `event[orderNumber]` | The order to send, by number. Works for guests, such as on an order confirmation page. |
| `event[orderId]` | The order to send, by ID. Accepts only the current cart or an order that belongs to the logged-in user. |

Without `event[orderNumber]` or `event[orderId]`, the plugin sends the current cart. With `event[trackOrder]`, the plugin sends the order's properties and does not send custom `event[PropertyName]` values. When the order has an email, the plugin sends the event to the order's customer instead of the posted profile. If the plugin cannot find the order, it does not track the event and writes the error to Craft's log.

Without `event[unique_id]`, the plugin gives a custom order event a new unique ID on every post. To record the event once, post your own `event[unique_id]`, such as one built from `order.number`.

## Response parameters

| Parameter | Description |
|---|---|
| `redirect` | Where to send the visitor after the request, hashed with `redirectInput()`. |
| `forward` | A controller action to run after the plugin queues its sends, such as `/commerce/cart/update-cart`. The forwarded action receives the same POST parameters and returns the response. Klaviyo Connect's own actions can't be forwarded to. |

For an Ajax request that doesn't forward, the track and identify actions return the JSON string `"success"`.

## GET /actions/klaviyoconnect/cart/restore

Restores an abandoned cart into the visitor's session, then redirects to the site's **Cart URL**. Requires Craft Commerce.

| Parameter | Description |
|---|---|
| `number` | Required. The cart's order number. |

Every built-in cart event includes this link as `CheckoutURL`. In a Klaviyo email, link to `{{ event.CheckoutURL }}` instead of building the URL.

The action handles a request in this order:

1. Without `number`, it returns a 400 error. For an unknown number, it returns a 404 error.
2. For a completed order, it returns a 400 error.
3. If the cart was created on another site, it redirects to the restore link on the order's site.
4. If the request is still not on the order's site after that redirect, such as when the order's site is disabled, it returns a 400 error for a cart from another Commerce store.
5. If the order's site has no **Cart URL**, it returns a 400 error. To set the URL, see [configuration](./configuration.md).
6. If a logged-in user requests another customer's cart, it shows a page with **Log Out to Continue**. Logging out reloads the restore link as a logged-out visitor.
7. If a logged-out visitor requests a cart that belongs to a user account that can log in, it shows a login page. The login button opens Craft's `loginPath`, and after login Craft returns the shopper to the restore link. If your login form posts a `redirect` param, Craft sends the shopper there instead, so leave `redirect` out of the form or set it to `craft.app.user.getReturnUrl()`.
8. It restores the cart, sets the flash notice "Your cart has been restored.", and redirects to the order site's **Cart URL**.
