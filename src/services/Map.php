<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\base\FieldInterface;
use craft\commerce\elements\Order;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User as UserElement;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use DateTimeInterface;
use fostercommerce\klaviyoconnect\helpers\SandboxedTwig;
use fostercommerce\klaviyoconnect\Plugin;
use Illuminate\Support\Collection;
use Stringable;
use yii\base\Component;

class Map extends Component
{
	/**
	 * Klaviyo's standard profile attributes a user field can set.
	 */
	public const PROFILE_ATTRIBUTES = ['external_id', 'phone_number', 'organization', 'title', 'locale', 'image'];

	/**
	 * @return array<string, mixed>
	 */
	public function mapUser(?UserElement $user = null): array
	{
		if (! $user instanceof UserElement) {
			$user = Craft::$app->user->getIdentity();
		}

		if (! $user) {
			return [];
		}

		return array_replace_recursive([
			'email' => $user->email,
			'first_name' => $user->firstName,
			'last_name' => $user->lastName,
		], $this->mapUserFields($user));
	}

	/**
	 * Returns the user's fields mapped in the Klaviyo Profile Attributes and Custom Profile Properties settings.
	 *
	 * @return array<string, mixed>
	 */
	public function mapUserFields(UserElement $user, ?Order $order = null): array
	{
		$settings = Plugin::getInstance()->getSettings();

		$profile = $this->mapSources(array_intersect_key($settings->getProfileAttributeFields(), array_flip(self::PROFILE_ATTRIBUTES)), $user, $order);
		$properties = $this->mapSources($settings->getProfileCustomProperties(), $user, $order);

		if ($properties !== []) {
			$profile['properties'] = $properties;
		}

		return $profile;
	}

	/**
	 * @param array<string, array{userField: string, twig: string}> $sources
	 * @return array<string, mixed>
	 */
	private function mapSources(array $sources, UserElement $user, ?Order $order): array
	{
		$values = [];
		foreach ($sources as $name => $source) {
			if ($source['userField'] === '__userId__') {
				$values[$name] = (string) $user->id;
			} elseif ($source['userField'] === '__twig__') {
				$value = SandboxedTwig::render($source['twig'], [
					'user' => $user,
					'order' => $order,
				], "profile attribute \"{$name}\"");
				if ($value !== '') {
					$values[$name] = $value;
				}
			} elseif ($this->hasField($user, $source['userField'])) {
				// Skip empty fields, so a blank value never clears one already set in Klaviyo
				$value = $this->profileValue($user->getFieldValue($source['userField']));
				if ($value !== null && $value !== '' && $value !== []) {
					$values[$name] = $value;
				}
			}
		}

		return $values;
	}

	private function hasField(UserElement $user, string $fieldHandle): bool
	{
		return $user->getFieldLayout()?->getFieldByHandle($fieldHandle) instanceof FieldInterface;
	}

	private function profileValue(mixed $value): mixed
	{
		if ($value instanceof ElementQueryInterface) {
			$value = $value->collect();
		}

		return match (true) {
			$value instanceof Collection => $value->map(static fn (mixed $element): string => match (true) {
				$element instanceof Asset => (string) $element->getUrl(),
				$element instanceof Stringable => (string) $element,
				default => '',
			})->values()->all(),
			$value instanceof DateTimeInterface => $value->format(DATE_ATOM),
			$value instanceof SingleOptionFieldData => $value->value,
			$value instanceof MultiOptionsFieldData => array_map(static fn (mixed $option): ?string => $option instanceof OptionData ? $option->value : null, $value->getArrayCopy()),
			$value === null, is_scalar($value) => $value,
			$value instanceof Stringable => (string) $value,
			default => null,
		};
	}
}
