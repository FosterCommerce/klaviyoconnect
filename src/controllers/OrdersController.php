<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use DateTime;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\utilities\KCUtilities;
use yii\web\Response;

class OrdersController extends Controller
{
	public function actionSync(): ?Response
	{
		$this->requirePostRequest();
		$this->requirePermission('utility:' . KCUtilities::id());

		if (! Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
			return $this->asFailure(Craft::t('klaviyoconnect', 'sync.error.commerceRequired'));
		}

		$fromDate = $this->dateParam('fromDate');
		$toDate = $this->dateParam('toDate');
		if (! $fromDate instanceof DateTime || ! $toDate instanceof DateTime) {
			return $this->asFailure(Craft::t('klaviyoconnect', 'sync.error.datesRequired'));
		}

		if ($fromDate > $toDate) {
			return $this->asFailure(Craft::t('klaviyoconnect', 'sync.error.datesReversed'));
		}

		$orderCount = Plugin::getInstance()->track->queueOrderSync($fromDate, $toDate);

		return $this->asSuccess(Craft::t('klaviyoconnect', 'sync.queued', [
			'count' => $orderCount,
		]));
	}

	private function dateParam(string $name): ?DateTime
	{
		$value = $this->request->getBodyParam($name);

		// Accept only the date fields' arrays, since a bare number would parse as a Unix timestamp
		if (! is_array($value)) {
			return null;
		}

		$date = DateTimeHelper::toDateTime($value, assumeSystemTimeZone: true);

		// Reject a date that doesn't exist, such as Feb 30, which the parser rolls into the next month
		$parseWarnings = DateTime::getLastErrors()['warning_count'] ?? 0;

		return $date === false || $parseWarnings > 0 ? null : $date;
	}
}
