<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * All expections thrown by the Request class will be of this type.
	 */
	class RequestException extends TFNException { }

	/**
	 * This class provides access to the various parts of the HTTP request.
	 */
	class Request
	{
		/**
		 * Initialise a new instance of the Request class.
		 *
		 * @return Request
		 */
		public static function init()
		{
			return new self();
		}

		/**
		 * Get a variable by searching the GET (g), POST (p), SERVER (s) and
		 * COOKIE (c) data in the given order.
		 *
		 * @param string $key     The variable to look for.
		 * @param mixed  $default This is returned if the variable cannot be found.
		 * @param string $order   The order in which to search the arrays (def: gpc).
		 * @return mixed          The value of the variable if found, or $default.
		 * @throws RequestException If there is an unknown character in $order.
		 */
		public function requestVar($key, $default = null, $order = 'gpc')
		{
			$funcmap = array(
					'g' => 'getVar',
					'p' => 'postVar',
					'c' => 'cookieVar',
					's' => 'serverVar',
				);

			$len = strlen($order);
			for ($i = 0; $i < $len; $i++) {
				if (!isset($funcmap[$order[$i]])) {
					throw new RequestException('Unknown variable type "'.$order[$i].'"');
				}
				$retval = $this->$funcmap[$order[$i]]($key, null);
				if (!is_null($retval)) {
					return $retval;
				}
			}

			return $default;
		}

		/**
		 * Get the number of GET variables in the request.
		 *
		 * @return int
		 */
		public function getCount()
		{
			return count($_GET);
		}

		/**
		 * Get a variable from the GET data.
		 *
		 * @param string $key     The key of the variable to get.
		 * @param mixed  $default The value to be returned if the variable is not present.
		 * @return mixed          The value of the variable or $default.
		 */
		public function getVar($key, $default = null)
		{
			return V($_GET, $key, $default);
		}

		/**
		 * Get all variables in the GET data excluding those specified in $exclude.
		 *
		 * @param array $exclude Keys to be excluded from the returned data.
		 * @return array         A copy of the GET data without the keys in $exclude.
		 */
		public function getVars($exclude = array())
		{
			return $this->duplicateArray($_GET, $exclude);
		}

		/**
		 * Get the number of POST variables in the request.
		 *
		 * @return int
		 */
		public function postCount()
		{
			return count($_POST);
		}

		/**
		 * Get a variable from the POST data.
		 *
		 * @param string $key     The key of the variable to get.
		 * @param mixed  $default The value to be returned if the variable is not present.
		 * @return mixed          The value of the variable or $default.
		 */
		public function postVar($key, $default = null)
		{
			return V($_POST, $key, $default);
		}

		/**
		 * Get all variables in the POST data excluding those specified in $exclude.
		 *
		 * @param array $exclude Keys to be excluded from the returned data.
		 * @return array         A copy of the POST data without the keys in $exclude.
		 */
		public function postVars($exclude = array())
		{
			return $this->duplicateArray($_POST, $exclude);
		}

		/**
		 * Get a variable from the SERVER data.
		 *
		 * @param string $key     The key of the variable to get.
		 * @param mixed  $default The value to be returned if the variable is not present.
		 * @return mixed          The value of the variable or $default.
		 */
		public function serverVar($key, $default = null)
		{
			return V($_SERVER, $key, $default);
		}

		/**
		 * Get a variable from the COOKIE data.
		 *
		 * @param string $key     The key of the variable to get.
		 * @param mixed  $default The value to be returned if the variable is not present.
		 * @return mixed          The value of the variable or $default.
		 */
		public function cookieVar($name, $default = '', $key = false)
		{
			return Cookie::get($name, $default, $key);
		}

		/**
		 * Duplicate the given array, excluding keys present in $exclude.
		 *
		 * @param array $exclude Keys to be excluded from the returned data.
		 * @return array         A copy of the array without the keys in $exclude.
		 */
		protected function duplicateArray($arr, $exclude = array())
		{
			$retval = array();
			foreach ($arr as $key => $val) {
				if (!in_array($key, $exclude)) {
					$retval[$key] = $val;
				}
			}
			return $retval;
		}
	}
