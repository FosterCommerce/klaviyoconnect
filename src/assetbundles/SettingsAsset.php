<?php

namespace fostercommerce\klaviyoconnect\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SettingsAsset extends AssetBundle
{
	public function init(): void
	{
		$this->sourcePath = __DIR__ . '/dist';

		$this->depends = [
			CpAsset::class,
		];

		$this->css = [
			'css/settings.css',
		];

		$this->js = [
			'js/settings.js',
		];

		parent::init();
	}
}
