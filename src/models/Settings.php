<?php

namespace fostercommerce\klaviyoconnect\models;

use craft\base\Model;

class Settings extends Model
{
	public $klaviyoSiteId = '';

	public $klaviyoApiKey = '';

	public $klaviyoDefaultProfileMapping = 'formdata_mapping';

	public $klaviyoAvailableLists = [];

	public $klaviyoListsAll = false;

	public $klaviyoAvailableGroups = [];

	public $cartUrl = '/shop/cart';

	public $productImageField = 'productImage';

	public $productImageFieldTransformation = 'productThumbnail';

	public $eventPrefix = '';

	// Tracking Event Options
	public $trackSaveUser = true;

	public $trackCommerceCartUpdated = true;

	public $trackCommerceOrderCompleted = true;

	public $trackCommerceStatusUpdated = true;

	public $trackCommerceRefunded = true;
}
