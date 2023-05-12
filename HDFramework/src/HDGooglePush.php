<?php
namespace HDFramework\src;

use HDFramework\libs\FCMPushMessage;

/**
 * Class to maintain Google Push notifications
 *
 * Dependencies: HDApplication, HDRedirect<br />
 * Configurations dependencies:<br />
 * - config.*.php: GOOGLE_PUSH_API_KEY, GOOGLE_DEFAULT_DID, PUSH_ADDRESS<br />
 * <br />Release date: 12/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDGooglePush
{

    private static $apiKey = "";

    private static $gcm = "";

    /**
     * Constructor to init objects if they do not exists
     */
    private static function init()
    {
        if ((self::$apiKey == "") || (! self::$gcm)) {
            // create api key
            self::$apiKey = HDApplication::getConfiguration("GOOGLE_PUSH_API_KEY");
            if (self::$apiKey == "") {
                HDRedirect::to("error", "index", "ERROR_200005");
            }

            // if api key created, make gcm object with the api key
            self::$gcm = new FCMPushMessage(self::$apiKey);
        }
    }

    /**
     * Push google message to specified device ID
     *
     * @param String $deviceId
     * @param String $message
     */
    public static function push($deviceId, $message, $collapseKey = "", $timeToLive = "")
    {
        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'message', $message, 3, "IN");
        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'collapseKey', $collapseKey, 3, "IN");
        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'timeToLive', $timeToLive, 3, "IN");

        if ($deviceId == HDApplication::getConfiguration("GOOGLE_DEFAULT_DID")) {
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'return', "Skip push for default id", 3, "OUT");
            return true;
        }

        $isError = false;
        if (! ($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $isError = true;
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'PushService', "Couldn't create socket: [$errorcode] $errormsg", 3, "OUT");
        } else {
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'PushService', "Socket created", 3, "OUT");
            $address = HDApplication::getConfiguration('PUSH_ADDRESS');
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'socket address', $address, 3, "OUT");

            foreach ($address as $addres) {
                if (! socket_connect($sock, $addres, 9090)) {
                    $errorcode = socket_last_error();
                    $errormsg = socket_strerror($errorcode);
                    $isError = true;
                    HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'PushService', "Could not connect: [$errorcode] $errormsg", 3, "OUT");
                } else {
                    HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'PushService', "Connection established", 3, "OUT");
                    $payload = array();
                    $payload['type'] = 'android';
                    $payload['to'] = $deviceId;
                    $payload['message'] = $message;
                    if ($collapseKey != "") {
                        $payload['collapseKey'] = $collapseKey;
                        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'collapseKey', $collapseKey, 3, "L");
                    }
                    if ($timeToLive != "") {
                        $payload['timeToLive'] = $timeToLive;
                        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'timeToLive', $timeToLive, 3, "L");
                    }
                    HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'payload', $payload, 3, "L");

                    $payload = json_encode($payload);
                    if (! socket_send($sock, $payload, strlen($payload), 0)) {
                        $errorcode = socket_last_error();
                        $errormsg = socket_strerror($errorcode);
                        $isError = true;
                        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'PushService', "Could not send data: [$errorcode] $errormsg", 3, "OUT");
                    } else {
                        HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'PushService', "Message sent successfully", 3, "OUT");
                        break;
                    }
                }
            }
        }

        if ($isError) {
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'fallback', "Sending via post", 3, "OUT");
            self::init();
            self::$gcm->setDevices(array(
                $deviceId
            ));
            $result = self::$gcm->send($message);
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'return', $result, 3, "OUT");
            return $result;
        }
        return true;
    }
}

