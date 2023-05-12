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
class HDAndroidCypherV2
{

    /**
     *
     * Encrypt information based on iv and encryption key
     *
     * @param String $input
     * @param String $key
     * @param String $iv
     */
    public static function encrypt($input, $key, $iv)
    {
        $key = substr($key, 0, 32);
        $input = self::pkcs5_pad($input);
        $td = phpseclib_mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
        phpseclib_mcrypt_generic_init($td, $key, $iv);
        $encrypted = phpseclib_mcrypt_generic($td, $input);
        phpseclib_mcrypt_generic_deinit($td);
        phpseclib_mcrypt_module_close($td);
        return base64_encode($encrypted);
    }

    /**
     *
     * Decrypt information coded based on iv and encryption key
     *
     * @param String $input
     * @param String $key
     * @param String $iv
     */
    public static function decrypt($input, $key, $iv)
    {
        $key = substr($key, 0, 32);
        $input = base64_decode($input);
        $td = phpseclib_mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
        phpseclib_mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $input);
        phpseclib_mcrypt_generic_deinit($td);
        phpseclib_mcrypt_module_close($td);
        $ut = utf8_encode(trim($decrypted));
        $unpadded = self::pkcs5_unpad($ut);
        if ($unpadded) {
            return $unpadded;
        } else {
            return $ut;
        }
    }

    /**
     * Internal use encryption method
     */
    private static function pkcs5_pad($text)
    {
        $blocksize = 16;
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * Internal use decryption method
     */
    private static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }

        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    }
}
