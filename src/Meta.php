<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * The Meta class will throw this exception when it encounters an error.
	 */
	class MetaException extends TFNException { }

	/**
	 * The static Meta class provides access to a key/value store in the storage
	 * system.
	 */
	class Meta
	{
		/**
		 * The name of the table where key/value data will be stored.
		 */
		const TABLE = 'meta';

		/**
		 * Get a value from the key/value store.
		 *
		 * @param  string $var     The name of the variable.
		 * @param  mixed  $default The value to return if the variable does not exist.
		 * @return mixed           The value from the k/v store or $default.
		 */
		static public function get($var, $default = null)
		{
			try {
				$retval = Storage::get(self::TABLE, array('var' => $var), array('val'));
				if (isset($retval['val'])) {
					if (strlen($retval['val']) > 2) {
						if ($retval['val'][0] == '$') {
							// Strip the § character that was added when the value was stored.
							$retval['val'] = substr($retval['val'], 1);
							if ($retval['val'][0] != '$') {
								// Value doesn't start with a § so it's JSON. Decode it.
								$retval['val'] = json_decode($retval['val'], true);
							}
						}
					}
					return $retval['val'];
				}
				// Not found, fall through to returning the default.
			} catch (Storage\Exception $e) {
				// Not found, fall through to returning the default.
			}
			return $default;
		}

		/**
		 * Set a value in the key/value store.
		 *
		 * @param  string        $var The name of the variable.
		 * @param  mixed         $val The value to store against that variable name.
		 * @throws MetaException      If something goes wrong.
		 */
		static public function set($var, $val)
		{
			// Convert arrays to JSON, inserting a character to indicate it's JSON.
			if (is_array($val)) {
				$val = '$'.json_encode($val);
			} elseif (is_string($val) && strlen($val) > 0 && $val[0] == '$') {
				// Not an array but it's a string that starts with a § so we need to
				// escape it.
				$val = '$'.$val;
			}

			try {
				Storage::insert(self::TABLE, array('var' => $var, 'val' => $val), true);
			} catch (Storage\Exception $e) {
				throw new MetaException($e->getMessage());
			}
		}

		/**
		 * Remove an item from the key/value store.
		 *
		 * @param  string        $var The name of the variable.
		 * @throws MetaException      If something goes wrong.
		 */
		static public function remove($var)
		{
			try {
				Storage::remove(self::TABLE, array('var' => $var));
			} catch (Storage\Exception $e) {
				throw new MetaException($e->getMessage());
			}
		}
	}
