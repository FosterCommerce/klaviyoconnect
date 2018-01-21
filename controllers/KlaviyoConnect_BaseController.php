<?php

namespace Craft;

class KlaviyoConnect_BaseController extends BaseController
{
    protected $allowAnonymous = true;

    protected function forwardOrRedirect()
    {
        if (array_key_exists('forward', $_POST)) {
            $url = $_POST['forward'];
            $this->forward($url, false);
        } else {
            $this->redirectToPostedUrl();
        }
    }

    protected function mapProfile()
    {
        // TODO Add a hook to allow users to add custom profile mapping
        // i.e. User/Customer/etc -> profile
        $profile = KlaviyoConnect_ProfileModel::populateModel($_POST);
        return $profile;
    }
}
