<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Language Class extension.
 *
 * Adds language fallback handling.
 *
 * When loading a language file, CodeIgniter will load first the english version,
 * if appropriate, and then the one appropriate to the language you specify.
 * This lets you define only the language settings that you wish to over-ride
 * in your idiom-specific files.
 *
 * This has the added benefit of the language facility not breaking if a new
 * language setting is added to the built-in ones (english), but not yet
 * provided for in one of the translations.
 *
 * To use this capability, transparently, copy this file (MY_Lang.php)
 * into your application/core folder.
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Language
 * @author        EllisLab Dev Team
 * @link        https://codeigniter.com/user_guide/libraries/language.html
 */
class MY_Lang extends CI_Lang
{

	/**
	 * Refactor: base language provided inside system/language
	 *
	 * @var string
	 */
	public $base_language = 'en';

	/**
	 * Class constructor
	 *
	 * @return    void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a single line of text from the language array
	 *
	 * @param string $line the language line
	 * @param bool $log_errors
	 *
	 * @return bool|string
	 */
	public function line($line = '', $log_errors = TRUE)
	{
		$translation = ($line == '' OR ! isset($this->language[$line])) ? FALSE : $this->language[$line];

		// Because killer robots like unicorns!
		if ($translation === FALSE && $log_errors === TRUE)
		{
			log_message('debug', 'Could not find the language line "' . $line . '"');
		}

		return $translation;
	}

	// --------------------------------------------------------------------

	/**
	 * Load a language file, with fallback to english.
	 *
	 * @param    mixed $langfile Language file name
	 * @param    string $idiom Language name (english, etc.)
	 * @param    bool $return Whether to return the loaded array of translations
	 * @param    bool $add_suffix Whether to add suffix to $langfile
	 * @param    string $alt_path Alternative path to look for the language file
	 *
	 * @return bool|string[]|void
	 */
	public function load($langfile, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
	{
		if (is_array($langfile))
		{
			foreach ($langfile as $value)
			{
				$this->load($value, $idiom, $return, $add_suffix, $alt_path);
			}

			return;
		}

		$langfile = str_replace('.php', '', $langfile);

		if ($add_suffix === TRUE)
		{
			$langfile = preg_replace('/_lang$/', '', $langfile) . '_lang';
		}

		$langfile .= '.php';

		if (empty($idiom) OR ! preg_match('/^[a-z_-]+$/i', $idiom))
		{
            $config = &get_config();
            $idiom = empty($config['language']) ? $this->base_language : $config['language'];
		}

		if ($return === FALSE && isset($this->is_loaded[$langfile]) && $this->is_loaded[$langfile] === $idiom)
		{
			return;
		}

		// load the default language first, if necessary
		// only do this for the language files under system/
		$basepath = SYSDIR . 'language/' . $this->base_language . '/' . $langfile;
		if (($found = file_exists($basepath)) === TRUE)
		{
			/** @noinspection PhpIncludeInspection */
			include($basepath);
		}

		// Load the base file, so any others found can override it
		$basepath = BASEPATH . 'language/' . $idiom . '/' . $langfile;
		if (($found = file_exists($basepath)) === TRUE)
		{
			/** @noinspection PhpIncludeInspection */
			include($basepath);
		}

		// Do we have an alternative path to look in?
		if ($alt_path !== '')
		{
			$alt_path .= 'language/' . $idiom . '/' . $langfile;
			if (file_exists($alt_path))
			{
				/** @noinspection PhpIncludeInspection */
				include($alt_path);
				$found = TRUE;
			}
		}
		else
		{
			foreach (get_instance()->load->get_package_paths(TRUE) as $package_path)
			{
				$package_path .= 'language/' . $idiom . '/' . $langfile;
				if ($basepath !== $package_path && file_exists($package_path))
				{
					/** @noinspection PhpIncludeInspection */
					include($package_path);
					$found = TRUE;
					break;
				}
			}
		}

		if ($found !== TRUE)
		{
			show_error('Unable to load the requested language file: language/' . $idiom . '/' . $langfile);
		}

		if (! isset($lang) OR ! is_array($lang))
		{
			log_message('error', 'Language file contains no data: language/' . $idiom . '/' . $langfile);

			if ($return === TRUE)
			{
				/** @noinspection PhpInconsistentReturnPointsInspection */
				return [];
			}

			return;
		}

		if ($return === TRUE)
		{
			/** @noinspection PhpInconsistentReturnPointsInspection */
			return $lang;
		}

		$this->is_loaded[$langfile] = $idiom;
		$this->language = array_merge($this->language, $lang);

		log_message('info', 'Language file loaded: language/' . $idiom . '/' . $langfile);

		/** @noinspection PhpInconsistentReturnPointsInspection */
		return TRUE;
	}
}
