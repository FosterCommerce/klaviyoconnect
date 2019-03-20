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
