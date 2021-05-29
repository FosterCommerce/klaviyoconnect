# Product Feed

In Klaviyo, product feeds are added via [Catalog Sources](https://www.klaviyo.com/catalog/sources). This view is hidden for custom API plugins like this one, but still accessible and usable here: [https://www.klaviyo.com/catalog/sources](https://www.klaviyo.com/catalog/sources). Once you've created your JSON feed, add it to a new [Catalog Source](https://www.klaviyo.com/catalog/sources/create) to make the products within it available to your Klaviyo emails. Confirm your product feed is working on the [Catalog page](https://www.klaviyo.com/catalog/items).

[Download Klaviyo's documentation for Custom Catalogs](https://help.klaviyo.com/attachments/token/mZ2rjQfoEcLjs5OMrXyIeOiK3/?name=Klaviyo+Custom+Catalog.zip)

## JSON Feed in Twig

You can use Twig to generate a JSON feed. Create a template like the following example and make sure it is publicly accessible:

```twig
{% if craft.app.plugins.isPluginInstalled('klaviyoconnect') %}
    {% set productImageField = craft.app.getPlugins().getPlugin('klaviyoconnect').getSettings().productImageField %}
{% endif %}

{% set products = craft.products().hasVariant({ hasStock: true }).with(productImageField is defined ? [productImageField] : []).all() %}

{% set feed = [] %}

{% for product in products %}
    {% set variant = product.defaultVariant %}
    {% set feedProduct = {
        'SKU'        : variant.sku,
        'ProductName': product.title,
        'ProductType': product.type,
        'ProductURL' : variant.url,
        'ItemPrice'  : variant.price,
        'ProductID'  : product.id,
    } %}

    {% if productImageField is defined and product[productImageField]|length > 0 %}
        {% set feedProduct = feedProduct|merge({
            'ProductImage': product[productImageField][0].url,
        }) %}
    {% endif %}

    {% set feed = feed|merge([feedProduct]) %}
{% endfor %}

{{ feed|json_encode }}
```
