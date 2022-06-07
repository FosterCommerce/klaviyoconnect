<?php
namespace fostercommerce\klaviyoconnect\services;

use Craft;
use fostercommerce\klaviyoconnect\Plugin;

class Map extends Base
{
    /**
     * mapUser.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$user	Default: null
     * @return	array
     */
    public function mapUser($user = null): array
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
