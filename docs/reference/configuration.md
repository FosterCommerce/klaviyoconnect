# Configuration

Every setting on **Settings -> Plugins -> Klaviyo Connect**, in the order the page shows them, with the key that sets it from `config/klaviyoconnect.php`.

## Klaviyo account

| Setting | Config key | Default | What it does |
|---|---|---|---|
| Sites: Public API Key | `klaviyoSiteId` | Blank | Used only by the tracking script. |
| Sites: Private API Key | `klaviyoApiKey` | Blank | Sends events, profiles and list signups. If a site's key is blank, Klaviyo Connect does not send that site's data. |
| Sites: Cart URL | `cartUrl` | `/shop/cart` | The page a restored cart opens on. Enter a path relative to the site, such as `cart`, `/` for the homepage, or a full URL. |
| Sites: Event Prefix | `eventPrefix` | Blank | Added before every event name, followed by a space. Up to 60 characters. |
| Connection | | | **Check Connection and Load Lists** checks each site's Private API Key, including unsaved changes, and reloads that site's lists. |

The **Sites** table has one row per site. Enter keys as environment variables, such as `$KLAVIYO_PRIVATE_API_KEY`, to keep them out of project config. To share one Klaviyo account across sites, enter the same variable names in each row. To use a separate account for a site, give that row its own variables.

A site with a blank cell uses the value of that config key from `config/klaviyoconnect.php`, and the table shows which keys the file sets. After an upgrade from a version before 7.3, the single account saved before the upgrade fills every row.

When a queued send fails, **Connection** shows the error Klaviyo returned and its date. The error clears after a connection check where every site with a Private API Key connects.

## Lists

| Setting | Config key | Default | What it does |
|---|---|---|---|
| Lists for {site} | `siteSettings.{siteUid}.klaviyoAvailableLists` | No lists | The lists that [Klaviyo List and Klaviyo Lists fields](../user-guide/list-fields.md) offer on that site. **All** offers every list in the site's account. |

