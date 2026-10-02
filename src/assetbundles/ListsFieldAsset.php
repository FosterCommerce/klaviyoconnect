<?php

namespace fostercommerce\klaviyoconnect\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ListsFieldAsset extends AssetBundle
{
	public function init(): void
	{
		$this->sourcePath = __DIR__ . '/dist';

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'js/lists-field.js',
		];

		parent::init();
	}
}
