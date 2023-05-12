<?php
namespace HDFramework\src;

/**
 * Class to maintain SMS api sending
 *
 * Dependencies: HDApplication, HDRedirect
 * Configurations dependencies:<br />
 * - config.*.php: NEXMO_SMS_KEY, NEXMO_SMS_SECRET<br />
 * <br />Release date: 09/11/2015
 *
 * @version 7.0
 * @author cornel
 * @package framework
 */
class HDNexmoSMS
{

    /**
     * The search is still in progress.
     *
     * @var string
     */
    const IN_PROGRESS = "IN PROGRESS";

    /**
     * User entered a correct verification code.
     *
     * @var string
     */
    const SUCCESS = "SUCCESS";

    /**
     * User entered an incorrect code more than three times
     *
     * @var string
     */
    const FAILED = "FAILED";

    /**
     * User did not enter a code before the pin_expiry time elapsed
     *
     * @var string
     */
    const EXPIRED = "EXPIRED";

    /**
     * The verification process was cancelled by a Verify control request
     *
     * @var string
     */
    const CANCELLED = "CANCELLED";

    const BASE_REST_URL = "https://rest.nexmo.com/";

    const BASE_API_URL = "https://api.nexmo.com/";

    private static $apiKey = '';

    private static $apiSecret = '';

    private static $data_array = '';

    private static $error_codes = array(
        '6' => 'Sms code exipred. Please request a new one.',
        '15' => 'The phone number is not in a supported network.',
        '16' => 'The code provided does not match the expected value.',
        '17' => 'Too many wrong codes. Please restart registration process.',
        '102' => 'An error occurred processing this request.'
    );

    private static function init()
    {
        if ((self::$apiKey == '') || (self::$apiSecret == '')) {
            self::$apiKey = HDApplication::getConfiguration("NEXMO_SMS_KEY");
            self::$apiSecret = HDApplication::getConfiguration("NEXMO_SMS_SECRET");
            self::$data_array = array(
                'api_key' => self::$apiKey,
                'api_secret' => self::$apiSecret
            );
        }
    }

    public static function sendSms($from = "", $to = "", $text = "")
    {
        if (empty($from) || empty($to) || empty($text)) {
            return "";
        }

        $from = strval($from);
        $to = strval($to);
        $text = strval($text);

        self::init();
        $target = self::BASE_REST_URL . 'sms/json';
        $data_array = self::$data_array;
        $data_array['from'] = $from;
        $data_array['to'] = $to;
        $data_array['text'] = $text;

        $response = HDUtils::http($target, 'POST', $data_array);

        // if we have cURL error we send email to support and return ''
        if (! empty($response['ERROR'])) {
            HDLog::AppLogMessage(__CLASS__, 'sendSms', 'cURL error', $response['ERROR'], 4, 'L');
            return '';
        } else {
            $file = json_decode($response['FILE'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                foreach ($file['messages'] as $message) {
                    if ($message['status'] == 0) {
                        HDLog::AppLogMessage(__CLASS__, 'sendSms', 'success', 'Success ' . $message['message-id'], 3, 'OUT');
                    } else {
                        HDLog::AppLogMessage(__CLASS__, 'sendSms', 'error', $message, 4, 'OUT');
                    }
                }
            } else {
                HDLog::AppLogMessage(__CLASS__, 'sendSms', 'response[FILE] not a json', var_dump($response['FILE']), 4, 'OUT');
                return '';
            }
        }
    }

    public static function getAccountBalance()
    {
        self::init();
        $target = self::BASE_REST_URL . 'account/get-balance/';
        $data_array = self::$data_array;
        $response = HDUtils::http($target, 'GET', $data_array);
        echo '<pre>' . print_r($response, true) . '</pre>';
    }

    public static function getCleanAccountBalance()
    {
        self::init();
        $target = self::BASE_REST_URL . 'account/get-balance/';
        $data_array = self::$data_array;
        $response = HDUtils::http($target, 'GET', $data_array);

        return $response;
    }

    public static function verifyRequest($parameters = array())
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'parameters', $parameters, 3, 'IN');

        // init return variable
        $returnArray = array();

        if (empty($parameters)) {
            $returnArray['error'] = self::$error_codes['102'];
            return $returnArray;
        }

