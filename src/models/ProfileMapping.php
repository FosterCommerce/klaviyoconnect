<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class ProfileMapping extends Model
{
    public $name = '';
    public $handle = '';
    public $description = 'formdata_mapping';
    public $method;
}