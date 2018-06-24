<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\events\TrackEventMappingEvent;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\models\KlaviyoList;
use craft\web\Controller;
use yii\base\Event;
use GuzzleHttp\Exception\RequestException;

class ApiController extends Controller
{
    const EVENT_TRACK_EVENT_MAPPING = 'trackEventMapping';

    protected $allowAnonymous = true;

    public function actionUpdateProfile()
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
                $trackOnce = array_key_exists('trackOnce', $event) ? (bool) $event['trackOnce'] : false;
                $profile = $this->mapProfile();

                $trackEventMappingEvent = new TrackEventMappingEvent(['name' => $event['name']]);
                Event::trigger(static::class, self::EVENT_TRACK_EVENT_MAPPING, $trackEventMappingEvent);

                $eventProperties = Plugin::getInstance()->populateModel(EventProperties::class, $event);

                if (sizeof($trackEventMappingEvent->extraProps) > 0) {
                    $eventProperties->setAttribute('extra', $trackEventMappingEvent->extraProps);
                }

                try {
                    Plugin::getInstance()->api->track($event['name'], $profile, $eventProperties, $trackOnce);
                } catch (RequestException $e) {
                    // TODO log this? // KlaviyoConnectPlugin::log($e, LogLevel::Error);
                }
            }
        }
    }

    private function addProfileToLists()
    {
        $lists = array();

        if (array_key_exists('list', $_POST)) {
            $lists[] = $_POST['list'];
        } else if (array_key_exists('lists', $_POST) && sizeof($_POST['lists']) > 0) {
            foreach ($_POST['lists'] as $listId) {
                if (!empty($listId)) {
                    $lists[] = $listId;
                }
            }
        }

        if (sizeof($lists) > 0) {
            $profile = $this->mapProfile();
            $confirmOptIn = true;

            if (array_key_exists('confirmOptIn', $_POST)) {
                if (!is_null($_POST['confirmOptIn'])) {
                    $confirmOptIn = (bool) $_POST['confirmOptIn'];
                }
            }

            foreach ($lists as $listId) {
                $list = new KlaviyoList();
                $list->id = $listId;

                try {
                    Plugin::getInstance()->api->addProfileToList($list, $profile, $confirmOptIn);
                } catch (RequestException $e) {
                    // TODO log this? // KlaviyoConnectPlugin::log($e, LogLevel::Error);
                }

            }
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
            Plugin::getInstance()->api->identify($profile);
        } catch (RequestException $e) {
            // TODO log this? // KlaviyoConnectPlugin::log($e, LogLevel::Error);
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
        $mapping = $request->getParam('klaviyoProfileMapping');
        if (!$mapping) {
            $mapping = '';
        }
        $profile = Plugin::getInstance()->map->map($mapping, $request->getBodyParams());
        return $profile;
    }
}
