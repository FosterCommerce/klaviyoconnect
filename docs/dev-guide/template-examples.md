# Template examples

Twig forms that send profiles, events and list signups to Klaviyo, and a Klaviyo email template for abandoned carts. For every parameter these forms can post, see [actions](../reference/actions.md).

Each form posts to a Klaviyo Connect action with Craft's CSRF token. The examples use the list ID `AbC123`. Find a list's ID in Klaviyo under the list's **Settings**, or let editors choose a list with a [list field](../user-guide/list-fields.md).

## Sign up for a list

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/track') }}
  {{ redirectInput('newsletter/thanks') }}
  {{ hiddenInput('list', 'AbC123') }}
  {{ hiddenInput('subscribe', '1') }}

  <label for="email">Email</label>
  <input type="email" id="email" name="email" required>

  <label for="phone">Mobile number (optional)</label>
  <input type="tel" id="phone" name="profile[phone_number]" placeholder="+15551234567">

  <label>
    <input type="checkbox" name="consent" required>
    Send me emails and text messages about offers.
  </label>

  <button type="submit">Sign up</button>
</form>
```

With `subscribe`, Klaviyo creates the profile if needed and subscribes the email to the list. A phone number subscribes to SMS on the same list. Klaviyo requires international format, such as `+15551234567`. The plugin does not send the `consent` checkbox to Klaviyo. Your form collects the opt-in.

To send several lists, post `lists[]` once per list ID instead of `list`. To use a list chosen by an editor, see [list fields](../user-guide/list-fields.md#use-the-field-in-a-signup-form).

## Let the visitor choose lists

`craft.klaviyoConnect.lists()` returns every list in the current site's Klaviyo account. Pass a site ID to read another site's account. Each list has `id` and `name`.

```twig
{% set klaviyoLists = craft.klaviyoConnect.lists() %}

{% if klaviyoLists %}
  <form method="post">
    {{ csrfInput() }}
    {{ actionInput('klaviyoconnect/api/track') }}
    {{ redirectInput('newsletter/thanks') }}
    {{ hiddenInput('subscribe', '1') }}

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    {% for klaviyoList in klaviyoLists %}
      <label>
        <input type="checkbox" name="lists[]" value="{{ klaviyoList.id }}">
        {{ klaviyoList.name }}
      </label>
    {% endfor %}

    <button type="submit">Sign up</button>
  </form>
{% else %}
  <p>Newsletter signup is unavailable.</p>
{% endif %}
```

`lists()` returns `null` when the site has no Private API Key, when the account has no lists, or when Klaviyo returns an error. After an error, `error()` returns Klaviyo's message. To refresh the lists, see [list fields](../user-guide/list-fields.md#refresh-the-lists).

## Update a profile

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/identify') }}
  {{ redirectInput('account') }}

  <label for="email">Email</label>
  <input type="email" id="email" name="email" value="{{ currentUser.email ?? '' }}" required>

  <label for="first-name">First name</label>
  <input type="text" id="first-name" name="profile[first_name]">

  <label for="city">City</label>
  <input type="text" id="city" name="profile[location][city]">

  <label for="favorite-color">Favorite color</label>
  <select id="favorite-color" name="profile[properties][Favorite Color]">
    <option>Red</option>
    <option>Blue</option>
  </select>

  <button type="submit">Save</button>
</form>
```

Klaviyo's own profile keys, such as `first_name` and `location`, go at the top level of `profile`. Custom properties go under `profile[properties]`. For the keys Klaviyo accepts, see [profile attributes](../reference/profile-attributes.md).

## Track a custom event

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/track') }}
  {{ redirectInput('contact/thanks') }}
  {{ hiddenInput('event[name]', 'Submitted Contact Form') }}

  <label for="email">Email</label>
  <input type="email" id="email" name="email" required>

  <label for="topic">Topic</label>
  <select id="topic" name="event[Topic]">
    <option>Shipping</option>
    <option>Returns</option>
  </select>

  <button type="submit">Send</button>
