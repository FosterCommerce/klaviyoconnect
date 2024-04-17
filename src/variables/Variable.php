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
     */
    public function lists(): mixed
    {
        if (! empty(Plugin::getInstance()->settings->klaviyoApiKey)) {
            if ($this->lists === null) {
                try {
                    $lists = Plugin::getInstance()->api->getLists();
                    if (count($lists) > 0) {
                        $this->lists = $lists;
                    }
                } catch (RequestException $e) {
                    try {
                        $response = json_decode(
                            $e->getResponse()?->getBody()->getContents(),
                            false,
                            512, // default
                            JSON_THROW_ON_ERROR
                        );

                        if (isset($response)) {
                            $this->error = $response->message;
                        } else {
                            $this->error = 'Unable to retrieve lists, please check your configuration';
                        }
                    } catch (\JsonException) {
                        $this->error = 'Unable to retrieve lists, please check your configuration';
                    }
                }
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
     */
    public function error(): mixed
    {
        return $this->error;
    }
}
