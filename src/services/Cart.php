<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\commerce\Plugin as Commerce;
use yii\web\HttpException;

class Cart extends Base
{
    /**
     * @param string $number Order number to restore
     */
    public function restore(string $number): ?string
    {
        $commerceInstance = Commerce::getInstance();

        $order = $commerceInstance->orders->getOrderByNumber($number);

        if ($order === null) {
            throw new HttpException(404);
        }

        $commerceInstance->carts->forgetCart();
        $cartNumber = $order->number;
        $session = Craft::$app->getSession();
        $session->set('commerce_cart', $cartNumber);
        return $cartNumber;
    }
}
