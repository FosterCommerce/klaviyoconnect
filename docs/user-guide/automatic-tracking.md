# Automatic tracking

What Klaviyo Connect sends to Klaviyo on its own once a site has a Private API Key, and what needs a template, a form or a utility.

## Cart and order events

Klaviyo Connect sends these events from the server, without template code, as shoppers use the store and as orders change.

- **Added to Cart**: a shopper adds a new item to their cart on the storefront. Adding more of an item already in the cart does not send it.
- **Updated Cart**: a cart changes, such as when an item is added or removed or a quantity changes. Klaviyo Connect skips the event when the cart matches the last Updated Cart it sent.
- **Started Checkout**: a storefront cart has an email and at least one item. Klaviyo Connect sends this event once per cart.
- **Placed Order**: an order is completed.
- **Ordered Product**: Klaviyo Connect sends one of these for each line item, along with Placed Order.
- **Fulfilled Order** and **Cancelled Order**: an order moves into a status you mapped to one of them. See [map statuses](#map-statuses-to-fulfilled-order-and-cancelled-order).
- **`{Status} Order`**: an order moves into any other status. A move to **Shipped** sends `Shipped Order`. Klaviyo Connect does not send this event for the status a new order starts with, because Placed Order covers it.
- **Refunded Order**: a full or partial refund succeeds. The event's value is the refund amount.

Each event except Ordered Product includes the order's items, totals and discount code. For every property, see [event properties](../reference/event-properties.md#events).

### Which carts and orders are sent

Klaviyo Connect sends an event only when the cart or order has a valid email. A guest's cart is not sent until the shopper enters an email. If the cart has an item, saving the email sends Updated Cart and Started Checkout.

Added to Cart and Started Checkout come only from storefront requests. Editing an order in the control panel does not send them.

Each site sends to the Klaviyo account in its row of the **Sites** table. If a site's **Private API Key** is blank, Klaviyo Connect does not send that site's events. When a site has an **Event Prefix**, every event name starts with it. To set either one, see [configuration](../reference/configuration.md#klaviyo-account).

### Map statuses to Fulfilled Order and Cancelled Order

Klaviyo's built-in flows and reports use the Fulfilled Order and Cancelled Order event names. To send them, go to **Settings -> Plugins -> Klaviyo Connect** and choose your statuses under **Tracking**:

- **Fulfilled Order Statuses**: an order moving into one of these statuses sends Fulfilled Order.
- **Cancelled Order Statuses**: an order moving into one of these statuses sends Cancelled Order.

Both are empty by default. They appear when **Track Commerce Order Status Updates** is on.

### Turn events on or off

Each event has a switch under **Tracking** in **Settings -> Plugins -> Klaviyo Connect**. Every switch is on by default.

| Setting | Events it controls |
|---|---|
| Track Commerce Added to Cart | Added to Cart |
| Track Commerce Cart Updated | Updated Cart |
| Track Commerce Started Checkout | Started Checkout |
| Track Commerce Order Complete | Placed Order and Ordered Product |
| Track Commerce Order Status Updates | Fulfilled Order, Cancelled Order and `{Status} Order` |
| Track Commerce Order Refunds | Refunded Order |

If your templates already post their own Started Checkout or Added to Cart event, switch off that event's setting. Otherwise Klaviyo records the event twice.

To leave addresses, categories, pricing detail or the site and store out of order events, use the switches under **Event Data**. See [event data](../reference/configuration.md#event-data).

## Customer profiles

Every order event updates the Klaviyo profile for the order's email. Klaviyo Connect sends the shopper's first and last name, and the city, region and country from the order's shipping address. Without a shipping address, it uses the billing address.

Klaviyo Connect can also keep profiles up to date for your Craft users. Under **Tracking**, choose the groups in **Sync Users in These Groups**. When a user in one of those groups is saved, Klaviyo Connect creates or updates their profile with their email and name, in every Klaviyo account in the **Sites** table. Sites that share a Private API Key get one update. No groups are chosen by default, so users do not sync until you choose one.

To send more user fields, such as a phone number or a custom property, use **Klaviyo Profile Attributes** and **Custom Profile Properties** under **Tracking**. Klaviyo Connect sends these fields with user syncs, with order events, and with forms a logged-in user posts. See [profile mapping](../reference/configuration.md#profile-mapping).

## Cart restore links

Added to Cart, Updated Cart and Started Checkout each include a `CheckoutURL` property. In a Klaviyo email, link a button to `{{ event.CheckoutURL }}`. The link puts the cart back in the shopper's session and opens the site's **Cart URL**. If the cart belongs to a user account, the shopper logs in first. For each case the link handles, see [cart restore](../reference/actions.md#get-actions-klaviyoconnect-cart-restore).

## Sending through Craft's queue

Klaviyo Connect sends each event and profile update from a Craft queue job. The shopper's request does not wait for Klaviyo. Klaviyo receives the data when Craft's queue runs. Klaviyo Connect queues Placed Order to send 10 seconds after the order is completed.

If Klaviyo limits the request rate, has an outage, or the connection drops, the job tries again, up to five attempts in all. Other errors fail without another attempt. When the last attempt fails, **Connection** under **Klaviyo Account** shows the error Klaviyo returned and its date.

Klaviyo records one event per ID, and a retried job sends the same ID as the first attempt. A retry does not create a second event. See [event attributes](../reference/event-properties.md#event-attributes).

## What is not automatic

These features need a template, a form or a utility.

- **Viewed Product**: a product template calls `craft.klaviyoConnect.viewedProduct()`, and the page needs klaviyo.js. Klaviyo's browse abandonment flows start from this event. See [track Viewed Product](./tracking-script.md#track-viewed-product).
- **The tracking script**: **Add Klaviyo Tracking Script** is off by default. When it is on, Klaviyo Connect adds klaviyo.js to your storefront pages. See [tracking script](./tracking-script.md).
- **List signups**: a form on your site posts the visitor's email and the lists to Klaviyo Connect. A list field lets editors choose those lists on an entry. See [list fields](./list-fields.md) and [list parameters](../reference/actions.md#list-parameters).
- **Past orders**: orders placed before you set the keys are not sent. To send them, use **Utilities -> Klaviyo Connect**. See [past orders](./past-orders.md).
- **Custom events**: a form on your site posts an event with the name and properties you choose. See [track a custom event](../dev-guide/template-examples.md#track-a-custom-event).
