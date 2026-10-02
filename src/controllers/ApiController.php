<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use DateTime;
use fostercommerce\klaviyoconnect\helpers\EmailAddress;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\queue\jobs\SyncOrders;
use fostercommerce\klaviyoconnect\utilities\KCUtilities;
use Throwable;
use yii\web\Response as YiiResponse;

class ApiController extends Controller
{
	protected array|int|bool $allowAnonymous = true;

	/**
	 * Resolved once per request, so an invalid email logs one warning.
	 */
	private ?string $postedEmail = null;

	private bool $hasResolvedPostedEmail = false;

	public function actionTrack(): mixed
	{
		$this->requirePostRequest();

		try {
			$this->identify();
		} catch (Throwable $throwable) {
			Craft::error('Klaviyo identify failed: ' . $throwable->getMessage(), 'klaviyoconnect');
		}

		try {
			$this->trackEvent();
		} catch (Throwable $throwable) {
			Craft::error('Klaviyo trackEvent failed: ' . $throwable->getMessage(), 'klaviyoconnect');
		}

		try {
			$this->addProfileToLists();
		} catch (Throwable $throwable) {
			Craft::error('Klaviyo addProfileToLists failed: ' . $throwable->getMessage(), 'klaviyoconnect');
		}

		return $this->forwardOrRedirect();
	}

	/**
	 * @deprecated in 7.3.0. Use the Klaviyo Connect utility or the `klaviyoconnect/orders/sync` command instead.
	 */
	public function actionSyncOrders(): ?YiiResponse
	{
		// Require the utility permission, since the action resends every order in the range
		$this->requirePermission('utility:' . KCUtilities::id());

		Craft::$app->getDeprecator()->log('klaviyoconnect/api/sync-orders', 'The `klaviyoconnect/api/sync-orders` action has been deprecated. Use the Klaviyo Connect utility or the `klaviyoconnect/orders/sync` command instead.');

		$start = $this->request->getQueryParam('start');
		$end = $this->request->getQueryParam('end');
		if (! Craft::$app->getPlugins()->isPluginEnabled('commerce') || ! is_numeric($start) || ! is_numeric($end)) {
			return null;
		}

		$orderIds = Order::find()->isCompleted()->dateCreated(['and', ">= {$start}", "<= {$end}"])->ids();
		foreach ($orderIds as $orderId) {
			Craft::$app->getQueue()->push(new SyncOrders([
				'orderId' => $orderId,
			]));
		}

		return null;
	}

	public function actionIdentify(): ?YiiResponse
	{
		$this->requirePostRequest();

		try {
			$this->identify();
		} catch (Throwable $throwable) {
			Craft::error('Klaviyo identify failed: ' . $throwable->getMessage(), 'klaviyoconnect');
		}

		return $this->forwardOrRedirect();
	}

	private function trackEvent(): void
	{
		$event = $this->request->getParam('event');
		if (! is_array($event)) {
			return;
		}

		// Reject input Klaviyo would refuse, so visitors can't fill the queue with failing jobs
		if (! is_string($event['name'] ?? null) || trim($event['name']) === '') {
			Craft::warning('Skipping event tracking; An event name is required.', __METHOD__);

			return;
		}

		$timestamp = null;
		if (array_key_exists('timestamp', $event)) {
			$timestampDate = is_string($event['timestamp']) ? DateTimeHelper::toDateTime($event['timestamp']) : false;
			$timestamp = $timestampDate === false || ! $this->isTimestampInRange($timestampDate) ? null : $timestampDate->format(DATE_ATOM);
			unset($event['timestamp']);
		}

		if (! array_key_exists('trackOrder', $event)) {
			Plugin::getInstance()->track->trackEvent(
				$event['name'],
				$this->mapProfile(),
				$this->eventProperties($event),
				$timestamp,
			);

			return;
		}

		if (! Craft::$app->plugins->isPluginEnabled('commerce')) {
			Craft::warning('Skipping order tracking; Craft Commerce needs to be installed and enabled to track order events.', __METHOD__);

			return;
		}

		$profile = $this->mapProfile();
		$order = $this->resolveOrder($event);

		// Warn rather than error, since any visitor can post an order that isn't theirs or doesn't exist
		if (! $order instanceof Order || $order->id === null) {
			Craft::warning('Skipping order tracking; the order could not be found.', __METHOD__);

			return;
		}

		Plugin::getInstance()->track->trackOrder($event['name'], $order, $profile, $timestamp, uniqueId: is_string($event['unique_id'] ?? null) && $event['unique_id'] !== '' ? $event['unique_id'] : null);
	}

	/**
	 * @param array<mixed> $event
	 */
	private function resolveOrder(array $event): ?Order
	{
		if (array_key_exists('orderNumber', $event)) {
			$order = is_string($event['orderNumber']) ? Order::find()->number($event['orderNumber'])->one() : null;

			return $order instanceof Order ? $order : null;
		}

		// Load the cart only when the visitor has one, since getCart() otherwise creates one and sets its cookie
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$cartsService = $commerce->getCarts();
		$cart = $cartsService->getHasSessionCartNumber() ? $cartsService->getCart() : null;
		if (! array_key_exists('orderId', $event)) {
			return $cart;
		}

		if ($cart !== null && (string) $cart->id === $event['orderId']) {
			return $cart;
		}

		// Accept another order's ID only for the customer's own orders, since IDs are guessable
		$currentUserId = Craft::$app->getUser()->getId();
		if ($currentUserId === null) {
			return null;
		}

		$order = Order::find()
			->id($event['orderId'])
			->customerId($currentUserId)
			->one();

		return $order instanceof Order ? $order : null;
	}

