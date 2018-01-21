<?php

namespace Craft;

use \GuzzleHttp\Exception\RequestException;

class KlaviyoConnect_ServerApiController extends KlaviyoConnect_BaseController
{
    public function actionTrack()
    {
        $this->track();
    }

    public function actionTrackOnce()
    {
        $this->track(true);
    }

    private function track($trackOnce = false)
    {
        $this->requirePostRequest();

        if (array_key_exists('event', $_POST)) {
            $event = $_POST['event'];
            if (array_key_exists('event', $event)) {
                $profile = $this->mapProfile();

                $eventProperties = KlaviyoConnect_EventPropertiesModel::populateModel($event);

                try {
                    craft()->klaviyoConnect_api->track($event['event'], $profile, $eventProperties, $trackOnce);
                } catch (RequestException $e) {
                    KlaviyoConnectPlugin::log($e, LogLevel::Error);
                }
            }
        }

        $this->forwardOrRedirect();
    }

    public function actionIdentify()
    {
        $this->requirePostRequest();

        $profile = $this->mapProfile();

        try {
            craft()->klaviyoConnect_api->identify($profile);
        } catch (RequestException $e) {
            KlaviyoConnectPlugin::log($e, LogLevel::Error);
        }

        $this->forwardOrRedirect();
    }
}
