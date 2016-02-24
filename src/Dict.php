<?php
	class Dict
	{
		// Variable access function
		static public function get($array, $key, $default = null)
		{
			if (isset($array[$key])) {
				return $array[$key];
			}
			return $default;
		}
	}
