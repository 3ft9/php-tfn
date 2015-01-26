<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * All expections thrown by the User class will be of this type, or a derived
	 * type.
	 */
	class UserException extends TFNException { }

	/**
	 * Thrown when a requested user cannot be found.
	 */
	class UserNotFoundException extends UserException
	{
		public function __construct($message = '')
		{
			parent::__construct($message ? $message : 'User not found');
		}
	}

	/**
	 * The User class wraps storage, retrieval and auth of users against a
	 * pluggable backend.
	 */
	class User
	{
		/**
		 * The table where this class will store its data.
		 */
		const STORAGE_TABLE = 'users';

		/**
		 * The status of an active user.
		 */
		const STATUS_ACTIVE = 'active';

		/**
		 * The status of a disabled user.
		 */
		const STATUS_DISABLED = 'disabled';

		/**
		 * The name of the cookie in which we store the encrypted user ID.
		 */
		const UserCookieName = 'u';

		/**
		 * The name of the cookie in which we store the authentication token.
		 */
		const TokenCookieName = 't';

		/**
		 * The name of the cookie for external auth provider sessions.
		 */
		const ExternalSessionCookieName = 'es';

		/**
		 * We use this key to encrypt things. DO NOT CHANGE THIS OR EVERYONE WILL
		 * BE LOGGED OUT!
		 */
		const LoginTokenKey = 'sdiovhubysiolosehgiuakelfihsale';

		/**
		 * The currently logged in user object.
		 * @var User
		 */
		private static $_loggedin = null;

		/**
		 * Password seasoning.
		 */
		private static $_password_salt = '';
		private static $_password_pepper = '';

		/**
		 * Set the password seasoning.
		 */
		public static function setSeasoning($salt, $pepper = '')
		{
			self::$_password_salt = $salt;
			self::$_password_pepper = $pepper;
		}

		/**
		 * Get a user object by ID.
		 *
		 * @param  int $id The user ID.
		 * @return \TFN\User
		 */
		public static function get($id)
		{
			return new self($id);
		}

		public static function getAll()
		{
			$rows = Storage::query(self::STORAGE_TABLE, array(), array('id'), array('username' => 'asc'));
			$retval = array();
			foreach ($rows as $row) {
				$retval[] = new self($row['id']);
			}
			return $retval;
		}

		/**
		 * Return the currently logged in user object, if one exists.
		 *
		 * @return User
		 */
		public static function getCurrent()
		{
			if (!is_null(self::$_loggedin) && self::$_loggedin !== false) {
				return self::$_loggedin;
			}

			if (!Cookie::isEmpty(self::UserCookieName)) {
				self::$_loggedin = self::cookieLogin();
				return self::$_loggedin;
			}

			// Nobody currently logged in.
			return false;
		}

		/**
		 * Create a new user from the supplied data.
		 *
		 * @param string $username The user's username. Could be their email address.
		 * @param string $password The user's desired password.
		 * @param array  $additional_data Additional data to store against this user.
		 * @return string The new user's ID.
		 * @throws UserException If anything goes wrong.
		 */
		public static function create($username, $password, $additional_data = array())
		{
			try {
				// Add the username and password to the user data.
				$additional_data['username'] = $username;
				$additional_data['password'] = self::seasonPassword($password);
				// Create the user in the storage system.
				return Storage::insert(self::STORAGE_TABLE, $additional_data);
			} catch (StorageException $e) {
				throw new UserException($e->getMessage());
			}
		}

		/**
		 * This member variable contains all the data associated with this user.
		 * @var array
		 */
		protected $_data = array();

		/**
		 * Boolean indicating whether the user is fully authenticated.
		 *
		 * @var bool
		 */
		protected $_authenticated = false;

		/**
		 * Boolean indicating whether the user data is dirty.
		 *
		 * @var bool
		 */
		protected $_dirty = false;

		/**
		 * Create a user object for the given ID.
		 *
		 * @param string $id The ID of the user to be loaded.
		 */
		protected function __construct($id)
		{
			$this->reloadData($id);
			$this->_authenticated = false;
		}

		/**
		 * Reload the user data from the backend storage system.
		 *
		 * @param string $id Optional user ID, defaults to the one in $this->_data.
		 */
		public function reloadData($id = false)
		{
			// Grab the ID if none was given
			if ($id === false) {
				if (empty($this->_data['id'])) {
					throw new UserException('Unable to ascertain the user ID');
				}
				$id = $this->_data['id'];
			}

			// Get the user data from the storage system.
			try {
				$data = Storage::get(self::STORAGE_TABLE, array('id' => $id));
				if (!$data) {
					throw new UserNotFoundException();
				}
				$this->_data = $data;
				$this->_dirty = false;
			} catch (StorageException $e) {
				throw new UserException('Failed to get user: '.$e->getMessage());
			}
		}

		/**
		 * Get a variable from the user's data.
		 *
		 * @param  string $var The name of the variable to retrieve.
		 * @return mixed  The variable value or null if it doesn't exist.
		 */
		public function __get($var)
		{
			if (isset($this->_data[$var])) {
				return $this->_data[$var];
			}
			return null;
		}

		/**
		 * Set a variable in the user's data.
		 *
		 * @param string $var The name of the variable to set.
		 * @param mixed  $val The value.
		 */
		public function __set($var, $val)
		{
			if (empty($this->_data[$var]) || $this->_data[$var] != $val) {
				$this->_dirty = true;
			}
			$this->_data[$var] = $val;
		}

		/**
		 * Returns true if the user data is dirty (i.e. has been changed but not
		 * saved to the storage system).
		 *
		 * @return boolean True if the user data is dirty.
		 */
		public function isDirty()
		{
			return $this->_dirty;
		}

		/**
		 * Set this user object as the currently logged in user.
		 *
		 * @param  boolean $authenticated Pass as true to indicate this user is
		 *                                fully authenticated.
		 * @param  boolean $rememberme    Pass as true to set the cookie for six
		 *                                months instead of the default (session).
		 * @return User                   The logged in user object.
		 */
		public function logMeIn($authenticated = true, $rememberme = false)
		{
			self::$_loggedin = $this;

			Cookie::set(
				self::UserCookieName,
				base64_encode($this->id),
				($rememberme ? Cookie::SixMonths : Cookie::Session));

			$this->createLoginToken();

			$this->_authenticated = $authenticated;

			// Set the timezone based on the user data, if present.
			if ($this->timezone) {
				date_default_timezone_set($this->timezone);
			}

			return $this;
		}

		/**
		 * @return bool True if an account is currently logged in.
		 */
		static public function isLoggedIn()
		{
			if (!self::$_loggedin) {
				// Attempt a cookie-based login.
				self::$_loggedin = self::_();
			}

			// Check that the logged in account is allowed to be logged in
			if (self::$_loggedin && self::$_loggedin->status != self::STATUS_ACTIVE) {
				self::logout();
				return false;
			}

			return (self::$_loggedin instanceof self);
		}

		/**
		 * Login.
		 *
		 * @param string $username
		 * @param string $password
		 * @return User
		 */
		public static function login($username, $password, $rememberme = false, $authenticated = true)
		{
			// Get the user data from the storage system.
			try {
				$data = Storage::get(self::STORAGE_TABLE, array('username' => $username, 'password' => self::seasonPassword($password)), array('id'));
				if (!$data) {
					throw new UserNotFoundException();
				}
			} catch (StorageException $e) {
				throw new UserNotFoundException();
			}

			return self::loginByID($data['id'], $rememberme, $authenticated);
		}

		public static function loginByID($id, $rememberme = false, $authenticated = true)
		{
			self::get($id)->logMeIn($authenticated, $rememberme);

			// If we get here then we logged in successfully set up the cookies and
			// other bits.
			Cookie::set(self::UserCookieName, base64_encode(json_encode(array('u' => self::$_loggedin->id, 't' => self::$_loggedin->timezone))), ($rememberme ? Cookie::SixMonths : Cookie::Session));
			self::$_loggedin->CreateLoginToken();
			self::$_loggedin->_authenticated = $authenticated;

			// Return the logged in user.
			return self::$_loggedin;
		}

		/**
		 * Uses the User cookie to log in.
		 * Note this does not authenticate the user.
		 *
		 * @return User
		 */
		public static function cookieLogin($c = '')
		{
			$retval = false;

			try {
				if (empty($c)) {
					$c = Cookie::get(self::UserCookieName);
				}

				if (!empty($c)) {
					$c = json_decode(base64_decode($c), true);
					if ($c && isset($c['u'])) {
						$retval = self::get($c['u']);
						if ($retval !== false) {
							$retval->tokenLogin();
							if (!empty($c['t'])) {
								date_default_timezone_set($c['t']);
							}
						}
					}
				}
			} catch (UserException $e) {
				// Clear the logged in user and delete the login cookies.
				self::logout();
				$retval = false;
			}

			return $retval;
		}

		/**
		 * Logout
		 *
		 */
		public static function logout()
		{
			self::$_loggedin = null;

			// Forget stuff
			Cookie::delete(self::UserCookieName);
			Cookie::delete(self::TokenCookieName);
		}

		public function tokenLogin()
		{
			$retval = false;
			$c = Cookie::get(self::TokenCookieName);
			if (strlen($c) > 0)
			{
				$iv_size = \mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
				$iv = \mcrypt_create_iv($iv_size, MCRYPT_RAND);
				$time = \mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::LoginTokenKey, base64_decode($c), MCRYPT_MODE_ECB, $iv);

				if ($time > time())
				{
					// Update the token
					$this->createLoginToken();
					$this->_authenticated = true;
				}
				$retval = true;
			}
			return $retval;
		}

		public function createLoginToken()
		{
			$iv_size = \mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
			$iv = \mcrypt_create_iv($iv_size, MCRYPT_RAND);
			$token = \mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::LoginTokenKey, time() + Time::OneHour, MCRYPT_MODE_ECB, $iv);
			Cookie::set(self::TokenCookieName, base64_encode($token));
		}

		public function isAuthenticated()
		{
			return $this->_authenticated;
		}

		/**
		 * Update data in the user.
		 *
		 * @param array $data The data to be updated.
		 * @return mixed True if the data was successfully updated, or an array of
		 *               validation errors.
		 * @throws UserNotFoundException If the user cannot be found.
		 * @throws UserException         If anythine else goes wrong.
		 */
		public function update($data)
		{
			try {
				Storage::update(self::STORAGE_TABLE, array('id' => $this->id), $this->processUpdateData($data));
				$this->reloadData();
				return true;
			} catch (StorageException $e) {
				switch ($e->getCode()) {
					default:
						throw new UserException($e->getMessage());
				}
			}
		}

		protected function processUpdateData($data)
		{
			// foreach (array_keys($data) as $key) {
			// 	// Switch on the key, default is to leave it untouched.
			// 	switch ($key) {
			// 	}
			// }

			return $data;
		}

		/**
		 * Create a token with the given name and store it.
		 *
		 * @param string $name The name of the new token.
		 */
		protected function createToken($name)
		{
			$token = substr(sha1(var_export($_SERVER, true)), -8);
			$this->update(array($name => $token));
			return $token;
		}

		/**
		 * Season a password with the salt and pepper.
		 *
		 * @param string $password The password.
		 * @return string          The seasoned password.
		 */
		static protected function seasonPassword($password)
		{
			return sha1(self::$_password_salt.$password.self::$_password_pepper);
		}

		/**
		 * Set the user's password.
		 *
		 * @param string $password The new password.
		 * @return mixed           True on success, or an array of errors.
		 * @throws UserException   If anything goes wrong.
		 */
		public function setPassword($password)
		{
			return $this->update(array('password' => self::seasonPassword($password)));
		}

		/**
		 * Delete this user.
		 *
		 * @return bool True on success.
		 * @throws UserNotFoundException If the user does not exist.
		 * @throws UserException If anything else goes wrong.
		 */
		public function delete()
		{
			try {
				Storage::remove(self::STORAGE_TABLE, array('id' => $this->id));
				// Clear the internal data.
				$this->_data = array();
				return true;
			} catch (StorageException $e) {
				switch ($e->getCode()) {
					case StorageException::NOT_FOUND:
						throw new UserNotFoundException();

					default:
						throw new UserException($e->getMessage());
				}
			}
		}
	}
