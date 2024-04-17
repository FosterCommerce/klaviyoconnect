<?php

namespace fostercommerce\klaviyoconnect\models;

use craft\base\Model;

class KlaviyoList extends Model implements \Stringable
{
    /**
     * @var		public
     */
    public string $id;

    /**
     * @var		public
     */
    public string $name;

    /**
     * __toString.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
