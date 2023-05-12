<?php
namespace HDFramework\src;

/**
 * Class Application
 *
 * The heart of the application<br />
 * Configurations dependencies:<br />
 * - index.php: apps_path, app_name, <br />
 * - config.*.php: NO_LOGS, MULTILANGUAGE_SUPPORT, DEFAULT_LANGUAGE, SESSION_KEY_USRLANG, DEFAULT_ACTION, DEFAULT_METHOD, ACTIONS, ERROR_ACTION, LOGIN_ACTION, LOGIN_METHOD, LIMIT_REQUESTS_BY_IP,
 *
 * @package framework
 */
class HDApplication
{

    /**
     * Member to hold all configurations
     *
     * @var mixed
     */
    private static $configurationArray;

    /** @var mixed Instance of the controller */
    private $controller;

    /** @var array URL parameters, will be passed to used controller-method */
    private $parameters = array();

    /** @var string Just the name of the action */
    private $action_name;

    /** @var string Just the name of the controller for the action */
    private $action_controller;

    /** @var string Store if action requires login or not */
    private $action_requires_login;

    /** @var string Store action type like html or php */
    private $action_type;

    /** @var string Just the name of the controller's method, useful for checks inside the view ("where am I ?") */
    private $method_name;

    /**
     * Include developer custom application classes
     *
     * @param string $className
     */
    public static function getClass($className)
    {
        $fileName = HDApplication::getConfiguration('apps_path') . DIRECTORY_SEPARATOR . HDApplication::getConfiguration('app_name') . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $className . '.php';

        // check if we have a general class
        if (file_exists($fileName)) {
            if (! class_exists($className)) {
                require $fileName;
            }
        } else {
            HDLog::AppLogMessage('HDApplication.php', 'HDApplication.getClass', 'warning', "Class source file not found " . $className, 2, "OUT");
        }
    }

    /**
     * Check if current action is blocked from logging
     *
     * @param string $action
     * @return boolean
     */
    public static function isLogBlockedForAction($action)
    {
        return in_array($action, self::getConfiguration("NO_LOGS"));
    }

    /**
     * Start the application, analyze URL elements, call according controller/method or relocate to fallback location
     */
    public function __construct($configurationArray)
    {

        // init global configurations
        self::$configurationArray = $configurationArray;

        // create array with URL parts in $url
        $this->splitUrl();

        // init components
        $this->init();

        // creates controller and action names (from URL input)
        $this->createControllerAndActionNames();

        // verify action
        $this->validateAction();

        // validate action access
        $this->validateAccess();

        // validate controller
        $this->validateController();

        // execute controller
        $this->executeController();
    }

    public static function getConfiguration($key)
    {
        // require conf file only if it wasn't allready loaded
        if (empty(self::$configurationArray["conf_loaded"])) {
            $config_file = self::$configurationArray["apps_path"] . DIRECTORY_SEPARATOR . self::$configurationArray["app_name"] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.' . self::$configurationArray["env_type"] . '.php';

            if (! file_exists($config_file)) {
                exit("Can't load configuration file" . $config_file);
            }
            $tmpArray = require $config_file;

            $templateConfigFile = self::$configurationArray["apps_path"] . DIRECTORY_SEPARATOR . self::$configurationArray["app_name"] . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
            if (file_exists($templateConfigFile)) {
                $templateConfigArray = require $templateConfigFile;
                $tmpArray = array_replace_recursive($templateConfigArray, $tmpArray);
            }

            self::$configurationArray = array_merge(self::$configurationArray, $tmpArray);
            self::$configurationArray["conf_loaded"] = 1;
        }

        // search key configuration
        if (array_key_exists($key, self::$configurationArray)) {
            return self::$configurationArray[$key];
        } else {
            HDLog::AppLogMessage('HDApplication.php', 'HDApplication.getConfiguration', 'warning', "Array key not found in config " . $key, 3, "OUT");
            return "";
        }
    }

    public static function setConfiguration($key, $value)
    {
        self::$configurationArray[$key] = $value;
    }

    public static function configurationKeyExist($key)
    {
        return array_key_exists($key, self::$configurationArray);
    }

    public static function setConfigurationArray($configurationArray)
    {
        self::$configurationArray = $configurationArray;
    }

