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
		const TwelfthOfAYear = 2592000;
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
			$diff = ($now ? $now : time()) - $time;
			if($diff == 0) {
				return 'now';
			} elseif($diff > 0) {
				$day_diff = floor($diff / 86400);
				if($day_diff == 0) {
					if($diff < 60) return 'just now';
					if($diff < 120) return '1 minute ago';
					if($diff < 3600) return floor($diff / 60) . ' minutes ago';
					if($diff < 7200) return '1 hour ago';
					if($diff < 86400) return floor($diff / 3600) . ' hours ago';
				}
				if($day_diff == 1) return 'Yesterday';
				if($day_diff < 7) return $day_diff . ' days ago';
				if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
				if($day_diff < 60) return 'last month';
				return date('F Y', $time);
			} else {
				$diff = abs($diff);
				$day_diff = floor($diff / 86400);
				if($day_diff == 0) {
					if($diff < 120) return 'in a minute';
					if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
					if($diff < 7200) return 'in an hour';
					if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
				}
				if($day_diff == 1) return 'Tomorrow';
				if($day_diff < 4) return date('l', $time);
				if($day_diff < 7 + (7 - date('w'))) return 'next week';
				if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
				if(date('n', $time) == date('n') + 1) return 'next month';
				return date('F Y', $time);
			}
		}

		public static function secondsToString($seconds, $short = false)
		{
			$retval = '';
			if ($seconds >= self::OneYear) {
				$years = intval($seconds / self::OneYear);
				$seconds = $seconds % self::OneYear;
				$retval .= ' '.$years.($short ? 'y' : ' year'.($years == 1 ? '' : 's'));
			}
			if ($seconds >= self::TwelfthOfAYear) {
				$months = intval($seconds / self::TwelfthOfAYear);
				$seconds = $seconds % self::TwelfthOfAYear;
				$retval .= ' '.$months.($short ? 'mth' : ' month').($months == 1 ? '' : 's');
			}
			if ($seconds >= self::OneDay) {
				$days = intval($seconds / self::OneDay);
				$seconds = $seconds % self::OneDay;
				$retval .= ' '.$days.($short ? 'd' : ' day'.($days == 1 ? '' : 's'));
			}
			if ($seconds >= self::OneHour) {
				$hours = intval($seconds / self::OneHour);
				$seconds = $seconds % self::OneHour;
				$retval .= ' '.$hours.($short ? 'h' : ' hour'.($hours == 1 ? '' : 's'));
			}
			if ($seconds >= self::OneMinute) {
				$minutes = intval($seconds / self::OneMinute);
				$seconds = $seconds % self::OneMinute;
				$retval .= ' '.$minutes.($short ? 'm' : ' minute'.($minutes == 1 ? '' : 's'));
			}
			if ($seconds > 0) {
				$retval .= ' '.$seconds.($short ? 's' : ' second'.($seconds == 1 ? '' : 's'));
			}
			return trim($retval);
		}
	}
