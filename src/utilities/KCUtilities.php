<?php

namespace fostercommerce\klaviyoconnect\utilities;

use Craft;
use craft\base\Utility;

class KCUtilities extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('klaviyoconnect', 'Klaviyo Connect');
    }

    public static function id(): string
    {
        return 'klaviyo-connect';
    }

    public static function iconPath(): string
    {
        return 'icon.svg';
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('klaviyoconnect/utilities');
    }
}
