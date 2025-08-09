<?php

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
function generateCode($length, $salt = 'cuztomisable', $prefix = null)
{
    return strval(strtoupper(
        (is_null($prefix) ? '' : $prefix).
        substr(
            md5($salt.uniqid().time()),
            rand(0, 23),
            // Prevents the code from being larger than what is possible for the md5 hash
            $length > 16 ? 16 : ($length < 1 ? 1 : $length)
        )
    ));
}

/******************************************************************************\
 *
 * Gets the current IPv4 address of the user
 * 
 * @return string  The IP Address v4 of the current user
 * 
\******************************************************************************/
function getIpAddress()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

/******************************************************************************\
 *
 * Converts the seconds parameter into hour:minute:second format
 * 
 * @return string  The time in 00:00:00 format
 * 
\******************************************************************************/
function convertToTimeOutput($seconds)
{
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
function appendZero($number)
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
function cleanPhone($number)
{
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
function displayNumber($number)
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
function obscureName($fullName)
{
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