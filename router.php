<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * The Router class will throw this custom exception when something goes
	 * wrong.
	 */
	class Router_Exception extends \Exception { }

	/**
	 * This class wraps a very simple routing mechanism.
	 * TODO: Usage documentation!
	 */
	class Router
	{
		/**
		 * Constants representing the different route types.
		 */
		const TYPE_SIMPLE = 'simple';
		const TYPE_REGEX = 'regex';

		/**
		 * Routes.
		 * @var array
		 */
		private $_routes = array();

		/**
		 * Construct a router object.
		 * @param $routes array An array of routes for this router.
		 */
		public function __construct($routes = array())
		{
			foreach ($routes as $route) {
				$this->addRoute($route[0], $route[1], $route[2]);
			}
		}

		/**
		 * Add a route. Routes are compared in the same order that they are added.
		 * @param $type string The route type.
		 * @param $source string The source for the route (i.e. the string to
		 *                       match or the regex).
		 * @param $target mixed If a string then this is the class that will
		 *                      handle the request. If it's an array then the
		 *                      first element is the class and the second is the
		 *                      method. Classes must already be loaded or auto-
		 *                      loadable when the request is dispatched.
		 * @throws Router_Exception
		 */
		public function addRoute($type, $source, $target)
		{
			if ($type != self::TYPE_SIMPLE and $type != self::TYPE_REGEX) {
				throw new Router_Exception('Unknown route type: "'.$type.'"');
			}

			if ($type == self::TYPE_SIMPLE) {
				// Simple routes are case insensitive
				$source = strtolower($source);
			}

			$this->_routes[] = array($type, $source, $target);
		}

		/**
		 * Dispatch a request.
		 * @param $url string The URL for the request.
		 */
		public function dispatch($url)
		{
			$matched = false;
			$params = array();
			foreach ($this->_routes as $route) {
				// Switch based on the type
				switch ($route[0]) {
					case self::TYPE_SIMPLE:
						if (substr($url, 0, strlen($route[1])) == $route[1]) {
							$matched = true;
							$url = substr($url, strlen($route[1]));
							if ($url !== false) {
								$params = $this->parseURL($url);
							}
						}
						break;

					case self::TYPE_REGEX:
						if (preg_match($route[1], $url, $params)) {
							array_shift($params);
							$matched = true;
						}
						break;
				}

				if ($matched) {
					// Got a match, call the handler
					if (is_array($route[2])) {
						// Handler is a method of a class
						if (count($route[2]) == 0 or
								count($route[2]) > 2 or
								!is_string($route[2][0]) or
								(count($route[2]) == 2 and !is_string($route[2][1]))) {
							throw new Router_Exception('Invalid route destination');
						}

						// Dispatch to the class/method
						$class = $route[2][0];
						$method = $route[2][1];
					} else {
						// Handler is a class, so we use the first parameter as the method
						// or call the index method if no such method exists
						$class = $route[2];
						$method = 'indexAction';
						if (count($params) > 0) {
							$method = array_shift($params).'Action';
						}
					}

					// Make sure we have the class
					if (!class_exists($class)) {
						throw new Router_Exception('Handler "'.$class.'" does not exist');
					}

					// Create the handler instance and make sure the required method
					// exists
					$handler = new $class();
					if (!method_exists($handler, $method)) {
						if ($method == 'indexAction' or !method_exists($handler, 'indexAction')) {
							throw new Router_Exception('Unable to route this request to handler "'.$class.'"');
						}
						array_unshift($params, substr($method, 0, -6));
						$method = 'indexAction';
					}

					// Make the call
					return $handler->$method($params);
				}
			}

			// If we get here then we didn't find a matching route
			throw new Router_Exception('No route found for this request');
		}

		/**
		 * Simple function to split the URL by /, trim it and return the array.
		 * Example: /blog/2007/01/14/example-post
		 *          parseURL()  => array('blog', '2007', '01', '14', 'example-post')
		 *          parseURL(false, 1) => array('2007', '01', '14', 'example-post')
		 *          parseURL(false, 4) => array('example-post')
		 *
		 * @param string $url The URL to parse
		 * @param integer $skip The number of items to drop from the start
		 * @return array
		 */
		public function parseURL($url = false, $skip = 0)
		{
			if ($url === false) {
				$url = $_SERVER['REQUEST_URI'];
			}

			$bits = explode('?', $url);
			$bits = explode('/', array_shift($bits));

			while (count($bits) > 0 and strlen($bits[0]) == 0) {
				array_shift($bits);
			}

			while (count($bits) > 0 and strlen($bits[count($bits)-1]) == 0) {
				array_pop($bits);
			}

			while ($skip-- > 0) {
				array_shift($bits);
			}

			return $bits;
		}

		/**
		 * Return the filename to be used for the cache file.
		 * @param $name string The name of the router.
		 * @return string The temporary filename.
		 */
		public static function getCacheFilename($name)
		{
			return
				sys_get_temp_dir().
				'/tfn_router_'.
				preg_replace('/\W/', '', $name).
				'.dat';
		}

		/**
		 * Load a serialized router object.
		 * @param $name string The name of the router to load.
		 * @return mixed The router object or false if the load failed.
		 */
		public static function load($name, $build_function = false)
		{
			$retval = false;

			// Try to load the cached routes
			$filename = self::getCacheFilename($name);
			if (file_exists($filename)) {
				$retval = unserialize(file_get_contents($filename));
			}

			// No routes loaded, call the build function if given
			if (!$retval && $build_function !== false) {
				$retval = new self();
				$build_function($retval);
				$retval->save($name);
			}

			return $retval;
		}

		/**
		 * Store this object in a temporary file.
		 * @param $name string The name under which to save this router.
		 * @return boolean Whether the object was successfully saved.
		 */
		public function save($name)
		{
			$filename = self::getCacheFilename($name);
			return file_put_contents($filename, serialize($this));
		}
	}
