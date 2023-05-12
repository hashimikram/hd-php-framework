<?php
namespace HDFramework\src;

use Plivo\RestAPI;
use Plivo\PlivoError;

/**
 * Class to maintain SMS api sending
 *
 * Dependencies: HDApplication, HDRedirect
 * Configurations dependencies:<br />
 * - config.*.php: PLIVO_SMS_AUTH_ID, PLIVO_SMS_AUTH_TOKEN, PLIVO_SMS_PHONE, PLIVO_SMS_PHONE_DEFAULT<br />
 * <br />Release date: 09/11/2015
 *
 * @version 6.0
 * @author Cornel
 * @package framework
 */
class HDPlivoSMS
{

    private static $auth_id = "";

    private static $auth_token = "";

    /**
     * Constructor to init objects if they do not exists
     */
    private static function init()
    {
        if (self::$auth_id == "" || self::$auth_token == "") {
            self::$auth_id = HDApplication::getConfiguration("PLIVO_SMS_AUTH_ID");
            self::$auth_token = HDApplication::getConfiguration("PLIVO_SMS_AUTH_TOKEN");
            if (self::$auth_id == "" || self::$auth_token == "") {
                HDRedirect::to("error", "index", "ERROR_200005");
            }

            HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.init", "auth_id", self::$auth_id);
            HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.init", "auth_token", self::$auth_token);

            try {
                return new RestAPI(self::$auth_id, self::$auth_token);
            } catch (PlivoError $e) {
                HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.init", "exception", $e->getMessage(), 3, 'PARAM');
                return false;
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
        $sender = substr((string) $receiver, 0, 1) == '1' ? HDApplication::getConfiguration('PLIVO_SMS_PHONE') : HDApplication::getConfiguration('PLIVO_SMS_PHONE_DEFAULT');
        return array(
            'src' => $sender, // Sender's phone number with country code
            'dst' => '+' . $receiver, // Receiver's phone number with country code
            'text' => $message, // Your SMS text message
            'method' => 'POST' // The method used to call the url
        );
    }

    /**
     * Send SMS via Plivo.com
     *
     * @param String $message
     * @param number $receiver
     * @return Array Response code and description
     */
    public static function sendSms($message, $receiver)
    {
        HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.sendSMS", "message", $message, 3, 'IN');
        HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.sendSMS", "receiver", $receiver, 3, 'IN');
        $plivo = self::init();
        if ($plivo !== false) {
            $params = self::createContext($message, $receiver);
            $response = $plivo->send_message($params);

            HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.sendSMS", "response", $response, 3, 'PARAM');

            if ($response['status'] != 202)
                return array(
                    "returnCode" => 1
                );
            return array(
                "returnCode" => 0
            );
        } else {
            HDLog::AppLogMessage("HDPlivoSMS.php", "HDPlivoSMS.sendSMS", "plivo", false, 3, 'OUT');
            return array(
                "returnCode" => 1
            );
        }
    }
}
