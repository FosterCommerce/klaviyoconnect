<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\queue\jobs\SyncOrders;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use yii\base\Event;
use yii\web\NotFoundHttpException;
use GuzzleHttp\Exception\RequestException;

class ApiController extends Controller
{
    /**
     * @var		bool	$allowAnonymous
     */
    protected $allowAnonymous = true;

    /**
     * actionTrack.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	void
     */
    public function actionTrack() // no return type as mixed is PHP 8 only
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

    /**
     * actionSyncOrders.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	void
     */
    public function actionSyncOrders(): void 
    {
        $params = $this->request->queryParams;
        $start  = is_numeric($params['start']) ? $params['start'] : null;
        $end    = is_numeric($params['end']) ? $params['end'] : null;

        if($start && $end) {
            $orders = Order::find()->isCompleted()->dateCreated(['and', ">= {$start}", "<= {$end}"])->all();

            foreach ($orders as $order) {
                Craft::$app->getQueue()->delay(10)->push(new SyncOrders([
                    'orderId' => $order->id
                ]));
            }
        }
    }
    
    
    /**
     * actionUpdateProfile.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, June 13th, 2022.
     * @access	public
     * @return	void
     */
    public function actionUpdateProfile(): void
    {
        $this->requirePostRequest();
        
        // user must be logged in to update a profile
        $currentUser = Craft::$app->getUser()->getIdentity();
        if(!$currentUser){
            throw new \Exception('You must be logged in to update your profile.');
        } 
                
        // Get the posted params
        $request = Craft::$app->getRequest();
        $klaviyoId = $request->getParam('profile_id') ?? $request->getParam('id') ?? null;
        $email = $request->getParam('profile_email') ?? $request->getParam('email') ?? null;
        
        // we need either an email or a klaviyo id, if we have neither then stop
        if($email === null && $klaviyoId === null){
            throw new \Exception('You must identify a user by email or ID.');
        }
         // if the logged in user's email is NOT the same as the one we want to update then stop
         if($email) {
             if ($currentUser->email !== $email) {
                 throw new NotFoundHttpException('You are not permitted to update this profile.');
             }
         }
        
        // if there is no klaviyo id provided then retrieve one using the email address
        if($klaviyoId === null && $email !== null) {
            // Get the Klaviyo ID based on the user email
            $result =  Plugin::getInstance()->api->getPersonIdfromEmail($email);
            $klaviyoId = $result ?? null;
        }
                
        // clean up the post data
        $formParams = $request->getBodyParams();
        
        unset($formParams[$request->csrfParam]);
        unset($formParams['action']);
            
        // Update the user's profile using the Update Profile API
        $params = [
            'params' => $formParams
        ];
        
        $result = Plugin::getInstance()->api->updateProfile($klaviyoId, $params);
        
        $this->forwardOrRedirect();
    }
    

    /**
     * trackEvent.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @return	void
     */
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

    /**
     * addProfileToLists.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @return	void
     */
    private function addProfileToLists(): void
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
            $useSubscribeEndpoint = (bool)$request->getParam('useSubscribeEndpoint');

            Plugin::getInstance()->track->addToLists($lists, $profile, $useSubscribeEndpoint);
        }
    
    }

    /**
     * actionIdentify.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	void
     */
    public function actionIdentify(): void
    {
        $this->identify();
        $this->forwardOrRedirect();
    }

    /**
     * identify.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @return	void
     */
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

    /**
     * forwardOrRedirect.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @return	mixed
     */
    private function forwardOrRedirect() // no return type as mixed is PHP 8 only
    {
        $request = Craft::$app->getRequest();
        $forwardUrl = $request->getParam('forward');
        if ($forwardUrl) {
            return $this->run($forwardUrl);
        } else {
            return $this->redirectToPostedUrl();
        }
    }

    /**
     * mapProfile.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	private
     * @return	mixed
     */
    private function mapProfile() // no return type as mixed is PHP 8 only
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
