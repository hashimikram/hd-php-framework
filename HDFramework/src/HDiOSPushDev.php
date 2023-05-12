<?php
namespace HDFramework\src;

/**
 * Class to maintain iOS Push messages
 *
 * Dependencies: HDSettings, HDRedirect
 * Configurations dependencies:<br />
 * - config.*.php: IOS_PUSH_URL, IOS_PUSH_CERT, IOS_PUSH_PASSWORD, IOS_DEFAULT_DID, PUSH_ADDRESS<br />
 * <br />Release date: 04/06/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDiOSPushDev
{

    private static $serverLink = "";

    private static $certificate = "";

    private static $password = "";

    /**
     * Constructor to init objects if they do not exists
     */
    private static function init()
    {
        if (self::$certificate == "" || self::$serverLink == "" || self::$password == "") {
            self::$serverLink = HDApplication::getConfiguration("IOSDEV_PUSH_URL");
            self::$certificate = HDApplication::getConfiguration("IOSDEV_PUSH_CERT");
            self::$password = HDApplication::getConfiguration("IOSDEV_PUSH_PASSWORD");
            if (self::$certificate == "" || self::$serverLink == "" || self::$password == "") {
                HDRedirect::to("error", "index", "ERROR_200005");
            }
        }
    }

    public static function push($deviceId, $message, $alertMessage, $silent = false, $category = '', $expiry = '')
    {
        if ($deviceId == HDApplication::getConfiguration("IOS_DEFAULT_DID")) {
            HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'return', "Skip push for default id", 3, "OUT");
            return true;
        }

        // Init vars
        $err = "";
        $errstr = "";
        $body = array();
        $result = true;

        $isError = false;
        // Create the payload body

        /*
         * $body['aps'] = array(
         * 'alert' => $alertMessage,
         * 'content-available' => 1,
         * 'sound' => 'default',
         * 'badge' => 1,
         * 'priority' => 5,
         * );
         */

        if (! $silent) {
            $body['aps']['alert'] = $alertMessage;
            $body['aps']['sound'] = 'default';
            $body['aps']['badge'] = 1;
        }

        $body['aps']['content-available'] = 1;
        $body['aps']['priority'] = 5;

        if ($category != '') {
            $body['aps']['category'] = $category;
            $body['aps']['mutable-content'] = 1;
        }

        // $body['message'] = $message;
        $body['data'] = array(
            'content' => $message
        );

        // Encode the payload as JSON
        $payload = json_encode($body);

        // try to connect to PushService
        if (! ($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $isError = true;
            HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService', "Couldn't create socket: [$errorcode] $errormsg", 3, "OUT");
        } else {
            HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService', "Socket created", 3, "OUT");

            $address = HDApplication::getConfiguration('PUSH_ADDRESS');
            HDLog::AppLogMessage('HDGooglePush.php', 'HDGooglePush.push', 'socket address', $address, 3, "OUT");

            foreach ($address as $addres) {
                if (! socket_connect($sock, $addres, 9091)) {
                    $errorcode = socket_last_error();
                    $errormsg = socket_strerror($errorcode);
                    $isError = true;
                    HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService', "Could not connect: [$errorcode] $errormsg", 3, "OUT");
                } else {
                    HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService', "Connection established", 3, "OUT");

                    $message = array();
                    $message['type'] = "ios";
                    $message['to'] = $deviceId;
                    if ($expiry !== "") {
                        $message['expiry'] = $expiry;
                    }
                    $message['message'] = $payload;
                    $message = json_encode($message);

                    HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService payload', $message, 3, "OUT");

                    if (! socket_send($sock, $message, strlen($message), 0)) {
                        $errorcode = socket_last_error();
                        $errormsg = socket_strerror($errorcode);
                        $isError = true;
                        HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService', "Could not send data: [$errorcode] $errormsg", 3, "OUT");
                    } else {
                        HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'PushService', "Message sent successfully", 3, "OUT");
                        return true;
                    }
                }
            }
        }

        if ($isError) {
            self::init();

            // Create a context
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', self::$certificate);
            stream_context_set_option($ctx, 'ssl', 'passphrase', self::$password);

            // Open a connection to the APNS server

            HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'stream START', self::$serverLink, 3, "L");
            $fp = stream_socket_client(self::$serverLink, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', '$err', $err, 3, "L");
            HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', '$errstr', $errstr, 3, "L");

            // test connection
            if (! $fp) {
                HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'failed', $err . " " . $errstr, 3, "L");
                return false;
            }

            // Build the binary notification
            $msg = chr(0) . pack('n', 32) . pack('H*', $deviceId) . pack('n', strlen($payload)) . $payload;

            // Send it to the server
            $res = fwrite($fp, $msg, strlen($msg));

            // Close the connection to the server
            fclose($fp);

            if ($res == false) {
                $result = false;
            }
        }

        HDLog::AppLogMessage('HDiOSPushDev.php', 'HDiOSPushDev.push', 'return', $result, 3, "OUT");
        return $result;
    }
}

