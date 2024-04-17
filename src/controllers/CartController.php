<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\web\Controller;
use fostercommerce\klaviyoconnect\Plugin;
use yii\web\HttpException;
use yii\web\Response;

class CartController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public function actionRestore(): Response
    {
        if (Craft::$app->plugins->isPluginEnabled('commerce')) {
            $number = Craft::$app->getRequest()->getParam('number');
            Plugin::getInstance()->cart->restore($number);
            $cartUrl = Plugin::getInstance()->settings->cartUrl;
            if (strlen($cartUrl) === 0) {
                throw new HttpException(400, 'Cart URL is required. Settings -> Klaviyo Connect -> Cart URL');
            }
            return $this->redirect($cartUrl);
        }

        throw new HttpException(400, 'Craft Commerce needs to be installed and enabled to restore carts.');
    }
}
