# Release Notes for Klaviyo Connect

## 7.3.0 - 2026-10-02

> [!WARNING]
> Klaviyo Connect now tracks “Started Checkout” and “Added to Cart” events, and their “Track Commerce Started Checkout” and “Track Commerce Added to Cart” settings are on by default. Sites that already post either event from their templates should remove that code, or turn the setting off, to avoid duplicate events.

### Added

- Added the “Image Engine” setting, which resizes the product images sent to Klaviyo with Craft, Imager X or Small Pics.
- Added a “Started Checkout” event and its “Track Commerce Started Checkout” setting, which is on by default.
- Added an “Added to Cart” event and its “Track Commerce Added to Cart” setting, which is on by default.
- Added `craft.klaviyoConnect.viewedProduct()`, which tracks Viewed Product and recently viewed items in Klaviyo from a product template.
- Added the “Fulfilled Order Statuses” and “Cancelled Order Statuses” settings, which send Klaviyo's Fulfilled Order and Cancelled Order events for the chosen order statuses.
- Added a `Status` property to order status events.
- Added the “Klaviyo Profile Attributes” and “Custom Profile Properties” settings, which send chosen user fields or Twig values to Klaviyo profiles.
- Added the “Product Type Data Mapping” setting, which chooses each product type's catalog item ID, image fields, category and brand.
- Added a “Refresh lists from Klaviyo” link to Klaviyo List and Lists fields.
- Added the `klaviyoconnect/orders/sync` console command, which sends completed orders from a date range to Klaviyo.
- Added the “Exclude URI Patterns” setting, which keeps the tracking script off matching pages, such as checkout payment pages.
- Added the “Add Klaviyo Tracking Script” setting, which adds klaviyo.js to front-end pages with each site's Public API Key and identifies logged-in users.
- Added `OrderReference`, `ItemNames`, `CheckoutURL`, `DiscountCode`, `DiscountValue`, `Currency`, `DateOrdered`, `Categories`, `Subtotal`, `ShippingTotal`, `TaxTotal`, `ShippingMethod`, `BillingAddress`, `ShippingAddress`, `SiteHandle` and `StoreHandle` to order events.
- Added `LineItemID`, `ProductID`, `VariantID`, `VariantTitle`, `ProductTypeHandle`, `ProductBrand`, `Categories`, `SalePrice`, `Discount`, `Tax` and `Total` to line items in order events.
- Added the “Send Addresses”, “Send Categories”, “Send Pricing Detail” and “Send Site and Store” settings.
- Added a “Check Connection and Load Lists” button to the plugin settings, which checks each site's Klaviyo account and reloads its lists.
- Added the last error Klaviyo returned for a queued send to the plugin settings.
- Added the `event[orderNumber]` param to the `klaviyoconnect/api/track` action.
- Added the Klaviyo Connect utility, which sends completed orders from a date range to Klaviyo when Craft Commerce is installed.
- Added `AddCustomPropertiesEvent::$event`.
- Added `fostercommerce\klaviyoconnect\services\Api::upsertProfile()`, which throws Klaviyo API errors.

### Changed

