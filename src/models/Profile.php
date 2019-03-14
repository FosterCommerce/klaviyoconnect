<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class Profile extends Base
{
    const SPECIAL_PROPERTIES = [
        'id', 'email', 'first_name', 'last_name', 'phone_number',
        'title', 'organization', 'city', 'region', 'country', 'zip',
        'image',
    ];

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
    public $extra;

    protected function getSpecialProperties(): Array
    {
        return self::SPECIAL_PROPERTIES;
    }

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
