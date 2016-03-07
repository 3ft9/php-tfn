<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * This class is a basic set of functionality for a controller class.
	 * TODO: Usage documentation!
	 */
	abstract class RestController extends BaseController
	{
		/**
		 * All requests come here.
		 *
		 * @param array $params Parameters from the URL.
		 */
		public function indexAction($params = array())
		{
			// Get the method.
			$method = strtolower($this->request->serverVar('REQUEST_METHOD'));
			// Check the method exists.
			if (method_exists($this, $method)) {
				// Call the method.
				$this->$method($params);
			} else {
				$this->_noHandler($method);
			}
		}

		/**
		 * Return a successful response in JSON.
		 *
		 * @param string $status  The status of the response.
		 * @param mixed  $data    The data to send with the response, usually an array.
		 * @param array  $headers Array of headers to send with the response.
		 */
		public function sendResponse($status = '204 No Content', $data = '', $headers = array())
		{
			$body = array(
				'success' => true,
			);
			if ($data) {
				$body['data'] = $data;
			}
			header('Content-Type: application/json');
			parent::sendResponse($status, json_encode($body), $headers);
		}

		/**
		 * Return an error response in JSON.
		 *
		 * @param string $status  The status of the response.
		 * @param string $message The message describing the error.
		 * @param array  $data    Any additional data related to the error.
		 */
		public function sendError($status, $message = false, $data = array())
		{
			$body = '';
			if ($message !== false) {
				$body = json_encode(array(
						'success' => false,
						'error' => $message,
					));
				if ($data) {
					$body['data'] = $data;
				}
			}
			$this->sendResponse($status, $body);
		}

		protected function _noHandler($method)
		{
			// Method doesn't exist.
			$this->returnError('405 Method Not Allowed', 'Unhandled method');
		}

		// GET requests are sent here.
		public function get($params = array())
		{
			$this->_noHandler('GET');
		}

		// HEAD requests are sent here.
		public function head($params = array())
		{
			return $this->get($params);
		}

		// POST requests are sent here.
		public function post($params = array())
		{
			$this->_noHandler('POST');
		}

		// PUT requests are sent here.
		public function put($params = array())
		{
			$this->_noHandler('PUT');
		}

		// DELETE requests are sent here.
		public function delete($params = array())
		{
			$this->_noHandler('DELETE');
		}
	}
