# Profile attributes

The profile keys Klaviyo Connect sends to Klaviyo. For the properties sent with each event, see [event properties](./event-properties.md).

Klaviyo accepts these keys at the top level of a profile. In a form, post them as `profile[key]`. Put every other property under `profile[properties]`. Klaviyo Connect moves any other top-level key there, and any value of the wrong type, such as a text `location`, since Klaviyo rejects the whole profile otherwise.

| Key | Notes |
|---|---|
| `email` | Required for events and profile updates. A list subscription can use `phone_number` alone. |
| `phone_number` | International format, such as `+15551234567`. |
| `external_id` | Your own ID for the person. |
| `first_name`, `last_name` | |
| `organization`, `title` | The person's company and job title. |
| `locale` | Such as `en-US`. |
| `image` | A URL to a photo of the person. |
| `location` | An object with `address1`, `address2`, `city`, `region`, `zip`, `country`, `timezone`, `latitude`, `longitude` and `ip`. |
| `properties` | Custom properties, as name and value pairs. |

For the profile Klaviyo Connect builds for order events, see [customer profiles](../user-guide/automatic-tracking.md#customer-profiles). To change its values, see [PHP events](../dev-guide/php-events.md).
