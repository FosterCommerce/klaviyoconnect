# Upgrading

What to change on a site when updating Klaviyo Connect. For every change in a release, see the [changelog](https://github.com/FosterCommerce/klaviyoconnect/blob/main/CHANGELOG.md).

## Upgrading to 7.3.1

### Update

If the site has giggsey/libphonenumber-for-php older than 8.13.35, such as through Formie, update both packages:

```sh
composer update fostercommerce/klaviyoconnect giggsey/libphonenumber-for-php
```

Otherwise, run `composer update fostercommerce/klaviyoconnect`.

### Phone numbers

Klaviyo Connect formats phone numbers in international format before it sends them, and ignores a number it can't format. A template or module that formats numbers itself can keep its code, since Klaviyo Connect sends a valid `+` number unchanged. For how Klaviyo Connect reads a number, and how to collect one it can read, see [phone numbers](./reference/profile-attributes.md#phone-numbers).

## Upgrading to 7.3

### Prepare

- Update Craft CMS to 5.6.0 or later, and Craft Commerce to 5.1.0 or later.
- Search your templates for the changes in [templates and forms](#templates-and-forms) and [events](#events), and your modules for the changes in [PHP](#php), so you know what to edit after the update.
- Back up the database.

### Update

```sh
composer update fostercommerce/klaviyoconnect
./craft migrate/all
```

Then go to **Settings -> Plugins -> Klaviyo Connect**, click **Check Connection and Load Lists**, and click **Save**. Saving moves the single Klaviyo account from earlier versions into the **Sites** table, with the same keys, lists, cart URL and event prefix for every site.

### Settings

**Per-site settings.** API keys, lists, Cart URL and Event Prefix are now set for each site, so sites can use different Klaviyo accounts. Earlier values fill every row. A config file that sets `klaviyoApiKey`, `klaviyoSiteId`, `cartUrl` or `eventPrefix` still works, and fills any blank row. To set different values per site in the config file, see [configuration](./reference/configuration.md).

**`trackSaveUser` is retired.** User syncing now turns on when **Sync Users in These Groups** has a group checked. If `config/klaviyoconnect.php` sets `trackSaveUser` to `false`, users do not sync whatever groups are checked. To let the groups control syncing, remove the key.

**Product image field.** **Product Image Handle** moved into the **Product Type Data Mapping** setting, one row per product type. An existing value fills every product type.

**Image transform.** **Product Image Transformation Handle** is now **Craft Transform**, under the new **Image Engine** setting. A transform that exists keeps working. When no transform has the saved handle, which is the case when the default `productThumbnail` handle was kept without creating that transform, Klaviyo Connect sends original images. To send resized images, set **Image Engine** to Craft and choose **Custom size** under **Craft Transform**, or set it to Imager X or Small Pics. Then enter a **Width** or **Height**.

### Events

**Started Checkout is new, and on by default.** Klaviyo Connect sends it once a cart has an email and at least one item. If your templates post their own Started Checkout event to `klaviyoconnect/api/track`, remove that code, or turn off **Track Commerce Started Checkout**. Otherwise Klaviyo records the event twice, and an abandoned cart flow triggered by it can send twice.

**Added to Cart is new, and on by default.** If a template posts its own Added to Cart event, remove it or turn off **Track Commerce Added to Cart**.

**Klaviyo Connect sends Updated Cart less often.** It sends Updated Cart only when the cart data sent to Klaviyo changes, and not for completed orders. A flow that counted Updated Cart events sees fewer of them.

**Country names are in English.** The profile country that order events set is now the English name, whatever the language of the request. Earlier versions used the request's language. If a segment or flow filters on a country name in another language, change it to the English name.

**Sends go through the queue.** Events, profile updates and list signups are now sent to Klaviyo when Craft's queue runs, and retry on rate limits and outages.

### Templates and forms

**The identify action needs a POST request.** Change any link or GET request to `klaviyoconnect/api/identify` into a form or request that posts, with `{{ csrfInput() }}`.

**`event[orderId]` is limited.** It now accepts only the current cart, or an order that belongs to the logged-in user. On an order confirmation page for guests, post `event[orderNumber]` with `{{ order.number }}` instead. See [actions](./reference/actions.md).

**`klaviyoconnect/api/sync-orders` is deprecated, and requires a logged-in user.** It works as before for a user with permission to use the Klaviyo Connect utility. A script that calls it without logging in gets a 403 error. Send past orders with the Klaviyo Connect utility or the `klaviyoconnect/orders/sync` command instead. See [past orders](./user-guide/past-orders.md).

**Cart restore links.** A restore link for a cart that belongs to a user account now shows a login page, and completed orders no longer restore.

### PHP

**`Cart::restore()` is deprecated.** Link to the `klaviyoconnect/cart/restore` action instead.

**Listener changes.** Changes an `addProfileProperties` listener makes to `$event->profile` are now sent to Klaviyo, and its `properties` merge with the profile's other custom properties instead of replacing them. See [PHP events](./dev-guide/php-events.md).

**`addCustomProperties` listeners read `$event->event`.** It holds the event name. `$event->name` is Yii's event name, `addCustomProperties`.

