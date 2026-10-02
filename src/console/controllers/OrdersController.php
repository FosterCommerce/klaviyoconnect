<?php

namespace fostercommerce\klaviyoconnect\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use DateTime;
use DateTimeZone;
use fostercommerce\klaviyoconnect\Plugin;
use yii\console\ExitCode;

/**
 * Sends historical orders to Klaviyo.
 */
class OrdersController extends Controller
{
	/**
	 * @var string|null The first day to send orders from, such as 2025-01-01.
	 */
	public ?string $from = null;

	/**
	 * @var string|null The last day to send orders from, such as 2025-12-31.
	 */
	public ?string $to = null;

	public function options($actionID): array
	{
		return [...parent::options($actionID), 'from', 'to'];
	}

	/**
	 * Queues Placed Order and Ordered Product events for completed orders placed between --from and --to.
	 */
	public function actionSync(): int
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
			$this->stderr('Craft Commerce must be installed and enabled to send orders.' . PHP_EOL, Console::FG_RED);

			return ExitCode::UNAVAILABLE;
		}

		$fromDate = $this->parseDate($this->from);
		$toDate = $this->parseDate($this->to);
		if ($fromDate === false || $toDate === false) {
			$this->stderr('Both --from and --to are required, as dates such as --from=2025-01-01 --to=2025-12-31.' . PHP_EOL, Console::FG_RED);

			return ExitCode::USAGE;
		}

		if ($fromDate > $toDate) {
			$this->stderr('--from must be on or before --to.' . PHP_EOL, Console::FG_RED);

			return ExitCode::USAGE;
		}

		$orderCount = Plugin::getInstance()->track->queueOrderSync($fromDate, $toDate);
		$this->stdout(Craft::t('klaviyoconnect', 'sync.queued', [
			'count' => $orderCount,
		]) . PHP_EOL, Console::FG_GREEN);

		return ExitCode::OK;
	}

	/**
	 * Accepts only real YYYY-MM-DD dates, since looser parsing rolls 2025-02-30 into March.
	 */
	private function parseDate(?string $value): DateTime|false
	{
		if ($value === null) {
			return false;
		}

		$date = DateTime::createFromFormat('!Y-m-d', $value, new DateTimeZone(Craft::$app->getTimeZone()));

		return $date !== false && $date->format('Y-m-d') === $value ? $date : false;
	}
}
