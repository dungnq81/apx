<?php defined('BASEPATH') OR exit('No direct script access allowed.');
/**
 * Created by PhpStorm.
 * User: NTH
 * Date: 11/2/2018
 * Time: 4:08 PM
 */

if (! function_exists('format_date'))
{
	/**
	 * Formats a timestamp into a human date format.
	 *
	 * @param int $unix The UNIX timestamp
	 * @param string $format The date format to use.
	 *
	 * @return string The formatted date.
	 */
	function format_date($unix, $format = '')
	{
		if ($unix == '' || ! is_numeric($unix))
		{
			$unix = strtotime($unix);
		}

		if (! $format)
		{
			$format = Setting::get('date_format');
		}

		return strstr($format, '%') !== FALSE ? ucfirst(utf8_encode(strftime($format, $unix))) : date($format, $unix);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('nice_time'))
{
	/**
	 * FACEBOOK STYLE TIMESTAMP
	 * $date = "2015-07-05 03:45";
	 * $result = nicetime($date); // 2 days ago
	 *
	 * @param $date
	 *
	 * @return string
	 */
	function nice_time($date)
	{
		if (empty($date))
		{
			return "No date provided";
		}

		$periods = ["second", "minute", "hour", "day", "week", "month", "year", "decade"];
		$lengths = ["60", "60", "24", "7", "4.35", "12", "10"];

		$now = time();
		$unix_date = strtotime($date);

		// check validity of date
		if (empty($unix_date))
		{
			return "Bad date";
		}

		// is it future date or past date
		if ($now > $unix_date)
		{
			$difference = $now - $unix_date;
			$tense = "ago";

		}
		else
		{
			$difference = $unix_date - $now;
			$tense = "from now";
		}

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j ++)
		{
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		if ($difference != 1)
		{
			$periods[$j] .= "s";
		}

		return "$difference $periods[$j] {$tense}";
	}
}
