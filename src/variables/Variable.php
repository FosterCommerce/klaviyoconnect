<?php
namespace fostercommerce\klaviyoconnect\variables;

use fostercommerce\klaviyoconnect\Plugin;
use GuzzleHttp\Exception\RequestException;

class Variable
{
    private $error = null;
    private $lists = null;

    /**
     * lists.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	mixed
     */
    public function lists() // no return type as mixed is PHP 8 only
    {
        if (is_null($this->lists)) {
            try {
                $lists = Plugin::getInstance()->api->getLists();
                if (sizeof($lists) > 0) {
                    $this->lists = $lists;
                }
            } catch (RequestException $e) {
                $response = json_decode($e->getResponse()->getBody()->getContents());
                $this->error = [$e->getCode() => $response->message];
            }
        }
        return $this->lists;
    }

    /**
     * profileFromList.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, November 14th, 2022.
     * @access	public
     * @param	string	$listId	
     * @param	string	$email 	
     * @return	array
     */
    public function profileFromList(string $listId, string $email): array
    {
        if($listId && $email) {
            $profile = Plugin::getInstance()->api->getProfileFromList($listId, $email);
        }

        return $profile;
    }

    /**
     * profileFromList.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, November 14th, 2022.
     * @access	public
     * @param	string	$profileId	
     * @return	array
     */
    public function profile(string $profileId): array
    {
        if($profileId) {
            $profile = Plugin::getInstance()->api->getProfile($profileId);
        }

        return json_decode(json_encode($profile), true);
    }

    /**
     * error.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	mixed
     */
    public function error() // no return type as mixed is PHP 8 only
    {
        return $this->error;
    }
}
