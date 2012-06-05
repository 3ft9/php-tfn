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
	abstract class Auth_Base
	{
		abstract public function doLogin($callback);

		abstract public function doCallback();
	}
