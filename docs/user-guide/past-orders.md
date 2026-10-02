# Past orders

How to send completed orders from a date range to Klaviyo, such as orders placed before you installed Klaviyo Connect.

Sending past orders needs Craft Commerce.

## Send orders from the control panel

1. Go to **Utilities -> Klaviyo Connect**.
2. Enter a **From** date and a **To** date. **To** is today by default.
3. Click **Send Orders to Klaviyo**.

A notice shows how many orders were queued. Klaviyo Connect queues one job per order, and the orders are sent the next time Craft's queue runs.

Both dates are inclusive: the range covers every completed order placed from the start of the **From** day to the end of the **To** day. Days start at midnight in Craft's system time zone.

To use the utility, a user needs the **Klaviyo Connect** utility permission, under **Utilities** in the user's permissions.

## Send orders from the command line

```sh
./craft klaviyoconnect/orders/sync --from=2025-01-01 --to=2025-12-31
```

Both options are required, and both dates are inclusive. The command queues the orders the same way as the utility.

## What Klaviyo receives

Each order is sent as a Placed Order event, plus one Ordered Product event per line item. Each event is dated when the order was placed, so the order appears at that date in Klaviyo's reports and the customer's activity feed.

The events use the same properties as live orders, including the **Event Data** settings. For the full list, see [event properties](../reference/event-properties.md).

Klaviyo Connect sends past orders whether or not **Track Commerce Order Complete** is on.

## Send a range again

Each Placed Order and Ordered Product event has an ID built from the order number. Klaviyo records an event once per ID, so sending a range again does not create duplicate events. Orders Klaviyo Connect already sent when they were placed are also recorded once.

Klaviyo Connect versions before 7.3.0 used different IDs. An order those versions sent is recorded a second time if you send it again.
