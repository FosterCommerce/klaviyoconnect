# Event properties

The properties Klaviyo Connect sends with its built-in events. Every event name gets the site's **Event Prefix**, when one is set.

The **Setting** column names the setting under **Event Data** in **Settings -> Plugins -> Klaviyo Connect** that includes the property. Every switch is on by default. A blank cell means the property is always sent.

To add or change properties, see [PHP events](../dev-guide/php-events.md). In a Klaviyo email, read a property as `{{ event.PropertyName }}`. For template syntax, see Klaviyo's [message personalization reference](https://help.klaviyo.com/hc/en-us/articles/4408802648731).

## Events

| Event | Sent when | `value` | `unique_id` |
|---|---|---|---|
| Started Checkout | A storefront request saves a cart that has an email and line items. Sent once per cart. | Order total | `{number}_started-checkout` |
| Added to Cart | A storefront request adds a new line item to a cart. Not sent when a line item's quantity changes. | Order total | `{number}_added_{lineItemId}` |
| Updated Cart | A cart is saved. Not sent for a new cart before its first item, or when the profile and properties match the last Updated Cart for that cart. | Order total | A random UUID |
| Placed Order | An order is completed, or a [past order](../user-guide/past-orders.md) is sent. | Order total | `{number}` |
| Ordered Product | Once per line item, with each Placed Order. | Line item subtotal | `{number}_{lineItemId}` |
| Fulfilled Order | An order moves into a status listed in **Fulfilled Order Statuses**. | Order total | `{number}_status_{orderHistoryId}` |
| Cancelled Order | An order moves into a status listed in **Cancelled Order Statuses**. | Order total | `{number}_status_{orderHistoryId}` |
| `{Status} Order` | An order moves into any other status, such as `Shipped Order`. Not sent for the status a new order starts with. | Order total | `{number}_status_{orderHistoryId}` |
| Refunded Order | A refund transaction succeeds. | Refund amount | `{number}_refund_{transactionId}` |
| Viewed Product | A product page calls `craft.klaviyoConnect.viewedProduct()`. | Not sent | Not sent |

`{number}` is the order number. The **Track Commerce** settings turn each live order event on or off. Klaviyo Connect sends an order event only when the order has a valid email.

## Event attributes

Each order event sends these as attributes of the Klaviyo event.

