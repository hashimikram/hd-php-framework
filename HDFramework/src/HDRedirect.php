<?php
namespace HDFramework\src;

/**
 * Redirect class
 *
 * Use it to obtain framework specific links and redirect to framework action/methods
 * Dependencies HDLog, HDApplication<br />
 * Configurations dependencies:<br />
 * - config.*.php: URL, DEFAULT_METHOD, <br />
 * <br />Release date: 29/09/2015
 *
 * @version 7.0
 * @author Alin
 * @package framework
 */
class HDRedirect
{

    /**
     * Redirect to a link using PHP header command
     *
     * @param String $fullUrl
     */
    public static function toLink($fullUrl)
    {
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.toLink', 'fullUrl', $fullUrl, 3, "IN");
        if ($fullUrl != "") {
            header("Location:" . $fullUrl);
            exit();
        }
    }

    /**
     * To the homepage
     */
    public static function home()
    {
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.home', 'start', "No parameters", 3, "IN");
        header("Location:" . HDApplication::getConfiguration('URL'));
        exit();
    }

    /**
     * Redirect to a page inside the application using PHP header command
     *
     * @param String $actionName
     *            = Action destination
     * @param Array $paramArray
     *            = List of params
     */
    public static function to($actionName, $methodName = "", $paramArray = array())
    {
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.to', 'actionName', $actionName, 3, "IN");
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.to', 'methodName', $methodName, 3, "IN");
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.to', 'paramArray', $paramArray, 3, "IN");
        if ($methodName == "") {
            $methodName = HDApplication::getConfiguration('DEFAULT_METHOD');
        }
        $paramStr = "Location:" . HDApplication::getConfiguration('URL') . "/" . $actionName . "/" . $methodName;
        if (is_array($paramArray)) {
            foreach ($paramArray as $param) {
                $paramStr .= "/" . urlencode($param);
            }
        } else {
            $paramStr .= "/" . urlencode($paramArray);
        }
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.to', 'redirect', $paramStr, 3, "OUT");
        header($paramStr);
        unset($paramStr);
        exit();
    }

    /**
     * Create a SEO framework specific link, it cand be done via view directly as well
     *
     * @param String $actionName
     * @param String $methodName
     * @param Array $paramArray
     * @return String created link
     */
    public static function buildLink($actionName, $methodName = "", $paramArray = array())
    {
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.buildLink', 'actionName', $actionName, 3, "IN");
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.buildLink', 'methodName', $methodName, 3, "IN");
        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.buildLink', 'paramArray', $paramArray, 3, "IN");

        if ($methodName == "") {
            $methodName = HDApplication::getConfiguration('DEFAULT_METHOD');
        }

        $paramStr = HDApplication::getConfiguration('URL') . "/" . $actionName . "/" . $methodName;

        foreach ($paramArray as $param) {
            $paramStr .= "/" . urlencode($param);
        }

        HDLog::AppLogMessage('HDRedirect.php', 'HDRedirect.buildLink', 'return', $paramStr, 3, "OUT");
        return $paramStr;
    }
}