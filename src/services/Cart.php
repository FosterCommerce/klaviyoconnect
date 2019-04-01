<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use craft\commerce\Plugin as Commerce;
use yii\web\HttpException;

class Cart extends Base
{
    public function restore($number)
    {
        $commerceInstance = Commerce::getInstance();

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
