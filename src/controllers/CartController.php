<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\web\Controller;
use fostercommerce\klaviyoconnect\models\Settings;
use fostercommerce\klaviyoconnect\Plugin;
use yii\web\HttpException;
use yii\web\Response;

class CartController extends Controller
{
	protected array|int|bool $allowAnonymous = true;

	public function actionRestore(): Response
	{
		$number = Craft::$app->getRequest()->getParam('number');

		if (! $number) {
			throw new HttpException(400, 'Cart number is required');
		}

		$commerce = Commerce::getInstance();
		$order = $commerce->getOrders()->getOrderByNumber($number);

		if (! $order) {
			throw new HttpException(404, 'Cart not found');
		}

		if ($order->isCompleted) {
			throw new HttpException(400, 'Cannot restore a completed order');
		}

		// Get current user
		$currentUser = Craft::$app->getUser()->getIdentity();
		$currentUserId = $currentUser ? $currentUser->id : null;

		// Check if cart belongs to a CREDENTIALED user account (matches Commerce's behavior)
		$cartCustomer = $order->getCustomer();

		// If no one is logged in, or wrong user is logged in
		if ($cartCustomer && $cartCustomer->getIsCredentialed() && (! $currentUserId || $order->customerId !== $currentUserId)) {
			// Show message page using CP template mode
			$loginPath = Craft::$app->getConfig()->getGeneral()->getLoginPath();
			$loginUrl = \craft\helpers\UrlHelper::url($loginPath, [
				'return' => Craft::$app->getRequest()->getAbsoluteUrl(),
			]);
			$view = Craft::$app->getView();
			$oldMode = $view->getTemplateMode();
			$view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
			$html = $view->renderTemplate('klaviyoconnect/login-required', [
				'loginUrl' => $loginUrl,
				'message' => Craft::t('klaviyoconnect', 'This cart belongs to a user account. Please log in to view it.'),
			]);
			$view->setTemplateMode($oldMode);
			return $this->asRaw($html);
		}

		// At this point, one of these is true:
		// - Cart has no customer (guest cart)
		// - Cart has non-credentialed customer (guest with email only)
		// - Current user owns the cart (credentialed customer)

		// Clear current cart and restore using Commerce 5's API
		$cartsService = $commerce->getCarts();
		$cartsService->forgetCart();
		$cartsService->setSessionCartNumber($order->number);

		$session = Craft::$app->getSession();
		$session->setNotice(Craft::t('klaviyoconnect', 'Your cart has been restored.'));

		/** @var Settings $settings */
		$settings = Plugin::getInstance()->getSettings();
		$cartUrl = $settings->cartUrl;

		if ((string) $cartUrl === '') {
			throw new HttpException(400, 'Cart URL is not configured. Please set it in Settings → Klaviyo Connect Plus → Cart URL.');
		}

		return $this->redirect($cartUrl);
	}
}
