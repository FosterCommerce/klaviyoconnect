<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\utilities\KCUtilities;
use KlaviyoAPI\ApiException;
use yii\web\Response;

class SettingsController extends Controller
{
	/**
	 * Checks each site's saved Private API Key and reloads its lists, for the Klaviyo Connect utility.
	 */
	public function actionTestConnection(): Response
	{
		$this->requirePermission('utility:' . KCUtilities::id());
		$this->requirePostRequest();
		$this->requireAcceptsJson();

		$settings = Plugin::getInstance()->getSettings();
		$results = [];
		$keyedSiteFailed = false;
		$sites = Craft::$app->getSites()->getAllSites(true);
		foreach ($sites as $site) {
			$rawApiKey = $settings->getRawApiKey($site->id);
			$test = $this->testRawApiKey($rawApiKey);
			$keyedSiteFailed = $keyedSiteFailed || ($rawApiKey !== '' && ! $test['connected']);
			$results[] = [
				'label' => $site->getUiLabel(),
				'connected' => $test['connected'],
				'message' => $test['message'],
			];
		}

		if (! $keyedSiteFailed) {
			Plugin::getInstance()->api->clearLastError();
		}

		return $this->asJson([
			'results' => $results,
		]);
	}

	/**
	 * @return array{connected: bool, message: string}
	 */
	private function testRawApiKey(string $rawApiKey): array
	{
		$apiKey = trim((string) App::parseEnv($rawApiKey));
		if ($apiKey === '') {
			// Name an env var that isn't set, rather than reporting the site as having no key
			return [
				'connected' => false,
				'message' => $rawApiKey === ''
					? Craft::t('klaviyoconnect', 'settings.testConnection.noKey')
					: Craft::t('klaviyoconnect', 'settings.testConnection.envMissing', [
						'variable' => $rawApiKey,
					]),
			];
		}

		$api = Plugin::getInstance()->api;

		try {
			$lists = $api->getListsForApiKey($apiKey, true);

			return [
				'connected' => true,
				'message' => Craft::t('klaviyoconnect', 'settings.testConnection.connected', [
					'count' => count($lists),
				]),
			];
		} catch (ApiException $apiException) {
			return [
				'connected' => false,
				'message' => $api->errorMessage($apiException),
			];
		}
	}
}
