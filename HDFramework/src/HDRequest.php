<?php
namespace HDFramework\src;

/**
 * This is under development.
 * Expect changes
 *
 * Abstracts the access to $_GET, $_POST and $_COOKIE, preventing direct access to these super-globals.
 * Version 6.0
 * Release date: 16/05/2015
 *
 * @author Alin
 * @package framework
 */
class HDRequest
{

    /**
     * gets/returns the value of a specific key of the FILE super-global
     *
     * @param mixed $key
     *            key
     * @return mixed the key's value or nothing
     */
    public static function files($key)
    {
        if (array_key_exists($key, $_FILES) && file_exists($_FILES[$key]['tmp_name'])) {
            return $_FILES[$key];
        }
        return "";
    }

    /**
     * Get IP Address of calling machine using various sources
     */
    public static function get_ip()
    {
        // Just get the headers if we can or else use the SERVER global
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }

        // Get the forwarded IP if it exists
        if (array_key_exists('X-Forwarded-For', $headers) && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $the_ip = $headers['X-Forwarded-For'];
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers) && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
        } else {
            $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }
        return $the_ip;
    }

    /**
     * Gets/returns the value of a specific key of the POST super-global.
     * When using just Request::post('x') it will return the raw and untouched $_POST['x'], when using it like
     * Request::post('x', true) then it will return a trimmed and stripped $_POST['x'] !
     *
     * @param mixed $key
     *            key
     * @param bool $clean
     *            marker for optional cleaning of the var
     * @return mixed the key's value or nothing
     */
    public static function post($key, $clean = false)
    {
        if (array_key_exists($key, $_POST) && isset($_POST[$key])) {
            return ($clean) ? trim(strip_tags($_POST[$key])) : $_POST[$key];
        }
        return "";
    }

    /**
     * gets/returns the value of a specific key of the GET super-global
     *
     * @param mixed $key
     *            key
     * @return mixed the key's value or nothing
     */
    public static function get($key)
    {
        if (array_key_exists($key, $_GET) && isset($_GET[$key])) {
            return $_GET[$key];
        }
        return "";
    }

    /**
     * gets/returns the value of a specific key of the COOKIE super-global
     *
     * @param mixed $key
     *            key
     * @return mixed the key's value or nothing
     */
    public static function cookie($key)
    {
        if (array_key_exists($key, $_COOKIE) && isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        return "";
    }
}