If a site's lists cannot load, saving the page keeps that site's chosen lists. For when lists refresh, see [refresh the lists](../user-guide/list-fields.md#refresh-the-lists).

To set lists for every site from the config file, use `klaviyoAvailableLists` with an array of list IDs, or `'*'` for every list. A site with its own lists chosen ignores that key.

## Tracking

| Setting | Config key | Default | What it does |
|---|---|---|---|
| Add Klaviyo Tracking Script | `injectOnsiteScript` | Off | Adds klaviyo.js to front-end pages of each site that has a Public API Key. See [tracking script](../user-guide/tracking-script.md). |
| Exclude URI Patterns | `excludedUriPatterns` | No patterns | Pages that do not get the tracking script. Shown when **Add Klaviyo Tracking Script** is on. See [exclude URI patterns](#exclude-uri-patterns). |
| Track Commerce Started Checkout | `trackCommerceStartedCheckout` | On | Sends Started Checkout once per cart, from front-end requests only. |
| Track Commerce Added to Cart | `trackCommerceAddedToCart` | On | Front-end requests only, so adding an item to an order in the control panel does not send the event. |
| Track Commerce Cart Updated | `trackCommerceCartUpdated` | On | Sends Updated Cart only when the cart data sent to Klaviyo changes. Completed orders do not send it. |
| Track Commerce Order Complete | `trackCommerceOrderCompleted` | On | Sends Placed Order and one Ordered Product event per line item. |
| Track Commerce Order Status Updates | `trackCommerceStatusUpdated` | On | Sends `{Status} Order`, such as `Shipped Order`, named after the new status. |
| Fulfilled Order Statuses | `fulfilledOrderStatuses` | No statuses | Order status handles that send Fulfilled Order instead. Shown when **Track Commerce Order Status Updates** is on. |
| Cancelled Order Statuses | `cancelledOrderStatuses` | No statuses | Order status handles that send Cancelled Order instead. Shown when **Track Commerce Order Status Updates** is on. |
| Track Commerce Order Refunds | `trackCommerceRefunded` | On | Sends Refunded Order for full and partial refunds. |
| Sync Users in These Groups | `klaviyoAvailableGroups` | No groups | User group IDs. Saving a user in one of these groups creates or updates their profile in each Klaviyo account the sites use. With no groups chosen, users do not sync. |

### Exclude URI patterns

For the pattern format, see [exclude pages](../user-guide/tracking-script.md#exclude-pages).

In the config file, each row is an array with `uriPattern`, `siteUid` (blank for all sites) and `enabled`.

## Profile mapping

These two tables appear under **Tracking**, after **Sync Users in These Groups**. Email comes from the user, or from the order on order events. First and last name come from the user.

| Setting | Config key | Default | What it does |
|---|---|---|---|
| Klaviyo Profile Attributes | `profileAttributeFields` | External ID: User ID | Sets Klaviyo's External ID, Phone Number, Organization, Title, Locale and Image attributes. Klaviyo treats External ID as identity, so installs that share a Klaviyo account need an ID unique to each install, such as `{{ siteUrl }}{{ user.id }}`. |
| Custom Profile Properties | `profileCustomProperties` | No properties | Sends a user field or Twig value as a custom profile property, under the **Property** name you enter. |

Each row's **Source** is a user field, **User ID**, or **Twig value**:

- A user field with an empty value is not sent, so the value already in Klaviyo stays.
- A Twig value gets `user`, and `order` on order events. Templates render in Craft's Twig sandbox when the `enableTwigSandbox` config setting is on. Saving fails on a Twig syntax error. If a template throws an error when it renders, Klaviyo Connect skips that attribute and logs a warning.

In the config file, `profileAttributeFields` is keyed by `external_id`, `phone_number`, `organization`, `title`, `locale` or `image`. Each value is `['userField' => 'fieldHandle']`, `['userField' => '__userId__']`, or `['userField' => '__twig__', 'twig' => '...']`. `profileCustomProperties` is a list of the same arrays, each with a `name`.

## Event data

| Setting | Config key | Default | What it does |
|---|---|---|---|
| Send Addresses | `sendAddresses` | On | Adds `BillingAddress` and `ShippingAddress` to order events. |
| Send Categories | `sendCategories` | On | Adds `Categories` to order events, their line items and Viewed Product. Categories come from **Product Type Data Mapping**. |
| Send Pricing Detail | `sendPricingDetail` | On | Adds `Subtotal`, `ShippingTotal`, `TaxTotal` and `ShippingMethod` to order events, and `SalePrice`, `Discount`, `Tax` and `Total` to line items. |
| Send Site and Store | `sendSiteContext` | On | Adds `SiteHandle` and `StoreHandle` to order events. |
| Product Type Data Mapping | `productTypeFields` | No fields | For each product type: its **Catalog Item ID**, image fields, and where its category and brand come from. Shown when Commerce has a product type. |
| Image Engine | `imageEngine` | None, or Craft when a saved transform exists | How the product and variant image URLs sent to Klaviyo are produced: `none` (the image's own URL), `craft`, `imagerx` or `smallpics`. Imager X and Small Pics appear when installed. |
| Craft Transform | `productImageFieldTransformation` | `productThumbnail` | With the Craft engine, a named image transform, or **Custom size** to use **Width**, **Height** and **Fit**. If no transform has the saved handle, Klaviyo Connect uses the saved size, or the original image. |
| Width, Height, Fit | `imageWidth`, `imageHeight`, `imageFit` | None, None, `crop` | The size for Imager X, Small Pics, or Craft without a named transform. Either width or height can be left blank. `imageFit` is `crop` or `fit`. With no width or height, Klaviyo Connect sends the original image. |

**Category** and **Brand** each come from a relation or option field (related titles or option labels), the **Product type name**, **Custom text**, or a **Twig value** that gets `product` and `variant`. **Brand** also accepts a plain text field. Custom text and Twig categories can be comma-separated. Saving fails on a Twig syntax error, and a template that fails when it renders is skipped and logged. In the config file, set `categoryField` or `brandField` to `__productType__`, `__custom__` (with `categoryText` or `brandText`) or `__twig__` (with `categoryTwig` or `brandTwig`).

**Catalog Item ID** is what Viewed Product and order line items send as `ProductID`: the product ID (the default), the variant ID, or the variant SKU (`productId`, `variantId` or `variantSku`). Klaviyo matches `ProductID` to the item ID in your product catalog, so choose the ID that product type's catalog feed uses. A variant without a SKU sends its variant ID.

In the config file, `productTypeFields` is keyed by product type UID or handle. Each value is an array with `catalogItemId`, `productImageField`, `variantImageField`, `categoryField` and `brandField`. To send the same brand for every product of a type, set `brandField` to `__custom__` and `brandText` to the brand.

## Config file

A key set in `config/klaviyoconnect.php` overrides the value saved in the control panel. The **Sites** table and list keys are the exception: they fill only blank cells, unless the file sets `siteSettings`.

```php
<?php

return [
    // Fill blank cells in the Sites table
    'klaviyoSiteId' => '$KLAVIYO_PUBLIC_API_KEY',
    'klaviyoApiKey' => '$KLAVIYO_PRIVATE_API_KEY',
    'cartUrl' => 'cart',
    'eventPrefix' => '',

    'klaviyoAvailableLists' => '*',

    'injectOnsiteScript' => true,
    'excludedUriPatterns' => [
        ['uriPattern' => '^checkout/payment', 'siteUid' => '', 'enabled' => true],
    ],
    'trackCommerceStartedCheckout' => true,
    'trackCommerceAddedToCart' => true,
    'trackCommerceCartUpdated' => true,
    'trackCommerceOrderCompleted' => true,
    'trackCommerceStatusUpdated' => true,
    'fulfilledOrderStatuses' => ['shipped'],
    'cancelledOrderStatuses' => ['cancelled'],
    'trackCommerceRefunded' => true,
    'klaviyoAvailableGroups' => [1],

    'profileAttributeFields' => [
        'external_id' => ['userField' => '__twig__', 'twig' => '{{ siteUrl }}{{ user.id }}'],
        'phone_number' => ['userField' => 'phone'],
    ],
    'profileCustomProperties' => [
        ['name' => 'Company Size', 'userField' => 'companySize'],
    ],

    'sendAddresses' => true,
    'sendCategories' => true,
    'sendPricingDetail' => true,
    'sendSiteContext' => true,
    'productTypeFields' => [
        'clothing' => [
            'catalogItemId' => 'variantSku',
            'productImageField' => 'productImage',
            'variantImageField' => 'variantImage',
            'categoryField' => 'productCategories',
            'brandField' => '__custom__',
            'brandText' => 'Acme',
        ],
    ],
    'imageEngine' => 'craft',
    'productImageFieldTransformation' => '',
    'imageWidth' => 600,
    'imageFit' => 'crop',
];
```

To set one site's values from the file, use `siteSettings`, keyed by site UID. Each site's array accepts `klaviyoSiteId`, `klaviyoApiKey`, `cartUrl`, `eventPrefix` and `klaviyoAvailableLists`. Setting `siteSettings` in the file replaces every row the control panel saved.
