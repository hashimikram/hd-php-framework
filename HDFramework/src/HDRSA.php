<?php
namespace HDFramework\src;

use Exception;

/**
 * Encrypt/decrypt class
 *
 * @author cornel
 * @package framework
 */
class HDRSA
{

    /**
     * Public wrapper for generateRandomKeys()
     *
     * @access public
     * @static
     * @return array strings of key pair/modulus in decimal
     * @see generateRandomKeys()
     * @since Method available since Release 0.1.0
     */
    public static function getRandomKeys($limiter = 4)
    {
        list ($d, $e, $n) = self::generateRandomKeys($limiter);
        return array(
            'private' => gmp_strval($d),
            'public' => gmp_strval($e),
            'modulus' => gmp_strval($n)
        );
    }

    /**
     * Encrypts a message using the RSA algorithm.
     * If the message is big enough,
     * it is split into chunks that are encrypted one by one and separated by
     * a white space. Aims to be compatible with jquery plugin jCryption.
     *
     * @param string $message
     *            secret message
     * @param string $publicKey
     *            encryption public key
     * @param string $modulus
     *            RSA modulus
     *            
     * @access public
     * @return string encrypted message
     * @throws Exception
     * @since Method available since Release 0.1.0
     */
    public static function encrypt($message, $publicKey, $modulus)
    {
        /**
         * holds encrypted message
         */
        $secret = array();

        /**
         * array of ascii values in hex
         */
        $message = str_split(bin2hex($message), 2);

        /**
         * calculates checksum of message
         */
        $checksum = self::arraySumHex($message);

        /**
         * array of ascii values in hex
         */
        $checksum = str_split(bin2hex($checksum), 2);

        /**
         * inserts checksum at the beginning of message
         */
        $message = array_merge($checksum, $message);

        /**
         * we can only encrypt a limited number of chars at a time
         */
        $message = array_chunk($message, self::chunkSize($modulus));

        foreach ($message as $chunk) {
            /**
             * reverses message for jCryption compatibility
             */
            $chunk = array_reverse($chunk);

            /**
             * turns message into a big integer
             */
            $chunk = gmp_init('0x' . (implode($chunk)));

            /**
             * do encryption
             */
            $secret[] = gmp_strval(gmp_powm($chunk, $publicKey, $modulus), 16);
        }
        return implode(' ', $secret);
    }

    /**
     * Decrypts a message using the RSA algorithm.
     * Aims to be compatible with
     * jquery plugin jCryption.
     *
     * @param string $secret
     *            encrypted message
     *            
     * @access public
     * @return string decrypted message
     * @throws Exception
     * @since Method available since Release 0.1.0
     */
    public static function decrypt($secret, $privateKey, $modulus)
    {
        /**
         * holds decrypted message
         */
        $message = '';

        /**
         * splits message into array of chunks
         */
        $secret = explode(' ', $secret);

        foreach ($secret as $chunk) {

            /**
             * turns message into a big integer
             */
            $chunk = gmp_init('0x' . $chunk);

            /**
             * decrypts chunk, extracts string from hex code and reverses
             * message for jCryption compatibility
             */
            $message .= strrev(pack('H*', gmp_strval(gmp_powm($chunk, $privateKey, $modulus), 16)));
        }

        $checksum = substr($message, 0, 2);
        $message = substr($message, 2);
        $hexsum = self::arraySumHex(str_split(bin2hex($message), 2));

        if ($hexsum != $checksum) {
            return false;
        }
        return $message;
    }

    /**
     * Generates a random key pair/modulus
     *
     * @access protected
     * @static
     * @return array GMP resources of key pair/modulus
     * @since Method available since Release 0.1.0
     */
    private static function generateRandomKeys($limiter = 4)
    {
        /**
         * Computing modulus
         */
        $p = self::getRandomPrime($limiter);
        $q = self::getRandomPrime($limiter);
        $n = gmp_mul($p, $q);

        /**
         * Computing Euler's totient function
         */
        $f = gmp_mul(gmp_sub($p, 1), gmp_sub($q, 1));

        /**
         * Computing the public key exponent
         */
        $e = gmp_random_bits(4 * 64);
        $e = gmp_nextprime($e);
        while (gmp_cmp(gmp_gcd($e, $f), 1) != 0) {
            $e = gmp_add($e, 1);
        }

        /**
         * Computing the private key exponent
         */
        $d = self::multiplicativeInverse($e, $f);

        return array(
            $d,
            $e,
            $n
        );
    }