	private function addProfileToLists(): void
	{
		$lists = [];
		$request = $this->request;

		$listId = $request->getParam('list');
		$listIds = $request->getParam('lists');
		if (is_string($listId) && $listId !== '') {
			$lists[] = $listId;
		} elseif (is_array($listIds)) {
			$lists = array_values(array_filter($listIds, static fn (mixed $listId): bool => is_string($listId) && $listId !== ''));
		}

		if ($lists !== []) {
			$profile = $this->mapProfile();
			$subscribe = (bool) $request->getParam('subscribe');

			// Subscribe only the channels the form posted, since a logged-in user's account email isn't consent to email marketing
			$profileParams = $request->getParam('profile');
			$postedPhone = is_array($profileParams) ? ($profileParams['phone_number'] ?? null) : null;
			$consentChannels = array_keys(array_filter([
				'email' => $this->postedEmail() !== null,
				'sms' => is_string($postedPhone) && $postedPhone !== '',
			]));

			Plugin::getInstance()->track->addToLists($lists, $profile, $subscribe, $consentChannels);
		}
	}

	private function identify(): void
	{
		$profile = $this->mapProfile();
		try {
			Plugin::getInstance()->track->identifyUser($profile);
		} catch (Throwable $throwable) {
			// Swallow. Klaviyo flake/outage must not break host request flow.
			Craft::error('Klaviyo identifyUser failed: ' . $throwable->getMessage(), 'klaviyoconnect');
		}
	}

	/**
	 * @param array<mixed> $event
	 */
	private function eventProperties(array $event): EventProperties
	{
		$eventProperties = new EventProperties();
		foreach ($event as $key => $value) {
			if ($key === 'unique_id') {
				$eventProperties->unique_id = is_scalar($value) && (string) $value !== '' ? (string) $value : null;
			} elseif ($key === 'value') {
				$eventProperties->value = is_numeric($value) ? (string) $value : null;
			} elseif ($key === 'value_currency') {
				// Drop a currency that isn't an ISO 4217 code, which Klaviyo rejects
				$eventProperties->value_currency = is_string($value) && preg_match('/^[A-Z]{3}$/', $value) === 1 ? $value : null;
			} else {
				$eventProperties->addCustomProperty((string) $key, $value);
			}
		}

		return $eventProperties;
	}

	private function forwardOrRedirect(): ?YiiResponse
	{
		$request = $this->request;
		$forwardRoute = $request->getParam('forward');
		$route = is_string($forwardRoute) ? trim($forwardRoute, '/') : '';

		// Forward only outside this plugin, since a forwarded plugin action would forward again and repeat its sends
		$controllerAndAction = $route === '' ? false : Craft::$app->createController($route);
		if ($controllerAndAction !== false && $controllerAndAction[0]->module !== $this->module) {
			$response = Craft::$app->runAction($route);

			return $response instanceof YiiResponse ? $response : null;
		}

		if ($request->isAjax || $request->getAcceptsJson()) {
			return $this->asJson('success');
		}

		return $this->redirectToPostedUrl();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapProfile(): array
	{
		$request = $this->request;
		/** @var array<string, mixed> $profileParams */
		$profileParams = is_array($request->getParam('profile')) ? $request->getParam('profile') : [];

		unset($profileParams['email']);
		$postedEmail = $this->postedEmail();
		if ($postedEmail !== null) {
			$profileParams['email'] = $postedEmail;
		}

		$currentUser = Craft::$app->getUser()->getIdentity();

		if ($currentUser) {
			return array_merge(
				Plugin::getInstance()->map->mapUser($currentUser),
				$profileParams
			);
		}

		return $profileParams;
	}

	/**
	 * Returns the first valid posted email, preferring `profile[email]`, so the profile and the list consent use the same address.
	 */
	private function postedEmail(): ?string
	{
		if ($this->hasResolvedPostedEmail) {
			return $this->postedEmail;
		}

		$this->hasResolvedPostedEmail = true;
		$profileParams = $this->request->getParam('profile');
		$postedEmails = [is_array($profileParams) ? ($profileParams['email'] ?? null) : null, $this->request->getParam('email')];
		foreach ($postedEmails as $postedEmail) {
			// Skip an email Klaviyo would reject, so visitors can't queue sends that are certain to fail
			if (EmailAddress::isValid($postedEmail)) {
				return $this->postedEmail = trim((string) $postedEmail);
			}

			if ($postedEmail !== null && $postedEmail !== '') {
				Craft::warning("Klaviyo Connect skipped a posted email, since it isn't a valid address.", 'klaviyoconnect');
			}
		}

		return null;
	}

	/**
	 * Klaviyo rejects event times before 1990 or more than a year ahead.
	 */
	private function isTimestampInRange(DateTime $timestampDate): bool
	{
		return $timestampDate >= new DateTime('1990-01-01') && $timestampDate <= new DateTime('+1 year');
	}
}
