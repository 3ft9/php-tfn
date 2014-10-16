<?php
	/**
	 * 3ft9 Validate.
	 *
	 * Part of the 3ft9 PHP Class Library.
	 * Copyright (C) 3ft9 Ltd. All rights reserved.
	 */
	namespace TFN;

	/**
	 * This static class provides common validation routines.
	 */
	class Validate
	{
		/**
		 * Validate an email address.
		 *
		 * @see   http://www.linuxjournal.com/article/9585?page=0,3
		 * @param string $email The email address to validate.
		 * @return mixed        True if validation passes, otherwise an error message.
		 */
		public static function email($email)
		{
			$error = false;
			$atIndex = strrpos($email, '@');
			if (is_bool($atIndex) && !$atIndex) {
				$error = 'invalid format [1]';
			} else {
				$domain = substr($email, $atIndex + 1);
				$local = substr($email, 0, $atIndex);
				$localLen = strlen($local);
				$domainLen = strlen($domain);
				if ($localLen < 1 || $localLen > 64) {
					// local part length exceeded
					$error = 'username too long';
				} elseif ($domainLen < 1 || $domainLen > 255) {
					// domain part length exceeded
					$error = 'domain too long';
				} elseif ($local[0] == '.' || $local[$localLen - 1] == '.') {
					// local part starts or ends with '.'
					$error = 'invalid format [2]';
				} elseif (preg_match('/\\.\\./', $local)) {
					// local part has two consecutive dots
					$error = 'invalid format [3]';
				} elseif (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
					// character not valid in domain part
					$error = 'invalid character found [1]';
				} elseif (preg_match('/\\.\\./', $domain)) {
					// domain part has two consecutive dots
					$error = 'invalid format [4]';
				} elseif (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
					// character not valid in local part unless local part is quoted
					if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
						$error = 'invalid character found [2]';
					}
				}

				if ($error && !(checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'))) {
					// domain not found in DNS
					$error = 'DNS lookup failed';
				}
			}

			// Valid!
			return $error === false ? true : $error;
		}
	}
