<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\helpers\App;
use craft\web\Controller;
use craft\web\View;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use fostercommerce\klaviyoconnect\Plugin;
use KlaviyoAPI\ApiException;
use yii\web\Response;

class SettingsController extends Controller
{
	public function actionTestConnection(): Response
	{
		$this->requireAdmin(false);
		$this->requirePostRequest();
		$this->requireAcceptsJson();

		/** @var array{siteSettings?: array<string, array{klaviyoApiKey?: string, klaviyoAvailableLists?: string|string[]}>} $settings */
		$settings = $this->request->getBodyParam('settings', []);
		// Test a blank row against the config file key, since saving the form clears the stored key
		$configFileSettings = Craft::$app->getConfig()->getConfigFromFile('klaviyoconnect');
		$configFileApiKey = is_array($configFileSettings) && isset($configFileSettings['klaviyoApiKey']) ? Plugin::getInstance()->getSettings()->klaviyoApiKey : '';

		$results = [];
		$siteListRows = [];
		$keyedSiteFailed = false;
		$sites = Craft::$app->getSites()->getAllSites(true);
		foreach ($sites as $site) {
			$formSiteSettings = $settings['siteSettings'][$site->uid] ?? [];
			$formApiKey = is_string($formSiteSettings['klaviyoApiKey'] ?? null) ? trim($formSiteSettings['klaviyoApiKey']) : '';
			$rawApiKey = $formApiKey === '' ? $configFileApiKey : $formApiKey;
			$apiKey = trim((string) App::parseEnv($rawApiKey));

			// Report an env var that isn't set, rather than treating the site as having no key
			$test = $rawApiKey !== '' && $apiKey === ''
				? [
					'connected' => false,
					'message' => Craft::t('klaviyoconnect', 'settings.testConnection.envMissing', [
						'variable' => $rawApiKey,
					]),
					'lists' => null,
				]
				: $this->testApiKey($apiKey);

			// A site without a key sends no data, so its failed check leaves the last error in place
			$keyedSiteFailed = $keyedSiteFailed || ($rawApiKey !== '' && ! $test['connected']);

			$results[] = [
				'label' => $site->getUiLabel(),
				'connected' => $test['connected'],
				'message' => $test['message'],
			];

			// Render the form's checked lists, so unsaved choices are kept
			$siteListRows[] = [
				'site' => $site,
				'lists' => $test['lists'],
				'error' => $test['connected'] ? null : $test['message'],
				'values' => $formSiteSettings['klaviyoAvailableLists'] ?? [],
			];
		}

		$view = Craft::$app->getView();
		$siteListsHtml = $view->namespaceInputs(fn (): string => $view->renderTemplate('klaviyoconnect/_site-lists', [
			'siteListRows' => $siteListRows,
		], View::TEMPLATE_MODE_CP), 'settings');

		if (! $keyedSiteFailed) {
			Plugin::getInstance()->api->clearLastError();
		}

		return $this->asJson([
			'results' => $results,
			'lastErrorCleared' => ! $keyedSiteFailed,
			'siteListsHtml' => $siteListsHtml,
		]);
	}

	/**
	 * @return array{connected: bool, message: string, lists: KlaviyoList[]|null}
	 */
	private function testApiKey(string $apiKey): array
	{
		if ($apiKey === '') {
			return [
				'connected' => false,
				'message' => Craft::t('klaviyoconnect', 'settings.testConnection.noKey'),
				'lists' => null,
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
				'lists' => $lists,
			];
		} catch (ApiException $apiException) {
			return [
				'connected' => false,
				'message' => $api->errorMessage($apiException),
				'lists' => null,
			];
		}
	}
}
