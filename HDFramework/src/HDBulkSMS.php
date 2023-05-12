<?php
namespace HDFramework\src;

/**
 * Class to maintain SMS api sending
 *
 * Dependencies: HDApplication, HDRedirect
 * <br />
 * Configurations dependencies:<br />
 * - config.*.php: BULK_SMS_URL, BULK_SMS_USER, BULK_SMS_PASSWORD
 * <br />Release date: 04/06/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDBulkSMS
{

    private static $serverLink = "";

    private static $username = "";

    private static $password = "";

    /**
     * Constructor to init objects if they do not exists
     */
    private static function init()
    {
        if (self::$username == "" || self::$serverLink == "" || self::$password == "") {
            self::$serverLink = HDApplication::getConfiguration("BULK_SMS_URL");
            self::$username = HDApplication::getConfiguration("BULK_SMS_USER");
            self::$password = HDApplication::getConfiguration("BULK_SMS_PASSWORD");
            if (self::$username == "" || self::$serverLink == "" || self::$password == "") {
                HDRedirect::to("error", "index", "ERROR_200005");
            }
        }
    }

    /**
     * Create server conetxt for file get contents php method
     *
     * @param String $message
     *            SMS message
     * @param number $receiver
     *            Message receiver
     * @return array for context
     */
    private static function createContext($message, $receiver)
    {
        // build post var
        $postdata = http_build_query(array(
            'username' => self::$username,
            'password' => self::$password,
            'message' => $message,
            'msisdn' => $receiver
        ));

        // build options
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        // create context
        return stream_context_create($opts);
    }

    /**
     * Format json output to array
     *
     * @param String $inputString
     * @return Array
     */
    private static function formatResult($inputString)
    {
        $resultArray = array();
        $responseArray = explode("|", $inputString);
        switch ($responseArray[0]) {
            case "0":
                $resultArray["returnCode"] = 0;
                $resultArray["returnDescription"] = "In progress";
                break;
            case "1":
                $resultArray["returnCode"] = 1;
                $resultArray["returnDescription"] = "Scheduled";
                break;
            case "22":
                $resultArray["returnCode"] = 22;
                $resultArray["returnDescription"] = "Internal fatal error";
                break;
            case "23":
                $resultArray["returnCode"] = 23;
                $resultArray["returnDescription"] = "Authentication failure";
                break;
            case "24":
                $resultArray["returnCode"] = 24;
                $resultArray["returnDescription"] = "Data validation failed";
                break;
            case "25":
                $resultArray["returnCode"] = 25;
                $resultArray["returnDescription"] = "You do not have sufficient credits";
                break;
            case "26":
                $resultArray["returnCode"] = 26;
                $resultArray["returnDescription"] = "Upstream credits not available";
                break;
            case "27":
                $resultArray["returnCode"] = 27;
                $resultArray["returnDescription"] = "You have exceeded your daily quota";
                break;
            case "28":
                $resultArray["returnCode"] = 28;
                $resultArray["returnDescription"] = "Upstream quota exceeded";
                break;
            case "40":
                $resultArray["returnCode"] = 40;
                $resultArray["returnDescription"] = "Temporarily unavailable";
                break;
            case "201":
                $resultArray["returnCode"] = 201;
                $resultArray["returnDescription"] = "Maximum batch size exceeded";
                break;
        }
        return $resultArray;
    }

    /**
     * Send SMS via BulkSMS.com
     *
     * @param String $message
     * @param number $receiver
     * @return Array Response code and description
     */
    public static function sendSms($message, $receiver)
    {
        HDLog::AppLogMessage("HDBulkSMS.php", "HDBulkSMS.sendSMS", "message", $message);
        HDLog::AppLogMessage("HDBulkSMS.php", "HDBulkSMS.sendSMS", "receiver", $receiver);
        self::init();
        $context = self::createContext($message, $receiver);
        return self::formatResult(trim(file_get_contents(self::$serverLink, false, $context)));
    }
}
