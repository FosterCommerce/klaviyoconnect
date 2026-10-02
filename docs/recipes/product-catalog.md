# Recipe: add your products to Klaviyo's catalog

This recipe puts your Commerce products in Klaviyo's catalog, for product blocks and recommendations in Klaviyo emails. Klaviyo Connect sends events and profiles, and does not send a catalog. To build the catalog feed, use Foster Commerce's [Product Feeds](https://www.fostercommerce.com/craft-cms-plugins/product-feeds) plugin.

## 1. Create a Klaviyo catalog feed

Install Product Feeds and create a feed for Klaviyo's catalog, following the [Product Feeds documentation](https://www.fostercommerce.com/craft-cms-plugins/product-feeds/docs). Note which ID the feed uses for each item.

## 2. Match the Catalog Item ID

Klaviyo links an event's product to a catalog item when the event's `ProductID` matches the item's ID. Go to **Settings -> Plugins -> Klaviyo Connect**. In the **Product Type Data Mapping** table, set **Catalog Item ID** for each product type in the feed to the ID the feed uses: **Product ID**, **Variant ID** or **Variant SKU**. Click **Save**.

## 3. Add the feed to Klaviyo

In Klaviyo, open **Catalog -> Sources** and add a custom catalog source. Paste the feed's URL, then map the feed's fields in Klaviyo. If your Klaviyo account does not show **Sources** in its navigation, open `https://www.klaviyo.com/catalog/sources` directly.

Klaviyo fetches the feed on its own schedule. Once it has, the products appear in Klaviyo's catalog and its product blocks.

## Where to go next

- [Back in stock](./back-in-stock.md), Klaviyo's back in stock flow from a catalog feed
- [Tracking script](../user-guide/tracking-script.md), Viewed Product events for browse abandonment
