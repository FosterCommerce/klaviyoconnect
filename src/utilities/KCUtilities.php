<?php

namespace fostercommerce\klaviyoconnect\utilities;

use Craft;
use craft\base\Utility;

class KCUtilities extends Utility
{
	public static function displayName(): string
	{
		return Craft::t('klaviyoconnect', 'sync.utility.name');
	}

	public static function id(): string
	{
		return 'klaviyo-connect';
	}

	public static function icon(): string
	{
		return (string) Craft::getAlias('@fostercommerce/klaviyoconnect/icon-mask.svg');
	}

	public static function contentHtml(): string
	{
		return Craft::$app->getView()->renderTemplate('klaviyoconnect/utilities');
	}
}
