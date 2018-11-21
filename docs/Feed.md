# Product Feed

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
                        'url' => UrlHelper::url($variant->getUrl()),
                    ];
                },
                'pretty' => true,
                'paginate' => false,
            ];
        },
    ]
];
```