| Attribute | Notes |
|---|---|
| `value` | The amount in the [events table](#events), as a number. |
| `value_currency` | The order's currency code, such as `USD`. |
| `unique_id` | Klaviyo records one event per ID for each profile and metric, so a resend of the same event is ignored. |
| `time` | The order date, for past orders. A form can set it with `event[timestamp]`. Otherwise Klaviyo uses the time it receives the event. |

The properties also include `$value`, `$value_currency` and `$unique_id` when they're set, with the same values.

## Order properties

Sent with every order event except Ordered Product.

| Property | Setting | Notes |
|---|---|---|
| `OrderID` | | The order's element ID. |
| `OrderNumber` | | |
| `OrderReference` | | |
| `TotalPrice` | | |
| `TotalQuantity` | | |
| `Items` | | One entry per line item, with the [line item properties](#line-item-properties). |
| `ItemNames` | | The `ProductName` of each line item. |
| `DiscountCode` | | The coupon code, or `null`. |
| `DiscountValue` | | The order's total discount, as a positive number. |
| `Currency` | | |
| `DateOrdered` | | ISO 8601 date, or `null` for a cart. |
| `CheckoutURL` | | A link that restores the cart on its site. Sent only for carts. |
| `Categories` | Send Categories | Every category of every line item, without repeats. |
| `Subtotal` | Send Pricing Detail | The item subtotal. |
| `ShippingTotal` | Send Pricing Detail | |
| `TaxTotal` | Send Pricing Detail | |
| `ShippingMethod` | Send Pricing Detail | The shipping method name. |
| `BillingAddress` | Send Addresses | The [address properties](#address-properties), or `null`. |
| `ShippingAddress` | Send Addresses | The [address properties](#address-properties), or `null`. |
| `SiteHandle` | Send Site and Store | The handle of the site the order was placed on. |
| `StoreHandle` | Send Site and Store | The Commerce store's handle. |

### Added to Cart

Added to Cart also sends the line item that was added.

| Property | Notes |
|---|---|
| `AddedItemProductName` | |
| `AddedItemProductID` | |
| `AddedItemSKU` | |
| `AddedItemCategories` | An empty array when **Send Categories** is off. |
| `AddedItemImageURL` | |
| `AddedItemURL` | The product URL. |
| `AddedItemPrice` | |
| `AddedItemQuantity` | |

### Order status events

Fulfilled Order, Cancelled Order and `{Status} Order` also send:

| Property | Notes |
|---|---|
| `Status` | The new status name. |
| `Reason` | The message entered with the status change. |

### Refunded Order

| Property | Notes |
|---|---|
| `Reason` | The note on the refund transaction. |

## Line item properties

Each entry in `Items`, and the properties of each Ordered Product event. Klaviyo Connect does not build properties for custom line items.

| Property | Setting | Notes |
|---|---|---|
| `LineItemID` | | |
| `VariantID` | | The purchasable's ID. |
| `ProductName` | | The product title. For a purchasable without a product, the line item description. |
| `ItemPrice` | | |
| `RowTotal` | | The line item subtotal. |
| `value` | | The line item subtotal. In Ordered Product, this sets the event's `value`. |
| `Quantity` | | |
| `SKU` | | |
| `Options` | | The line item options. |
| `Adjustments` | | Each adjustment's name and formatted amount. |
| `ImageURL` | | The variant's **Variant Image**. When **Variant Image** isn't mapped or the variant's is empty, the product's **Product Image**. Both are set in **Product Type Data Mapping**. Left out when neither gives an image. |
| `SalePrice` | Send Pricing Detail | |
| `Discount` | Send Pricing Detail | |
| `Tax` | Send Pricing Detail | |
| `Total` | Send Pricing Detail | The line item total. |
| `ProductID` | | Products only. The **Catalog Item ID**: the product ID by default, or the variant's ID or SKU. |
| `VariantTitle` | | Products only. |
| `Slug` | | Products only. The product slug. |
| `ProductURL` | | Products only. |
| `ProductType` | | Products only. The product type name. |
| `ProductTypeHandle` | | Products only. |
| `ProductBrand` | | Products only. From the **Brand** in **Product Type Data Mapping**. |
| `Categories` | Send Categories | Products only. From the **Category** in **Product Type Data Mapping**. |

"Products only" properties are sent for Commerce variants, and left out for other purchasables.

## Address properties

The value of `BillingAddress` and `ShippingAddress`.

| Property | Notes |
|---|---|
| `FirstName` | |
| `LastName` | |
| `Company` | The address organization. |
| `Address1` | |
| `Address2` | |
| `City` | |
| `RegionCode` | The administrative area, such as a state code. |
| `Zip` | |
| `Country` | The country name, in English. |
| `CountryCode` | |

## Viewed Product

Sent from the shopper's browser, without a `value` or `unique_id`. For what the call needs, see [track Viewed Product](../user-guide/tracking-script.md#track-viewed-product).

| Property | Setting | Notes |
|---|---|---|
| `ProductName` | | The product title. |
| `ProductID` | | The **Catalog Item ID**: the product ID by default, or the variant's ID or SKU. |
| `VariantID` | | |
| `SKU` | | |
| `URL` | | The product URL. |
| `ImageURL` | | Same as `ImageURL` in [line item properties](#line-item-properties), or `null`. |
| `Brand` | | From the **Brand** in **Product Type Data Mapping**. |
| `Price` | | The variant's sale price. |
| `CompareAtPrice` | | The variant's regular price, when it is higher than the sale price. Otherwise `null`. |
| `Categories` | Send Categories | From the **Category** in **Product Type Data Mapping**. |

The same call sends Klaviyo's `trackViewedItem`, for Recently Viewed Items in emails. It sends `Title`, `ItemId`, `Categories`, `ImageUrl` and `Url`, plus `Brand`, `Price` and `CompareAtPrice` under `Metadata`.
