<?php
namespace HDFramework\src;

use Smarty;

/**
 * Config class to manage application controller functionalities
 *
 * Dependencies: HDConfig, HDTemplateEngine, HDSession, HDScreenMessages<br />
 * Configurations dependencies:<br />
 * - index.php: apps_path, app_name, <br />
 * - config.*.php: URL
 *
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDController
{

    /** @var Smarty template engine */
    private $templateEngine;

    private static $action;

    private static $method;

    private static $parameters;

    private $actionType;

    private $serviceResults = array();

    public $hdCsrf;

    /**
     * Construct the (base) controller.
     * This happens when a real controller is constructed
     */
    public function __construct($action, $method, $actionType, $parameters)
    {
        HDLog::AppLogMessage('HDController.php', 'HDController.__construct', 'input', "Action: " . $action . "; Method: " . $method . "; Action type: " . $actionType, 3, "IN");
        self::$action = strtolower(str_replace("Controller", "", $action));
        self::$method = $method;
        self::$parameters = $parameters;
        $this->hdCsrf = new HDCsrf(new HDSession());
        $this->actionType = $actionType;
        $this->initController();
        $this->includeModel();
    }

    private function initTemplateEngine($escapeHtml = true)
    {
        $this->templateEngine = HDTemplateEngine::getTemplateEngine($escapeHtml);
    }

    /**
     * Executed before view execution.
     * Used to init vars required in template layout for example
     */
    private function initController()
    {
        // code to execute for each controller - to init global vars etc - customized for html views only
        if ($this->actionType == "html") {
            $this->addViewVariable("appUrl", HDApplication::getConfiguration('URL'));
            $this->addViewVariable("currentAction", self::$action);
            $this->addViewVariable("currentMethod", self::$method);
            $this->addViewVariable("currentParameters", self::$parameters);
            $this->addViewVariable("currentEnvType", HDApplication::getEnvType());
            $this->addViewVariable("isUserLoged", HDSession::isUserSessionSet());
            if (HDSession::isUserSessionSet()) {
                $this->addViewVariable('loggedUserGroup', HDSession::getSessionUserGroup());
            }
            $this->addViewVariable('sideMenu', HDApplication::getConfiguration('SIDE_MENU'));
            $this->addViewVariable('CSSFILES', HDApplication::getConfiguration('CSS_FILES'));
            $this->addViewVariable('IMAGES', HDApplication::getConfiguration('IMAGES'));
            $this->addViewVariable('usrLang', HDSession::getFromSession(HDApplication::getConfiguration('SESSION_KEY_USRLANG')));
        }
    }

    /**
     * Check if there is a model associated to this controller and load it
     */
    private function includeModel()
    {
        // check if we have a model defined for the action
        $modelPath = HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . ucwords(self::$action) . 'Model.php';
        if (file_exists($modelPath)) {
            require $modelPath;
        }
    }

    /**
     * Add object to template engine view
     *
     * @param String $key
     *            name
     * @param object $object
     */
    public function addViewObject($key, $object)
    {
        $this->initTemplateEngine();
        $this->templateEngine->registerObject($key, $object);
    }

    /**
     * Add view variable to template engine
     *
     * @param String $key
     *            variable name
     * @param mixed $value
     *            variable value
     */
    public function addViewVariable($key, $value)
    {
        $this->initTemplateEngine();
        $this->templateEngine->assign($key, $value);
    }

    /**
     * Add json variable
     *
     * @param String $key
     *            variable name
     * @param mixed $value
     *            variable value
     */
    public function addJsonVariable($key, $value)
    {
        $this->serviceResults[$key] = $value;
    }

    /**
     * Get view text
     *
     * @param String $key
     *            variable name
     * @param mixed $value
     *            variable value
     */
    public function fetchView($textContent)
    {
        $this->initTemplateEngine();
        $result = $this->templateEngine->fetch('string:' . $textContent);
        return $result;
    }

    /**
     * Create and render the page, display user screen messages first
     */
    public function displayView($escapeHtml = true, $headers = array())
    {
        if (! headers_sent()) {
            foreach ($headers as $headerKey => $headerValue) {
                header("$headerKey: $headerValue");
            }
        }
        HDLog::AppLogMessage('HDController.php', 'HDController.displayView', 'start', self::$action . '/' . self::$method . ".html", 3, "IN");
        $this->initTemplateEngine($escapeHtml);
        $this->templateEngine->display(self::$action . '/' . self::$method . ".html");
        HDLog::AppLogMessage('HDController.php', 'HDController.displayView', 'note', "Finished", 3, "OUT");
    }

    public function displayJson($printLogs = true)
    {
        if ($printLogs) {
            HDLog::AppLogMessage('HDController.php', 'HDController.displayJson', 'start', $this->serviceResults, 3, "IN");
        }

        if (! headers_sent()) {
            header('Content-type: application/json');
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

            HDLog::AppLogMessage('HDController.php', 'HDController.displayJson', 'headers not sent', headers_list());
        } else {
            HDLog::AppLogMessage('HDController.php', 'HDController.displayJson', 'headers sent', headers_list());
        }

        $errorGetLast = error_get_last();
        if (! empty($errorGetLast)) {
            HDLog::AppLogMessage('HDController.php', 'HDController.displayJson', 'errorGetLast', $errorGetLast, 3, "OUT");
        }

        print json_encode($this->serviceResults);
    }

    public function getJson($printVar)
    {
        HDLog::AppLogMessage('HDController.php', 'HDController.getJson', 'start', $this->serviceResults, 3, "IN");
        if ($printVar == null) {
            return json_encode($this->serviceResults);
        } else {
            return json_encode($this->serviceResults, $printVar);
        }
    }

    public function displayArray()
    {
        HDLog::AppLogMessage('HDController.php', 'HDController.displayArray', 'start', $this->serviceResults, 3, "IN");
        print "<pre>";
        print_r($this->serviceResults);
        print "</pre>";
        HDLog::AppLogMessage('HDController.php', 'HDController.displayArray', 'note', "Finished", 3, "OUT");
    }

    public function getActionName()
    {
        return self::$action;
    }

    public function getMethodName()
    {
        return self::$method;
    }

    public function getParameters()
    {
        return self::$parameters;
    }

    public function addHeader($headerName, $headerValue)
    {
        header("$headerName: $headerValue");
    }
}
