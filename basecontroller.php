<?php
	/**
	 * 3ft9 BaseController.
	 *
	 * Part of the 3ft9 PHP Class Library.
	 * Copyright (C) 3ft9 Ltd. All rights reserved.
	 */
	namespace TFN;

	/**
	 * This class is a basic set of functionality for a controller class.
	 * TODO: Usage documentation!
	 */
	BaseController::setTemplateRoot(__DIR__.'/../../tpl/');
	class BaseController
	{
		protected static $_template_root = '';

		public static function setTemplateRoot($tplroot)
		{
			self::$_template_root = $tplroot;
		}

		protected $_view = null;

		public function __construct()
		{
			$this->_view = View::create(self::$_template_root);
		}
	}
