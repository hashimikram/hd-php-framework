<?php
namespace HDFramework\src;

/**
 * Wrapper class for touch FX API
 *
 * Configurations dependencies:<br />
 * - config.*.php: TOUCHFX_USER, TOUCHFX_PASSWORD, TOUCHFX_POST_URL<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author cornel
 * @package framework
 */
class HDTouchFX
{

    // Constants
    const TEST_DEFAULT_FILE_ID = "http";

    const LIVE_DEFAULT_FILE_ID = "https";

    const DEFAULT_PRODUCT = "SPOT";

    const DEFAULT_COUNTERPARTY = "M3";

    const DEFAULT_DEALTYPE = "BUY";

    const DEFAULT_FWDPOINTS = "0";

    const DEFAULT_SETTLEMENT_DAYS = 2;

    // Members
    private static $default_file_id;

    /**
     * Sends data through POST to TouchFX trade provider
     *
     * @param array $data
     *            - FX transaction details as array to send to provider
     * @return array - Response received from provider
     */
    public static function postTradeData($data)
    {
        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.postTradeData", "data", $data, 3, "IN");

        // 1. Retrieve provider credentials: user, password, url
        $touchFxUser = HDApplication::getConfiguration('TOUCHFX_USER');
        $touchFxPassword = HDApplication::getConfiguration('TOUCHFX_PASSWORD');
        $touchFxPostUrl = HDApplication::getConfiguration('TOUCHFX_POST_URL');

        // 2. Set static data
        self::setStaticData(HDApplication::getEnvType());

        // 3. Prepare data as expected by provider
        $trade = array();
        $trade["fileId"] = self::$default_file_id;
        $trade["clientRefId"] = array_key_exists("clientRefId", $data) ? $data["clientRefId"] : "";
        $trade["product"] = HDTouchFX::DEFAULT_PRODUCT;
        // a. "broker" field is not mandatory and is not sent
        $trade["counterparty"] = HDTouchFX::DEFAULT_COUNTERPARTY;
        $trade["ccyPair"] = array_key_exists("ccyPair", $data) ? $data["ccyPair"] : "";
        $trade["dealType"] = HDTouchFX::DEFAULT_DEALTYPE;
        $trade["dealtCcy"] = array_key_exists("fromCcy", $data) ? $data["fromCcy"] : "";
        $trade["dealtAmount"] = array_key_exists("fromAmount", $data) ? floatval($data["fromAmount"]) : "";
        $trade["counterCcy"] = array_key_exists("toCcy", $data) ? $data["toCcy"] : "";
        $trade["counterAmount"] = array_key_exists("toAmount", $data) ? floatval($data["toAmount"]) : "";
        $trade["rate"] = array_key_exists("rate", $data) ? floatval($data["rate"]) : "";
        // b. date trade is executed as GMT date
        $trade["tradeDate"] = array_key_exists("executionTime", $data) ? gmdate('Ymd', intval($data["executionTime"])) : gmdate('Ymd', time());
        // c. settlement date is 2 working days further from trade date
        $trade["settlementDate"] = date('Ymd', strtotime($trade["tradeDate"] . " +" . HDTouchFX::DEFAULT_SETTLEMENT_DAYS . " Weekday"));
        $trade["spotRate"] = floatval($trade["rate"]);
        $trade["fwdPoints"] = HDTouchFX::DEFAULT_FWDPOINTS;
        // d. time at GMT
        $trade["executionTime"] = array_key_exists("executionTime", $data) ? gmdate('H:i:s', intval($data["executionTime"])) : gmdate('H:i:s', time());
        // e. "notionalAmount" field is not mandatory and is not sent
        $trade["userId"] = $touchFxUser;
        // f. "entity" field is not mandatory and is not sent

        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.postTradeData", "prepared trade", $trade, 3, "L");

        // 4. Determine authorization credentials to be put in the header
        $authorization = self::setAuthorizationCredentials(HDApplication::getEnvType(), $touchFxUser, $touchFxPassword);

        // 5. Construct POST method parameters
        $options = array(
            'http' => array(
                'method' => "POST",
                'header' => $authorization . "\r\n" . "Content-type: application/json" . "\r\n",
                'content' => json_encode($trade)
            ),
            // https://stackoverflow.com/a/26151993/5798913 -- fix warning file_get_contents - ssl3_get_server_certificate:certificate verify failed
            'ssl' => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        );
        $context = stream_context_create($options);

        // 6. Send request to provider
        $result = file_get_contents($touchFxPostUrl, false, $context);
        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.postTradeData", "TouchFX result", $result, 3, "L");

        // 7. Get response and transform it to array
        $reply = json_decode($result, true);
        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.postTradeData", "TouchFX decoded reply", $reply, 3, "L");

        // 8. If file_get_contents did not work, or the result is not a proper json
        if (($result === FALSE) || ($reply == "")) {
            // a. prepare an error $reply
            $reply = array();
            $reply["rejectReason"] = "ERROR of POST to TouchFX: file_get_contents failed or returned response is not proper";
            $reply["clientRefId"] = $trade["clientRefId"];
            HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.postTradeData", "file_get_contents failed", "", 3, "L");
        }

        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.postTradeData", "reply", $reply, 3, "OUT");
        return $reply;
    }

    /**
     * Determines default values as expected by TouchFX provider, depending on the environment type
     *
     * @param string $envType
     *            - Indicates the type of the environment: live or test
     */
    private static function setStaticData($envType)
    {
        if ($envType == "live") {
            self::$default_file_id = HDTouchFX::LIVE_DEFAULT_FILE_ID;
        } else {
            self::$default_file_id = HDTouchFX::TEST_DEFAULT_FILE_ID;
        }
    }

    /**
     * Sets the authorization credentials to be put in the header, depending on the environment type
     *
     * @param string $envType
     *            - Indicates the type of the environment: live or test
     * @param string $user
     *            - User provided by TouchFX
     * @param string $password
     *            - Password provided by TouchFX
     *            return string - authorization to be used in the POST header
     */
    private static function setAuthorizationCredentials($envType, $user, $password)
    {
        if ($envType == "live") {
            // TODO: change in case live connection changes from user + password to user + password + API key
            return "Authorization: Basic " . base64_encode("$user:$password");
        } else {
            return "Authorization: Basic " . base64_encode("$user:$password");
        }
    }

    /**
     * Analyzes the reply sent by TouchFX provider following a POST.
     *
     * Example of failure response: array ("rejectReason" => "some text", "clientRefId" => "TRX700").
     *
     * Example of success response:array ("clientRefId" => "TRX700", "tradeId" => "1476313712754-18").
     *
     * @param array $reply
     *            - Result of a data sent through POST, as replied by provider
     * @return array - Reinterpreted reply as array ("hasError" => 1/0, "trxIdRemote" => "clientRefId", "tradeId" => received ID, "rejectReason" => received reason)
     */
    public static function analyzeResponse($reply)
    {
        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.analyzeResponse", "reply", $reply, 3, "IN");

        $response = array();
        $response["hasError"] = array_key_exists("rejectReason", $reply) ? "1" : "0";
        $response["trxIdRemote"] = array_key_exists("clientRefId", $reply) ? $reply["clientRefId"] : "";
        $response["tradeId"] = array_key_exists("tradeId", $reply) ? $reply["tradeId"] : "";
        $response["rejectReason"] = array_key_exists("rejectReason", $reply) ? $reply["rejectReason"] : "";

        HDLog::AppLogMessage("HDTouchFX.php", "HDTouchFX.analyzeResponse", "response", $response, 3, "OUT");
        return $response;
    }
}