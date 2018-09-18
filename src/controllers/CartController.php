<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use craft\web\Controller;

class CartController extends Controller
{
    protected $allowAnonymous = true;

    public function actionRestore()
    {
        $number = Craft::$app->getRequest()->getParam('number');
        Plugin::getInstance()->cart->restore($number);
        $this->redirect('/store/cart');
    }
}