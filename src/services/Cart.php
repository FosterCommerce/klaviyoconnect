<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use craft\commerce\Plugin as CommercePlugin;
use yii\web\HttpException;

abstract class Cart extends Base
{
    public function restore($number)
    {
        $commerceInstance = CommercePlugin::getInstance();

        $order = $commerceInstance->orders->getOrderByNumber($number);

        if ($order) {
            $commerceInstance->carts->forgetCart();
            $cartNumber = $order->number;
            $session = Craft::$app->getSession();
            $session->set('commerce_cart', $cartNumber);
            return $cartNumber;
        }

        throw new HttpException(404);
    }
}


