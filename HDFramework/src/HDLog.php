<?php
namespace HDFramework\src;

use MongoException;

/**
 * Security class to manage application users and permissions management
 *
 * Dependencies: HDApplication
 * Configurations dependencies:<br />
 * - index.php: apps_path<br />
 * - config.*.php: APP_LOG_FILE_PREFIX, APP_LOG_ENABLED, APP_LOG_FILE_PREFIX, APP_LOG_HEADER_TABLE, LOGING_TYPE, DBMODE, RABBIT_LOGS_QUEUE, APP_LOG_LEVEL, APP_LOG_TABLE, MAIL_SENDING_METHOD, APP_LOG_FROM_EMAIL, APP_LOG_FROM_NAME, APP_LOG_SUPPORT_EMAIL, RABBIT_MAILS_QUEUE, EMAIL_TEMPLATES, EMAIL_QUEUE_TABLE<br />
 * <br />Release date: 29/09/2015
 *
 * @version 7.0
 * @author alin
 * @package framework
 */
class HDLog
{

    private static $stepNumber;

    private static $appLoggingFileName;

    private static $startTime;

    private static $lastStepExecutionStamp;

    private static $actionName = "";

    private static $sessionKey = "";

    private static $sessionPhone = "";

    /**
     * Create a new file for each request for easy tracking
     */
    public static function AppLoggingInit($actionName, $methodName)
    {
        self::$stepNumber = 0;
        self::$startTime = 0;
        self::$lastStepExecutionStamp = 0;

        self::$sessionKey = HDRequest::post("id");
        $microtime = abs(intval(round(microtime(true) * 10000)));

        if (self::$sessionKey == "") {
            self::$sessionKey = "UnknownId_" . uniqid();
        } else {
            self::$sessionPhone = intval(substr(self::$sessionKey, 0, strpos(self::$sessionKey, substr(self::$sessionKey, - 14))));
        }

        self::$appLoggingFileName = $microtime . "_____" . HDApplication::getConfiguration("APP_LOG_FILE_PREFIX") . "_" . $actionName . "_" . $methodName . "_____" . date("YmdHis") . "_____" . self::$sessionKey;

        self::$actionName = $actionName;

        // check if log is enabled
        if (HDApplication::getConfiguration("APP_LOG_ENABLED") != 1) {
            return;
        }

        // check if log request id is defined
        if (empty(self::$appLoggingFileName)) {
            return;
        }

        // check if action is banned from logs
        if (HDApplication::isLogBlockedForAction(self::$actionName)) {
            return;
        }

        $insertArray = array(
            'requestId' => self::$appLoggingFileName,
            'application' => HDApplication::getConfiguration("APP_LOG_FILE_PREFIX"),
            'action' => self::$actionName,
            'method' => $methodName,
            'sessionKey' => self::$sessionKey,
            'phone' => self::$sessionPhone,
            'logDate' => date("Y-m-d H:i:s", time()),
            'caller' => 'AppLoggingInit'
        );

        $confFileTable = HDApplication::getConfiguration("APP_LOG_HEADER_TABLE");
        $confFileDatabase = HDApplication::getConfiguration('APP_LOG_DATABASE');

        if (! empty($confFileTable)) {
            // get logging type
            $logType = HDApplication::getConfiguration('LOGING_TYPE');

            // get DBMODE
            $dbType = HDApplication::getConfiguration('DBMODE');

            if ($logType == 'CONSUMER') {

                // get queue name
                $queueName = HDApplication::getConfiguration("RABBIT_LOGS_QUEUE");

                // publish
                HDRabbit::basicPublish($insertArray, '', $queueName);
            } else {
                unset($insertArray['caller']);

                // insert log in table based on db type
                if ($dbType == "mongo") {
                    try {
                        HDDatabase::getTable($confFileTable, $confFileDatabase)->insert($insertArray, array(
                            "w" => 1
                        ));
                    } catch (MongoException $e) {
                        // send email
                        $logArray = array();
                        $logArray['error message'] = $e->getMessage();
                        $logArray['error code'] = $e->getCode();
                        $logArray['backtrace'] = $e->getTraceAsString();
                        self::AppLogMessage('HDLog.php', 'HDLog.AppLogMessage', 'insert HDLOG failed', $logArray, 4, "IN");
                        unset($logArray);
                    }
                } else {
                    HDDatabase::getConnection()->insert($confFileTable, $insertArray, false, true);
                }
            }
        }
    }

