<?php

namespace fostercommerce\klaviyoconnect\helpers;

use craft\helpers\App;
use yii\validators\EmailValidator;

final class EmailAddress
{
	/**
	 * Requires a top-level domain of two or more characters, not all digits, matching Foster Checkout.
	 */
	private const TOP_LEVEL_DOMAIN_PATTERN = '/\.(?!\d+$)[^\s@.]{2,}$/u';

	/**
	 * Applies Craft's user email rule and the top-level domain rule.
	 */
	public static function isValid(mixed $email): bool
	{
		if (! is_string($email)) {
			return false;
		}

		$email = trim($email);
		$emailValidator = new EmailValidator([
			'enableIDN' => App::supportsIdn(),
			'enableLocalIDN' => App::supportsIdn(),
		]);

		return $emailValidator->validate($email) && preg_match(self::TOP_LEVEL_DOMAIN_PATTERN, $email) === 1;
	}
}
