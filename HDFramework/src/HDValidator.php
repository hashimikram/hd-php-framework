<?php
namespace HDFramework\src;

/**
 * Abstract class to manage form validations
 * Version 6.0
 * Release date: 16/05/2015
 *
 * @author Alin
 * @package framework
 */
abstract class HDValidator
{

    /**
     * Returns TRUE for "1", "true", "on" and "yes".
     * Returns FALSE otherwise.
     * If considerNull is set to 1, FALSE is returned only for "0", "false", "off", "no", and "", and NULL is returned for all non-boolean values.
     *
     * @param $assumedBool -
     *            Boolean value to validate
     */
    public static function validBool($assumedBool, $considerNull = 0)
    {
        HDLog::AppLogMessage('HDValidator.php', 'HDValidator.validBool', 'assumedBool', $assumedBool, 3, 'IN');
        $result = true;
        if ($considerNull == 0) {
            $result = filter_var($assumedBool, FILTER_VALIDATE_BOOLEAN);
            HDLog::AppLogMessage('HDValidator.php', 'HDValidator.validBool', 'result', $result, 3, 'IN');
        } else {
            $result = filter_var($assumedBool, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        return $result;
    }

    /**
     * Validate birth date.
     * Returns false if age less the 18 years of age
     *
     * @param Int $var
     */
    public static function validAdult($birthday)
    {
        $birthday = intval($birthday);
        $minAge = strtotime("-18 YEAR +1 DAY");
        if ($birthday > $minAge)
            return false;
        return true;
    }

    /**
     * Validate birth date.
     * Returns false if age less the 18 years of age or greater than 100
     *
     * @param Int $var
     */
    public static function validAdult100($birthday)
    {
        $birthday = intval($birthday);
        $minAge = strtotime("-18 YEAR +1 DAY");
        $maxAge = strtotime("-100 YEAR");
        if ($birthday < $minAge && $birthday > $maxAge) {
            return true;
        }
        return false;
    }

    /**
     * Validate timestamp.
     * Returns false assumed timestamp is not valid
     *
     * @param string $assumedTimestamp
     */
    public static function validTimestamp($assumedTimestamp)
    {
        return (((string) (int) $assumedTimestamp === $assumedTimestamp) && ($assumedTimestamp <= PHP_INT_MAX) && ($assumedTimestamp >= ~ PHP_INT_MAX));
    }

    /**
     * Validate email address
     *
     * @param String $emailAddress
     *            - Email address to validate
     */
    public static function validEmail($emailAddress)
    {
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate a float number
     *
     * @param $assumedFloat -
     *            Float to validate
     */
    public static function validFloat($assumedFloat)
    {
        if (filter_var($assumedFloat, FILTER_VALIDATE_FLOAT) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate an integer number
     *
     * @param $assumedInt -
     *            Int to validate
     */
    public static function validInt($assumedInt)
    {
        if (filter_var($assumedInt, FILTER_VALIDATE_INT) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate an IPV4 string
     *
     * @param $assumedIP -
     *            IPV4 to validate
     */
    public static function validIpv4($assumedIP)
    {
        if (filter_var($assumedIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate an IPV6 string
     *
     * @param $assumedIP -
     *            IPV6 to validate
     */
    public static function validIpv6($assumedIP)
    {
        if (filter_var($assumedIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate an URL string
     *
     * @param $assumedURL -
     *            URL to validate
     */
    public static function validUrl($assumedURL)
    {
        if (filter_var($assumedURL, FILTER_VALIDATE_URL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate a required value, mostly String
     *
     * @param $assumedNotEmpty -
     *            Value not allowed to be empty
     */
    public static function validRequired($assumedNotEmpty)
    {
        if ($assumedNotEmpty != "") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate a required value to be non empty string
     *
     * @param $assumedNotEmpty -
     *            Value not allowed to be empty
     */
    public static function validRequiredstrict($assumedNotEmpty)
    {
        if ($assumedNotEmpty !== "") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be of a maximum length, mostly String
     *
     * @param $subject -
     *            Value that should not be bigger than specified length
     * @param $maxLength -
     *            Specified length
     */
    public static function validMaxlen($subject, $maxLength)
    {
        if (self::validInt($maxLength)) {
            if (strlen($subject) <= ((int) $maxLength)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be of a maximum length, mostly multybyte String
     *
     * @param $subject -
     *            Value that should not be bigger than specified length
     * @param $maxLength -
     *            Specified length
     */
    public static function validMbmaxlen($subject, $maxLength)
    {
        if (self::validInt($maxLength)) {
            if (mb_strlen($subject) <= ((int) $maxLength)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be of a minimum length, mostly String
     *
     * @param $subject -
     *            Value that should not be smaller than specified length
     * @param $minLength -
     *            Specified length
     */
    public static function validMinlen($subject, $minLength)
    {
        if (self::validInt($minLength)) {
            if (strlen($subject) >= ((int) $minLength)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be of an exact length, mostly String
     *
     * @param $subject -
     *            Value that should not be different than specified length
     * @param $wantedLength -
     *            Specified length
     */
    public static function validExactlen($subject, $wantedLength)
    {
        if (self::validInt($wantedLength)) {
            if (strlen($subject) == ((int) $wantedLength)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate if a string contains another string
     *
     * @param $needle -
     *            Value to search for
     * @param $haystack -
     *            Value to be searched in
     */
    public static function validContains($haystack, $needle)
    {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate if a string exists in an array of values
     *
     * @param string $needle
     *            - Value to search for
     * @param array $haystack
     *            - Value to be searched in
     */
    public static function validInarray($needle, $haystack)
    {
        if (is_array($haystack)) {
            if (in_array(strval($needle), $haystack)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate if a string is a valid mysql date witrh format YYYY-MM-DD
     *
     * @param $assumedSqlDate -
     *            Value to be checked
     */
    public static function validMysqldate($assumedSqlDate)
    {
        $elementsArray = explode("-", $assumedSqlDate);
        if (count($elementsArray) != 3) {
            return false;
        }
        return checkdate((int) $elementsArray[1], (int) $elementsArray[2], (int) $elementsArray[0]);
    }

    /**
     * Check if a password is valid
     * Password must be at least 6 characters and must contain at least one lower case letter, one upper case letter and one digit
     *
     * @param String $password
     *            - password to be validated
     */
    public static function validPassword($password)
    {
        if (preg_match("/^.*(?=.{6,})(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*+=]).*$/", $password) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if a password is valid
     * Password must be at least 6 characters and must contain at least one leter and at least one number
     *
     * @param String $password
     *            - password to be validated
     */
    public static function validLightpassword($password)
    {
        if (preg_match("/^(?=(.*[A-Za-z]){1,})(?=(.*[\d]){1,}).{6,}$/", $password) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if a password is valid
     * Password must be at least 7 characters and must contain at least one leter and at least one number
     *
     * @param String $password
     *            - password to be validated
     */
    public static function validPassword7alpha($password)
    {
        if (preg_match("/^(?=(.*[A-Za-z]){1,})(?=(.*[\d]){1,}).{7,}$/", $password) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if a user password is valid
     * Password must be at least 8 characters and must contain at least one leter and at least one number
     *
     * @param String $password
     *            - password to be validated
     */
    public static function validPassword8alpha($password)
    {
        if (preg_match("/^(?=(.*[A-Za-z]){1,})(?=(.*[\d]){1,}).{8,}$/", $password) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if IBAN is valid
     *
     * @param String $iban
     *            - iban to be validated
     */
    public static function validIBAN($iban)
    {
        if (empty($iban)) {
            return false;
        }
        return verify_iban($iban);
    }

    /**
     * Check if CUI cod unic inregistrare is valid
     *
     * @param String $cui
     *            - cui to be validated
     */
    public static function validCIF($cif)
    {
        if (! is_numeric($cif)) {
            return false;
        }

        if (strlen($cif) > 10) {
            return false;
        }

        $cifra_control = substr($cif, - 1);

        $cif = substr($cif, 0, - 1);

        while (strlen($cif) != 9) {
            $cif = '0' . $cif;
        }

        $suma = $cif[0] * 7 + $cif[1] * 5 + $cif[2] * 3 + $cif[3] * 2 + $cif[4] * 1 + $cif[5] * 7 + $cif[6] * 5 + $cif[7] * 3 + $cif[8] * 2;

        $suma = $suma * 10;

        $rest = fmod($suma, 11);

        if ($rest == 10) {
            $rest = 0;
        }

        if ($rest == $cifra_control) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if CNP cod numeric personal is valid
     *
     * @param String $cnp
     *            - cnp to be validated
     */
    public static function validCNP($cnp)
    {
        $p1 = substr($cnp, - 13, 1) * 2;
        $p2 = substr($cnp, - 12, 1) * 7;
        $p3 = substr($cnp, - 11, 1) * 9;
        $p4 = substr($cnp, - 10, 1) * 1;
        $p5 = substr($cnp, - 9, 1) * 4;
        $p6 = substr($cnp, - 8, 1) * 6;
        $p7 = substr($cnp, - 7, 1) * 3;
        $p8 = substr($cnp, - 6, 1) * 5;
        $p9 = substr($cnp, - 5, 1) * 8;
        $p10 = substr($cnp, - 4, 1) * 2;
        $p11 = substr($cnp, - 3, 1) * 7;
        $p12 = substr($cnp, - 2, 1) * 9;
        $uc = substr($cnp, - 1, 1); // suma de control din input
        $s = $p1 + $p2 + $p3 + $p4 + $p5 + $p6 + $p7 + $p8 + $p9 + $p10 + $p11 + $p12;
        $int = (int) ($s / 11);
        $c = $s - (11 * $int); // suma de control calculata
        if ($c == 10) {
            $c = 1;
        }
        if ($c == $uc) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be equal with another value
     *
     * @param $subject -
     *            Value that should not be different than the other
     * @param $wantedEqual -
     *            The other
     */
    public static function validEqual($subject, $wantedEqual)
    {
        if (($subject != "") && ($wantedEqual != "")) {
            if ($subject == $wantedEqual) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be not equal with another value
     *
     * @param $subject -
     *            Value that should be different than the other
     * @param $wantedEqual -
     *            The other
     */
    public static function validNotEqual($subject, $wantedEqual)
    {
        if ($subject != "") {
            if ($subject != $wantedEqual) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate a value to be a valid file/folder name
     *
     * @param $subject -
     *            Folder name
     */
    public static function validFilename($subject)
    {
        if (strpbrk($subject, "\\/?%*:|\"<>") === FALSE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate a file to exists
     *
     * @param $subject -
     *            File/folder name
     */
    public static function validFileexists($subject)
    {
        return file_exists($subject);
    }

    /**
     * Validate a string to be a directory on HDD
     *
     * @param $subject -
     *            Folder name
     */
    public static function validIsdir($subject)
    {
        return is_dir($subject);
    }

    /**
     * Validate String if it is ISO 3166-1 alpha-2 valid.
     * Returns bool. Last edit in 2016/02/29
     *
     * @param String $assumedCountryCode.
     *
     */
    public static function validCountryCode($assumedCountryCode)
    {
        $isoCountryCodes = array(
            'AD' => 'Andorra',
            'AE' => 'United Arab Emirates',
            'AF' => 'Afghanistan',
            'AG' => 'Antigua and Barbuda',
            'AI' => 'Anguilla',
            'AL' => 'Albania',
            'AM' => 'Armenia',
            'AO' => 'Angola',
            'AQ' => 'Antarctica',
            'AR' => 'Argentina',
            'AS' => 'American Samoa',
            'AT' => 'Austria',
            'AU' => 'Australia',
            'AW' => 'Aruba',
            'AX' => '�land Islands',
            'AZ' => 'Azerbaijan',
            'BA' => 'Bosnia and Herzegovina',
            'BB' => 'Barbados',
            'BD' => 'Bangladesh',
            'BE' => 'Belgium',
            'BF' => 'Burkina Faso',
            'BG' => 'Bulgaria',
            'BH' => 'Bahrain',
            'BI' => 'Burundi',
            'BJ' => 'Benin',
            'BL' => 'Saint Barth�lemy',
            'BM' => 'Bermuda',
            'BN' => 'Brunei Darussalam',
            'BO' => 'Bolivia, Plurinational State of',
            'BQ' => 'Bonaire, Sint Eustatius and Saba',
            'BR' => 'Brazil',
            'BS' => 'Bahamas',
            'BT' => 'Bhutan',
            'BV' => 'Bouvet Island',
            'BW' => 'Botswana',
            'BY' => 'Belarus',
            'BZ' => 'Belize',
            'CA' => 'Canada',
            'CC' => 'Cocos (Keeling) Islands',
            'CD' => 'Congo, the Democratic Republic of the',
            'CF' => 'Central African Republic',
            'CG' => 'Congo',
            'CH' => 'Switzerland',
            'CI' => 'Cote d\'Ivoire',
            'CK' => 'Cook Islands',
            'CL' => 'Chile',
            'CM' => 'Cameroon',
            'CN' => 'China',
            'CO' => 'Colombia',
            'CR' => 'Costa Rica',
            'CU' => 'Cuba',
            'CV' => 'Cabo Verde',
            'CW' => 'Curacao',
            'CX' => 'Christmas Island',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DJ' => 'Djibouti',
            'DK' => 'Denmark',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'DZ' => 'Algeria',
            'EC' => 'Ecuador',
            'EE' => 'Estonia',
            'EG' => 'Egypt',
            'EH' => 'Western Sahara',
            'ER' => 'Eritrea',
            'ES' => 'Spain',
            'ET' => 'Ethiopia',
            'FI' => 'Finland',
            'FJ' => 'Fiji',
            'FK' => 'Falkland Islands (Malvinas)',
            'FM' => 'Micronesia, Federated States of',
            'FO' => 'Faroe Islands',
            'FR' => 'France',
            'GA' => 'Gabon',
            'GB' => 'United Kingdom of Great Britain and Northern Ireland',
            'GD' => 'Grenada',
            'GE' => 'Georgia',
            'GF' => 'French Guiana',
            'GG' => 'Guernsey',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GL' => 'Greenland',
            'GM' => 'Gambia',
            'GN' => 'Guinea',
            'GP' => 'Guadeloupe',
            'GQ' => 'Equatorial Guinea',
            'GR' => 'Greece',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'GT' => 'Guatemala',
            'GU' => 'Guam',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HK' => 'Hong Kong',
            'HM' => 'Heard Island and McDonald Islands',
            'HN' => 'Honduras',
            'HR' => 'Croatia',
            'HT' => 'Haiti',
            'HU' => 'Hungary',
            'ID' => 'Indonesia',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IM' => 'Isle of Man',
            'IN' => 'India',
            'IO' => 'British Indian Ocean Territory',
            'IQ' => 'Iraq',
            'IR' => 'Iran, Islamic Republic of',
            'IS' => 'Iceland',
            'IT' => 'Italy',
            'JE' => 'Jersey',
            'JM' => 'Jamaica',
            'JO' => 'Jordan',
            'JP' => 'Japan',
            'KE' => 'Kenya',
            'KG' => 'Kyrgyzstan',
            'KH' => 'Cambodia',
            'KI' => 'Kiribati',
            'KM' => 'Comoros',
            'KN' => 'Saint Kitts and Nevis',
            'KP' => 'Korea, Democratic People\'s Republic of',
            'KR' => 'Korea, Republic of',
            'KW' => 'Kuwait',
            'KY' => 'Cayman Islands',
            'KZ' => 'Kazakhstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LB' => 'Lebanon',
            'LC' => 'Saint Lucia',
            'LI' => 'Liechtenstein',
            'LK' => 'Sri Lanka',
            'LR' => 'Liberia',
            'LS' => 'Lesotho',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'LY' => 'Libya',
            'MA' => 'Morocco',
            'MC' => 'Monaco',
            'MD' => 'Moldova, Republic of',
            'ME' => 'Montenegro',
            'MF' => 'Saint Martin (French part)',
            'MG' => 'Madagascar',
            'MH' => 'Marshall Islands',
            'MK' => 'Macedonia, the former Yugoslav Republic of',
            'ML' => 'Mali',
            'MM' => 'Myanmar',
            'MN' => 'Mongolia',
            'MO' => 'Macao',
            'MP' => 'Northern Mariana Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MS' => 'Montserrat',
            'MT' => 'Malta',
            'MU' => 'Mauritius',
            'MV' => 'Maldives',
            'MW' => 'Malawi',
            'MX' => 'Mexico',
            'MY' => 'Malaysia',
            'MZ' => 'Mozambique',
            'NA' => 'Namibia',
            'NC' => 'New Caledonia',
            'NE' => 'Niger',
            'NF' => 'Norfolk Island',
            'NG' => 'Nigeria',
            'NI' => 'Nicaragua',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'NP' => 'Nepal',
            'NR' => 'Nauru',
            'NU' => 'Niue',
            'NZ' => 'New Zealand',
            'OM' => 'Oman',
            'PA' => 'Panama',
            'PE' => 'Peru',
            'PF' => 'French Polynesia',
            'PG' => 'Papua New Guinea',
            'PH' => 'Philippines',
            'PK' => 'Pakistan',
            'PL' => 'Poland',
            'PM' => 'Saint Pierre and Miquelon',
            'PN' => 'Pitcairn',
            'PR' => 'Puerto Rico',
            'PS' => 'Palestine, State of',
            'PT' => 'Portugal',
            'PW' => 'Palau',
            'PY' => 'Paraguay',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RS' => 'Serbia',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'SA' => 'Saudi Arabia',
            'SB' => 'Solomon Islands',
            'SC' => 'Seychelles',
            'SD' => 'Sudan',
            'SE' => 'Sweden',
            'SG' => 'Singapore',
            'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
            'SI' => 'Slovenia',
            'SJ' => 'Svalbard and Jan Mayen',
            'SK' => 'Slovakia',
            'SL' => 'Sierra Leone',
            'SM' => 'San Marino',
            'SN' => 'Senegal',
            'SO' => 'Somalia',
            'SR' => 'Suriname',
            'SS' => 'South Sudan',
            'ST' => 'Sao Tome and Principe',
            'SV' => 'El Salvador',
            'SX' => 'Sint Maarten (Dutch part)',
            'SY' => 'Syrian Arab Republic',
            'SZ' => 'Swaziland',
            'TC' => 'Turks and Caicos Islands',
            'TD' => 'Chad',
            'TF' => 'French Southern Territories',
            'TG' => 'Togo',
            'TH' => 'Thailand',
            'TJ' => 'Tajikistan',
            'TK' => 'Tokelau',
            'TL' => 'Timor-Leste',
            'TM' => 'Turkmenistan',
            'TN' => 'Tunisia',
            'TO' => 'Tonga',
            'TR' => 'Turkey',
            'TT' => 'Trinidad and Tobago',
            'TV' => 'Tuvalu',
            'TW' => 'Taiwan, Province of China',
            'TZ' => 'Tanzania, United Republic of',
            'UA' => 'Ukraine',
            'UG' => 'Uganda',
            'UM' => 'United States Minor Outlying Islands',
            'US' => 'United States of America',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VA' => 'Holy See',
            'VC' => 'Saint Vincent and the Grenadines',
            'VE' => 'Venezuela, Bolivarian Republic of',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'VN' => 'Viet Nam',
            'VU' => 'Vanuatu',
            'WF' => 'Wallis and Futuna',
            'WS' => 'Samoa',
            'YE' => 'Yemen',
            'YT' => 'Mayotte',
            'ZA' => 'South Africa',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe'
        );
        $assumedCountryCode = strtoupper($assumedCountryCode);
        if (array_key_exists($assumedCountryCode, $isoCountryCodes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate several values by calling appropriate validate method
     *
     * @param $validationsArray -
     *            An array of value to be evaluated. Each element is an array with this elements: field, validation, message, value1, value2 where
     *            field = name of the field/variable which is evaluated
     *            validation = validation type which can be : required or minlen
     *            message = error message to be consider in case value does not pass validation
     *            value1 = value to be evaluated
     *            value2 = second parameters which might be needed by the evaluate method. Ex: minlen requires the length to be compared with
     */
    public static function validate($validationsArray)
    {
        $errorMessages = array();
        foreach ($validationsArray as $validationElement) {
            $result = true;
            $forceTwoParams = false;

            if (array_key_exists('forceTwoParams', $validationElement)) {
                $forceTwoParams = $validationElement['forceTwoParams'];
            }

            if (! $forceTwoParams && $validationElement["value2"] == "") {
                $result = call_user_func(__CLASS__ . "::valid" . ucfirst(strtolower($validationElement["validation"])), $validationElement["value1"]);
            } else {
                $result = call_user_func(__CLASS__ . "::valid" . ucfirst(strtolower($validationElement["validation"])), $validationElement["value1"], $validationElement["value2"]);
            }
            if (! $result) {
                $errorMessages[$validationElement["field"]][] = $validationElement["message"];
            }
        }
        return $errorMessages;
    }

    /**
     * Similar with validate but messages are returned all in one simple classic array
     */
    public static function validatePlain($validationsArray)
    {
        $errorMessages = array();
        foreach ($validationsArray as $validationElement) {
            $result = true;
            if ($validationElement["value2"] == "") {
                $result = @call_user_func(__CLASS__ . "::valid" . ucfirst(strtolower($validationElement["validation"])), $validationElement["value1"]);
            } else {
                $result = call_user_func(__CLASS__ . "::valid" . ucfirst(strtolower($validationElement["validation"])), $validationElement["value1"], $validationElement["value2"]);
            }
            if (! $result) {
                $errorMessages[] = $validationElement["message"];
            }
        }
        return $errorMessages;
    }

    public static function validRoutingNumber($routingNumber = 0)
    {
        $routingNumber = preg_replace('[\D]', '', $routingNumber); // only digits
        if (strlen($routingNumber) != 9) {
            return false;
        }

        $checkSum = 0;
        for ($i = 0, $j = strlen($routingNumber); $i < $j; $i += 3) {
            // loop through routingNumber character by character
            $checkSum += ($routingNumber[$i] * 3);
            $checkSum += ($routingNumber[$i + 1] * 7);
            $checkSum += ($routingNumber[$i + 2]);
        }

        if ($checkSum != 0 and ($checkSum % 10) == 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function validBic($bic = "")
    {
        // validateEmpty
        if (null === $bic || '' === $bic) {
            return false;
        }

        // must contain alphanumeric values only
        if (! ctype_alnum($bic)) {
            return false;
        }

        // the bic must be either 8 or 11 characters long
        if (! in_array(strlen($bic), array(
            8,
            11
        ))) {
            return false;
        }

        // should contain uppercase characters only
        if (strtoupper($bic) !== $bic) {
            return false;
        }

        // first 4 letters must be alphabetic (bank code)
        if (! ctype_alpha(substr($bic, 0, 4))) {
            return false;
        }

        // next 2 letters must be alphabetic (country code)
        if (! ctype_alpha(substr($bic, 4, 2))) {
            return false;
        }

        // next 2 letters must be alphabetic (location code)
        if (! ctype_alpha(substr($bic, 6, 2))) {
            return false;
        }

        // if bic is 11 chars long validate branch code
        if (strlen($bic) == 11) {
            // branch code must be alpha numeric
            if (! ctype_alnum(substr($bic, 8, 11))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate if subject is greater or equal to compare value
     *
     * @param string $subject
     * @param string $wantedLess
     */
    public static function validGreaterorequalthan($subject, $compareValue)
    {
        $subject = strval($subject);
        $compareValue = strval($compareValue);
        if (bccomp($compareValue, $subject) === 1) {
            return false;
        }
        return true;
    }

    /**
     * Validate if subject is greater to compare value
     *
     * @param string $subject
     * @param string $wantedMore
     */
    public static function validGreaterthan($subject, $compareValue)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'subject', $subject, 3, 'IN');
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'compareValue', $compareValue, 3, 'IN');
        $subject = strval($subject);
        $compareValue = strval($compareValue);
        if (bccomp($subject, $compareValue) === 1) {
            return true;
        }
        return false;
    }

    /**
     * Validate if subject is smaller to compare value
     *
     * @param string $subject
     * @param string $wantedMore
     */
    public static function validSmallerthan($subject, $compareValue)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'subject', $subject, 3, 'IN');
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'compareValue', $compareValue, 3, 'IN');
        $subject = strval($subject);
        $compareValue = strval($compareValue);
        if (bccomp($compareValue, $subject) === 1) {
            return true;
        }
        return false;
    }

    /**
     * Validates if subject is valid for the given pattern
     *
     * @param string $subject
     * @param string $pattern
     * @return boolean
     */
    public static function validRegex($subject, $pattern)
    {
        if (empty($subject) || empty($pattern)) {
            return false;
        }
        return boolval(preg_match($pattern, $subject));
    }
}
