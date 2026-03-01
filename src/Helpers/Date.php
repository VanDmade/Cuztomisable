<?php

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