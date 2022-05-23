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
    public function lists(): mixed
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
     * error.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	mixed
     */
    public function error(): mixed
    {
        return $this->error;
    }
}
