<?php
	/**
	 * TFN: 3ft9 Ltd PHP Component Library.
	 */
	namespace TFN;

	/**
	 * Simple class to encapsulate absolute and relative time values.
	 */
	class Time
	{
		const OneMinute = 60;
		const FiveMinutes = 300;
		const TenMinutes = 600;
		const FifteenMinutes = 900;
		const HalfHour = 1800;
		const OneHour = 3600;
		const SixHours = 21600;
		const HalfDay = 43200;
		const OneDay = 86400;
		const SevenDays = 604800;
		const ThirtyDays = 2592000;
		const OneYear = 31536000;

		public static function getAbsolute($time, $format = false)
		{
			if (is_numeric($time) and $time < (time()-1)) {
				$time = time() + $time;
			} else {
				$time = strtotime($time);
			}
			return (false === $format ? $time : date($format, $time));
		}

		public static function toRelative($time, $now = false)
		{
			if ($now === false) {
				$now = time();
			}

			$diff = $now - $time;

			if ($diff < 0) {
				return 'in the past';
			}

			if ($diff > (self::OneYear - self::ThirtyDays)) {
				return 'about a year ago';
			}

			// TODO: Finish this!
			// if ($diff > (self::SevenDays - self::OneDay)) {
			// 	if ($diff > (self::))
			// }
		}
	}
