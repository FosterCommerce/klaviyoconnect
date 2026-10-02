# Recipe: send SMS from Klaviyo flows

This recipe sends shoppers' phone numbers to Klaviyo, collects SMS consent on your storefront, and adds SMS steps to flows triggered by Klaviyo Connect's events. It assumes Klaviyo Connect is installed and connected, as in [getting started](../getting-started.md), and that SMS is set up in your Klaviyo account.

Klaviyo texts a profile only when it has a phone number and SMS consent. Klaviyo Connect sends the number from Craft. The consent comes from a form on your storefront.

## 1. Store the phone number in Craft

Craft addresses have no phone field. To collect a phone number at checkout, add a custom field to the address field layout in Craft's settings, such as a Plain Text field with the handle `phone`, and add an input for it to your checkout address form.

To collect it on user accounts instead, add a field to the user field layout.

## 2. Map the number to Klaviyo's phone number

Go to **Settings -> Plugins -> Klaviyo Connect**. In **Klaviyo Profile Attributes**, find the **Phone Number** row.

- **For a user field**, choose the field in the **Source** column.
- **For an address field**, choose **Twig value**. The template gets `user` and, on order events including guest checkouts, `order`. Read the address field from `order`.

Klaviyo needs the number in international format, such as `+15551234567`. Klaviyo Connect sends the template's output as written, so format the number in the template. For a store whose customers enter 10-digit US numbers:

```twig
{%- set digits = (order.billingAddress.phone ?? '')|replace('/[^0-9]/', '') -%}
{{- digits|length == 10 ? '+1' ~ digits : (digits ? '+' ~ digits) -}}
```

Click **Save**. If the template has a Twig error, the settings do not save and the field shows the error.

When the template outputs a blank value, Klaviyo Connect leaves the profile's phone number unchanged. Started Checkout can be sent before the shopper enters an address, so Klaviyo gets the number with the first order event after that.

Mapping the number does not give SMS consent. Klaviyo stores the number on the profile, and does not text it until the shopper consents in step 3.

## 3. Collect SMS consent

Add a form that posts to `klaviyoconnect/api/track` with the number, a Klaviyo list ID and `subscribe` set to `1`. Klaviyo Connect subscribes the number to SMS marketing on that list.

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/track') }}
  {{ redirectInput('sms/thanks') }}
  {{ hiddenInput('list', 'AbC123') }}
  {{ hiddenInput('subscribe', '1') }}

  <label for="sms-phone">Mobile number</label>
  <input id="sms-phone" type="tel" name="profile[phone_number]" placeholder="+15551234567" pattern="\+[1-9][0-9]{7,14}" required>

  <p>{# Your SMS consent wording #}</p>

  <button type="submit">Text me offers</button>
</form>
```

Replace `AbC123` with the ID of your Klaviyo list. The form posts the number as the shopper types it, so the `pattern` attribute asks for international format.

A phone number alone is enough. To subscribe an email to the same list, add an input named `profile[email]`.

The list's opt-in setting in Klaviyo controls whether Klaviyo asks the shopper to confirm before the subscription takes effect.

Klaviyo Connect does not add consent wording to the form. Add your SMS consent wording beside the input.

For every parameter the action accepts, see [actions](../reference/actions.md).

## 4. Add SMS steps to your flows

In Klaviyo, open a flow triggered by one of Klaviyo Connect's events, such as Started Checkout for an abandoned cart flow, and add an SMS step. Klaviyo sends the text only to profiles with a phone number and SMS consent, and skips the rest.

An SMS message can use the same event properties as an email, such as `{{ event.CheckoutURL }}` on Started Checkout. For each event's properties, see [event properties](../reference/event-properties.md).
