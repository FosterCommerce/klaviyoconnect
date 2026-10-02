<?php

namespace fostercommerce\klaviyoconnect;

use Craft;
use craft\base\Model;
use craft\commerce\elements\Order;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\services\OrderHistories;
use craft\commerce\services\Payments;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\queue\jobs\TrackOrderComplete;
use fostercommerce\klaviyoconnect\utilities\KCUtilities;
use fostercommerce\klaviyoconnect\variables\Variable;
use Throwable;
use yii\base\Event;

/**
 * @package fostercommerce\klaviyoconnect
 * @property \fostercommerce\klaviyoconnect\services\Api $api
 * @property \fostercommerce\klaviyoconnect\services\Track $track
 * @property \fostercommerce\klaviyoconnect\services\Map $map
 */
class Plugin extends \craft\base\Plugin
{
	public bool $hasCpSettings = true;

	public function init(): void
	{
		parent::init();

		// Set the base template directory for the plugin
		Craft::setAlias('@klaviyoconnect', $this->getBasePath());

		$this->setComponents([
			'api' => \fostercommerce\klaviyoconnect\services\Api::class,
			'track' => \fostercommerce\klaviyoconnect\services\Track::class,
			'map' => \fostercommerce\klaviyoconnect\services\Map::class,
		]);

		/** @var Settings $settings */
		$settings = $this->getSettings();

		// TODO: Fix this page - the date range picker is not working
		// Event::on(
		// 	Utilities::class,
		// 	Utilities::EVENT_REGISTER_UTILITIES,
		// 	static function(RegisterComponentTypesEvent $event): void {
		// 		$event->types[] = KCUtilities::class;
		// 	}
		// );

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			static function (RegisterUrlRulesEvent $event): void {
				$event->rules['klaviyoconnect/sync-orders'] = 'klaviyoconnect/api/sync-orders';
			}
		);

		Event::on(
			\craft\web\Application::class,
			\craft\web\Application::EVENT_INIT,
			static function (): void {
				$request = Craft::$app->getRequest();

				if (! $request->getIsConsoleRequest()) {
					$path = $request->getPathInfo();

					// Redirect old plugin handle URLs to new one
					if (str_starts_with($path, 'actions/klaviyoconnect/cart/restore') ||
						str_starts_with($path, 'actions/klaviyoconnect/cart/restore')) {
						$number = $request->getParam('number');
						$newUrl = \craft\helpers\UrlHelper::actionUrl('klaviyoconnect/cart/restore', [
							'number' => $number,
						]);

						Craft::$app->getResponse()->redirect($newUrl)->send();
						Craft::$app->end();
					}
				}
			}
		);

		Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, static function (RegisterComponentTypesEvent $event): void {
			$event->types[] = \fostercommerce\klaviyoconnect\fields\ListField::class;
			$event->types[] = \fostercommerce\klaviyoconnect\fields\ListsField::class;
		});

		if ($settings->trackSaveUser) {
			Event::on(User::class, User::EVENT_AFTER_SAVE, static function (Event $event): void {
				try {
					self::getInstance()->track->onSaveUser($event);
				} catch (Throwable $throwable) {
					Craft::error('Klaviyo onSaveUser failed: ' . $throwable->getMessage(), 'klaviyoconnect');
				}
			});
		}

		if (Craft::$app->plugins->isPluginEnabled('commerce')) {
			if ($settings->trackCommerceCartUpdated) {
				Event::on(Order::class, Order::EVENT_AFTER_SAVE, static function (Event $e): void {
					try {
						self::getInstance()->track->onCartUpdated($e);
					} catch (Throwable $throwable) {
						Craft::error('Klaviyo onCartUpdated failed: ' . $throwable->getMessage(), 'klaviyoconnect');
					}
				});
			}

			if ($settings->trackCommerceOrderCompleted) {
				Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, static function (Event $e): void {
					/** @var Order $order */
					$order = $e->sender;
					Craft::$app->getQueue()->delay(10)->push(new TrackOrderComplete([
						'name' => $e->name,
						'orderId' => $order->id,
					]));
				});
			}

			if ($settings->trackCommerceStatusUpdated) {
				Event::on(
					OrderHistories::class,
					OrderHistories::EVENT_ORDER_STATUS_CHANGE,
					static function (OrderStatusEvent $e): void {
						try {
							self::getInstance()->track->onStatusChanged($e);
						} catch (Throwable $throwable) {
							Craft::error('Klaviyo onStatusChanged failed: ' . $throwable->getMessage(), 'klaviyoconnect');
						}
					}
				);
			}

			if ($settings->trackCommerceRefunded) {
				Event::on(
					Payments::class,
					Payments::EVENT_AFTER_REFUND_TRANSACTION,
					static function (RefundTransactionEvent $e): void {
						try {
							self::getInstance()->track->onOrderRefunded($e);
						} catch (Throwable $throwable) {
							Craft::error('Klaviyo onOrderRefunded failed: ' . $throwable->getMessage(), 'klaviyoconnect');
						}
					}
				);
			}
		}

		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, static function (Event $event): void {
			$variable = $event->sender;
			$variable->set('klaviyoconnect', Variable::class);
		});
	}

	protected function settingsHtml(): ?string
	{
		return Craft::$app->getView()->renderTemplate('klaviyoconnect/settings', [
			'settings' => $this->getSettings(),
		]);
	}

	protected function createSettingsModel(): ?Model
	{
		return new Settings();
	}
}
