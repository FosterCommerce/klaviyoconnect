# Product Feed

In Klaviyo, product feeds are added via [Catalog Sources](https://www.klaviyo.com/catalog/sources). This view is hidden for custom API plugins like this one, but still accessible and usable here: [https://www.klaviyo.com/catalog/sources](https://www.klaviyo.com/catalog/sources). Once you've created your JSON feed, add it to a new [Catalog Source](https://www.klaviyo.com/catalog/sources/create) to make the products within it available to your Klaviyo emails. Confirm your product feed is working on the [Catalog page](https://www.klaviyo.com/catalog/items).

[Download Klaviyo's documentation for Custom Catalogs](https://help.klaviyo.com/attachments/token/mZ2rjQfoEcLjs5OMrXyIeOiK3/?name=Klaviyo+Custom+Catalog.zip)

## JSON Feed

- Install Craft's [Element API](https://github.com/craftcms/element-api) plugin
- Configure Element API to export a product feed, for example:

```php
<?php

use craft\commerce\elements\Product;
use craft\helpers\UrlHelper;

return [
    'endpoints' => [
        'products.json' => function() {
            return [
                'elementType' => Product::class,
                'transformer' => function(Product $entry) {
                    $variant = $entry->defaultVariant;
                    return [
                        'id' => $entry->id,
                        'title' => $entry->title,
                        'sku' => $variant->sku,
                        'url' => UrlHelper::url("/products/{$entry->id}"),
                    ];
                },
                'pretty' => true,
                'paginate' => false,
            ];
        },
    ]
];
```

- Navigate to `<your-site-url>/products.json`

## Example output

```json
{
    "data": [
        {
            "id": "2",
            "title": "A New Toga",
            "sku": "ANT-001",
            "url": "http://commerce.foster.test/products/2"
        },
        {
            "id": "10",
            "title": "The Last Knee-High",
            "sku": "LKH-001",
            "url": "http://commerce.foster.test/products/10"
        },
        {
            "id": "8",
            "title": "The Fleece Awakens",
            "sku": "TFA-001",
            "url": "http://commerce.foster.test/products/8"
        },
        {
            "id": "6",
            "title": "Romper For A Red Eye",
            "sku": "RRE-001",
            "url": "http://commerce.foster.test/products/6"
        },
        {
            "id": "4",
            "title": "Parka With Stripes On Back",
            "sku": "PSB-001",
            "url": "http://commerce.foster.test/products/4"
        }
    ]
}
```
