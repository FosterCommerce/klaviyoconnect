<?php

namespace fostercommerce\klaviyoconnect\models;

use craft\base\Model;
use yii\base\UnknownPropertyException;

abstract class Base extends Model
{
	/**
	 * @var array<string, mixed>
	 */
	private array $customProperties = [];

	public function __set(mixed $name, mixed $value): void
	{
		try {
			parent::__set($name, $value);
		} catch (UnknownPropertyException) {
			// Keep unknown keys in an array, since PHP 8.2 deprecates dynamic properties
			$this->customProperties[(string) $name] = $value;
		}
	}

	/**
	 * Untyped like `yii\base\Component::__get()`, so a subclass written for 7.2.5 still loads.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		try {
			return parent::__get($name);
		} catch (UnknownPropertyException $unknownPropertyException) {
			if (array_key_exists((string) $name, $this->customProperties)) {
				return $this->customProperties[(string) $name];
			}

			throw $unknownPropertyException;
		}
	}

	/**
	 * Adds a property without calling the model's setters, so a posted key such as `scenario` doesn't call `setScenario()`.
	 */
	public function addCustomProperty(string $name, mixed $value): void
	{
		$this->customProperties[$name] = $value;
	}

	/**
	 * @param array<string, mixed> $properties
	 */
	public function setCustomProperties(array $properties): void
	{
		foreach ($properties as $property => $value) {
			$this->{$property} = $value;
		}
	}

	/**
	 * @param string[] $fields
	 * @param string[] $expand
	 * @return array<string, mixed> Declared `mixed`, so a subclass written for 7.2.5 still loads
	 */
	public function toArray(array $fields = [], array $expand = [], $recursive = true): mixed
	{
		$arr = parent::toArray($fields, $expand, $recursive);

		$mapped = [];
		foreach ($arr as $name => $value) {
			// Leave out unset values, since Klaviyo shows them as empty properties on the event
			if ($value !== null) {
				$mapped["\${$name}"] = $value;
			}
		}

		return [...$mapped, ...$this->customProperties];
	}
}
