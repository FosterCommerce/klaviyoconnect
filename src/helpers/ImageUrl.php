<?php

namespace fostercommerce\klaviyoconnect\helpers;

use Craft;
use craft\elements\Asset;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\InvalidConfigException;
use yii\base\Module;

/**
 * Resolves the image URL sent to Klaviyo through the image engine chosen in the settings.
 */
final class ImageUrl
{
	/**
	 * Image engines that come from another plugin: plugin handle => component that transforms images.
	 */
	public const PLUGIN_ENGINES = [
		'imagerx' => ['imager-x', 'imager'],
		'smallpics' => ['smallpics', 'transformer'],
	];

	/**
	 * @throws InvalidConfigException
	 */
	public static function forAsset(Asset $asset): ?string
	{
		$settings = Plugin::getInstance()->getSettings();
		$imageEngine = $settings->getImageEngine();

		return match (true) {
			$imageEngine === 'craft' => self::craftUrl($asset, $settings),
			isset(self::PLUGIN_ENGINES[$imageEngine]) => self::pluginUrl($asset, $settings, $imageEngine),
			default => $asset->getUrl(),
		};
	}

	/**
	 * Transforms are generated immediately, so Klaviyo gets a real image URL rather than Craft's deferred generate-transform URL.
	 *
	 * @throws InvalidConfigException
	 */
	private static function craftUrl(Asset $asset, Settings $settings): ?string
	{
		// Look up a named transform first, so an unknown handle falls back to the custom size instead of throwing
		$namedTransform = $settings->productImageFieldTransformation === '' ? null : Craft::$app->getImageTransforms()->getTransformByHandle($settings->productImageFieldTransformation);
		$transform = $namedTransform ?? self::sizeConfig($settings);

		return $asset->getUrl($transform, true) ?? $asset->getUrl();
	}

	/**
	 * Calls the engine's `transformImage()` by duck typing, since neither plugin is a Composer dependency.
	 */
	private static function pluginUrl(Asset $asset, Settings $settings, string $imageEngine): ?string
	{
		[$pluginHandle, $componentId] = self::PLUGIN_ENGINES[$imageEngine];
		$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
		$service = $plugin instanceof Module ? $plugin->get($componentId, false) : null;
		$sizeConfig = self::sizeConfig($settings);
		if (! is_object($service) || ! method_exists($service, 'transformImage') || $sizeConfig === null) {
			return $asset->getUrl();
		}

		$transformedImage = $service->transformImage($asset, $sizeConfig);
		$url = is_object($transformedImage) && method_exists($transformedImage, 'getUrl') ? $transformedImage->getUrl() : null;

		return is_string($url) ? $url : $asset->getUrl();
	}

	/**
	 * Craft, Imager X and Small Pics all accept a width or a height alone, and spell crop and fit the same way.
	 *
	 * @return array{width?: int, height?: int, mode: string}|null
	 */
	private static function sizeConfig(Settings $settings): ?array
	{
		$width = is_numeric($settings->imageWidth) ? (int) $settings->imageWidth : null;
		$height = is_numeric($settings->imageHeight) ? (int) $settings->imageHeight : null;
		if ($width === null && $height === null) {
			return null;
		}

		return array_filter([
			'width' => $width,
			'height' => $height,
			// Settings validation doesn't cover a config file value, and Small Pics throws for an unknown mode
			'mode' => strtolower($settings->imageFit) === 'fit' ? 'fit' : 'crop',
		], static fn (int|string|null $value): bool => $value !== null);
	}
}
