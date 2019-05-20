<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class Profile extends Base
{
    public $id;
    public $email;
    public $first_name;
    public $last_name;
    public $phone_number;
    public $title;
    public $organization;
    public $city;
    public $region;
    public $country;
    public $zip;
    public $image;

    /**
     * GDPR related properties
     * See: https://help.klaviyo.com/hc/en-us/articles/360003536031-Collect-GDPR-Compliant-Consent
     */

    /**
     * One of "email", "web", "sms", "directmail", "mobile"
     */
    public $consent;
    public $consent_id;
    public $consent_method;

    /**
     * Automatically added by Klaviyo if not present
     */
    public $consent_timestamp;
    public $consent_version;

    public function __toString()
    {
        return $this->id ? $this->id : $this->email;
    }

    public function hasEmail()
    {
        return isset($this->email) && !is_null($this->email);
    }

    public function hasId()
    {
        return isset($this->id) && !is_null($this->email);
    }

    public function hasEmailOrId()
    {
        return $this->hasEmail() || $this->hasId();
    }
}
