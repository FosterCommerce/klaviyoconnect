<?php

namespace fostercommerce\klaviyoconnect\helpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneNumber
{
	/**
	 * Returns the number in E.164 format, or null when it isn't a valid number.
	 *
	 * @param string|null $countryCode Country for a number typed without its country code, such as `US` for `6148067959`
	 * @since 7.3.1
	 */
	public static function toE164(mixed $phoneNumber, ?string $countryCode): ?string
	{
		// Accept a number from a Number field too
		if (is_int($phoneNumber)) {
			$phoneNumber = (string) $phoneNumber;
		}

		if (! is_string($phoneNumber)) {
			return null;
		}

		// Read digits that start with the country's calling code as international first, since `4915…` is also a valid German national number
		$callingCode = $countryCode === null ? 0 : PhoneNumberUtil::getInstance()->getCountryCodeForRegion($countryCode);
		$digits = preg_replace('/\D/', '', $phoneNumber) ?? '';
		if ($callingCode !== 0 && ! str_starts_with(trim($phoneNumber), '+') && str_starts_with($digits, (string) $callingCode)) {
			$internationalPhoneNumber = self::parseValid('+' . $digits, null);
			if ($internationalPhoneNumber !== null) {
				return $internationalPhoneNumber;
			}
		}

		return self::parseValid($phoneNumber, $countryCode);
	}

	private static function parseValid(string $phoneNumber, ?string $countryCode): ?string
	{
		$phoneNumberUtil = PhoneNumberUtil::getInstance();

		try {
			$parsedPhoneNumber = $phoneNumberUtil->parse($phoneNumber, $countryCode);
		} catch (NumberParseException) {
			return null;
		}

		if (! $phoneNumberUtil->isValidNumber($parsedPhoneNumber)) {
			return null;
		}

		return $phoneNumberUtil->format($parsedPhoneNumber, PhoneNumberFormat::E164);
	}
}
