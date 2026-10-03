# Profile attributes

The profile keys Klaviyo Connect sends to Klaviyo. For the properties sent with each event, see [event properties](./event-properties.md).

Klaviyo accepts these keys at the top level of a profile. In a form, post them as `profile[key]`. Put every other property under `profile[properties]`. Klaviyo Connect moves any other top-level key there, and any value of the wrong type, such as a text `location`, since Klaviyo rejects the whole profile otherwise.

| Key | Notes |
|---|---|
| `email` | Required for events and profile updates. A list subscription can use `phone_number` alone. |
| `phone_number` | As typed. Klaviyo Connect formats it, as described in [phone numbers](#phone-numbers). |
| `external_id` | Your own ID for the person. |
| `first_name`, `last_name` | |
| `organization`, `title` | The person's company and job title. |
| `locale` | Such as `en-US`. |
| `image` | A URL to a photo of the person. |
| `location` | An object with `address1`, `address2`, `city`, `region`, `zip`, `country`, `timezone`, `latitude`, `longitude` and `ip`. |
| `properties` | Custom properties, as name and value pairs. |

For the profile Klaviyo Connect builds for order events, see [customer profiles](../user-guide/automatic-tracking.md#customer-profiles). To change its values, see [PHP events](../dev-guide/php-events.md).

## Phone numbers

Klaviyo Connect sends `phone_number` to Klaviyo in international format. It removes spaces and punctuation, adds the country code, and drops a leading `0` that callers dial only inside the country:

| Typed | Country | Sent |
|---|---|---|
| `(614) 555-0142` | US | `+16145550142` |
| `0151 23456789` | DE | `+4915123456789` |
| `+44 7911 123456` | any | `+447911123456` |

### How Klaviyo Connect finds the country

Klaviyo Connect reads a number typed with `+` as international. For a number typed without `+`, it uses the first country it finds:

1. The country of the order's shipping address, then its billing address.
2. A two-letter country code posted as `profile[location][country]`, such as `GB`.
3. The region of the site's language, such as `US` for `en-US`.

If the digits start with that country's calling code, such as `4915123456789` in Germany, Klaviyo Connect tries them as an international number first.

### When Klaviyo Connect ignores a number

If the number is not valid in that country, or Klaviyo Connect finds no country, it ignores the number and logs a warning that says why. It still sends the event or profile update. A list signup with no valid email is not sent, because no contact is left. The visitor still gets the form's redirect.

Klaviyo Connect finds no country when a site's language has no region, such as `en` or `de`, and the send has no order address or posted country. On such a site, Klaviyo Connect sends only numbers typed with `+`.

### Limits

Klaviyo Connect reads each number in one country. A number from another country is usually ignored, and is sometimes sent wrong:

- On an `en-US` site, a UK mobile typed as `07911 123456` is not a valid US number, and Klaviyo Connect ignores it.
- On a `de-DE` site, the same UK mobile is also a valid German number, and Klaviyo Connect sends `+497911123456`.

A site with visitors from many countries, such as an English site that sells abroad, meets these limits most often.

### Collect the number with its country

Validate phone numbers in your own forms before posting them to Klaviyo Connect. Either:

- Post the number with `+` and its country code, from a phone input with a country code picker.
- Post the visitor's country beside the number, as in [collect a phone number with its country](../dev-guide/template-examples.md#collect-a-phone-number-with-its-country).
