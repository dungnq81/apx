<?php defined('BASEPATH') OR exit('No direct script access allowed.');

// ------------------------------------------------------------------------

if (! function_exists('strtotimetz'))
{
    /**
     * Returns the timestamp of the provided time string using a specific timezone as the reference
     * https://stackoverflow.com/questions/3569014/php-strtotime-specify-timezone
     *
     * @param string $str
     * @param string|null $timezone
     * @return int number of the seconds
     *
     * @throws Exception
     */
    function strtotimetz($str, $timezone = NULL)
    {
	    if ( empty( $timezone ) )
        {
            $timezone = config_item('time_reference');
        }

        return strtotime(
            $str, strtotime(
                // convert timezone to offset seconds
                (new \DateTimeZone($timezone))->getOffset(new \DateTime) - (new \DateTimeZone(date_default_timezone_get()))->getOffset(new \DateTime) . ' seconds'
            )
        );
    }
}

// ------------------------------------------------------------------------

if (! function_exists('format_date'))
{
    /**
     * Formats a timestamp into a human date format.
     *
     * @param int $unix The UNIX timestamp
     * @param string $format The date format to use.
     *
     * @return string The formatted date.
     * @throws Exception
     */
	function format_date( $unix, $format = '' ) {
		if ( $unix == '' OR ! is_numeric( $unix ) )
		{
			$unix = strtotimetz( $unix );
		}

		if ( ! $format )
		{
			$CI     = &get_instance();
			$format = $CI->setting->date_format;
		}

		return strstr( $format, '%' ) !== FALSE ? ucfirst( utf8_encode( strftime( $format, $unix ) ) ) : date( $format, $unix );
	}
}
