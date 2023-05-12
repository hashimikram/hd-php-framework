<?php
namespace HDFramework\src;

/**
 * Cypher Android class to manage encryption
 *
 * Version 6.0
 * Release date: 16/05/2015
 *
 * @author Alin
 * @package framework
 */
class HDCypher
{

    /**
     *
     * Calculate password reset hash for users
     *
     * @param String $mobile
     * @param String $email
     */
    public static function passwordResetHash($mobile, $email)
    {
        $salt = self::generateSalt($mobile);
        $salted = $email . time() . "{" . $salt . "}";
        $digest = hash('sha512', $salted, true);
        for ($i = 1; $i < 100; $i ++) {
            $digest = hash('sha512', $digest . $salted, true);
        }
        return md5($digest);
    }

    /**
     *
     * Calculate password hash for users
     *
     * @param
     *            String salt $mobile
     * @param String $clearPassword
     */
    public static function passwordHash($mobile, $clearPassword)
    {
        $salt = self::generateSalt($mobile);
        $salted = $clearPassword . "{" . $salt . "}";
        $digest = hash('sha512', $salted, true);
        for ($i = 1; $i < 5000; $i ++) {
            $digest = hash('sha512', $digest . $salted, true);
        }
        return base64_encode($digest);
    }

    /**
     *
     * Common interface to be used in encryption for both OS devices
     *
     * @param String $input
     * @param String $key
     * @param String $mobile
     * @param String $os
     */
    public static function encrypt($input, $key, $mobile, $os)
    {
        if ($os == 1) {
            return HDAndroidCypher::encrypt($input, $key, self::generateIv($mobile));
        } elseif ($os == 2) {
            return HDIOSCypher::encrypt($input, $key);
        } else
            return "";
    }

    /**
     *
     * Common interface to be used in decryption for bots OS devices
     *
     * @param
     *            $input
     * @param
     *            $key
     * @param
     *            $mobile
     * @param
     *            $os
     */
    public static function decrypt($input, $key, $mobile, $os)
    {
        $response = "";

        // make sure we test both operating systems in case something goes wrong
        if ($os == 1) {
            $response = HDAndroidCypher::decrypt($input, $key, self::generateIv($mobile));

            // test if decryption worked
            if (json_decode($response) === null) {
                // if not try IOS way
                $response = HDIOSCypher::decrypt($input, $key);

                // test if IOS way worked as well
                if (json_decode($response) === null) {
                    // if not
                    $response = "";
                }
            }
        } elseif ($os == 2) {
            $response = HDIOSCypher::decrypt($input, $key);

            // test if decryption worked
            if (json_decode($response) === null) {
                // if not try Android way way
                $response = HDAndroidCypher::decrypt($input, $key, self::generateIv($mobile));

                // test if Android way worked as well
                if (json_decode($response) === null) {
                    // if not
                    $response = "";
                }
            }
        }
        return $response;
    }

    /**
     *
     * Generate security iv based on input string
     *
     * @param
     *            $input
     */
    public static function generateIv($input)
    {
        return substr(sha1(base64_encode(md5($input))), 0, 16);
    }

    /**
     *
     * Generate security iv based on input string
     *
     * @param
     *            $input
     */
    public static function generateSalt($input)
    {
        return md5(sha1(base64_encode($input)));
    }
}