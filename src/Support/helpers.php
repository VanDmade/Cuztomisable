<?php

/**
 * Global helper functions - consolidated from the former Helpers/Date.php and Helpers/Global.php
 * (Step 5 of the refactor plan) into one file. Signatures unchanged.
 */

/******************************************************************************\
 *
 * Converts a date from one timezone to another and formats the output
 *
 * @param string       $format      The date format
 * @param mixed        $date        The date string, timestamp, or DateTimeInterface
 * @param string       $timezone    The source timezone (UTC, America/New_York, etc.)
 * @param string|null  $toTimezone  The destination timezone (defaults to app timezone)
 *
 * @return string|null  The converted date or null if parsing fails
 *
\******************************************************************************/
function convertDate($format, $date, $timezone = 'UTC', $toTimezone = null)
{
	$targetTz = $toTimezone ?? config('app.timezone', 'UTC');
	return toTimezone($date, $timezone, $targetTz, $format);
}

/******************************************************************************\
 *
 * Converts a date string/timestamp between timezones and returns the formatted value
 *
 * @param mixed   $date      The date string, timestamp, or DateTimeInterface
 * @param string  $fromTz    The source timezone (UTC, America/New_York, etc.)
 * @param string  $toTz      The destination timezone
 * @param string  $format    The date format to output
 *
 * @return string|null  The converted date or null if parsing fails
 *
\******************************************************************************/
function toTimezone($date, $fromTz, $toTz, $format = 'Y-m-d H:i:s')
{
	$parsed = parseDate($date, $fromTz);
	if ($parsed === null) {
		return null;
	}
	$target = isValidTimezone($toTz) ? $toTz : 'UTC';
	return $parsed->setTimezone(new \DateTimeZone($target))->format($format);
}

/******************************************************************************\
 *
 * Converts a date in the user's timezone into the app timezone
 *
 * @param mixed   $date    The date string, timestamp, or DateTimeInterface
 * @param string  $format  The date format to output
 *
 * @return string|null  The converted date or null if parsing fails
 *
\******************************************************************************/
function fromUserTimezone($date, $format = 'Y-m-d H:i:s')
{
	$userTz = config('app.timezone', 'UTC');
	if (class_exists('Illuminate\\Support\\Facades\\Auth') && \Illuminate\Support\Facades\Auth::check()) {
		$userTz = \Illuminate\Support\Facades\Auth::user()->timezone ?? $userTz;
	}
	return toTimezone($date, $userTz, config('app.timezone', 'UTC'), $format);
}

/******************************************************************************\
 *
 * Converts a date in the app timezone into the user's timezone
 *
 * @param mixed   $date    The date string, timestamp, or DateTimeInterface
 * @param string  $format  The date format to output
 *
 * @return string|null  The converted date or null if parsing fails
 *
\******************************************************************************/
function toUserTimezone($date, $format = 'Y-m-d H:i:s')
{
	$userTz = config('app.timezone', 'UTC');
	if (class_exists('Illuminate\\Support\\Facades\\Auth') && \Illuminate\Support\Facades\Auth::check()) {
		$userTz = \Illuminate\Support\Facades\Auth::user()->timezone ?? $userTz;
	}
	return toTimezone($date, config('app.timezone', 'UTC'), $userTz, $format);
}

/******************************************************************************\
 *
 * Parses a date string/timestamp into a DateTimeImmutable at the given timezone
 *
 * @param mixed   $date      The date string, timestamp, or DateTimeInterface
 * @param string  $timezone  The timezone to parse into
 *
 * @return \DateTimeImmutable|null  Parsed date or null if parsing fails
 *
\******************************************************************************/
function parseDate($date, $timezone = 'UTC')
{
	if ($date === null || $date === '') {
		return null;
	}
	$tz = isValidTimezone($timezone) ? $timezone : 'UTC';
	$tzObj = new \DateTimeZone($tz);

	if ($date instanceof \DateTimeInterface) {
		return (new \DateTimeImmutable($date->format('Y-m-d H:i:s.u'), $date->getTimezone()))
			->setTimezone($tzObj);
	}
	if (is_int($date) || (is_string($date) && ctype_digit($date))) {
		return (new \DateTimeImmutable('@'.(string) $date))->setTimezone($tzObj);
	}
	try {
		return new \DateTimeImmutable((string) $date, $tzObj);
	} catch (\Exception $error) {
		return null;
	}
}

/******************************************************************************\
 *
 * Validates whether a timezone identifier is supported by PHP
 *
 * @param mixed  $timezone  The timezone identifier to validate
 *
 * @return bool  True if valid, otherwise false
 *
\******************************************************************************/
function isValidTimezone($timezone): bool
{
	if (!is_string($timezone) || $timezone === '') {
		return false;
	}
	return in_array($timezone, \DateTimeZone::listIdentifiers(), true);
}

