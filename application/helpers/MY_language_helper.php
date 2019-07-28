<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// ------------------------------------------------------------------------

if (! function_exists('lang_id'))
{
    /**
     * @param string $langcode
     * @return mixed
     */
	function lang_id($langcode = '')
	{
        $CI = get_instance();
        $CI->load->model('language_m');

        string_not_empty($langcode) OR $langcode = lang_code();
        return $CI->language_m->lang_item($langcode)->languages_id;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('lang_code'))
{
	/**
     * Get site lang or admin base lang
     *
	 * @param string $default
	 * @return string
	 */
	function lang_code($default = '')
	{
        $CI = get_instance();
        string_not_empty($default) OR $default = $CI->setting->default_language;

        return !empty($lang = $CI->load->get_var('lang')->code) ? $lang : $default;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('__'))
{
	/**
	 * @param string $line
	 * @param string $for
	 * @param array $attributes
	 * @param string $extra
	 *
	 * @return string
	 */
	function __($line = '', $for = '', $attributes = [], $extra = "")
	{
		if (substr($line, 0, 5) == 'lang:')
		{
			$line = substr($line, 5);
		}

		$lang = lang($line, $for, $attributes);
		if (! $lang)
		{
			return $extra . $line . $extra;
		}

		return $lang;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('esc__'))
{
    /**
     * @param string $line
     * @param string $for
     * @param array $attributes
     * @param string $extra
     * @return mixed
     */
    function esc__($line = '', $for = '', $attributes = [], $extra = "")
    {
        return escape_html(__($line, $for, $attributes, $extra));
    }
}

// ------------------------------------------------------------------------

if (! function_exists('esc_attr_'))
{
    /**
     * @param string $line
     * @param string $for
     * @param array $attributes
     * @param string $extra
     * @return mixed
     */
    function esc_attr_($line = '', $for = '', $attributes = [], $extra = "")
    {
        return escape_html_attr(__($line, $for, $attributes, $extra));
    }
}

// ------------------------------------------------------------------------

if (! function_exists('_e'))
{
	/**
	 * @param string $line
	 * @param string $for
	 * @param array $attributes
	 * @param string $extra
	 */
	function _e($line = '', $for = '', $attributes = [], $extra = "")
	{
        echo __($line, $for, $attributes, $extra);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('esc_e'))
{
    /**
     * @param string $line
     * @param string $for
     * @param array $attributes
     * @param string $extra
     */
    function esc_e($line = '', $for = '', $attributes = [], $extra = "")
    {
        echo esc__($line, $for, $attributes, $extra);
    }
}

// ------------------------------------------------------------------------

if (! function_exists('esc_attr_e'))
{
    /**
     * @param string $line
     * @param string $for
     * @param array $attributes
     * @param string $extra
     */
    function esc_attr_e($line = '', $for = '', $attributes = [], $extra = "")
    {
        echo esc_attr_($line, $for, $attributes, $extra);
    }
}

// ------------------------------------------------------------------------

if (! function_exists('_f'))
{
	/**
	 * @param $line
	 * @param array $variables
	 *
	 * @return mixed
	 */
	function _f($line, $variables = [])
	{
        return sprintf_lang($line, $variables);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('sprintf_lang'))
{
	/**
	 * @param $line
	 * @param array $variables
	 *
	 * @return mixed
	 */
	function sprintf_lang($line, $variables = [])
	{
        is_array($variables) OR $variables = [$variables];
        array_unshift($variables, __($line));

        return call_user_func_array('sprintf', $variables);
	}
}
