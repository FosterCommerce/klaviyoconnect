<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;

class Map extends Base
{
    public function mapUser($user = null)
    {
        if (!$user) {
            $user = Craft::$app->user->getIdentity();
        }

        if (!$user) {
            return [];
        }

        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
        ];
    }
}
