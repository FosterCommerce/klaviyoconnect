<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\User;
use craft\web\View;
use fostercommerce\klaviyoconnect\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CartController extends Controller
{
	protected array|int|bool $allowAnonymous = true;

	/**
	 * @throws BadRequestHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionRestore(): Response
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('commerce')) {
			throw new BadRequestHttpException(Craft::t('klaviyoconnect', 'cart.error.commerceRequired'));
		}

		$number = $this->request->getQueryParam('number');
		if (! is_string($number) || $number === '') {
			throw new BadRequestHttpException(Craft::t('klaviyoconnect', 'cart.error.numberRequired'));
		}

		// Log out from the login page's form, then reload this link, since Craft's logout action ignores a posted redirect
		if ($this->request->getIsPost() && $this->request->getBodyParam('logOut')) {
			/** @var User $user */
			$user = Craft::$app->getUser();
			$user->logout(false);

			return $this->redirect($this->request->getAbsoluteUrl());
		}

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$order = $commerce->getOrders()->getOrderByNumber($number);
		if ($order === null) {
			throw new NotFoundHttpException(Craft::t('klaviyoconnect', 'cart.error.notFound'));
		}

		if ($order->isCompleted) {
			throw new BadRequestHttpException(Craft::t('klaviyoconnect', 'cart.error.completed'));
		}

		// Restore on the order's site, since the cart cookie belongs to that site's store and domain
		$currentSiteId = Craft::$app->getSites()->getCurrentSite()->id;
		if ($order->orderSiteId !== null && $order->orderSiteId !== $currentSiteId) {
			// Redirect once, since a disabled order site or a pinned CRAFT_SITE never becomes the current site
			if (! $this->request->getQueryParam('siteRedirected')) {
				return $this->redirect(UrlHelper::urlWithParams(Plugin::getInstance()->cart->restoreUrl($order), [
					'siteRedirected' => 1,
				]));
			}

			// Restore on this site only for the same store, since another store's cart number breaks this site's cart
			if ($order->storeId !== $commerce->getStores()->getCurrentStore()->id) {
				throw new BadRequestHttpException(Craft::t('klaviyoconnect', 'cart.error.otherStore'));
			}
		}

		$cartUrl = Plugin::getInstance()->getSettings()->getCartUrl($currentSiteId);
		if ($cartUrl === '') {
			throw new BadRequestHttpException(Craft::t('klaviyoconnect', 'cart.error.cartUrlMissing'));
		}

		$currentUser = Craft::$app->getUser()->getIdentity();
		$cartCustomer = $order->getCustomer();

		// Block taking over another customer's cart, and require login for a cart that belongs to a user account
		if ($currentUser !== null && $cartCustomer !== null && $cartCustomer->id !== $currentUser->id) {
			return $this->renderLoginRequired(Craft::t('klaviyoconnect', 'cart.error.belongsToOther'), true);
		}

		if ($currentUser === null && $cartCustomer?->getIsCredentialed()) {
			return $this->renderLoginRequired(Craft::t('klaviyoconnect', 'cart.error.loginRequired'), false);
		}

		$cartsService = $commerce->getCarts();
		$cartsService->forgetCart();
		$cartsService->setSessionCartNumber($number);

		Craft::$app->getSession()->setNotice(Craft::t('klaviyoconnect', 'cart.restored'));

		return $this->redirect(UrlHelper::siteUrl($cartUrl, siteId: $currentSiteId));
	}

	private function renderLoginRequired(string $message, bool $logOutFirst): Response
	{
		$restoreUrl = $this->request->getAbsoluteUrl();

		/** @var User $user */
		$user = Craft::$app->getUser();
		// Send the owner back to this restore link after login
		$user->setReturnUrl($restoreUrl);

		/** @var string $loginPath */
		$loginPath = Craft::$app->getConfig()->getGeneral()->getLoginPath();

		// Link a logged-in user through logout and back to this restore link
		return $this->renderTemplate('klaviyoconnect/login-required', [
			'loginUrl' => UrlHelper::url($loginPath),
			'logOutFirst' => $logOutFirst,
			'message' => $message,
		], View::TEMPLATE_MODE_CP);
	}
}
