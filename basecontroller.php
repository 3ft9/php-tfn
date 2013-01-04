<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * This class is a basic set of functionality for a controller class.
	 * TODO: Usage documentation!
	 */
	class BaseController
	{
		protected static $_template_root = '';

		public static function setTemplateRoot($tplroot)
		{
			self::$_template_root = $tplroot;
		}

		/**
		 * Redirect the browser to a different location.
		 *
		 * @param string $url The destination URL - according to the spec this
		 *                    should be absolute not relative.
		 * @param bool $exit Set to false to return to the caller rather than
		 *                   ending execution.
		 */
		public static function _redirect($url, $exit = true, $permanent = false)
		{
			if ((substr($url, 0, 7) != 'http://' || substr($url, 0, 8) != 'https://') and $url[0] == '/') {
				$url = 'http://'.$_SERVER['HTTP_HOST'].$url;
			}

			if (headers_sent()) {
				echo '<script type="text/javascript"><!--'.PHP_EOL.'location.href = \''.$url.'\';'.PHP_EOL.'--></script>'.PHP_EOL;
			} else {
				// Output the redirect header
				header('Location: '.$url, true, ($permanent ? 301 : 302));
			}

			// If told to exit, output the moved message and do so
			if ($exit) {
				// Empty and clean up any pending output buffers
				while (@ob_end_clean());

				print '<h1>Document Moved</h1>';
				print '<p>The requested document has moved <a href="'.$url.'">here</a>.</p>';
				print '<script type="text/javascript"> location.href = "'.$url.'"; </script>';
				exit;
			}
		}

		protected $view = null;

		protected $request = null;

		public function __construct()
		{
			$this->request = Request::init();
			$this->view = View::create(self::$_template_root);
			$this->view->request = $this->request;
		}

		/**
		 * Default 404 handler. Sends a 404 response
		 *
		 * @param array $params The parameters from the URL.
		 */
		public function notfoundAction($params = array())
		{
			$this->sendResponse('404 Not Found', is_array($params) ? 'Not found' : $params);
		}

		/**
		 * Send a response. Should only be used when sending a non-200 status code.
		 *
		 * @param string $status The HTTP status line.
		 * @param string $content Optional body content.
		 * @param array  $headers Additional headers to send (header => value).
		 */
		public function sendResponse($status = '204 No Content', $body = '', $headers = array())
		{
			if (!headers_sent()) {
				header('HTTP/1.0 '.$status);
				header('Status: '.$status);
				foreach ($headers as $key => $val) {
					header($key.': '.$val);
				}
			}
			echo $body;
		}

		/**
		 * Redirect the browser to a different location.
		 *
		 * @param string $url The destination URL - according to the spec this
		 *                    should be absolute not relative.
		 * @param bool $exit Set to false to return to the caller rather than
		 *                   ending execution.
		 */
		public function redirect($url, $exit = true, $permanent = false)
		{
			self::_redirect($url, $exit, $permanent);
		}

		/**
		 * Simple function to modify a URL by adding or overriding GET vars.
		 *
		 * @param string $url The URL to be modified, or false to use the request URL.
		 * @param array $addvars The querystring variables to be added.
		 * @param array $removevars The querystring variables to be removed.
		 * @param array $preservedvars Will not be changed if they already exist.
		 * @return string The new URL.
		 */
		public function modifyURL($url = false, $addvars = array(), $removevars = array(), $preservedvars = array())
		{
			if ($url === false) {
				$url = $_SERVER['REQUEST_URI'];
			}

			// Are we making any changes?
			if (count($addvars) == 0 and count($removevars) == 0) {
				return $url;
			}

			// Do we have an existing query string?
			if (strpos($url, '?') === false) {
				// Nope, so just add the ones that need adding
				$parsed_params = $addvars;
			} else {
				// Grab the current vars
				list($url, $params) = explode('?', $url, 2);
				parse_str($params, $parsed_params);
				// Remove those that need to be removed
				foreach ($removevars as $key) {
					if (!in_array($key, $preservedvars)) {
						unset($parsed_params[$key]);
					}
				}
				// Add those that need to be added
				foreach ($addvars as $key => $val) {
					if (!in_array($key, $preservedvars)) {
						$parsed_params[$key] = $val;
					}
				}
			}

			// Return the modified URL
			return $url.(empty($parsed_params) ? '' : '?'.http_build_query($parsed_params));
		}

		/**
		 * Get the raw body of the request.
		 *
		 * @param string $override_body Specify this to use this as the body (for unit test purposes).
		 * @return string The raw body.
		 */
		public function getRequestBody($override_body = false)
		{
			static $body = false;
			if ($override_body !== false) {
				$body = $override_body;
			} elseif ($body === false) {
				$body = file_get_contents('php://input');
			}
			return $body;
		}
	}
