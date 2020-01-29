<?php defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

// ------------------------------------------------------------------------

if ( ! function_exists( 'is_dashboard' ) )
{
	/**
	 * @return bool
	 */
	function is_dashboard() {
		$CI = &get_instance();
		if ( empty( $CI->directory ) AND ( $CI->method == 'index' OR empty( $CI->method ) ) AND $CI->controller == 'admin' )
		{
			return TRUE;
		}

		return FALSE;
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists( 'file_partial' ) )
{
	/**
	 * Partial Helper
	 *
	 * Loads the partial
	 *
	 * @param string $file The name of the file to load.
	 * @param string $ext The file's extension.
	 */
	function file_partial( $file = '', $ext = 'php' ) {
		$CI   = &get_instance();
		$data = $CI->load->get_vars();

		$path = $data['template_views'] . 'partials/' . $file;
		echo $CI->load->file( $path . '.' . $ext, TRUE );
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists( 'template_partial' ) )
{
	/**
	 * Template Partial
	 *
	 * Display a partial set by the template
	 *
	 * @param string $name The view partial to display.
	 */
	function template_partial( $name = '' ) {
		$CI   = &get_instance();
		$data = $CI->load->get_vars();

		echo isset( $data['template']['partials'][$name] ) ? $data['template']['partials'][$name] : '';
	}
}

// ------------------------------------------------------------------------

if ( ! function_exists( 'accented_characters' ) )
{
	/**
	 * Accented Foreign Characters Output
	 *
	 * @return array|null
	 */
	function accented_characters() {
		if ( is_file( APPPATH . 'config/foreign_chars.php' ) )
		{
			include( APPPATH . 'config/foreign_chars.php' );
		}

		if ( ! isset( $foreign_characters ) )
		{
			return NULL;
		}

		$languages = [];
		foreach ( $foreign_characters as $key => $value )
		{
			$languages[] = [
				'search'  => $key,
				'replace' => $value,
			];
		}

		return $languages;
	}
}
