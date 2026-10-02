<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\UrlHelper;
use yii\base\Component;
use yii\web\NotFoundHttpException;

class Cart extends Component
{
	public function restoreUrl(Order $order): string
	{
		return UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->actionTrigger . '/klaviyoconnect/cart/restore', [
			'number' => $order->number,
		], siteId: $order->orderSiteId);
	}

	/**
	 * @param string $number Order number to restore
	 * @throws NotFoundHttpException
	 * @deprecated in 7.3.0. Link to the `klaviyoconnect/cart/restore` action instead.
	 */
	public function restore(string $number): ?string
	{
		Craft::$app->getDeprecator()->log(__METHOD__, '`Cart::restore()` has been deprecated. Link to the `klaviyoconnect/cart/restore` action instead.');

		/** @var Commerce $commerceInstance */
		$commerceInstance = Commerce::getInstance();

		$order = $commerceInstance->orders->getOrderByNumber($number);

		if ($order === null) {
			throw new NotFoundHttpException();
		}

		$cartsService = $commerceInstance->getCarts();
		$cartsService->forgetCart();
		$cartsService->setSessionCartNumber($number);

		return $order->number;
	}
}