/******************************************************************************\
 *
 * Generates a randomized code of a length between 1 and 24
 *
 * @param integer  $length  The length of the code
 * @param string   $salt    The customized salt string
 * @param string   $prefix  A prefixed string that is appended finalized code
 *
 * @return string  The code generated for use by the system in uppercase
 *
\******************************************************************************/
function generateCode($length, $salt = 'cuztomisable', $prefix = null): string
{
    $size = $length > 16 ? 16 : ($length < 1 ? 1 : (int) $length);
    $random = strtoupper(bin2hex(random_bytes(16)));
    return (is_null($prefix) ? '' : $prefix).substr($random, 0, $size);
}

/******************************************************************************\
 *
 * Builds the "+{country_code} {phone}" combined format phone numbers are stored in
 * (e.g. Users\Registration::phone), from separate country code and phone number values.
 *
 * @param mixed  $countryCode  The country code (e.g. 1, 44)
 * @param mixed  $phone        The phone number
 *
 * @return string|null  The combined phone number, or null if either value is missing
 *
\******************************************************************************/
function generatePhoneNumber($countryCode, $phone): ?string
{
    return isset($countryCode, $phone) ? '+'.$countryCode.' '.$phone : null;
}

/******************************************************************************\
 *
 * Splits the "+{country_code} {phone}" combined format phone numbers are stored in
 * back into separate country code and phone number values - the inverse of generatePhoneNumber().
 *
 * @param string|null  $combined  The combined phone number
 *
 * @return array{0: string|null, 1: string|null}  [$countryCode, $phone], both null if empty
 *
\******************************************************************************/
function splitPhoneNumber(?string $combined): array
{
    if (empty($combined)) {
        return [null, null];
    }
    $parts = explode(' ', trim($combined, '+'), 2);
    return [$parts[0] ?? null, $parts[1] ?? null];
}

/******************************************************************************\
 *
 * Gets the current IPv4 address of the user
 *
 * @return string  The IP Address v4 of the current user
 *
\******************************************************************************/
function getIpAddress(): ?string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return trim($_SERVER['HTTP_CLIENT_IP']);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Note: This assumes trusted proxies are configured; otherwise, XFF can be spoofed.
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($forwarded[0] ?? '') ?: null;
    }
    return isset($_SERVER['REMOTE_ADDR']) ? trim($_SERVER['REMOTE_ADDR']) : null;
}

/******************************************************************************\
 *
 * Converts the seconds parameter into hour:minute:second format
 *
 * @return string  The time in 00:00:00 format
 *
\******************************************************************************/
function convertToTimeOutput($seconds): string
{
    $seconds = (int) $seconds;
    if ($seconds < 0) {
        $seconds = 0;
    }
    if ($seconds === 0) {
        return '00:00:00';
    }
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds - ($hours * 3600 + $minutes * 60);

    return ($hours != 0 ? (appendZero($hours).($minutes != 0 && $seconds != 0 ? ':' : '')) : '').
        ($minutes != 0 ? (appendZero($minutes).($seconds != 0 ? ':' : '')) : '').
        ($seconds != 0 ? appendZero($seconds) : '');
}

/******************************************************************************\
 *
 * Takes the number and appends a zero to the front if less than ten
 *
 * @return string  The number with zero ahead if less than ten
 *
\******************************************************************************/
function appendZero($number): string
{
    return (intval($number) < 10 ? '0' : '').intval($number);
}

/******************************************************************************\
 *
 * Cleans the phone number so that it has a static format for the database
 *
 * @return string  The cleaned phone number
 *
\******************************************************************************/
function cleanPhone($number): string
{
    $number = (string) $number;
    foreach ([' ', '_', '(', ')', '-'] as $i => $key) {
        $number = str_replace($key, '', $number);
    }
    return $number;
}

/******************************************************************************\
 *
 * Cleans the number and makes it a whole number unless it should be a decimal
 *
 * @return string  The cleaned number
 *
\******************************************************************************/
function displayNumber($number): int|float
{
    return ($number == (int)$number) ? (int)$number : (float)$number;
}

/******************************************************************************\
 *
 * Obscures the name of the user for public use.
 *
 * @return string  The obscured name
 *
\******************************************************************************/
function obscureName($fullName): string
{
    $fullName = trim((string) $fullName);
    $parts = preg_split('/\s+/', trim($fullName));
    if (count($parts) < 2) {
        // If there's no last name, just return the first name as-is
        return $fullName;
    }
    $firstName = $parts[0];
    $lastName = end($parts);
    $lastChar = mb_substr($lastName, 0, 1);
    return ucwords($firstName.' '.$lastChar.'.');
}
