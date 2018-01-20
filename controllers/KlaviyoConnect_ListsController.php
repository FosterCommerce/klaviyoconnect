<?php

namespace Craft;

use \GuzzleHttp\Exception\RequestException;

class KlaviyoConnect_ListsController extends BaseController
{
    protected $allowAnonymous = true;

    public function actionAddToLists()
    {
        $this->requirePostRequest();

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
            $profile = KlaviyoConnect_ProfileModel::populateModel($_POST);
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
                    // Fail quietly.
                    // XXX Should we let this through?
                    KlaviyoConnectPlugin::log($e, LogLevel::Error);
                }

            }
        }

        if (array_key_exists('forward', $_POST)) {
            $url = $_POST['forward'];
            $this->forward($url, false);
        } else {
            $this->redirectToPostedUrl();
        }
    }

}