</form>
```

Klaviyo shows the event as a metric named Submitted Contact Form, with a `Topic` property. To record an event once however often the form is posted, add `event[unique_id]`. To send a value, add `event[value]` and `event[value_currency]`.

### Send an array of items

Nested field names send an array of objects, which a Klaviyo email can loop over. This form sends the cart's line items with a Requested Quote event:

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/track') }}
  {{ redirectInput('quote/thanks') }}
  {{ hiddenInput('event[name]', 'Requested Quote') }}

  {% for item in cart.lineItems %}
    {{ hiddenInput("event[Items][#{loop.index0}][SKU]", item.sku) }}
    {{ hiddenInput("event[Items][#{loop.index0}][ProductName]", item.description) }}
    {{ hiddenInput("event[Items][#{loop.index0}][Quantity]", item.qty) }}
  {% endfor %}

  <label for="email">Email</label>
  <input type="email" id="email" name="email" required>

  <button type="submit">Request a quote</button>
</form>
```

Klaviyo receives `Items` as an array, with `SKU`, `ProductName` and `Quantity` on each entry. Form values arrive as text, so `Quantity` is `"2"` rather than `2`. Don't post the array as JSON in one field: Klaviyo Connect sends that as a single string. For cart and order events, Klaviyo Connect already sends `Items` itself; see [event properties](../reference/event-properties.md).

## Track an event for an order

On an order confirmation page, this form sends the order's details with the event:

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/track') }}
  {{ redirectInput('shop/order?number=' ~ order.number) }}
  {{ hiddenInput('event[name]', 'Requested Shipping Updates') }}
  {{ hiddenInput('event[trackOrder]', '1') }}
  {{ hiddenInput('event[orderNumber]', order.number) }}

  <button type="submit">Email me when my order ships</button>
</form>
```

The plugin sends the event to the order's email, with the same properties as Placed Order. Without `event[orderNumber]`, the plugin sends the current cart. For the properties, see [order event properties](../reference/event-properties.md#order-properties).

## Save the shopper's email on the cart

The `forward` parameter runs a second action after Klaviyo Connect queues the profile. This form identifies the shopper to Klaviyo, then runs Commerce's `update-cart` action with the same parameters:

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('klaviyoconnect/api/identify') }}
  {{ hiddenInput('forward', '/commerce/cart/update-cart') }}
  {{ redirectInput('shop/checkout/address') }}

  <label for="email">Email</label>
  <input type="email" id="email" name="email" value="{{ cart.email }}" required>

  <button type="submit">Continue</button>
</form>
```

## Track a product view

```twig
{% do craft.klaviyoConnect.viewedProduct(product) %}
```

Add the call to a product template. For what it sends and what it needs to work, see [track Viewed Product](../user-guide/tracking-script.md#track-viewed-product).

## Klaviyo email for an abandoned cart

In a Klaviyo flow triggered by Started Checkout, Added to Cart or Updated Cart, this email lists the cart's items and links back to the cart. Add it in a text block's source view, or in an HTML template:

```twig
<p>Hi {{ person.first_name|default:'there' }},</p>

<p>Your cart is waiting:</p>

<table>
  {% for item in event.Items %}
    <tr>
      <td>
        {% if item.ImageURL %}
          <img src="{{ item.ImageURL }}" alt="{{ item.ProductName }}" width="80">
        {% endif %}
      </td>
      <td>
        <a href="{{ item.ProductURL }}">{{ item.ProductName }}</a><br>
        Quantity: {{ item.Quantity }}
      </td>
    </tr>
  {% endfor %}
</table>

<p><a href="{{ event.CheckoutURL }}">Return to your cart</a></p>
```

`event.CheckoutURL` restores the cart on the site where the shopper left it. For how the link behaves, see [actions](../reference/actions.md). For every item property, see [line item properties](../reference/event-properties.md#line-item-properties). For Klaviyo's template syntax, see its [message personalization reference](https://help.klaviyo.com/hc/en-us/articles/4408802648731).
