<?php

namespace Craft;

use \GuzzleHttp\Exception\RequestException;

class KlaviyoConnect_ApiController extends BaseController
{
    protected $allowAnonymous = true;

    public function actionUpdateProfile()
    {
        $this->requirePostRequest();

        $this->trackEvent();
        $this->addProfileToLists();

        if (craft()->request->isAjaxRequest() && !array_key_exists('forward', $_POST)) {
            $this->returnJSON('success');
        } else {
            $this->forwardOrRedirect();
        }
    }

    private function trackEvent()
    {
        if (array_key_exists('event', $_POST)) {
            $event = $_POST['event'];
            if (array_key_exists('name', $event)) {
                $trackOnce = array_key_exists('trackOnce', $event) ? (bool) $event['trackOnce'] : false;
                $profile = $this->mapProfile();
                $eventProperties = KlaviyoConnect_EventPropertiesModel::populateModel($event);

                try {
                    craft()->klaviyoConnect_api->track($event['name'], $profile, $eventProperties, $trackOnce);
                } catch (RequestException $e) {
                    KlaviyoConnectPlugin::log($e, LogLevel::Error);
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
                $list = new KlaviyoConnect_ListModel();
                $list->id = $listId;

                try {
                    craft()->klaviyoConnect_api->addProfileToList($list, $profile, $confirmOptIn);
                } catch (RequestException $e) {
                    KlaviyoConnectPlugin::log($e, LogLevel::Error);
                }

            }
        }
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

    private function forwardOrRedirect()
    {
        if (array_key_exists('forward', $_POST)) {
            $url = $_POST['forward'];
            $this->forward($url, false);
        } else {
            $this->redirectToPostedUrl();
        }
    }

    private function mapProfile()
    {
        $mapping = '';
        if (array_key_exists('klaviyoProfileMapping', $_POST)) {
            $mapping = $_POST['klaviyoProfileMapping'];
        }
        $profile = craft()->klaviyoConnect_map->map($mapping, $_POST);
        return $profile;
    }
}
