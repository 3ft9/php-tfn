<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * All expections thrown by the HTTPClient class will be of this type.
	 */
	class HTTPClientException extends TFNException
	{
		protected $_response = '';

		public function __construct($message, $code, $response = '')
		{
			parent::__construct($message, $code);
			$this->_response = $response;
		}

		public function getResponse()
		{
			return $this->_response;
		}
	}

	/**
	 * A basic HTTP client.
	 */
	class HTTPClient
	{
		/**
		 * An array of instances that have been instantiated for various services.
		 *
		 * @var array
		 */
		private static $_instances = array();

		/**
		 * Get an HTTPClient instance for the given service. Assumes there's a
		 * global configuration in a specific structure, but that trade-off is
		 * worth it for the convenience this adds.
		 *
		 * @param string $base_url The base URL to prepend to URLs requested.
		 * @return HTTPClient
		 */
		public static function factory($base_url = '')
		{
			return new self($base_url);
		}

		/**
		 * The base URL for this instance.
		 *
		 * @var string
		 */
		protected $base_url = '';

		/**
		 * Constants for the request methods.
		 */
		const GET         = 1;
		const POST        = 2;
		const PUT         = 3;
		const DELETE      = 4;
		const POST_JSON   = 5;
		const PUT_JSON    = 6;
		const DELETE_JSON = 7;

		/**
		 * The default timeout for all requests.
		 */
		const DEFAULT_TIMEOUT = 5;

		/**
		 * Constructor.
		 *
		 * @param string $base_url The base URL for all requests made with this instance.
		 * @return HTTPClient
		 */
		public function __construct($base_url)
		{
			if (strlen($base_url) > 0 && substr($base_url, -1) != '/') {
				$base_url .= '/';
			}
			$this->base_url = $base_url;
		}

		/**
		 * Returns the base url this object was given during construction.
		 */
		public function getBaseUrl()
		{
			return $this->base_url;
		}

		/**
		 * Perform an HTTP GET request.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function get($url, $params = false, $options = false, $timeout = false)
		{
			return $this->request($url, $params, self::GET, $options, $timeout);
		}

		/**
		 * Perform an HTTP POST request.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function post($url, $params = false, $options = false, $timeout = false)
		{
			return $this->request($url, $params, self::POST, $options, $timeout);
		}

		/**
		 * Perform an HTTP POST request with a JSON body.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function postJSON($url, $params = false, $options = false, $timeout = false)
		{
			return $this->request($url, $params, self::POST_JSON, $options, $timeout);
		}

		/**
		 * Perform an HTTP PUT request.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function put($url, $params = false, $options = false, $timeout = false)
		{
			return $this->request($url, $params, self::PUT, $options, $timeout);
		}

		/**
		 * Perform an HTTP PUT request with a JSON body.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function putJSON($url, $params = false, $options = false, $timeout = false)
		{
			return $this->request($url, $params, self::PUT_JSON, $options, $timeout);
		}

		/**
		 * Perform an HTTP DELETE request.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function delete($url, $options = false, $timeout = false)
		{
			return $this->request($url, false, self::DELETE, $options, $timeout);
		}

		/**
		 * Perform an HTTP DELETE request.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function deleteJSON($url, $params, $options = false, $timeout = false)
		{
			return $this->request($url, $params, self::DELETE_JSON, $options, $timeout);
		}

		/**
		 * Perform an HTTP request.
		 *
		 * @param string $url     The URL to request.
		 * @param array  $params  Optional array of parameters to send with the request.
		 * @param int    $method  One of these class constants: GET, POST, PUT, DELETE (default: GET).
		 * @param array  $options Array of additional cURL options (default: none).
		 * @param int    $timeout	Optional timeout (in seconds) for this request.
		 * @return string         The response string.
		 * @throws HTTPClientException If an error occurs.
		 */
		public function request($url, $params = false, $method = false, $options = false, $timeout = false, $raw = false)
		{
			if ($method === false) {
				$method = self::GET;
			}

			if ($timeout === false) {
				$timeout = self::DEFAULT_TIMEOUT;
			}

			if ($url[0] == '/') {
				$url = substr($url, 1);
			}
			$url = $this->base_url.$url;

			if ($method == self::GET && is_array($params) && count($params) > 0) {
					$url = $url.(strpos($url, '?') !== false ? '&' : '?').http_build_query($params);
			}

			$ch = curl_init($url);
			if (isset($GLOBALS['config']['curl']['options'])) {
				curl_setopt_array($ch, $GLOBALS['config']['curl']['options']);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			if (is_array($options) && count($options) > 0) {
				curl_setopt_array($ch, $options);
			}

			switch ($method) {
				case self::GET:
					break;

				case self::POST:
				case self::PUT:
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
					if ($method == self::POST) {
						curl_setopt($ch, CURLOPT_POST, 1);
					} else {
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
					}
					break;

				case self::POST_JSON:
				case self::PUT_JSON:
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method == self::PUT_JSON ? 'PUT' : 'POST');
					$json_data = json_encode($params);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Content-Length: '.strlen($json_data),
					));
					break;

				case self::DELETE_JSON:
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
					$json_data = json_encode($params);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Content-Length: '.strlen($json_data),
					));
					break;

				case self::DELETE:
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
					break;

				default:
					throw new HTTPClientException('Unknown method: '.$method, -1);
			}

			$response = curl_exec($ch);
			$info = curl_getinfo($ch);

			curl_close($ch);

			if ($info['http_code'] < 200 || $info['http_code'] > 299) {
				throw new HTTPClientException(__METHOD__.': Failed: ['.$info['http_code'].'] '.substr($response, 0, 150), $info['http_code'], $response);
			}

			// If we've not been told to return the raw response, and the response
			// was not a 204 which has no content, decode the response if we
			// understand the format.
			if (!$raw && $info['http_code'] != 204) {
				switch ($info['content_type']) {
					case 'application/json':
						// Decode the JSON response.
						$decoded = json_decode($response, true);
						if (!is_array($decoded)) {
							throw new HTTPClientException('Failed to decode response: '.$response, $info['http_code'], $response);
						}
						$response = $decoded;
						break;
				}
			}

			return $response;
		}
	}
