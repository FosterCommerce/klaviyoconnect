<?php

namespace fostercommerce\klaviyoconnect\models;

use craft\base\Model;

class KlaviyoList extends Model implements \Stringable
{
	public string $id;

	public string $name;

	public function __toString(): string
	{
		return $this->name;
	}
}
