<?php
namespace HDFramework\src;

/**
 * Created by PhpStorm.
 * User: CristiC
 * Date: 12/08/16
 * Time: 16:49
 *
 * @package framework
 */
class HDUtils
{

    public static function dieObject($object, $kill = true)
    {
        echo '<xmp style="text-align: left;">';
        print_r($object);
        echo '</xmp><br />';
        if ($kill) {
            die('END');
        }
        return $object;
    }

    /**
     * Prints argument without stopping the script
     *
     * @param mixed $object
     */
    public static function ppp($object)
    {
        return (HDUtils::dieObject($object, false));
    }

    /**
     * Prints argument stopping the script
     *
     * @param mixed $object
     */
    public static function ddd($object)
    {
        return (HDUtils::dieObject($object, true));
    }

    /**
     * Binary safe function that returns an array with all keys from array lowercased first letter
     *
     * @param array $data
     *            multidimensional array
     * @return array with all keys lowercased first letter
     */
    public static function mb_lcFirstKeys($data)
    {
        $res = array();
        foreach ($data as $key => $value) {
            $newKey = mb_strtolower(mb_substr($key, 0, 1)) . mb_substr($key, 1);
            $res[$newKey] = is_array($value) ? self::mb_lcFirstKeys($value) : $value;
        }
        return $res;
    }

    /**
     * Method to execute cUrl
     *
     * @param string $target
     *            = url
     * @param string $method
     *            = 'POST' or 'GET'
     * @param array $data_array
     *            = data to send
     * @param array $customHeaders
     *            = array with custom headers if default ones are not enough
     * @return mixed
     */
    public static function http($target, $method, $data_array, $customHeaders = array())
    {

        // Initialize php/curl handle
        $ch = curl_init();

        // set custom headers
        if (count($customHeaders) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
        }

        // Prcess data, if presented
        if ((is_array($data_array) == true) && (count($data_array) > 0)) {
            if (in_array('content-type: application/json', $customHeaders)) {
                $query_string = json_encode($data_array);
            } else {
                // Convert data array into a query string (ie animal=dog&sport=baseball)
                $temp_string = array();
                foreach ($data_array as $key => $value) {
                    if (strlen(trim($value)) > 0) {
                        $temp_string[] = $key . "=" . urlencode($value);
                    } else {
                        $temp_string[] = $key;
                    }
                }
                $query_string = join('&', $temp_string);
            }
        }

        // GET method configuration
        if ($method == 'GET') {
            if (isset($query_string)) {
                $target = $target . "?" . $query_string;
            }
            curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
            curl_setopt($ch, CURLOPT_POST, FALSE);
        }

        // POST method configuration
        if ($method == 'POST') {
            if (isset($query_string)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
            }
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_HTTPGET, FALSE);
        }

