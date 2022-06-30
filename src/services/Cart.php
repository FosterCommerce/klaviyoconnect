<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use craft\commerce\Plugin as Commerce;
use yii\web\HttpException;

class Cart extends Base
{
    
    /**
     * restore.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$number	
     * @return	mixed
     */
    public function restore($number)
    {
        $commerceInstance = Commerce::getInstance();

        $order = $commerceInstance->orders->getOrderByNumber($number);

        if (!$order) {
            throw new HttpException(404);
        }
        
        $commerceInstance->carts->forgetCart();
        $cartNumber = $order->number;
        $session = Craft::$app->getSession();
        $session->set('commerce_cart', $cartNumber);
        return $cartNumber;
    }
}
