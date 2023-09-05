<?php
declare(strict_types = 1);

use craft\rector\SetList as CraftSetList;
use Rector\Config\RectorConfig;

return static function(RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        CraftSetList::CRAFT_CMS_40,
        CraftSetList::CRAFT_COMMERCE_40,
    ]);
};
