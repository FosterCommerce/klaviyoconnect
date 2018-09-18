<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public $klaviyoSiteId = '';
    public $klaviyoApiKey = '';
    public $klaviyoDefaultProfileMapping = 'formdata_mapping';
    public $klaviyoAvailableLists = array();
    public $klaviyoListsAll = false;
    public $klaviyoAvailableGroups = array();
    public $cartUrl = '/shop/cart';
}