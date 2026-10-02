<?php

namespace fostercommerce\klaviyoconnect\helpers;

use Craft;
use craft\web\View;
use Throwable;

final class SandboxedTwig
{
	/**
	 * Renders a template from the settings, or returns an empty string when it fails.
	 *
	 * @param array<string, mixed> $variables
	 */
	public static function render(string $template, array $variables, string $source): string
	{
		try {
			return trim((string) Craft::$app->getView()->renderSandboxedString($template, $variables, View::TEMPLATE_MODE_SITE));
		} catch (Throwable $throwable) {
			// Skip the value rather than fail the whole send
			Craft::warning("Klaviyo {$source} template failed: {$throwable->getMessage()}", 'klaviyoconnect');

			return '';
		}
	}
}
