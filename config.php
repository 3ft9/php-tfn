<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * The Config class will throw this exception when it encounters an error.
	 */
	class Exception_ConfigurationError extends \Exception { }

	/**
	 * The static Config class provides access to a configuration file in the
	 * traditional conf format (name=value, ; at line start indicates a comment).
	 */
	class Config
	{
		/**
		 * The configuration filename.
		 * @var string
		 */
		static private $_filename = '/etc/tfn.conf';

		/**
		 * Internal storage for the configuration data.
		 * @var string
		 */
		static private $_data = array();

		/**
		 * Initialise the configuration object.
		 * @throws Exception
		 */
		static public function init($filename)
		{
			// If the filename passed in doesn't contain any path separaters,
			// assume it's just the filename and prepend /etc/.
			if (false === strpos($filename, '/') && false === strpos($filename, '\\') && !file_exists($filename)) {
				$filename = '/etc/'.$filename;
			}
			self::$_filename = $filename;
			self::reload();
		}

		/**
		 * (Re)load the configuration file.
		 * @throws Exception
		 */
		static public function reload()
		{
			// Make sure the file exists
			if (!file_exists(self::$_filename)) {
				throw new Exception_ConfigurationError('Configuration file not found: "'.self::$_filename.'"');
			}

			// Clear the array
			self::$_data = array();

			// Open the configuration file
			$fp = fopen(self::$_filename, 'rt');
			if (!$fp) {
				throw new Exception_ConfigurationError('Failed to open configuration file: "'.self::$_filename.'"');
			}

			// Parse the file
			$linenum = 0;
			while (!feof($fp)) {
				$line = trim(fgets($fp));
				$linenum++;
				// Check the line has content and is not a comment
				if (strlen($line) > 0 and $line[0] != ';' and $line[0] != '#') {
					// Separate the key from the value
					$parts = explode('=', $line, 2);
					// Trim both
					foreach (array_keys($parts) as $key) {
						$parts[$key] = trim($parts[$key]);
					}
					// Check that we have both a key and a value, and that the key has
					// content
					if (count($parts) == 1 or strlen($parts[0]) == 0) {
						throw new
							Exception_ConfigurationError('Syntax error on line '.$linenum);
					}
					// Add the option to the data array
					self::$_data[$parts[0]] = $parts[1];
				}
			}

			// Close the file
			fclose($fp);
		}

		/**
		 * Get a configuration value. Returns null if the variable requested does
		 * not exist.
		 * @param string $var
		 * @return mixed
		 */
		static public function get($var, $default = null)
		{
			if (isset(self::$_data[$var])) {
				return self::$_data[$var];
			}
			return $default;
		}
	}