        self::init();

        $target = self::BASE_API_URL . 'verify/json';
        $data_array = self::$data_array;
        foreach ($parameters as $key => $parameter) {
            $data_array[$key] = $parameter;
        }
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'data_array', $data_array, 3, 'L');

        $response = HDUtils::http($target, 'POST', $data_array);
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'response', $response, 3, 'IN');

        // if we have cURL error we send email to support
        if (! empty($response['ERROR'])) {

            // An error occurred. We cannot register you at the moment.
            $returnArray['error'] = self::$error_codes['102'];
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cURL error', $response['ERROR'], 4, 'L');

            return $returnArray;
        }

        return self::treatNexmoResponse($response, $parameters);
    }

    public static function verifySearch($requestId = '')
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'requestId', $requestId, 3, 'IN');

        if (empty($requestId)) {
            return '';
        }
        self::init();

        $target = self::BASE_API_URL . 'verify/search/json';

        $data_array = self::$data_array;
        $data_array['request_id'] = $requestId;
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'data_array', $data_array, 3, 'OUT');

        $response = HDUtils::http($target, 'POST', $data_array);
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'response', $response, 3, 'OUT');

        if (! empty($response['ERROR'])) {
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cURL error', $response['ERROR'], 4, 'L');
            return '';
        } else {
            $file = json_decode($response['FILE'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'file', $file, 3, 'OUT');
                return $file;
            } else {
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'response[FILE] not a json', var_dump($response['FILE']), 3, 'OUT');
                return '';
            }
        }
    }

    public static function verifyCheck($parameters = array())
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'parameters', $parameters, 3, 'IN');

        // init return variable
        $returnArray = array();
        if (empty($parameters)) {

            // An error occurred. We cannot register you at the moment.
            $returnArray['error'] = self::$error_codes['102'];
            return $returnArray;
        }

        self::init();

        $target = self::BASE_API_URL . 'verify/check/json';
        $data_array = self::$data_array;
        foreach ($parameters as $key => $parameter) {
            $data_array[$key] = $parameter;
        }
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'data_array', $data_array, 3, 'OUT');

        $response = HDUtils::http($target, 'POST', $data_array);
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'response', $response, 3, 'OUT');

        // cURL error
        if (! empty($response['ERROR'])) {

            // An error occurred. We cannot register you at the moment.
            $returnArray['error'] = self::$error_codes['102'];
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cURL error', $response['ERROR'], 4, 'L');

            return $returnArray;
        }

        return self::treatNexmoResponse($response, $parameters, "check");
    }

    private static function treatNexmoResponse($response, $parameters, $method = "request")
    {
        $returnArray = array();

        // decode response
        $file = json_decode($response['FILE'], true);

        if (json_last_error() === JSON_ERROR_NONE) {

            switch ($file['status']) {

                case 0:
                    // Success
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'file', $file, 3, 'OUT');
                    return $file;

                case 1:
                    // Throttled - You are trying to send more than the maximum of 30 requests per second.
                    sleep(1);
                    $method == "check" ? self::verifyCheck($parameters) : self::verifyRequest($parameters);
                    break;

                case 6:
                    // Sms code exipred. Please request a new one.
                    $returnArray['error'] = self::$error_codes['6'];
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'return', 'status 6', 3, 'OUT');
                    break;

                case 15:
                    // The phone number is not in a supported network.
                    $returnArray['error'] = self::$error_codes['15'];
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'return', 'status 15', 3, 'OUT');
                    break;

                case 16:
                    // The code provided does not match the expected value.
                    $returnArray['error'] = self::$error_codes['16'];
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'return', 'status 16', 3, 'OUT');
                    break;

                case 17:
                    // Too many wrong codes. Please restart registration process.
                    $returnArray['error'] = self::$error_codes['17'];
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'return', 'status 17', 3, 'OUT');
                    break;

                default:
                    // An error occurred. We cannot register you at the moment.
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'nexmo status not 0', 'status: ' . $file['status'] . ', error text: ' . $file['error_text'], 4, 'L');
                    $returnArray['error'] = self::$error_codes['102'];
                    break;
            }
        }
        return $returnArray;
    }
}
