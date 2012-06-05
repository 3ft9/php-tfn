<?php
	/**
	 * 3ft9 Authentication Base Class.
	 *
	 * Part of the 3ft9 PHP Class Library.
	 * Copyright (C) 3ft9 Ltd. All rights reserved.
	 */
	namespace TFN;

	/**
	 * This class implements the common authentication functionality.
	 */
	class Auth
	{
		public function login($type, $callback = false)
		{
			$impl = $this->getImplObject($type);
			return $impl->doLogin($callback);
		}

		public function callback($type)
		{
			$impl = $this->getImplObject($type);
			return $impl->doCallback();
		}

		private function getImplObject($type)
		{
			$class = '\TFN\Auth_'.$type;
			if (!class_exists($class)) {
				throw new Auth_Exception('Unknown auth provider: '.$type);
			}
			return new $class();
		}
	}
