<?php
declare(strict_types = 1);

use fostercommerce\rector\SetList;
use Rector\Config\RectorConfig;

return static function(RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        SetList::CRAFT_CMS_40
    ]);
};
