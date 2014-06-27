<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 *
	 * Including this file is all that's required to make use of this class
	 * library.
	 */
	namespace TFN;

	// Figure out where we are.
	define('TFN_ROOT_DIR', __DIR__.'/');

	// Register our class autoloader
	spl_autoload_register(function($class) {
		$class = strtolower($class);
		if (substr($class, 0, 4) == 'tfn\\') {
			// Looking for a TFN class.
			$class = substr($class, 4);
			$class_filename = TFN_ROOT_DIR.str_replace('_', '/', $class).'.php';
			if (file_exists($class_filename)) {
				require $class_filename;
			}
		}
	});

	// PHP_VERSION_ID is used to select code based on the PHP version number, but
	// was not introduced into PHP until version 5.2.7 so if it's not defined we
	// define it.
	if (!defined('PHP_VERSION_ID')) {
		$____version = explode('.', PHP_VERSION);
		define('PHP_VERSION_ID', ($____version[0] * 10000) + ($____version[1] * 100) + $____version[2]);
	}

	// Variable access function
	function V($array, $key, $default = null)
	{
		if (isset($array[$key])) {
			return $array[$key];
		}
		return $default;
	}
