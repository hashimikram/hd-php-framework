<?php
namespace HDFramework\src;

/**
 * Translation class to manage strings to be translated
 *
 * Configurations dependencies:<br />
 * - config.*.php: URL<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author cornel
 * @package framework
 */
class HDTranslator
{

    /**
     * Searches the received string.
     * If found returns error array, else false
     *
     * @param string $string
     */
    public static function isError($string = '')
    {
        global $_LANG;
        $errorCode = array();

        // generate hash of string
        $stringHash = hash('md5', $string);

        foreach ($_LANG as $hashCode => $message) {
            $hash = substr($hashCode, - 32);
            if ($stringHash == $hash) {
                $underscorePosition = strpos($hashCode, '_');
                if ($underscorePosition !== false) {
                    $errorCode = substr($hashCode, 0, $underscorePosition);
                    return array(
                        'code' => $errorCode,
                        'message' => $message
                    );
                }
            }
        }
        return false;
    }

    /**
     * Function that returns an error array response type from a given error string
     *
     * @param string $string
     * @return array
     */
    public static function getCode($string = '')
    {
        global $_LANG;
        $errorCode = array();

        // HDLog::AppLogMessage('HDTranslator', 'getCode', 'string', $string, 3, 'IN');

        // generate hash of string
        $stringHash = hash('md5', $string);
        // HDLog::AppLogMessage('HDTranslator', 'getCode', 'stringHash', $stringHash, 3, 'L');

        foreach ($_LANG as $hashCode => $message) {
            $hash = substr($hashCode, - 32);
            if ($stringHash == $hash) {
                $underscorePosition = strpos($hashCode, '_');
                // HDLog::AppLogMessage('HDTranslator', 'getCode', 'hashCode', $hashCode, 3, 'L');
                // HDLog::AppLogMessage('HDTranslator', 'getCode', 'hash', $hash, 3, 'L');
                // HDLog::AppLogMessage('HDTranslator', 'getCode', 'underscorePosition', $underscorePosition, 3, 'L');

                if ($underscorePosition !== false) {
                    $errorCode = substr($hashCode, 0, $underscorePosition);
                    return array(
                        'code' => $errorCode,
                        'message' => $message
                    );
                }
                // HDLog::AppLogMessage('HDTranslator', 'getCode', 'CODE NOT FOUND 01', $string, 4, 'L');
                return array(
                    'code' => '0',
                    'message' => $string
                );
            }
        }
        HDLog::AppLogMessage('HDTranslator', 'getCode', 'CODE NOT FOUND 02', $string, 4, 'L');
        return array(
            'code' => '0',
            'message' => $string
        );
    }

    public static function getString($string = '', $isCode = false, $args = array(), $languages = array())
    {
        global $_LANG;
        // HDLog::AppLogMessage('HDTranslator', 'getString', 'string', $string, 3, 'IN');
        // HDLog::AppLogMessage('HDTranslator', 'getString', 'args', $args, 3, 'IN');
        $returnString = $string;

        // generate hash of string
        $stringHash = hash('md5', $string);
        if ($isCode) {
            $stringHash = $string . '_' . $stringHash;
        }

        // load language file
        if (count($languages)) {
            HDApplication::loadLanguage($languages['receiver']);
        }

        // HDLog::AppLogMessage('HDTranslator', 'getString', 'stringHash', $stringHash, 3, 'L');
        if (isset($_LANG) && is_array($_LANG) && (count($_LANG) > 0) && array_key_exists($stringHash, $_LANG)) {
            if (count($args) != 0) {
                $returnString = vsprintf($_LANG[$stringHash], $args);
            } else {
                $returnString = $_LANG[$stringHash];
            }
        } else {
            HDLog::AppLogMessage('HDTranslator', 'getString', 'string not translated', '$_LANG["' . $stringHash . '"] = "' . $string . '";', 4, 'L');
        }

        // HDLog::AppLogMessage('HDTranslator', 'getString', 'returnString', $returnString, 3, 'OUT');
        // unload language
        if (count($languages)) {
            HDApplication::loadLanguage($languages['sender']);
        }
        return $returnString;
    }
}
