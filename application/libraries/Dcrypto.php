<?php

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Dcrypto
 *
 * https://github.com/defuse/php-encryption
 */
class Dcrypto
{
    /**
     * @return string
     * @throws EnvironmentIsBrokenException
     */
    public function generate_key()
    {
        return Key::createNewRandomKey()
            ->saveToAsciiSafeString();
    }

    /**
     * @param $secret_data
     * @param string $key - Ascii key
     * @return string
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt($secret_data, $key = '')
    {
        return Crypto::encrypt($secret_data, Key::loadFromAsciiSafeString($key));
    }

    /**
     * @param $cipher
     * @param string $key - Ascii key
     * @return string
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decrypt($cipher, $key = '')
    {
        return Crypto::decrypt($cipher, Key::loadFromAsciiSafeString($key));
    }

    /**
     * @param $password - user password
     * @return string - save into the user's account record
     *
     * @throws EnvironmentIsBrokenException
     */
    public function password_key($password)
    {
        return KeyProtectedByPassword::createRandomPasswordProtectedKey($password)
            ->saveToAsciiSafeString();
    }

    /**
     * @param $protected_key - load it from the user's account record
     * @param $password - user password
     * @return string - save key in the session
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function get_password_key($protected_key, $password)
    {
        $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString($protected_key);
        $user_key = $protected_key->unlockKey($password);

        return $user_key->saveToAsciiSafeString();
    }

    /**
     * @param $data
     * @param $password_key - get from session
     * @return string
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    public function encrypt_password($data, $password_key)
    {
        return Crypto::encrypt($data, Key::loadFromAsciiSafeString($password_key));
    }

    /**
     * @param $encrypted_data
     * @param $password_key - get from session
     * @return string
     *
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    public function decrypt_password($encrypted_data, $password_key)
    {
        return Crypto::decrypt($encrypted_data, Key::loadFromAsciiSafeString($password_key));
    }
}
