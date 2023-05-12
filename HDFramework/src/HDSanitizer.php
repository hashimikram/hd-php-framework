<?php
namespace HDFramework\src;

/**
 * Class to manage value cleaners
 *
 * Version 6.0
 * Release date: 16/05/2015
 *
 * @author Alin
 * @package framework
 */
class HDSanitizer
{

    /**
     * Remove all characters except "0-9 -"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeMySQLDate($var)
    {
        return (string) preg_replace('/[^0-9\-]/', '', $var);
    }

    /**
     * Remove all characters except "a-z A-Z 0-9 .
     * /"
     * "File Link", "https://s3.amazonaws.com/media.m3.internal/avatars/848b64e4101fe15da651765a3d7e819f"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeFileLink($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\.\:\/]/', '', $var);
    }

    /**
     * Remove all characters except letter, a-z A-Z 0-9 - _ .
     * and space allowed only
     *
     * @param $var -
     *            File name to clean
     */
    public static function sanitizeFileName($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\-\_\.\s]/', '', $var);
    }

    /**
     * Remove all characters except letters, digits and !#$%&'*+-/=?^_`{|}~@.[].
     *
     * @param $emailAddress -
     *            Email address to clean for illegal chars
     */
    public static function sanitizeEmail($emailAddress)
    {
        return (string) trim(strtolower(filter_var($emailAddress, FILTER_SANITIZE_EMAIL)));
    }

    /**
     * Remove all characters except digits, +- and optionally .,eE.
     *
     * @param $numberFloat -
     *            Float number to clean
     */
    public static function sanitizeFloat($numberFloat)
    {
        return floatval(trim(filter_var($numberFloat, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)));
    }

    /**
     * Remove all characters except digits, plus and minus sign.
     *
     * @param
     *            - Int number to clean
     */
    public static function sanitizeInt($numberInt)
    {
        return (int) (trim(filter_var($numberInt, FILTER_SANITIZE_NUMBER_INT)));
    }

    /**
     * Remove all characters except digits, 0-9 allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeDigits($var)
    {
        return (string) preg_replace('/[^0-9]/', '', $var);
    }

    /**
     * Remove all characters except letter, a-z A-Z allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLetters($var)
    {
        return (string) preg_replace('/[^A-Za-z]/', '', $var);
    }

    /**
     * Remove all characters except letter, a-z A-Z 0-9 allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLettersDigits($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9]/', '', $var);
    }

    /**
     * Remove all characters except letter, a-z A-Z 0-9 and space allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLettersDigitsAndSpace($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\s]/', '', $var);
    }

    /**
     * Remove all characters except "a-z A-Z - .
     * and space"
     * Ex: "John Doe", "Alin-Daniel", "M. John", "Ion"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeCUI($var)
    {
        return (string) trim(preg_replace('/[^0-9RrOo\s]/', '', $var));
    }

    /**
     * Remove all characters except "a-z A-Z - .
     * and space"
     * Ex: "John Doe", "Alin-Daniel", "M. John", "Ion"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizePersonName($var)
    {
        return (string) trim(preg_replace('/[^A-Za-z\-\.\s]/', '', $var));
    }

    /**
     * Remove all characters except "a-z A-Z 0-9 - .
     * , / and space"
     * "Grivitei 9", "Bd. Independentei 6-8"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeStreetAddress($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\-\,\.\/\s]/', '', $var);
    }

    /**
     * Remove all characters except "0-9 +"
     * "0722195055", "0040722195055", "+40722195055"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizePhoneNumber($var)
    {
        return (string) preg_replace('/[^0-9\+]/', '', $var);
    }

    /**
     * Remove all characters except "a-z A-Z 0-9 - .
     * , / ! and space"
     * "User comment", "This is my feedback"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeGPSLocation($var)
    {
        return (string) preg_replace('/[^0-9\,\.\s\-]/', '', $var);
    }

    /**
     * Remove all characters except "a-z A-Z 0-9 - .
     * , / ! and space"
     * "User comment", "This is my feedback"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeUserComment($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\-\,\.\/\!\s]/', '', $var);
    }

    /**
     * Remove all characters except letters an underscores, a-z A-Z 0-9 _ allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLettersDigitsUndersore($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9_]+$/', '', $var);
    }

    /**
     * Remove all characters except letters, numbers and dash, a-z A-Z 0-9 - allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLettersDigitsDash($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9-]+$/', '', $var);
    }

    /**
     * Remove all characters except letters, numbers, space and dash, a-z A-Z 0-9 - and space allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLettersDigitsSpaceDash($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\s-]+/', '', $var);
    }

    /**
     * Remove all characters except letters an underscores, a-z A-Z _ allowed only
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeLettersUndersore($var)
    {
        return (string) preg_replace('/[^A-Za-z_]+$/', '', $var);
    }

    /**
     * Remove all characters except letters, digits and $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=.
     *
     * @param $url -
     *            url to clean for illegal chars
     */
    public static function sanitizeUrl($url)
    {
        return (string) trim(filter_var($url, FILTER_SANITIZE_URL));
    }

    /**
     * Remove all characters except digits and space: 0-9 space
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeDigitsSpace($var)
    {
        return (string) preg_replace('/[^0-9\s]/', '', $var);
    }

    /**
     * Remove all characters except "a-z A-Z 0-9 - _ and space"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizePhoneName($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\-\_\s]/', '', $var);
    }

    /**
     * Remove all characters except "a-z A-Z 0-9 -"
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeUID($var)
    {
        return (string) preg_replace('/[^A-Za-z0-9\-]/', '', $var);
    }

    /**
     * Remove all characters except letter any language \p{L}
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeUnicodeLetters($var)
    {
        return (string) preg_replace('~[^\p{L}]++~u', '', $var);
    }

    /**
     * Remove all characters except letters \p{L} and numbers \p{N} any language
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizeUnicodeLettersDigits($var)
    {
        return (string) preg_replace('~[^\p{L}\p{N}]++~u', '', $var);
    }

    /**
     * Remove all characters except:<br />
     * letters in any language \p{L}<br />
     * any kind of punctuation character \p{P}<br />
     * any kind of whitespace or invisible separator \p{Z}<br />
     * any kind of numbers \p{N}<br />
     * Ex: "John Doe", "Alin-Daniel", "M. John", "Ion"<br />
     *
     * @param $var -
     *            Variable to clean<br />
     */
    public static function sanitizeUnicodeGeneralString($var)
    {
        return (string) preg_replace('~\n|\r|\n\r[^\p{L}\p{P}\p{Z}\p{N}\p{M}\S\x0a\x0d]++~u', '', $var);
    }

    /**
     * Remove all characters except upper letters A-Z and number from 0 to 9
     *
     * @param $var -
     *            Variable to clean
     */
    public static function sanitizePromoCode($var)
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', $var);
    }
}
