<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\models\EventProperties;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use yii\base\Event;
use yii\web\NotFoundHttpException;
use GuzzleHttp\Exception\RequestException;

class ApiController extends Controller
{
    protected $allowAnonymous = true;

    public function actionTrack()
    {
        $this->requirePostRequest();

        $this->identify();
        $this->trackEvent();
        $this->addProfileToLists();

        $request = Craft::$app->getRequest();
        if ($request->isAjax && !$request->getParam('forward')) {
            return $this->asJson('success');
        } else {
            return $this->forwardOrRedirect();
        }
    }

    private function trackEvent()
    {
        $request = Craft::$app->getRequest();
        $event = $request->getParam('event');
        if ($event) {
            if (array_key_exists('name', $event)) {
                $timestamp = null;
                if (array_key_exists('timestamp', $event)) {
                    $timestamp = $event['timestamp'];
                    unset($event['timestamp']);
                }

                if (array_key_exists('trackOrder', $event)) {
                    if(Craft::$app->plugins->isPluginEnabled('commerce')) {
                        $profile = $this->mapProfile();
                        if (array_key_exists('orderId', $event)) {
                            $order = Order::find()
                                ->id($event['orderId'])
                                ->one();

                            if (!$order) {
                                throw new NotFoundHttpException("Order with ID {$orderId} could not be found");
                            }
                        } else {
                            // Use the current cart
                            $order = Commerce::getInstance()->carts->getCart();
                        }

                        Plugin::getInstance()->track->trackOrder($event['name'], $order, $profile, $timestamp);
                    } else {
                        Craft::warning(
                            Craft::t(
                                'klaviyoconnect',
                                'Skipping order tracking; Craft Commerce needs to be installed and enabled to track order events.'
                            ),
                            __METHOD__
                        );
                    }
                } else {
                    $trackOnce = array_key_exists('trackOnce', $event) ? (bool) $event['trackOnce'] : false;

                    $eventProperties = new EventProperties($event);

                    Plugin::getInstance()->track->trackEvent(
                        $event['name'],
                        $this->mapProfile(),
                        $eventProperties,
                        $trackOnce,
                        $timestamp
                    );
                }
            } else {
                Craft::warning(
                    Craft::t(
                        'klaviyoconnect',
                        'Skipping event tracking; An event name is required.'
                    ),
                    __METHOD__
                );
            }
        }
    }

    private function addProfileToLists()
    {
        $lists = array();
        $request = Craft::$app->getRequest();

        if ($listId = $request->getParam('list')) {
            $lists[] = $listId;
        } elseif ($listIds = $request->getParam('lists')) {
            if (is_array($listIds) && sizeof($listIds) > 0) {
                foreach ($listIds as $listId) {
                    if (!empty($listId)) {
                        $lists[] = $listId;
                    }
                }
            }
        }

        if (sizeof($lists) > 0) {
            $profile = $this->mapProfile();

            Plugin::getInstance()->track->addToLists($lists, $profile);
        }
    }

    public function actionIdentify()
    {
        $this->identify();
        $this->forwardOrRedirect();
    }

    private function identify()
    {
        $this->requirePostRequest();
        $profile = $this->mapProfile();
        try {
            Plugin::getInstance()->track->identifyUser($profile);
        } catch (RequestException $e) {
            // Swallow. Klaviyo responds with a 200.
        }
    }

    private function forwardOrRedirect()
    {
        $request = Craft::$app->getRequest();
        $forwardUrl = $request->getParam('forward');
        if ($forwardUrl) {
            return $this->run($forwardUrl);
        } else {
            return $this->redirectToPostedUrl();
        }
    }

    private function mapProfile()
    {
        $request = Craft::$app->getRequest();
        $profileParams = $request->getParam('profile');

        if (!$profileParams) {
            $profileParams = [];
        }

        if ($request->getParam('email') && !isset($profileParams['email'])) {
            $profileParams['email'] = $request->getParam('email');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            return $profileParams = array_merge(
                Plugin::getInstance()->map->mapUser($currentUser),
                $profileParams
            );
        }

        return $profileParams;
    }
}
