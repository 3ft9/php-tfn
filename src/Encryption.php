<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * This file won't be included unless the encryption class is used, so this
	 * check for the required library (mcrypt) is executed only the first time
	 * it's used.
	 */
	if (!function_exists('mcrypt_encrypt')) {
		throw new TFNException('The mcrypt extension is required for encryption support');
	}

	/**
	 * Encryption utility class.
	 */
	class Encryption
	{
		/**
		 * This is the key used to calculate the encrypted value. Set it to a
		 * custom value or pass it in whenever you use the encrypt or decrypt
		 * functions.
		 *
		 * @var string
		 */
		static private $_encryption_key = 'replace me with something else';

		/**
		 * Set the key to be used when encrypting and decrypting.
		 *
		 * @param string $key The key to be used.
		 */
		static public function setKey($key)
		{
			if (!is_string($key)) {
				throw new Exception('The encryption key must be a string!');
			}
			self::$_encryption_key = $key;
		}

		/**
		 * Encrypt a value.
		 *
		 * @param mixed $value The value to be encrypted.
		 * @param string $key The key to use when encrypting, or false to use
		 *                    the class variable.
		 * @return string The encrypted value.
		 */
		static public function encrypt($value, $key = false)
		{
			if ($key === false) {
				$key = self::$_encryption_key;
			}
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, serialize($value), MCRYPT_MODE_ECB, self::getIV()));
		}

		/**
		 * Decrypt a value.
		 *
		 * @param string $value The value to be decrypted.
		 * @param string $key The key to use when decrypting, or false to use
		 *                    the class variable.
		 * @return mixed The decrypted value.
		 */
		static public function decrypt($value, $key = false)
		{
			if ($key === false) {
				$key = self::$_encryption_key;
			}
			$retval = unserialize(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($value), MCRYPT_MODE_ECB, self::getIV()));
			return $retval;
		}

		/**
		 * We cache the IV here.
		 *
		 * @var string
		 */
		static private $_iv = false;

		/**
		 * Get the IV to pass in to the mcrypt_(en|de)crypt functions. This
		 * value is cached in a class variable.
		 *
		 * @return string The IV.
		 */
		static private function getIV()
		{
			if (self::$_iv === false) {
				self::$_iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
			}
			return self::$_iv;
		}
	}
