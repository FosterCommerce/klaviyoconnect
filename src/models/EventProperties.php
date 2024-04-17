<?php

namespace fostercommerce\klaviyoconnect\models;

class EventProperties extends Base implements \Stringable
{
    /**
     * @var		public
     */
    public $event_id;

    /**
     * @var		public
     */
    public $value;

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
        return "[{$this->name}] {$this->value}";
    }
}
