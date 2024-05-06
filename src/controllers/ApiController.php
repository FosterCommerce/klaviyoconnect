<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\web\Controller;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\queue\jobs\SyncOrders;
use GuzzleHttp\Exception\RequestException;
use yii\web\NotFoundHttpException;
use yii\web\Response as YiiResponse;

class ApiController extends Controller
{
    protected $allowAnonymous = true;

    /**
     * actionTrack.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     */
    public function actionTrack(): YiiResponse
    {
        $this->requirePostRequest();

        $this->identify();
        $this->trackEvent();
        $this->addProfileToLists();

        $request = Craft::$app->getRequest();
        if ($request->isAjax && ! $request->getParam('forward')) {
            return $this->asJson('success');
        }

        return $this->forwardOrRedirect();
    }

    /**
     * actionSyncOrders.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     */
    public function actionSyncOrders(): void
    {
        $params = $this->request->queryParams;
        $start = is_numeric($params['start']) ? $params['start'] : null;
        $end = is_numeric($params['end']) ? $params['end'] : null;

        if ($start && $end) {
            $orders = Order::find()->isCompleted()->dateCreated(['and', ">= {$start}", "<= {$end}"])->all();

            foreach ($orders as $order) {
                Craft::$app->getQueue()->delay(10)->push(new SyncOrders([
                    'orderId' => $order->id,
                ]));
            }
        }
    }

    /**
     * actionIdentify.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     */
    public function actionIdentify(): void
    {
        $this->identify();
        $this->forwardOrRedirect();
    }

    private function trackEvent(): void
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
                    if (Craft::$app->plugins->isPluginEnabled('commerce')) {
                        $profile = $this->mapProfile();
                        if (array_key_exists('orderId', $event)) {
                            $order = Order::find()
                                ->id($event['orderId'])
                                ->one();

                            if (! $order) {
                                throw new NotFoundHttpException("Order with ID {$event['orderId']} could not be found");
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
                    Plugin::getInstance()->track->trackEvent(
                        $event['name'],
                        $this->mapProfile(),
                        new EventProperties($event),
                        $timestamp,
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

    private function addProfileToLists(): void
    {
        $lists = [];
        $request = Craft::$app->getRequest();

        if ($listId = $request->getParam('list')) {
            $lists[] = $listId;
        } elseif ($listIds = $request->getParam('lists')) {
            if (is_array($listIds) && $listIds !== []) {
                foreach ($listIds as $listId) {
                    if (! empty($listId)) {
                        $lists[] = $listId;
                    }
                }
            }
        }

        if ($lists !== []) {
            $profile = $this->mapProfile();
            $subscribe = (bool) $request->getParam('subscribe');

            Plugin::getInstance()->track->addToLists($lists, $profile, $subscribe);
        }
    }

    private function identify(): void
    {
        $this->requirePostRequest();
        $profile = $this->mapProfile();
        try {
            Plugin::getInstance()->track->identifyUser($profile);
        } catch (RequestException $e) {
            // Swallow. Klaviyo responds with a 200.
        }
    }

    private function forwardOrRedirect(): YiiResponse
    {
        $request = Craft::$app->getRequest();
        $forwardUrl = $request->getParam('forward');
        if ($forwardUrl) {
            return $this->run($forwardUrl);
        }

        return $this->redirectToPostedUrl();
    }

    private function mapProfile(): array
    {
        $request = Craft::$app->getRequest();
        $profileParams = $request->getParam('profile');

        if (! $profileParams) {
            $profileParams = [];
        }

        if ($request->getParam('email') && ! isset($profileParams['email'])) {
            $profileParams['email'] = $request->getParam('email');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            return array_merge(
                Plugin::getInstance()->map->mapUser($currentUser),
                $profileParams
            );
        }

        return $profileParams;
    }
}