- Klaviyo Connect now skips an email that Craft wouldn't accept for a user account, or that has no valid top-level domain, and logs a warning.
- Renamed the “Product Image Transformation Handle” setting to “Craft Transform”, under “Image Engine”.
- Klaviyo Connect now requires Craft CMS 5.6.0 or later.
- Klaviyo Connect now requires Craft Commerce 5.1.0 or later, when Commerce is installed.
- Klaviyo API keys, lists, cart URL and event prefix are now set for each site, so sites can use different Klaviyo accounts. Users in “Sync Users in These Groups” sync to every account the sites use, and existing settings apply to every site.
- The product image field is now chosen for each product type in the “Product Type Data Mapping” setting. An existing “Product Image Handle” setting applies to every product type.
- The `trackSaveUser` config setting is replaced by “Sync Users in These Groups”. Users sync when they're in a selected group, and an install with user syncing switched off stays off.
- Klaviyo lists are now stored until refreshed, so loading elements and the plugin settings no longer calls Klaviyo.
- Klaviyo Connect now sends data to Klaviyo from the queue, so a slow or unavailable Klaviyo no longer delays storefront requests, and sends retry on rate limits and outages. ([#140](https://github.com/FosterCommerce/klaviyoconnect/issues/140))
- `craft.klaviyoConnect.lists()` now returns the lists of the current site's Klaviyo account, and accepts a site ID to return another site's.
- Updated to klaviyo/api v20, revision [2026-07-15](https://developers.klaviyo.com/en/docs/changelog_#revision-2026-07-15-ga).
- Restoring a cart that belongs to a user account now requires that user to log in.
- Restoring another customer's cart while logged in now shows a login page.
- Restoring a cart now sets a flash notice.
- Completed orders can no longer be restored as carts.
- Klaviyo API calls now time out after 10 seconds.
- Klaviyo now records a retried or re-synced Placed Order, Ordered Product, Started Checkout, status or refund event once.
- The `klaviyoconnect/api/identify` action now requires a POST request.
- Custom properties from an `addProfileProperties` handler are now merged with the profile's other custom properties instead of replacing them.
- The `klaviyoconnect/api/identify` action now returns `"success"` for Ajax requests, like the track action.
- “Updated Cart” is now tracked only when the data sent to Klaviyo changes, and no longer for completed orders or for a new cart before its first item.
- Order status events are no longer sent for the status a new order starts with, which Placed Order already covers.
- Event properties no longer include an empty `$value`, `$value_currency` or `$unique_id`.

### Deprecated

- Deprecated `fostercommerce\klaviyoconnect\services\Cart::restore()`. Link to the `klaviyoconnect/cart/restore` action instead.
- Deprecated the `klaviyoconnect/api/sync-orders` action. Use the Klaviyo Connect utility or the `klaviyoconnect/orders/sync` console command instead.

### Fixed

- Fixed a bug where a blank or invalid `profile[email]` kept a form's `email` param from being used.
- Fixed a bug where a list signup from a logged-in user subscribed their account email to email marketing, even when the form posted only a phone number.
- Fixed a bug where a posted `event[unique_id]` was ignored for order events sent with `event[trackOrder]`.
- Fixed a bug where a `forward` param naming one of Klaviyo Connect's own actions queued thousands of sends.
- Fixed a bug where an `event[customAttributes]` param broke the event it was posted with.
- Fixed a bug where order events for an order with no email were sent to the profile of the logged-in user, such as a control panel user changing the order's status.
- Fixed a bug where country names in order events were in the language of the request that sent them.
- Fixed a bug where a profile with an unknown key, or a value of the wrong type such as a text `location`, wasn't sent to Klaviyo.
- Fixed a bug where an event posted with an invalid `event[value]`, `event[value_currency]` or `event[timestamp]` wasn't recorded in Klaviyo.
- Fixed a bug where order events sent line items for purchasables without a product, such as donations, without their properties.
- Fixed an error that occurred when a template read the `id` or `name` of a Klaviyo List field whose list isn't in the site's Klaviyo account.
- Fixed a bug where Klaviyo recorded only one “Ordered Product” event for an order with several products.
- Fixed a bug where cart events and unpaid orders sent a value of 0 to Klaviyo, and Ordered Product sent the order total instead of the line item total.
- Fixed a bug where order events left out line items whose product had been deleted.
- Fixed a bug where a site with no Private API Key queued sends that failed.
- Fixed an error that occurred when the “Product Image Transformation Handle” setting named a transform that doesn't exist, which stopped order events from sending.
- Fixed a bug where Refunded Order events sent the order total instead of the refunded amount.
- Fixed a bug where saving an element while Klaviyo was unreachable cleared its Klaviyo Lists field.
- Fixed a bug where restoring a cart from another site didn't restore the cart.
- Fixed an error that occurred when looking up a profile whose email contained a quote.
- Fixed a bug where restoring a cart redirected to the cart URL on the current site rather than the order's site.
- Fixed a bug where changes an `addProfileProperties` handler made to `$event->profile` weren't sent to Klaviyo.
- Fixed a bug where order events could include empty entries in `Items`.
- Fixed a bug where a custom line item could be sent with the previous line item's properties.
- Fixed an error that occurred when a Klaviyo List or Lists field loaded while Klaviyo was unreachable or the API key was invalid.
- Fixed an error that occurred when the action named in a `forward` param returned no response, such as a failed payment.

### Security

- Fixed a bug where any visitor could post an order's ID to the `klaviyoconnect/api/track` action and send an event with that order's details to the order's customer in Klaviyo.
- Fixed a bug where any visitor could call the `klaviyoconnect/api/sync-orders` action to send completed orders to Klaviyo again. It now requires a user with permission to use the Klaviyo Connect utility.

## 7.2.5 - 2026-09-18

### Fixed

- Fixed an error on edit screens when the saved Klaviyo list isn't in the connected account.

## 7.2.4 - 2026-05-05

### Fixed

- Klaviyo API failures no longer break host requests. Errors are caught, logged to the `klaviyoconnect` category, and the request continues.
- Klaviyo API calls now hard-cap at 5s (5s connect) with retries disabled, so an unresponsive Klaviyo can't stall the host request.

## 7.2.3 - 2025-06-21

### Fixed

- Fix an issue in the ListsField where setting the value wasn't normalizing correctly.

## 7.2.2 - 2025-04-28

### Fixed

- Fix issue where Klaviyo lists on the plugin settings page were not being loaded correctly.

## 7.2.1 - 2025-04-25

### Fixed

- Fixed an issue where accessing custom line item purchasables was causing an exception and preventing orders from being updated.

## 7.2.0 - 2025-04-17

### Updated

- Updated to use klaviyo/api v14, revision [2025-04-15](https://developers.klaviyo.com/en/docs/changelog_#revision-2025-04-15-ga)

## 7.1.1 - 2025-03-24

## Updated

- Updated profile data that is used for order events.

## 7.1.0 - 2025-03-24

### Added

- Added order billing address location to profiles on order events. 

### Fixed

- Use correct timestamp value when syncing past orders
- Use correct value format for normalizing list field values


## 7.0.0 - 2024-09-23

- Migrated to Craft 5 and Commerce 5

