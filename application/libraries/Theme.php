<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Theme Definition
 *
 * This class should be extended to allow for theme management.
 *
 * @author  Stephen Cozart
 * @abstract
 */
abstract class Theme
{
	/**
	 * @var string theme name
	 */
	public $name;

	/**
	 * @var string author name
	 */
	public $author;

	/**
	 * @var string theme website
	 */
	public $website;

	/**
	 * @var string theme description
	 */
	public $description;

	/**
	 * @var string the version of the theme.
	 */
	public $version;

	/**
	 * @var string Front-end or back-end.
	 */
	public $type;

	/**
	 * Allows this class and classes that extend this to use $this-> just like
	 * you were in a controller.
	 *
	 * @access    public
	 * @return    mixed
	 */
	public function __get($var)
	{
		static $ci;
		isset($ci) OR $ci =& get_instance();

		return $ci->{$var};
	}

	/**
	 * run
	 */
	public function run() {}
}