    /**
     * Calculates the modular multiplicative inverse as found in:
     * http://en.wikipedia.org/wiki/Modular_multiplicative_inverse
     *
     * @param resource $e
     *            GMP resource number
     * @param resource $f
     *            GMP resource number
     *            
     * @access protected
     * @static
     * @return resource calculated number as a GMP resource
     * @since Method available since Release 0.1.0
     */
    private static function multiplicativeInverse($e, $f)
    {
        $u1 = 1;
        $u2 = 0;
        $u3 = $f;

        $v1 = 0;
        $v2 = 1;
        $v3 = $e;

        while (gmp_cmp($v3, 0) != 0) {
            $qq = gmp_div($u3, $v3);

            $t1 = gmp_sub($u1, gmp_mul($qq, $v1));
            $t2 = gmp_sub($u2, gmp_mul($qq, $v2));
            $t3 = gmp_sub($u3, gmp_mul($qq, $v3));

            $u1 = $v1;
            $u2 = $v2;
            $u3 = $v3;

            $v1 = $t1;
            $v2 = $t2;
            $v3 = $t3;
        }

        if (gmp_cmp($u2, 0) < 0) {
            return gmp_add($u2, $f);
        }
        return $u2;
    }

    /**
     * Returns a random prime number
     *
     * @param integer $limiter
     *            gmp_random's limiter (now it's gmp_random_bits($limiter * 64))
     *            
     * @access protected
     * @static
     * @return resource GMP resource
     * @since Method available since Release 0.1.0
     */
    private static function getRandomPrime($limiter = 4)
    {
        return gmp_nextprime(gmp_random_bits($limiter * 64));
    }

    /**
     * This function returns the maximum chunk size our key can encrypt.
     *
     * The message m (as a number) should fit into the rule:
     *
     * 0 < m < modulus
     *
     * So we have to split it into chunks before the encryption.
     *
     * For compatibility with jCryption, this is calculated by couting how many
     * times we can break the modulus into slices of two bytes and the multiply
     * this number by two, since each character in message m is traslated to a
     * single byte. This ensures that the chunk will never have more bytes than
     * (number of bytes in modulus, minus one). Note that this algorithm is
     * sub-optimal, since for most cases it returns (number of bytes in modulus,
     * minus two), as shown below:
     *
     * CS OP MODULUS
     * 0 0 FF
     * 0 1 FFF
     * 0 1 FFFF
     * 2 2 FFFFF
     * 2 2 FFFFFF
     * 2 3 FFFFFFF
     * 2 3 FFFFFFFF
     * 4 4 FFFFFFFFF
     * 4 4 FFFFFFFFFF
     * 4 5 FFFFFFFFFFF
     * 4 5 FFFFFFFFFFFF
     * 6 6 FFFFFFFFFFFFF
     *
     * CS = this algorithm chunk size
     * OP = optimus chunk size
     *
     * @return int the number of characters for a chunk
     * @access private
     * @since Method available since Release 0.1.0
     */
    private static function chunkSize($modulus)
    {
        $j = 0;
        for ($n = $modulus; gmp_strval($n = gmp_div($n, '0x10000')); $j ++);
        return $j * 2;
    }

    /**
     * Returns the last two hexadecimal digits of the sum of an array o
     * hexadecimal numbers.
     * This is intended to be used as the checksum of the
     * message that is being encrypted.
     *
     * @param array $arr
     *            strings representing a 2-digit hex number
     *            
     * @access protected
     * @return string checksum
     * @since Method available since Release 0.1.0
     */
    private static function arraySumHex($arr)
    {
        $sum = 0;
        foreach ($arr as $v) {
            /**
             * adds current char
             */
            $sum += hexdec("0x$v");

            /**
             * keeps the sum with only 2 digits
             */
            $sum %= 0x100;
        }

        /**
         * returns string representaion of number in hex
         */
        return dechex($sum);
    }
}
