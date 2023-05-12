<?php
namespace HDFramework\src;

/**
 * Class to maintain iOS Push messages
 *
 * Dependencies: HDSettings, HDRedirect<br />
 * Configurations dependencies:<br />
 * - config.*.php: IOS_PUSH_URL, IOS_PUSH_CERT, IOS_PUSH_PASSWORD, IOS_DEFAULT_DID, PUSH_ADDRESS<br />
 * <br />Release date: 04/06/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDiOSPush
{

    private static $serverLink = "";

    private static $certificate = "";

    private static $password = "";

    private static $port = "";

    /**
     * Constructor to init objects if they do not exists
     */
    public static function getInstance($serverLink = "", $certificate = "", $password = "", $port = "")
    {
        self::$serverLink = $serverLink;
        self::$certificate = $certificate;
        self::$password = $password;
        self::$port = $port;
        if (self::$certificate == "" || self::$serverLink == "" || self::$password == "" || self::$port == "") {
            HDRedirect::to("error", "index", "ERROR_200005");
        }
        return new self();
    }

    public function push($deviceId, $message, $alertMessage, $silent = false, $category = '', $expiry = '')
    {
        if ($deviceId == HDApplication::getConfiguration("IOS_DEFAULT_DID")) {
            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'return', "Skip push for default id", 3, "OUT");
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
        HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'payload', $payload, 3, "L");

        // try to connect to PushService
        if (! ($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $isError = true;
            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService', "Couldn't create socket: [$errorcode] $errormsg", 3, "OUT");
        } else {
            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService', "Socket created", 3, "OUT");

            $address = HDApplication::getConfiguration('PUSH_ADDRESS');
            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'socket address', $address, 3, "OUT");

            foreach ($address as $addres) {
                if (! socket_connect($sock, $addres, self::$port)) {
                    $errorcode = socket_last_error();
                    $errormsg = socket_strerror($errorcode);
                    $isError = true;
                    HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService', "Could not connect: [$errorcode] $errormsg", 3, "OUT");
                } else {
                    HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService', "Connection established", 3, "OUT");

                    $message = array();
                    $message['type'] = "ios";
                    $message['to'] = $deviceId;
                    if ($expiry !== "") {
                        $message['expiry'] = $expiry;
                    }
                    $message['message'] = $payload;
                    $message = json_encode($message);

                    HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService payload', $message, 3, "OUT");

                    if (! socket_send($sock, $message, strlen($message), 0)) {
                        $errorcode = socket_last_error();
                        $errormsg = socket_strerror($errorcode);
                        $isError = true;
                        HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService', "Could not send data: [$errorcode] $errormsg", 3, "OUT");
                    } else {
                        HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'PushService', "Message sent successfully", 3, "OUT");
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

            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'stream START', self::$serverLink, 3, "L");
            $fp = stream_socket_client(self::$serverLink, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', '$err', $err, 3, "L");
            HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', '$errstr', $errstr, 3, "L");

            // test connection
            if (! $fp) {
                HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'failed', $err . " " . $errstr, 3, "L");
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

        HDLog::AppLogMessage('HDiOSPush.php', 'HDiOSPush.push', 'return', $result, 3, "OUT");
        return $result;
    }
}

