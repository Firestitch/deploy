<?php
/**
 * Created by PhpStorm.
 * User: Milad Rahimi <info@miladrahimi.com>
 * Date: 6/23/2017
 * Time: 12:26 AM
 */

namespace MiladRahimi\PhpCrypt;

use MiladRahimi\PhpCrypt\Exceptions\DecryptionException;
use MiladRahimi\PhpCrypt\Exceptions\CipherMethodNotSupportedException;

class Crypt implements CryptInterface
{
    /** @var string $key */
    private $key;

    /** @var string $method */
    private $method;

    /** @var int $options */
    private $options = OPENSSL_RAW_DATA;

    /**
     * Constructor
     *
     * @param string|null $key Cryptography key (salt)
     * @param string $method
     */
    public function __construct($key = null, $method = 'AES-256-CBC')
    {
        if ($key == null) {
            $key = self::generateRandomKey();
        }

        $this->setMethod($method);
        $this->setKey($key);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Encrypt text
     *
     * @param string $plainText
     * @return string
     */
    function encrypt($plainText)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt($plainText, $this->method, $this->key, $this->options, $iv);
        return base64_encode($encrypted) . ':' . base64_encode($iv);
    }

    /**
     * Decrypt text
     *
     * @param string $encryptedText
     * @return string
     * @throws DecryptionException
     */
    function decrypt($encryptedText)
    {
        $parts = explode(':', $encryptedText);

        if (count($parts) != 2) {
            throw new DecryptionException();
        }

        $main = base64_decode($parts[0]);
        $iv = base64_decode($parts[1]);
        return openssl_decrypt($main, $this->method, $this->key, $this->options, $iv);
    }

    /**
     * @return int
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param int $method
     * @throws CipherMethodNotSupportedException
     */
    public function setMethod($method)
    {
        if (in_array($method, openssl_get_cipher_methods()) == false) {
            throw new CipherMethodNotSupportedException();
        }

        $this->method = $method;
    }

    /**
     * Return all supported cipher methods
     *
     * @return array
     */
    public static function availableMethods()
    {
        return openssl_get_cipher_methods(true);
    }

    /**
     * Generate a random key
     *
     * @return string
     */
    public static function generateRandomKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * @return int
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }
}