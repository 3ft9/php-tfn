<?php
	/**
	 * 3ft9 View class.
	 *
	 * Part of the 3ft9 PHP Class Library.
	 * Copyright (C) 3ft9 Ltd. All rights reserved.
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
			}
		}
	}
