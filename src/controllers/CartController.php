<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use craft\web\Controller;
use yii\web\HttpException;

class CartController extends Controller
{
    protected $allowAnonymous = true;

    public function actionRestore()
    {
        $number = Craft::$app->getRequest()->getParam('number');
        Plugin::getInstance()->cart->restore($number);
        $cartUrl = Plugin::getInstance()->settings->cartUrl;
        if (strlen($cartUrl) === 0) {
            throw new HttpException(400, 'Cart URL is required. Settings -> Klaviyo Connect -> Cart URL');
        }
        return $this->redirect($cartUrl);
    }
}