    /**
     * Init application components
     */
    private function init()
    {
        // init application logging engine only if action name and method name is not empty (if they are empty the script will end in a redirect)
        if (! empty($this->action_name) && ! empty($this->method_name)) {
            HDLog::AppLoggingInit($this->action_name, $this->method_name);
            HDLog::AppLogMessage('HDApplication.php', 'HDApplication.init', 'init', "Action: " . $this->action_name . "; Method: " . $this->method_name . "; Server: " . $_SERVER['SERVER_ADDR'] . "; IP: " . HDRequest::get_ip(), 3, "IN");
        }

        // set up languages, if required
        if ((HDApplication::getConfiguration("MULTILANGUAGE_SUPPORT") == 1) && (HDApplication::getConfiguration("DEFAULT_LANGUAGE") != "")) {
            // if multilanguage is required, check if there is any language set to set the default if required
            $sessionKeyLang = HDApplication::getConfiguration('SESSION_KEY_USRLANG');

            $usrLang = HDSession::getFromSession($sessionKeyLang);
            HDLog::AppLogMessage('HDApplication.php', 'HDApplication.init', 'init', $usrLang, 3, "L");
            if (empty($usrLang)) {
                HDSession::saveToSession($sessionKeyLang, HDApplication::getConfiguration("DEFAULT_LANGUAGE"));
            }

            // load language file
            self::loadLanguage(HDSession::getFromSession($sessionKeyLang));
            unset($sessionKeyLang);
        }

        // check ip based requests "leacky bucket"
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'LIMIT_REQUESTS_BY_IP', HDApplication::getConfiguration("LIMIT_REQUESTS_BY_IP"), 3, 'L');
        if (HDApplication::getConfiguration("LIMIT_REQUESTS_BY_IP") == "YES") {
            $hdLimitRequests = new HDLimitRequests(HDRequest::get_ip());

            if ($hdLimitRequests->isActive()) {
                $hdLimitRequests->leakyBucket->leak();
                $hdLimitRequests->leakyBucket->fill();
                $hdLimitRequests->leakyBucket->overflow();
                $hdLimitRequests->leakyBucket->save();

                if ($hdLimitRequests->leakyBucket->isFull()) {

                    if (HDApplication::getEnvType() == 'live') {
                        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'LIMIT_REQUESTS_BY_IP', json_encode(get_object_vars($hdLimitRequests)), 4, 'L');
                    }

                    // send email to support team
                    http_response_code(429);
                    header("Content-Type: text/html");
                    header("Retry-After: " . $hdLimitRequests->getTtl() . " seconds");
                    die("Too Many Requests");
                }
            }
        }

        // set custom error handler
        set_error_handler(array(
            $this,
            "hdErrorHandler"
        ));

        // check if the application is restricted by ip
        if (HDApplication::getConfiguration('RESTRICT_BY_IP') === true) {
            $allowedIps = HDDatabase::getTable('m3_settings', 'm3static')->findOne();
            $allowedIps = $allowedIps[self::$configurationArray['app_name'] . 'AlowedIps'];
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'allowedIps', $allowedIps);

            if (strpos($allowedIps, strval(HDRequest::get_ip())) === false) {
                header("HTTP/1.1 401 Unauthorized");
                exit();
            }
        }
    }

    public static function loadLanguage($lang)
    {
        HDLog::AppLogMessage('HDApplication.php', 'loadLanguage', 'lang', $lang, 3, "IN");
        $lang_file = self::$configurationArray["apps_path"] . DIRECTORY_SEPARATOR . self::$configurationArray["app_name"] . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php';

        /** @var mixed $_LANG */
        global $_LANG;

        if (! file_exists($lang_file)) {
            exit("Can't load language file" . $lang_file);
        }
        HDLog::AppLogMessage('HDApplication.php', 'loadLanguage', 'lang_file', $lang_file, 3, "OUT");
        $_LANG = require $lang_file;
    }

    /**
     * Get and split the URL
     */
    private function splitUrl()
    {
        if (HDRequest::get('url')) {
            // split URL
            $url = trim(HDRequest::get('url'), '/');

            // decode url to make sure it was not encoded before
            $url = urldecode($url);

            // encode url because FILTER_SANITIZE_URL remove empty spaces
            $url = urlencode($url);

            // sanitize url and obtain the original url with spaces as well
            $url = urldecode(filter_var($url, FILTER_SANITIZE_URL));
            $url = explode('/', $url);

            // put URL parts into according properties
            $this->action_name = isset($url[0]) ? $url[0] : null;
            $this->method_name = isset($url[1]) ? $url[1] : null;

            // remove controller name and action name from the split URL
            unset($url[0], $url[1]);

            // rebase array keys and store the URL parameters
            $this->parameters = array_values($url);
        }
    }

    /**
     * Checks if controller and action names are given.
     * If not, default values are put into the properties.
     * Also renames controller to usable name.
     */
    private function createControllerAndActionNames()
    {
        HDLog::AppLogMessage('HDApplication.php', 'createControllerAndActionNames', 'start', 'No params', 3, "IN");

        // check for controller: no controller given ? then make controller = default controller (from config)
        if (empty($this->action_name)) {
            $this->action_name = HDApplication::getConfiguration("DEFAULT_ACTION");
        }

        // check for action: no action given ? then make action = default action (from config)
        if (empty($this->method_name)) {
            $this->method_name = HDApplication::getConfiguration('DEFAULT_METHOD');
        }

        // rename controller name to real controller class/file name ("index" to "IndexController")
        $this->action_controller = ucwords($this->action_name) . 'Controller';

        HDLog::AppLogMessage('HDApplication.php', 'createControllerAndActionNames', 'this->action_name', $this->action_name, 3, "OUT");
        HDLog::AppLogMessage('HDApplication.php', 'createControllerAndActionNames', 'this->method_name', $this->method_name, 3, "OUT");
        HDLog::AppLogMessage('HDApplication.php', 'createControllerAndActionNames', 'this->action_controller', $this->action_controller, 3, "OUT");
    }

    /**
     * Retrieve action details from the database
     */
    private function validateAction()
    {
        HDLog::AppLogMessage('HDApplication.php', 'validateAction', 'start', 'No params', 3, "IN");
        $actionsTable = HDApplication::getConfiguration("ACTIONS");

        // get action details from the database
        $actionDetails = @$actionsTable[$this->action_name];

        if ($actionDetails["actionIsValid"] == 1) {
            // action identified
            $this->action_type = $actionDetails["actionType"];
            $this->action_requires_login = $actionDetails["actionRequiresLogin"];
        } else {
            // action not defined - we redirect to error page
            HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200000");
        }
        unset($actionDetails);
        HDLog::AppLogMessage('HDApplication.php', 'validateAction', 'end', 'No params', 3, "OUT");
    }

    /**
     * Validate if action is allowed based on user rights
     */
    private function validateAccess()
    {
        HDLog::AppLogMessage('HDApplication.php', 'validateAccess', 'start', 'No params', 3, "IN");

        // check if action requires login
        if ($this->action_requires_login == 1) {
            // if action requires login check if user is logged
            if (HDSession::isUserSessionSet()) {
                $actionsTable = HDApplication::getConfiguration("ACTIONS");

                if (array_key_exists($this->method_name, @$actionsTable[$this->action_name]["methods"])) {
                    if (! self::isAccessGrantedToGroup($this->action_name, $this->method_name)) {
                        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'access denied: rights', 'user ' . HDSession::getSessionUserId() . 'group ' . HDSession::getSessionUserGroup() . ' when accessing action/method ' . $this->action_name . '/' . $this->method_name, 3, "IN");
                        // Permission denied
                        HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200011");
                    }
                } else {
                    // Method permissions not set
                    HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'access denied: method permission not set', 'when accessing action/method ' . $this->action_name . '/' . $this->method_name, 3, "IN");
                    HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200001");
                }
            } else {
                // if user not logged in we display message and redirect to login if we have a html or cms page
                if ($this->action_type == "html") {
                    HDRedirect::to(HDApplication::getConfiguration("LOGIN_ACTION"), HDApplication::getConfiguration("LOGIN_METHOD"));
                } else {
                    // user is not logged and action type is php (json/xml etc)
                    HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200002");
                }
            }
        }
        HDLog::AppLogMessage('HDApplication.php', 'validateAccess', 'end', 'No params', 3, "OUT");
    }

    /**
     * Checks if a group (admin, operators, developers) has acccess to action/method
     *
     * @param string $action
     * @param string $method
     * @return boolean true if the group has access, false otherwise
     */
    public static function isAccessGrantedToGroup($action, $method)
    {
        $actionsTable = HDApplication::getConfiguration("ACTIONS");
        return in_array(HDSession::getSessionUserGroup(), @$actionsTable[$action]["methods"][$method]);
    }

    /**
     * Validate controller to be executed
     */
    private function validateController()
    {
        HDLog::AppLogMessage('HDApplication.php', 'validateController', 'start', 'No params', 3, "IN");

        // does such a controller exist ?
        if (! file_exists(HDApplication::getConfiguration("apps_path") . DIRECTORY_SEPARATOR . HDApplication::getConfiguration("app_name") . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . $this->action_controller . '.php')) {
            HDLog::AppLogMessage('HDApplication.php', 'HDApplication.validateController', 'error', "ERROR_200003", 3, "OUT");
            HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200003");
        }

        HDLog::AppLogMessage('HDApplication.php', 'validateController', 'end', 'No params', 3, "OUT");
    }

    /**
     * Execute application controller
     */
    private function executeController()
    {
        HDLog::AppLogMessage('HDApplication.php', 'executeController', 'start', 'No params', 3, "IN");

        // example: if action would be "login", then this line would import LoginController.php
        require HDApplication::getConfiguration("apps_path") . DIRECTORY_SEPARATOR . HDApplication::getConfiguration("app_name") . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . $this->action_controller . '.php';

        // check if class exists in controller
        if (! class_exists($this->action_controller)) {
            HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200044");
        }

        // example: if action would be "login", then this line would execute new LoginController('login','index','html');
        $this->controller = new $this->action_controller($this->action_name, $this->method_name, $this->action_type, $this->parameters);

        // check for method: does such a method exist in the controller ?
        if (method_exists($this->controller, $this->method_name)) {

            if (! empty($this->parameters)) {
                // call the method and pass arguments to it
                call_user_func_array(array(
                    $this->controller,
                    $this->method_name
                ), $this->parameters);
            } else {
                // if no parameters are given, just call the method without parameters, like $this->index->index();
                $this->controller->{$this->method_name}();
            }
        } else {
            HDLog::AppLogMessage('HDApplication.php', 'HDApplication.executeController', 'error', "ERROR_200004", 3, "IN");
            HDRedirect::to(HDApplication::getConfiguration("ERROR_ACTION"), "", "ERROR_200004");
        }
    }

    public static function getEnvType()
    {
        if (self::$configurationArray) {
            return self::$configurationArray['env_type'];
        } else {
            return '';
        }
    }

    public function hdErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (! (error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        $mailEnv = array();
        $mailEnv[] = "live";
        $mailEnv[] = "test";

        $level = in_array(HDApplication::getEnvType(), $mailEnv) ? 4 : 3;

        switch ($errno) {
            case E_USER_ERROR:
                $paramValue = "E_USER_ERROR errstr: $errstr, errno: $errno\n";
                $paramValue .= "FATAL ERROR on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_USER_ERROR', $paramValue, $level, "L");
                break;

            case E_USER_WARNING:
                $paramValue = "E_USER_WARNING errstr: $errstr, errno: $errno\n";
                $paramValue .= "WARNING on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_USER_WARNING', $paramValue, $level, "L");
                break;

            case E_USER_NOTICE:
                $paramValue = "E_USER_NOTICE errstr: $errstr, errno: $errno\n";
                $paramValue .= "NOTICE on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_USER_NOTICE', $paramValue, $level, "L");
                break;

            case E_ERROR:
                $paramValue = "E_ERROR errstr: $errstr, errno: $errno\n";
                $paramValue .= "ERROR on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_ERROR', $paramValue, $level, "L");
                break;

            case E_WARNING:
                $paramValue = "E_WARNING errstr: $errstr, errno: $errno\n";
                $paramValue .= "WARNING on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_WARNING', $paramValue, $level, "L");
                break;

            case E_NOTICE:
                $paramValue = "E_NOTICE errstr: $errstr, errno: $errno\n";
                $paramValue .= "NOTICE on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_NOTICE', $paramValue, $level, "L");
                break;

            default:
                $paramValue = "UNKNOWN errstr: $errstr, errno: $errno\n";
                $paramValue .= "UNKNOWN on line $errline\n";
                $paramValue .= "in file $errfile\n";
                $paramValue .= "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'E_UNKNOWN', $paramValue, $level, "L");
                break;
        }
        return false;
    }
}
