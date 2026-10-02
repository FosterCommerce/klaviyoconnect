<?php

namespace fostercommerce\klaviyoconnect\models;

class EventProperties extends Base implements \Stringable
{
	/**
	 * A unique identifier for an event. If the unique_id is repeated for the same
	 * profile and metric, only the first processed event will be recorded.
	 */
	public ?string $unique_id = null;

	/**
	 * A numeric, monetary value to associate with this event. For example, the dollar amount of a purchase.
	 */
	public ?string $value = null;

	/**
	 * The ISO 4217 currency code of the value associated with the event.
	 */
	public ?string $value_currency = null;

	public function __toString(): string
	{
		return "[{$this->unique_id}] {$this->value}";
	}
}
