# Recipe: back in stock emails

This recipe sends shoppers an email when a sold-out variant is restocked, using Klaviyo's back in stock feature. Klaviyo determines when an item is back in stock from its product catalog, and Klaviyo Connect does not send a catalog.

## 1. Publish a catalog with inventory

Klaviyo's back in stock feature needs a catalog with one item per variant, each with its stock as `$inventory_quantity`. Set `$inventory_policy` to `1` on each item: Klaviyo then hides an item at zero stock from product blocks and recommendations, and its back in stock flows use the item. With `0` or `2`, Klaviyo keeps showing the item.

Klaviyo records a restock only when it fetches the feed after the stock changes. To keep the feed's stock current, see the [Product Feeds documentation](https://www.fostercommerce.com/craft-cms-plugins/product-feeds/docs). To build the feed, follow the [product catalog recipe](./product-catalog.md) with a feed of variants.

## 2. Add a back in stock signup

Shoppers sign up on the product page of a sold-out variant. Klaviyo provides the signup for a custom catalog. To set it up, see Klaviyo's [enable back in stock for custom catalog feeds](https://developers.klaviyo.com/en/docs/how_to_enable_back_in_stock_for_custom_catalog_feeds).

The signup identifies the item by its ID in your catalog feed. Pass that ID from your product template. For a feed that uses variant SKUs:

```twig
{% if not variant.hasStock() %}
  <div class="back-in-stock" data-catalog-id="{{ variant.sku }}">
    {# Klaviyo's back in stock signup #}
  </div>
{% endif %}
```

Klaviyo's signup also needs the site's Public API Key, which is the one in the **Sites** table at **Settings -> Plugins -> Klaviyo Connect**.

## 3. Build the flow

In Klaviyo, create a back in stock flow, triggered by Klaviyo's back in stock metric. Klaviyo sends the flow to the shoppers who signed up for an item once the item's `$inventory_quantity` rises above zero.

Klaviyo's back in stock settings control how soon after a restock it sends the flow, and to how many shoppers at a time.
