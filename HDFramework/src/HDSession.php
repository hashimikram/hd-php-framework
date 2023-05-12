<?php
namespace HDFramework\src;

/**
 * Sessions abstract class to manage server sessions
 *
 * Dependencies: HDApplication<br />
 * Configurations dependencies:<br />
 * - config.*.php: SESSION_USE_DEFAULT, PATH_SESSIONS, SESSION_KEY_USRLGD, SESSION_KEY_USRGRP<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDSession
{

    private static $sessionId = "";

    /**
     * Activate session usage in PHP application
     */
    private static function startSession()
    {
        if (self::$sessionId == '') {
            if (HDApplication::getConfiguration("SESSION_USE_DEFAULT") != 1) {
                session_save_path(HDApplication::getConfiguration("PATH_SESSIONS"));
            }

            session_set_cookie_params(0, '/', null, HDApplication::getConfiguration("SESSION_COOKIE_SECURE"), HDApplication::getConfiguration("SESSION_HTTPONLY"));

            session_start();

            self::$sessionId = session_id();

            $sessionUserID = self::getSessionUserId();
            if (empty($sessionUserID)) {
                self::registerUserSession(0, "");
            }
        }
    }

    /**
     * Retrieve current session id
     *
     * @return String - Session id assigned to user
     */
    public static function getSessionId()
    {
        if (self::$sessionId == "")
            self::startSession();
        return self::$sessionId;
    }

    /**
     * Save var in session
     *
     * @param String $key
     * @param Object $val
     */
    public static function saveToSession($key, $val)
    {
        if (self::$sessionId == "")
            self::startSession();
        $_SESSION[$key] = $val;
    }

    /**
     * Get var from session
     *
     * @param string $sessionKey
     *            <p>The key of the session</p>
     * @param boolean $delete
     *            <p>Defaults to false. If true the element is deleted from the session</p>
     * @return Object associated with session key
     */
    public static function getFromSession($sessionKey, $delete = false)
    {
        if (self::$sessionId == "") {
            self::startSession();
        }

        // if key is not primitive return
        if (! is_scalar($sessionKey)) {
            return "";
        }

        // return empty string if session key is not set
        if (! isset($_SESSION[$sessionKey])) {
            return "";
        }

        // store session key value to temporary variable
        $value = $_SESSION[$sessionKey];

        // delete session key
        if ($delete) {
            self::unsetSessionKey($sessionKey);
        }
        return $value;
    }

    /**
     * Adds a value as a new array element to the key.
     * Useful for collecting error messages etc
     *
     * @param mixed $key
     * @param mixed $value
     */
    public static function add($key, $value)
    {
        if (self::$sessionId == "")
            self::startSession();
        $_SESSION[$key][] = $value;
    }

    /**
     * deletes the session (= logs the user out)
     */
    public static function destroy()
    {
        session_destroy();
    }

    public static function unregisterUserSession()
    {
        if (self::$sessionId == "")
            self::startSession();
        self::saveToSession(HDApplication::getConfiguration("SESSION_KEY_USRLGD"), "");
        self::saveToSession(HDApplication::getConfiguration("SESSION_KEY_USRGRP"), "");
    }

    public static function registerUserSession($userId, $group)
    {
        if (self::$sessionId == "")
            self::startSession();
        self::saveToSession(HDApplication::getConfiguration("SESSION_KEY_USRLGD"), $userId);
        self::saveToSession(HDApplication::getConfiguration("SESSION_KEY_USRGRP"), $group);
    }

    /**
     * Checks if the user is logged in or not
     *
     * @return bool user's login status
     */
    public static function isUserSessionSet()
    {
        if (self::$sessionId == "")
            self::startSession();
        $userSession = self::getSessionUserId();
        if (empty($userSession)) {
            $userSession = null;
            return 0;
        } else {
            $userSession = null;
            return 1;
        }
    }

    /**
     * Get logged user id
     *
     * @return int user's login status
     */
    public static function getSessionUserId()
    {
        if (self::$sessionId == "")
            self::startSession();
        return self::getFromSession(HDApplication::getConfiguration("SESSION_KEY_USRLGD"));
    }

    /**
     * Get logged user group id
     *
     * @return int user's login status
     */
    public static function getSessionUserGroup()
    {
        if (self::$sessionId == "")
            self::startSession();
        return self::getFromSession(HDApplication::getConfiguration("SESSION_KEY_USRGRP"));
    }

    /**
     * Save var in cookie
     *
     * @param String $cookieKey
     * @param Object $cookieVal
     * @param String $cookieLifeTime
     * @param String $cookiePath
     */
    public static function saveToCookie($cookieKey, $cookieVal, $cookieLifeTime, $cookiePath)
    {
        setcookie($cookieKey, $cookieVal, time() + $cookieLifeTime, $cookiePath);
    }

    /**
     * Deletes the cookie
     * It's necessary to split deleteCookie() and logout() as cookies are deleted without logging out too!
     * Sets the remember-me-cookie to ten years ago (3600sec * 24 hours * 365 days * 10).
     * that's obviously the best practice to kill a cookie @see http://stackoverflow.com/a/686166/1114320
     */
    public static function deleteCookie($cookieKey, $cookiePath)
    {
        setcookie($cookieKey, false, time() - (3600 * 24 * 3650), $cookiePath);
    }

    /**
     * Will replace the current session id with a new one, and keep the current session information.
     *
     * @param boolean $delete_old_session
     *            whether to delete the old associated session file or not. You should not delete old session if you need to avoid races caused by deletion or detect/avoid session hijack attacks.
     */
    public static function regenerateId($delete_old_session = false)
    {
        session_regenerate_id($delete_old_session);
    }

    /**
     * Destroys a single element from session
     *
     * @param string $sessionKey
     * @return boolean
     */
    public static function unsetSessionKey($sessionKey)
    {
        if (self::$sessionId == "") {
            self::startSession();
        }

        // Scalar variables are those containing an integer, float, string or boolean
        if (! is_scalar($sessionKey)) {
            return false;
        }

        if (isset($_SESSION[$sessionKey])) {
            unset($_SESSION[$sessionKey]);
        }
        return true;
    }
}
