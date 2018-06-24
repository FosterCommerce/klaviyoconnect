<?php
namespace fostercommerce\klaviyoconnect\services;

use fostercommerce\klaviyoconnect\models\Profile;
use Klaviyo;

class Events extends Base
{
    public function onAssignUserToGroups($event)
    {
        $userId = $event->params['userId'];
        $user = Craft::$app->users->getUserById((int) $userId);
        $this->identifyUser($user);
    }

    public function onSaveUser($event)
    {
        $user = $event->params['user'];
        $this->identifyUser($user);
    }

    private function identifyUser ($user)
    {
        $groups = $this->getSetting('klaviyoAvailableGroups');
        $isInGroups = false;
        foreach ($groups as $group) {
            if ($user->isInGroup((Int) $group)) {
                $isInGroups = true;
            }
        }

        if ($isInGroups) {
            Plugin::getInstance()->api->identify(new Profile([
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
            ]));
        }
    }
}
