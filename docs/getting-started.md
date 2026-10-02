# Getting started

Sends Craft Commerce carts, orders and customers to Klaviyo, so you can run email and SMS flows from what shoppers do.

This walks you from `composer require` to an abandoned cart flow in Klaviyo, triggered by a real cart. By the end, each site's Klaviyo account is set and you have checked that an event is recorded in Klaviyo.

## Requirements

- Craft CMS 5.6.0 or later
- Craft Commerce 5.1.0 or later, for cart and order events
- PHP 8.2 or later
- A Klaviyo account

## 1. Install

```sh
composer require fostercommerce/klaviyoconnect
./craft plugin/install klaviyoconnect
```

## 2. Add your Klaviyo API keys

In Klaviyo's account settings, open **API keys** and create a Private API Key. Give it full access, or read and write access to Events, Profiles, Lists and Subscriptions. The Public API Key is on the same page.

In Craft, go to **Settings -> Plugins -> Klaviyo Connect**. In the **Sites** table, enter each site's **Public API Key** and **Private API Key**. Use environment variables, such as `$KLAVIYO_PRIVATE_API_KEY`, to keep keys out of project config. To share one Klaviyo account across sites, see [configuration](./reference/configuration.md).

Enter the **Cart URL** for each site, such as `cart`. Cart restore links in Klaviyo emails open this page.

Click **Check Connection and Load Lists**. Each site shows a green dot and its number of Klaviyo lists. A red dot shows why a site can't connect, such as a missing key, an environment variable that isn't set, or an error from Klaviyo.

Click **Save**.

## 3. Choose what to send

Under **Tracking**, every Commerce event is on by default. For the list, see [turn events on or off](./user-guide/automatic-tracking.md#turn-events-on-or-off). Under **Event Data**, choose whether order events include addresses, categories, pricing detail and the site and store.

## 4. Check an event in Klaviyo

On your storefront, add a product to a cart and enter an email at checkout. Klaviyo Connect sends Started Checkout to Klaviyo when Craft's queue runs.

In Klaviyo, open the profile for that email. Its activity feed shows Started Checkout, with the cart's items and a `CheckoutURL` property.

## 5. Build an abandoned cart flow

In Klaviyo, create a flow triggered by the Started Checkout metric. In the email, link the button to `{{ event.CheckoutURL }}`. The link restores the shopper's cart on the site where they left it, and shows a login page when the cart belongs to an account.

## Where to go next

- [Automatic tracking](./user-guide/automatic-tracking.md), what Klaviyo Connect sends without template code
- [Configuration](./reference/configuration.md), every setting
- [Actions](./reference/actions.md), forms and links that send events, profiles and list signups
- [Template examples](./dev-guide/template-examples.md), Twig and Klaviyo email templates
- [PHP events](./dev-guide/php-events.md), for adding your own properties to profiles and events
