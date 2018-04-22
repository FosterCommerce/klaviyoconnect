<?php
namespace Craft;

use \Klaviyo;

class KlaviyoConnectService extends KlaviyoConnect_BaseService
{
    public function onAssignUserToGroups($event)
    {
        $userId = $event->params['userId'];
        $user = craft()->users->getUserById((int) $userId);
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
            craft()->klaviyoConnect_api->identify(KlaviyoConnect_ProfileModel::populateModel([
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->firstName,
                'last_name' => $user->lastName,
            ]));
        }
    }
}
