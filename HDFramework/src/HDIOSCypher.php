<?php
namespace HDFramework\src;

use Exception;

/**
 * Cypher IOS class to manage encryption
 *
 * Configurations dependencies:<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDIOSCypher
{

    private static $encryptor;

    private static $decryptor;

    public static function init()
    {
        if (! self::$encryptor || ! self::$decryptor) {
            self::$encryptor = new \RNCryptor\RNCryptor\Encryptor();
            self::$decryptor = new \RNCryptor\RNCryptor\Decryptor();
        }
    }

    /**
     * Encrypt text
     *
     * @param String $input
     *            Input text to encrypt
     * @param String $encryptionKey
     *            Encryption key
     * @return String Encrypted text
     */
    public static function encrypt($input, $encryptionKey)
    {
        self::init();
        try {
            return self::$encryptor->encrypt($input, $encryptionKey);
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Decrypt text
     *
     * @param String $input
     *            Input text to decrypt
     * @param String $encryptionKey
     *            Encryption key
     * @return String Decrypted text
     */
    public static function decrypt($input, $encryptionKey)
    {
        self::init();
        try {
            return self::$decryptor->decrypt($input, $encryptionKey);
        } catch (Exception $e) {
            return "";
        }
    }
}
