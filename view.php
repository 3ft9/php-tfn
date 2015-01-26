<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * This class inherits from the Template class and adds methods to make it
	 * a basic but half-decent view class.
	 * TODO: Usage documentation!
	 */
	class View extends Template
	{
		/**
		 * True if the destructor should call footer().
		 *
		 * @var bool
		 */
		protected $_destruct_with_footer = false;

		/**
		 * The root for all static file references. Must end with a /.
		 *
		 * @var string
		 */
		protected $_static_root = '/';

		/**
		 * Factory function to create an instance of the View class.
		 *
		 * @param string $tplroot
		 * @return View
		 */
		public static function create($tplroot = '')
		{
			return new self($tplroot);
		}

		/**
		 * Constructer. Calls the parent constructor.
		 *
		 * @param string $tplroot
		 * @return View
		 */
		public function __construct($tplroot)
		{
			parent::__construct($tplroot);
		}

		/**
		 * Destructor. If __destruct_with_footer is true we call footer(). Either
		 * way we then call the parent destructor.
		 */
		public function __destruct()
		{
			if ($this->_destruct_with_footer) {
				$this->renderFooter();
			}
		}

		/**
		 * Magic function that returns a value from the internal data array.
		 *
		 * @param mixed $var
		 * @return mixed
		 */
		public function __get($var)
		{
			return parent::__get($var);
		}

		/**
		 * Magic function to set a value in the internal data.
		 *
		 * @param string $var
		 * @param mixed $val
		 */
		public function __set($var, $val)
		{
			parent::__set($var, $val);
		}

		/**
		 * Return the full URL to the given static file, prefixing the configured
		 * root.
		 */
		public function staticURL($url)
		{
			return rtrim($this->_static_root, '/').'/'.ltrim($url, '/');
		}

		/**
		 * Starts the view output. Sets a variable to tell the destructor to
		 * output the footer, then outputs the header.
		 */
		public function start()
		{
			$this->_destruct_with_footer = true;
			$this->renderHeader();
		}

		/**
		 * Render the header template.
		 */
		public function renderHeader($data = array())
		{
			if (!headers_sent())
			{
				header('Content-Type: text/html; charset=utf-8');
			}

			$this->render('layout/header.tpl.php', $data);
		}

		/**
		 * Render the footer template.
		 */
		public function renderFooter($data = array())
		{
			$this->render('layout/footer.tpl.php', $data);
			$this->_destruct_with_footer = false;
		}

		/**
		 * Set expiry headers. Age is in seconds. If age is 0 (default) the
		 * headers will expire the page immediately.
		 *
		 * @param integer $age
		 */
		public function expires($age = 0)
		{
			if (!headers_sent()) {
				if ($age == 0) {
					// Expire immediately
					header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
					header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
					// always modified
					header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
					header("Cache-Control: post-check=0, pre-check=0", false);
					header("Pragma: no-cache");                          // HTTP/1.0
				} else {
					// Expire in the future
					header('Cache-Control: PUBLIC, max-age='.$age.', must-revalidate');
					header('Expires: '.gmdate('r', time() + $age).' GMT');
				}
			} else {
				// Headers already sent!
			}
		}

		/**
		 * Convert \r\n\r\n and \n\n to </p><p>, then do nl2br, and return wrapped
		 * in <p> tags.
		 */
		public function pnl2pbr($str)
		{
			return '<p>'.nl2br(str_replace("\n\n", '</p><p>', str_replace("\r\n\r\n", '</p><p>', $str))).'</p>';
		}

		/**
		 * Return an HTML-escaped version of the given string.
		 *
		 * @param string $str The string to escape.
		 * @return string     The escaped string.
		 */
		public function escape($str)
		{
			return $this->escapeHTML($str);
		}

		/**
		 * Render a generic message. This method assumes there is a template called
		 * message.tpl.php in the template root.
		 */
		public function message($title, $message)
		{
			if (empty($this->body_id)) {
				$this->body_id = 'msg';
			}
			$this->start();
			$this->render('message.tpl.php', array('title' => $title, 'message' => $message));
		}
	}
