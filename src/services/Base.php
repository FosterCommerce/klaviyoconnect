<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\helpers\App;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

/**
 * @deprecated in 7.4.0. Extend `yii\base\Component`, and read settings with the `Settings` getters, such as `getApiKey()`, which return each site's value.
 */
abstract class Base extends Component
{
	private ?Settings $settings = null;

	protected function getSetting(string $name): mixed
	{
		Craft::$app->getDeprecator()->log(__METHOD__, '`' . __METHOD__ . '()` has been deprecated. Use the `Settings` getters, such as `getApiKey()`, instead.');

		if (! $this->settings instanceof Settings) {
			$this->settings = Plugin::getInstance()->getSettings();
		}

		$value = $this->settings->{$name};

		if (is_string($value)) {
			return App::parseEnv($value);
		}

		return $value;
	}
}