    /**
     * Record application execution step in application flow log
     *
     * @param string $phpFileName
     * @param string $methodName
     * @param string $paramName
     * @param mixed $paramValue
     * @param number $level
     * @param string $paramDirection
     */
    public static function AppLogMessage($phpFileName, $methodName, $paramName, $paramValue, $level = 3, $paramDirection = "")
    {
        // check if log is enabled
        if (HDApplication::getConfiguration("APP_LOG_ENABLED") != 1) {
            return;
        }

        // check if log request id is defined
        if (empty(self::$appLoggingFileName)) {
            return;
        }

        // check if current log match level
        if ($level > HDApplication::getConfiguration("APP_LOG_LEVEL")) {
            return;
        }

        // check if action is banned from logs
        if (HDApplication::isLogBlockedForAction(self::$actionName)) {
            return;
        }

        // init script start time
        if (self::$startTime == 0) {
            self::$startTime = microtime(true);
            self::$lastStepExecutionStamp = self::$startTime;
            $timeElapsed = 0;
            $timeElapsedFromLastStep = 0;
        } else {
            $timeElapsed = microtime(true) - self::$startTime;
            $timeElapsedFromLastStep = microtime(true) - self::$lastStepExecutionStamp;
            self::$lastStepExecutionStamp = microtime(true);
        }

        // increase script step number
        self::$stepNumber ++;

        if (is_array($paramValue) || is_object($paramValue)) {
            $paramValue = print_r($paramValue, true);
        }

        if ($paramDirection == "IN") {
            $paramDirection = 1;
        } elseif ($paramDirection == "OUT") {
            $paramDirection = 2;
        } else {
            $paramDirection = 3;
        }

        $insertArray = array(
            "requestId" => self::$appLoggingFileName,
            "step" => "S" . (self::$stepNumber < 10 ? "00" . self::$stepNumber : (self::$stepNumber < 100 ? "0" . self::$stepNumber : self::$stepNumber)),
            "timeSinceLastStep" => number_format($timeElapsedFromLastStep, 10),
            "totalTimeElapsed" => number_format($timeElapsed, 10),
            "fileName" => $phpFileName,
            "methodName" => $methodName,
            "paramDirection" => $paramDirection,
            "paramName" => $paramName,
            "paramValue" => $paramValue,
            "level" => $level
        );

        $confFileTable = HDApplication::getConfiguration("APP_LOG_TABLE");
        $confFileDatabase = HDApplication::getConfiguration("APP_LOG_DATABASE");

        if (! empty($confFileTable)) {
            // get logging type
            $logType = HDApplication::getConfiguration('LOGING_TYPE');

            // get DBMODE
            $dbType = HDApplication::getConfiguration('DBMODE');

            if ($logType == 'CONSUMER') {

                // get queue name
                $queueName = HDApplication::getConfiguration("RABBIT_LOGS_QUEUE");

                // publish
                HDRabbit::basicPublish($insertArray, '', $queueName);
            } else {
                // insert log in table based on db type
                if ($dbType == "mongo") {
                    try {
                        HDDatabase::getTable($confFileTable, $confFileDatabase)->insert($insertArray, array(
                            "w" => 1
                        ));
                    } catch (MongoException $e) {
                        // send email
                        $logArray = array();
                        $logArray['error message'] = $e->getMessage();
                        $logArray['error code'] = $e->getCode();
                        $logArray['backtrace'] = $e->getTraceAsString();
                        self::AppLogMessage('HDLog.php', 'HDLog.AppLogMessage', 'insert HDLOG failed', $logArray, 4, "IN");
                        unset($logArray);
                    }
                } else {
                    HDDatabase::getConnection()->insert($confFileTable, $insertArray, false, true);
                }
            }
        }

        if ($level === 4) {
            // get sending mail method
            $sendMailMethod = HDApplication::getConfiguration('MAIL_SENDING_METHOD');

            switch ($sendMailMethod) {
                case 'CONSUMER':
                    $insertArray = array();
                    $insertArray['fromEmail'] = HDApplication::getConfiguration('APP_LOG_FROM_EMAIL');
                    $insertArray['fromName'] = HDApplication::getConfiguration('APP_LOG_FROM_NAME');
                    $insertArray['toEmail'] = HDApplication::getConfiguration('APP_LOG_SUPPORT_EMAIL');
                    $insertArray['toName'] = 'Support Team';
                    $insertArray['templateName'] = 'SUPPORT_EMAIL_EXCEPTION_OCCURRENCE';
                    $insertArray['templateVars'] = array(
                        'requestId' => self::$appLoggingFileName,
                        'fileName' => $phpFileName,
                        'methodName' => $methodName,
                        'paramName' => $paramName,
                        'paramValue' => $paramValue
                    );

                    // send message to rabbit
                    HDRabbit::basicPublish($insertArray, '', HDApplication::getConfiguration("RABBIT_MAILS_QUEUE"));

                    // write email to database
                    if ($dbType == "mongo") {
                        HDDatabase::getTable(HDApplication::getConfiguration('EMAIL_QUEUE_TABLE'))->insert($insertArray);
                    }
                    break;
                case 'DATABASE':
                    $body = HDApplication::getConfiguration('EMAIL_TEMPLATES');
                    $body = $body['SUPPORT_EMAIL_EXCEPTION_OCCURRENCE'];

                    HDTemplateEngine::getTemplateEngine()->assign('requestId', self::$appLoggingFileName);
                    HDTemplateEngine::getTemplateEngine()->assign('fileName', $phpFileName);
                    HDTemplateEngine::getTemplateEngine()->assign('methodName', $methodName);
                    HDTemplateEngine::getTemplateEngine()->assign('paramName', $paramName);
                    HDTemplateEngine::getTemplateEngine()->assign('paramValue', $paramValue);

                    $body['content'] .= "<br /><pre><br />" . print_r(debug_backtrace(), true) . "<br /></pre>";

                    // set up email body
                    $body['content'] = HDTemplateEngine::getTemplateEngine()->fetch('string:' . $body['content']);

                    // send email
                    HDMail::sendEmail(HDApplication::getConfiguration('APP_LOG_FROM_EMAIL'), // fromEmail
                    HDApplication::getConfiguration('APP_LOG_FROM_NAME'), // fromName
                    HDApplication::getConfiguration('APP_LOG_SUPPORT_EMAIL'), // toEmail
                    "Support Team", // toName
                    "", // ccEmail
                    "", // bccEmail
                    $body['subject'], // emailSubject
                    $body['content'], // emailHTMLContent
                    "", // emailTxtContent
                    "" // emailAtachements
                    );
                    $body = null;
                    break;
            }
        }

        unset($insertArray);
    }
}
