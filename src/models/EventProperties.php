<?php
namespace fostercommerce\klaviyoconnect\models;

use Craft;
use craft\base\Model;

class EventProperties extends Base
{
    /**
     * @var		public	$event_id
     */
    public $event_id;
    
    /**
     * @var		public	$value
     */
    public $value;

    /**
     * __toString.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @return	string
     */
    public function __toString(): string
    {
        return "[{$this->name}] {$this->value}";
    }
}
