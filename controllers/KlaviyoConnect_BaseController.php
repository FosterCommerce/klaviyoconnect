<?php

namespace Craft;

abstract class KlaviyoConnect_BaseController extends BaseController
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
        $provider = '';
        if (array_key_exists('klaviyoProfileMapping', $_POST)) {
            $provider = $_POST['klaviyoProfileMapping'];
        }
        $profile = craft()->klaviyoConnect_map->map($provider, $_POST);
        return $profile;
    }
}