        curl_setopt($ch, CURLOPT_URL, $target); // Target site
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Minimize logs
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // No certificate
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); // Follow redirects
        curl_setopt($ch, CURLOPT_MAXREDIRS, 4); // Limit redirections to four
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // Return in string
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // View sent headers in curl_getinfo
                                                     // Create return array
        $return_array = array();
        $return_array['FILE'] = curl_exec($ch);
        $return_array['STATUS'] = curl_getinfo($ch);
        $return_array['ERROR'] = curl_error($ch);

        // Close PHP/CURL handle
        curl_close($ch);

        // Return results
        return $return_array;
    }

    /**
     * Tests if a string is JSON or not
     *
     * @param string $string
     *            - Input to test
     * @return boolean - True if JSON, false otherwise
     */
    public static function isJson($string)
    {
        if (is_string($string) == false) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Get file size from url without downloading the file
     *
     * @param string $url
     *            - http url of file (not https)
     * @return boolean|number - -1 if file can't be opened, size in bytes otherwise
     */
    public static function getRemoteFilesize($url)
    {
        // Assume failure.
        $result = - 1;
        $matches = array();

        $curl = curl_init($url);

        // Issue a HEAD request and follow any redirects.
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, self::get_user_agent_string());

        $data = curl_exec($curl);
        curl_close($curl);

        if ($data) {
            $content_length = "unknown";
            $status = "unknown";

            if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
                $status = (int) $matches[1];
            }

            if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
                $content_length = (int) $matches[1];
            }

            // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
            if ($status == 200 || ($status > 300 && $status <= 308)) {
                $result = $content_length;
            }
        }

        return $result;
    }

    public static function get_user_agent_string()
    {
        if (! empty($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }
        return false;
    }

    /**
     * If there is at least one string key, $array will be regarded as an associative array.
     *
     * @param array $array
     * @return boolean
     */
    public static function has_string_keys(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * Transforms an object into an array, no matter the depth of the object properties
     *
     * @param Object $obj
     */
    public static function objectToArray($obj)
    {
        $arr = array();
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? self::objectToArray($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    /**
     * Generates a random string
     *
     * @param string $characters
     *            - Allowed characters for the random string; initialised with a-z / A-Z and 0-9
     * @param Number $length
     *            - desired size of the generated string; initialised with 10
     * @return String - random string of the desired length
     */
    public static function generateRandomString($characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length = 10)
    {
        $randomString = '';
        for ($i = 0; $i < $length; $i ++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Send the contents of the output buffer (if any)
     *
     * @param string|array|object $var
     * @param string $key
     *            name of variable
     */
    public static function flushBuffer($var, $key = "")
    {
        if (! empty($key)) {
            echo $key . ': ';
        }
        if (is_object($var)) {
            echo '<pre>' . print_r(self::objectToArray($var), true) . '</pre>';
        } else if (is_array($var)) {
            echo '<pre>' . print_r($var, true) . '</pre>';
        } else {
            echo $var . '<br />';
        }
        flush();
        ob_flush();
    }

    /**
     * Send the contents of the output buffer (if any)
     *
     * @param string|array|object $var
     * @param string $key
     *            name of variable
     */
    public static function flushBufferToConsole($var, $key = "")
    {
        if (! empty($key)) {
            echo $key . ': ';
        }
        if (is_object($var)) {
            echo print_r(self::objectToArray($var), true) . "\n";
        } else if (is_array($var)) {
            echo print_r($var, true) . "\n";
        } else {
            echo $var . "\n";
        }
        flush();
        ob_flush();
    }

    /**
     * Returns the value of $var, if set and not null, or the default value, or null
     *
     * @param array|mixed $var
     * @param mixed $defaultValue
     * @return mixed|NULL
     */
    public static function defaultIfNull(&$var, $defaultValue = null)
    {
        if (isset($var) && $var != null) {
            return $var;
        }
        if ($defaultValue != null) {
            return $defaultValue;
        }
        return null;
    }

    /**
     * Returns a string representation of $_FILES['error']
     *
     * @param integer $code
     * @return string
     */
    public static function getErrorStringFromFiles($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }

    /**
     * Cast array to object recursively The object must have a method called getSubObjects that returns an array of fields and classes if any
     *
     * @param array $sourceArray
     * @param string $className
     * @return null|object of $destination type or null if $sourceArray is null
     */
    public static function castArrayToObject($sourceArray = null, $className)
    {
        if (is_null($sourceArray)) {
            return null;
        }

        if (is_string($className)) {
            $entity = new $className();
        } else {
            throw new \Exception('Cannot cast response to entity object. Wrong entity class name');
        }

        $entityReflection = new \ReflectionObject($entity);

        $subObjects = $entity->GetSubObjects();

        foreach ($sourceArray as $name => $value) {

            if ($entityReflection->hasProperty($name)) {

                // is sub object?
                if (isset($subObjects[$name])) {
                    $object = null;
                    if (! is_null($value)) {
                        $object = self::castArrayToObject($value, $subObjects[$name]);
                    }

                    $entity->{$name} = $object;
                } else {
                    $entity->{$name} = $value;
                }
            }
        }
        return $entity;
    }
}