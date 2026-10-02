<?php

namespace fostercommerce\klaviyoconnect;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Order;
use craft\commerce\events\LineItemEvent;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\services\OrderHistories;
use craft\commerce\services\Payments;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\Request;
use craft\web\Response;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use fostercommerce\klaviyoconnect\fields\ListField;
use fostercommerce\klaviyoconnect\fields\ListsField;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\queue\jobs\TrackOrderComplete;
use fostercommerce\klaviyoconnect\services\Api;
use fostercommerce\klaviyoconnect\services\Cart;
use fostercommerce\klaviyoconnect\services\Map;
use fostercommerce\klaviyoconnect\services\Onsite;
use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\utilities\KCUtilities;
use fostercommerce\klaviyoconnect\variables\Variable;
use Throwable;
use yii\base\Event;

/**
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @property-read Settings $settings
 * @property-read Api $api
 * @property-read Track $track
 * @property-read Map $map
 * @property-read Onsite $onsite
 * @property-read Cart $cart
 */
class Plugin extends BasePlugin
{
	public bool $hasCpSettings = true;

	public function init(): void
	{
		parent::init();

		$this->setComponents([
			'api' => Api::class,
			'track' => Track::class,
			'map' => Map::class,
			'onsite' => Onsite::class,
			'cart' => Cart::class,
		]);

		/** @var Settings $settings */
		$settings = $this->getSettings();

		Craft::$app->onInit($this->redirectLegacyCartRestore(...));

		if ($settings->injectOnsiteScript && Craft::$app->getRequest()->getIsSiteRequest()) {
			Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, static function (TemplateEvent $event): void {
				if ($event->templateMode === View::TEMPLATE_MODE_SITE) {
					/** @var View $view */
					$view = $event->sender;
					self::getInstance()->onsite->registerScript($view);
				}
			});
		}

		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, static function (RegisterUrlRulesEvent $event): void {
			$event->rules['klaviyoconnect/sync-orders'] = 'klaviyoconnect/api/sync-orders';
		});

		Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, static function (RegisterComponentTypesEvent $event): void {
			$event->types[] = ListField::class;
			$event->types[] = ListsField::class;
		});

		if ($settings->trackSaveUser && array_filter($settings->klaviyoAvailableGroups) !== []) {
			Event::on(User::class, User::EVENT_AFTER_SAVE, static function (Event $event): void {
				try {
					self::getInstance()->track->onSaveUser($event);
				} catch (Throwable $throwable) {
					Craft::error('Klaviyo onSaveUser failed: ' . $throwable->getMessage(), 'klaviyoconnect');
				}
			});
		}

		if (Craft::$app->plugins->isPluginEnabled('commerce')) {
			Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITIES, static function (RegisterComponentTypesEvent $event): void {
				$event->types[] = KCUtilities::class;
			});

			// Register for site requests only, so CP and console order saves don't send Started Checkout
			if ($settings->trackCommerceStartedCheckout && Craft::$app->getRequest()->getIsSiteRequest()) {
				Event::on(Order::class, Order::EVENT_AFTER_SAVE, static function (Event $event): void {
					try {
						self::getInstance()->track->onStartedCheckout($event);
					} catch (Throwable $throwable) {
						Craft::error('Klaviyo onStartedCheckout failed: ' . $throwable->getMessage(), 'klaviyoconnect');
					}
				});
			}

			// Register for site requests only, so CP order edits don't send Added to Cart
			if ($settings->trackCommerceAddedToCart && Craft::$app->getRequest()->getIsSiteRequest()) {
				Event::on(Order::class, Order::EVENT_AFTER_APPLY_ADD_LINE_ITEM, static function (LineItemEvent $event): void {
					try {
						self::getInstance()->track->onAddedToCart($event);
					} catch (Throwable $throwable) {
						Craft::error('Klaviyo onAddedToCart failed: ' . $throwable->getMessage(), 'klaviyoconnect');
					}
				});
			}

			if ($settings->trackCommerceCartUpdated) {
				Event::on(Order::class, Order::EVENT_AFTER_SAVE, static function (Event $event): void {
					try {
						self::getInstance()->track->onCartUpdated($event);
					} catch (Throwable $throwable) {
						Craft::error('Klaviyo onCartUpdated failed: ' . $throwable->getMessage(), 'klaviyoconnect');
					}
				});
			}

			if ($settings->trackCommerceOrderCompleted) {
				Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, static function (Event $event): void {
					/** @var Order $order */
					$order = $event->sender;
					Craft::$app->getQueue()->delay(10)->push(new TrackOrderComplete([
						'name' => $event->name,
						'orderId' => $order->id,
					]));
				});
			}

			if ($settings->trackCommerceStatusUpdated) {
				Event::on(
					OrderHistories::class,
					OrderHistories::EVENT_ORDER_STATUS_CHANGE,
					static function (OrderStatusEvent $event): void {
						try {
							self::getInstance()->track->onStatusChanged($event);
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
					static function (RefundTransactionEvent $event): void {
						try {
							self::getInstance()->track->onOrderRefunded($event);
						} catch (Throwable $throwable) {
							Craft::error('Klaviyo onOrderRefunded failed: ' . $throwable->getMessage(), 'klaviyoconnect');
						}
					}
				);
			}
		}

		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, static function (Event $event): void {
			/** @var CraftVariable $variable */
			$variable = $event->sender;
			$variable->set('klaviyoConnect', Variable::class);
		});
	}

	public function getApi(): Api
	{
		/** @var Api $api */
		$api = $this->get('api');

		return $api;
	}

	public function getTrack(): Track
	{
		/** @var Track $track */
		$track = $this->get('track');

		return $track;
	}

	public function getMap(): Map
	{
		/** @var Map $map */
		$map = $this->get('map');

		return $map;
	}

	public function getOnsite(): Onsite
	{
		/** @var Onsite $onsite */
		$onsite = $this->get('onsite');

		return $onsite;
	}

	public function getCart(): Cart
	{
		/** @var Cart $cart */
		$cart = $this->get('cart');

		return $cart;
	}

	protected function settingsHtml(): ?string
	{
		$settings = $this->getSettings();

		// Prefill the Sites table from project config, so saving doesn't copy in a config file's rows
		$savedSiteSettings = Craft::$app->getProjectConfig()->get('plugins.klaviyoconnect.settings.siteSettings');
		$savedSiteSettings = is_array($savedSiteSettings) ? ProjectConfigHelper::unpackAssociativeArray($savedSiteSettings) : [];

		return Craft::$app->getView()->renderTemplate('klaviyoconnect/settings', [
			'settings' => $settings,
			// Keep the posted rows after a failed save
			'savedSiteSettings' => $settings->hasErrors() ? $settings->siteSettings : $savedSiteSettings,
		]);
	}

	protected function createSettingsModel(): ?Model
	{
		return new Settings();
	}

	/**
	 * Redirects cart restore links that use the Klaviyo Connect Plus handle.
	 */
	private function redirectLegacyCartRestore(): void
	{
		$request = Craft::$app->getRequest();
		if (! $request instanceof Request || $request->getActionSegments() !== ['klaviyo-connect-plus', 'cart', 'restore']) {
			return;
		}

		$restoreUrl = UrlHelper::actionUrl('klaviyoconnect/cart/restore', [
			'number' => $request->getQueryParam('number'),
		]);
		/** @var Response $response */
		$response = Craft::$app->getResponse();
		$response->redirect($restoreUrl);

		Craft::$app->end();
	}
}
