<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Build your pages much easier with partials, breadcrumbs, layouts and themes
 *
 * @author  Philip Sturgeon
 * @license http://philsturgeon.co.uk/code/dbad-license
 * @link    http://philsturgeon.co.uk/code/codeigniter-template
 */
class Template
{
	private $_ci;

	private $_controller = '';
	private $_method = '';

	private $_theme = NULL;
	private $_theme_path = NULL;

	// By default, dont wrap the view with anything
	private $_layout = FALSE;

	// Layouts and partials will exist in views/layouts
	// but can be set to views/foo/layouts with a subdirectory
	private $_layout_subdir = '';

	private $_title = '';
	private $_body = '';

	private $_title_separator = ' | ';

	private $_metadata = [];
	private $_partials = [];
	private $_breadcrumbs = [];

	private $_parser_enabled = TRUE;
	private $_parser_body_enabled = TRUE;
	private $_minify_enabled = FALSE;

	private $_theme_locations = [];
	private $_is_mobile = FALSE;

	// Seconds that cache will be alive for
	private $cache_lifetime = 0;//7200;

	private $_data = [];

	/**
	 * Constructor - Sets Preferences. The constructor can be passed an array of config values
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		$this->_ci =& get_instance();

		if (! empty($config))
		{
			$this->initialize($config);
		}

		log_message('debug', 'Template class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize preferences
	 *
	 * @param    array $config
	 *
	 * @return    void
	 */
	public function initialize($config = [])
	{
		foreach ($config as $key => $val)
		{
			if ($key == 'theme' AND $val != '')
			{
				$this->set_theme($val);
				continue;
			}

			$this->{'_' . $key} = $val;
		}

		// No locations set in config?
		if (empty($this->_theme_locations))
		{
			// Let's use this obvious default
			$this->_theme_locations = [APPPATH . 'themes/'];
		}

		// If the parse is going to be used, best make sure it's loaded
		if ($this->_parser_enabled === TRUE)
		{
			$this->_ci->load->library('parser');
		}

		// What controllers or methods are in use
		$this->_controller = $this->_ci->router->class;
		$this->_method = $this->_ci->router->method;

		// Load user agent library if not loaded
		$this->_ci->load->library('user_agent');

		// We'll want to know this later
		$this->_is_mobile = $this->_ci->agent->is_mobile();
	}

	// --------------------------------------------------------------------

	/**
	 * Magic Get function to get data
	 *
	 * @param    string $name
	 *
	 * @return    mixed
	 */
	public function __get($name)
	{
		return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Magic Set function to set data
	 *
	 * @param    string $name
	 * @param    mixed $value
	 */
	public function __set($name, $value)
	{
		$this->_data[$name] = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * Set data using a chainable method. Provide two strings or an array of data.
	 *
	 * @param    string $name
	 * @param    mixed $value
	 *
	 * @return    object    $this
	 */
	public function set($name, $value = NULL)
	{
		// Lots of things! Set them all
		if (is_array($name) or is_object($name))
		{
			foreach ($name as $item => $value)
			{
				$this->_data[$item] = $value;
			}
		} // Just one thing, set that
		else
		{
			$this->_data[$name] = $value;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Build the entire HTML output combining partials, layouts and views.
	 *
	 * @param    string $view
	 * @param    array $data
	 * @param    bool $return
	 * @param    bool $IE_cache
	 * @param    bool $pre_parsed_view Did we already parse our view?
	 *
	 * @return    string
	 * @throws Asset_Exception
	 */
	public function build($view, $data = [], $return = FALSE, $IE_cache = TRUE, $pre_parsed_view = FALSE)
	{
		// Set whatever values are given. These will be available to all view files
		is_array($data) OR $data = (array) $data;

		// Merge in what we already have set
		$this->_data = array_merge($this->_data, $data);

		// We don't need you any more buddy
		unset($data);

		// If we don't have a title, we'll take our best guess.
		// Everybody needs a title!
		if (empty($this->_title))
		{
			$this->_title = $this->_guess_title();
		}

		// Output template variables to the template
		$template['title'] = strip_tags($this->_title);
		$template['page_title'] = $this->_title;
		$template['breadcrumbs'] = $this->_breadcrumbs;
		$template['metadata'] = $this->get_metadata() . Asset::render('extra') . $this->get_metadata('late_header');
		$template['partials'] = [];

		// Assign by reference, as all loaded views will need access to partials
		$this->_data['template'] =& $template;

		// Process partials.
		foreach ($this->_partials as $name => $partial)
		{
			// We can only work with data arrays
			is_array($partial['data']) OR $partial['data'] = (array) $partial['data'];

			// If it uses a view, load it
			if (isset($partial['view']))
			{
				$template['partials'][$name] = $this->_find_view($partial['view'], $partial['data']);
			}
			// Otherwise the partial must be a string
			else
			{
				if ($this->_parser_enabled === TRUE)
				{
					$partial['string'] = $this->_ci->parser->parse_string($partial['string'], $this->_data + $partial['data'], TRUE);
				}

				$template['partials'][$name] = $partial['string'];
			}
		}

		// Disable sodding IE7's constant cacheing!!
		// This is in a conditional because otherwise it errors when output is returned instead of output to browser.
		if ($IE_cache)
		{
			$this->_ci->output->set_header('Expires: Sat, 01 Jan 2000 00:00:01 GMT');
			$this->_ci->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
			$this->_ci->output->set_header('Cache-Control: post-check=0, pre-check=0, max-age=0');
			$this->_ci->output->set_header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			$this->_ci->output->set_header('Pragma: no-cache');
		}

		// Let CI do the caching instead of the browser
		$this->cache_lifetime > 0 && $this->_ci->output->cache($this->cache_lifetime);

		// Set the _body var. If we have pre-parsed our
		// view, then our work is done. Otherwise, we will
		// find the view and parse it.
		if ($pre_parsed_view)
		{
			$this->_body = $view;
		}
		else
		{
			$this->_body = $this->_find_view($view, [], $this->_parser_body_enabled);
		}

		// Want this file wrapped with a layout file?
		if ($this->_layout)
		{
			// Added to $this->_data['template'] by refference
			$template['body'] = $this->_body;

			if ($this->_parser_enabled)
			{
				// Persistent tags is an experiment to parse some tags after
				// parsing of all other tags, so the tag persistent should be:
				//
				// a) Defined only if depends of others tags
				// b) Plugin that is a callback, so could retrieve runtime data.
				// c) Returned with a content parsed
				$this->_data['_tags']['persistent_tags'][] = 'template:metadata';
			}

			// Find the main body and 3rd param means parse if its a theme view (only if parser is enabled)
			$this->_body = self::_load_view('layouts/' . $this->_layout, $this->_data, TRUE, self::_find_view_folder());
		}

		if ($this->_minify_enabled && function_exists('process_data_jmr1'))
		{
			$this->_body = process_data_jmr1($this->_body);
		}

		// Now that *all* parsing is sure to be done we inject the {{ noparse }} contents back into the output
		if (class_exists('Lex_Parser'))
		{
		    $this->_body = Lex_Parser::inject_noparse($this->_body);
		}

		// Want it returned or output to browser?
		if (! $return)
		{
			$this->_ci->output->set_output($this->_body);
		}

		return $this->_body;
	}

	/**
	 * Build the entire JSON output, setting the headers for response.
	 *
	 * @param    array $data
	 *
	 * @return    void
	 */
	public function build_json($data = [])
	{
		$this->_ci->output->set_header('Content-Type: application/json; charset=utf-8');
		$this->_ci->output->set_output(json_encode((object) $data));
	}

	/**
	 * Set the title of the page
	 *
	 * @return    object    $this
	 */
	public function title()
	{
		// If we have some segments passed
		if ($title_segments = func_get_args())
		{
			$this->_title = implode($this->_title_separator, $title_segments);
		}

		return $this;
	}


	/**
	 * Put extra javascipt, css, meta tags, etc before all other head data
	 *
	 * @param    string $line The line being added to head
	 * @param string $place
	 *
	 * @return    object    $this
	 */
	public function prepend_metadata($line, $place = 'header')
	{
		//we need to declare all new key's in _metadata as an array for the unshift function to work
		if (! isset($this->_metadata[$place]))
		{
			$this->_metadata[$place] = [];
		}

		array_unshift($this->_metadata[$place], $line);

		return $this;
	}


	/**
	 * Put extra javascipt, css, meta tags, etc after other head data
	 *
	 * @param    string $line The line being added to head
	 * @param string $place
	 *
	 * @return    object    $this
	 */
	public function append_metadata($line, $place = 'header')
	{
		$this->_metadata[$place][] = $line;

		return $this;
	}

	/**
	 * Put extra javascipt, css, meta tags, etc after other head data
	 *
	 * @param $files
	 * @param null $min_file
	 * @param string $group
	 *
	 * @return    object    $this
	 * @throws Asset_Exception
	 */
	public function append_css($files, $min_file = NULL, $group = 'extra')
	{
		Asset::css($files, $min_file, $group);

		return $this;
	}

	/**
	 * @param $files
	 * @param null $min_file
	 * @param string $group
	 *
	 * @return $this
	 * @throws Asset_Exception
	 */
	public function append_js($files, $min_file = NULL, $group = 'extra')
	{
		Asset::js($files, $min_file, $group);

		return $this;
	}


	/**
	 * Set metadata for output later
	 *
	 * @param    string $name keywords, description, etc
	 * @param    string $content The content of meta data
	 * @param    string $type Meta-data comes in a few types, links for example
	 *
	 * @return    object    $this
	 */
	public function set_metadata($name, $content, $type = 'meta')
	{
		$name = htmlspecialchars(strip_tags($name));
		$content = trim(htmlspecialchars(strip_tags($content)));

		// Keywords with no comments? ARG! comment them
		if ($name == 'keywords' AND ! strpos($content, ','))
		{
			$content = preg_replace('/[\s]+/', ', ', trim($content));
		}

		switch ($type)
		{
			case 'meta':
				$this->_metadata['header'][$name] = '<meta name="' . $name . '" content="' . $content . '" />';
				break;

			case 'link':
				$this->_metadata['header'][$content] = '<link rel="' . $name . '" href="' . $content . '" />';
				break;

			case 'og':
				$this->_metadata['header'][md5($name . $content)] = '<meta property="' . $name . '" content="' . $content . '" />';
				break;
		}

		return $this;
	}


	/**
	 * Which theme are we using here?
	 *
	 * @param    string $theme Set a theme for the template library to use
	 *
	 * @return    object    $this
	 */
	public function set_theme($theme = NULL)
	{
		$this->_theme = $theme;
		foreach ($this->_theme_locations as $location)
		{
			if ($this->_theme AND file_exists($location . $this->_theme))
			{
				$this->_theme_path = rtrim($location . $this->_theme . '/');
				break;
			}
		}

		return $this;
	}

	/**
	 * Get the current theme path
	 *
	 * @return    string The current theme path
	 */
	public function get_theme_path()
	{
		return $this->_theme_path;
	}

	/**
	 * Get the current view path
	 *
	 * @param    bool    Set if should be returned the view path full (with theme path) or the view relative the theme path
	 *
	 * @return    string    The current view path
	 */
	public function get_views_path($relative = FALSE)
	{
		return $relative ? substr($this->_find_view_folder(), strlen($this->get_theme_path())) : $this->_find_view_folder();
	}

	/**
	 * Which theme layout should we using here?
	 *
	 * @param    string $view
	 * @param    string $layout_subdir
	 *
	 * @return    object    $this
	 */
	public function set_layout($view, $layout_subdir = NULL)
	{
		$this->_layout = $view;

		if ($layout_subdir !== NULL)
		{
			$this->_layout_subdir = $layout_subdir;
		}

		return $this;
	}

	/**
	 * Set a view partial
	 *
	 * @param    string $name
	 * @param    string $view
	 * @param    array $data
	 *
	 * @return    object    $this
	 */
	public function set_partial($name, $view, $data = [])
	{
		$this->_partials[$name] = ['view' => $view, 'data' => $data];

		return $this;
	}

	/**
	 * Set a view partial
	 *
	 * @param    string $name
	 * @param    string $string
	 * @param    array $data
	 *
	 * @return    object    $this
	 */
	public function inject_partial($name, $string, $data = [])
	{
		$this->_partials[$name] = ['string' => $string, 'data' => $data];

		return $this;
	}

	/**
	 * Helps build custom breadcrumb trails
	 *
	 * @param $name
	 * @param string $uri
	 * @param bool $reset
	 *
	 * @return $this
	 */
	public function set_breadcrumb($name, $uri = '', $reset = FALSE)
	{
		// perhaps they want to start over
		if ($reset)
		{
			$this->_breadcrumbs = [];
		}

		$this->_breadcrumbs[] = ['name' => $name, 'uri' => $uri];

		return $this;
	}

	/**
	 * Set a the cache lifetime
	 *
	 * @param    int $seconds
	 *
	 * @return    object    $this
	 */
	public function set_cache($seconds = 0)
	{
		$this->cache_lifetime = $seconds;

		return $this;
	}


	/**
	 * enable_minify
	 * Should be minify used or the output html files just delivered normally?
	 *
	 * @param    bool $bool
	 *
	 * @return    object    $this
	 */
	public function enable_minify($bool)
	{
		$this->_minify_enabled = $bool;

		return $this;
	}


	/**
	 * enable_parser
	 * Should be parser be used or the view files just loaded normally?
	 *
	 * @param    bool $bool
	 *
	 * @return    object    $this
	 */
	public function enable_parser($bool)
	{
		$this->_parser_enabled = $bool;

		return $this;
	}

	/**
	 * enable_parser_body
	 * Should be parser be used or the body view files just loaded normally?
	 *
	 * @param    bool $bool
	 *
	 * @return    object    $this
	 */
	public function enable_parser_body($bool)
	{
		$this->_parser_body_enabled = $bool;

		return $this;
	}

	/**
	 * theme_locations
	 * List the locations where themes may be stored
	 *
	 * @return    array
	 */
	public function theme_locations()
	{
		return $this->_theme_locations;
	}

	/**
	 * add_theme_location
	 * Set another location for themes to be looked in
	 *
	 * @access    public
	 *
	 * @param    string $location
	 */
	public function add_theme_location($location)
	{
		$this->_theme_locations[] = $location;
	}

	/**
	 * theme_exists
	 * Check if a theme exists
	 *
	 * @param    string $theme
	 *
	 * @return    bool
	 */
	public function theme_exists($theme = NULL)
	{
		$theme OR $theme = $this->_theme;

		foreach ($this->_theme_locations as $location)
		{
			if (is_dir($location . $theme))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * get_layouts
	 * Get all current layouts (if using a theme you'll get a list of theme layouts)
	 *
	 * @return    array
	 */
	public function get_layouts()
	{
		$layouts = [];

		foreach (glob(self::_find_view_folder() . 'layouts/*.*') as $layout)
		{
			$layouts[] = pathinfo($layout, PATHINFO_BASENAME);
		}

		return $layouts;
	}

	/**
	 * @param string $place
	 *
	 * @return null|string
	 */
	public function get_metadata($place = 'header')
	{
		return isset($this->_metadata[$place]) && is_array($this->_metadata[$place])
			? implode("\n\t\t", $this->_metadata[$place]) : NULL;
	}

	/**
	 * get_layouts
	 * Get all current layouts (if using a theme you'll get a list of theme layouts)
	 *
	 * @param    string $theme
	 *
	 * @return    array
	 */
	public function get_theme_layouts($theme = NULL)
	{
		$theme OR $theme = $this->_theme;

		$layouts = [];

		foreach ($this->_theme_locations as $location)
		{
			// Get special web layouts
			if (is_dir($location . $theme . '/views/web/layouts/'))
			{
				foreach (glob($location . $theme . '/views/web/layouts/*.*') as $layout)
				{
					$layouts[] = pathinfo($layout, PATHINFO_BASENAME);
				}
				break;
			}

			// So there are no web layouts, assume all layouts are web layouts
			if (is_dir($location . $theme . '/views/layouts/'))
			{
				foreach (glob($location . $theme . '/views/layouts/*.*') as $layout)
				{
					$layouts[] = pathinfo($layout, PATHINFO_BASENAME);
				}
				break;
			}
		}

		return $layouts;
	}

	/**
	 * layout_exists
	 * Check if a theme layout exists
	 *
	 * @param    string $layout
	 *
	 * @return    bool
	 */
	public function layout_exists($layout)
	{
		// If there is a theme, check it exists in there
		if (! empty($this->_theme) AND in_array($layout, self::get_theme_layouts()))
		{
			return TRUE;
		}

		// Otherwise look in the normal places
		return file_exists(self::_find_view_folder() . 'layouts/' . $layout . self::_ext($layout));
	}


	/**
	 * layout_is
	 * Check if the current theme layout is equal the $layout argument
	 *
	 * @param    string $layout
	 *
	 * @return    bool
	 */
	public function layout_is($layout)
	{
		return $layout === $this->_layout;
	}

	/**
	 * find layout files, they could be mobile or web
	 *
	 * @return string
	 */
	private function _find_view_folder()
	{
		if (isset($this->_ci->load->get_var['template_views']))
		{
			return $this->_ci->load->get_var['template_views'];
		}

		// Base view folder
		$view_folder = APPPATH . 'views/';

		// Using a theme? Put the theme path in before the view folder
		if (! empty($this->_theme))
		{
			$view_folder = $this->_theme_path . 'views/';
		}

		// Would they like the mobile version?
		if ($this->_is_mobile === TRUE AND is_dir($view_folder . 'mobile/'))
		{
			// Use mobile as the base location for views
			$view_folder .= 'mobile/';
		} // Use the web version
		else if (is_dir($view_folder . 'web/'))
		{
			$view_folder .= 'web/';
		}

		// Things like views/admin/web/view admin = subdir
		if ($this->_layout_subdir)
		{
			$view_folder .= $this->_layout_subdir . '/';
		}

		// If using themes store this for later, available to all views
		$this->_ci->load->vars(['template_views' => $view_folder]);

		return $view_folder;
	}

	/**
	 * @param $view
	 * @param array $data
	 * @param bool $parse_view
	 *
	 * @return object|string
	 */
	private function _find_view($view, array $data, $parse_view = TRUE)
	{
		// Only bother looking in themes if there is a theme
		if (! empty($this->_theme))
		{
			$location = $this->get_theme_path();
			$theme_view = $this->get_views_path(TRUE) . $view;

			if (file_exists($location . $theme_view . self::_ext($theme_view)))
			{
				return self::_load_view($theme_view, $this->_data + $data, $parse_view, $location);
			}
		}

		// Not found it yet? Just load, its either in root view
		return self::_load_view($view, $this->_data + $data, $parse_view);
	}

	/**
	 * @param $view
	 * @param array $data
	 * @param bool $parse_view
	 * @param null $override_view_path
	 *
	 * @return object|string
	 */
	private function _load_view($view, array $data, $parse_view = TRUE, $override_view_path = NULL)
	{
		// Sevear hackery to load views from custom places
		if ($override_view_path !== NULL)
		{
			// Load it directly, bypassing $this->load->view() as ME resets _ci_view
			$content = $this->_ci->load->view_with_path(
											$override_view_path . $view . self::_ext($view),
											$data,
											TRUE
										);

			if ($this->_parser_enabled === TRUE AND $parse_view === TRUE)
			{
				// Load content and pass through the parser
				$content = $this->_ci->parser->parse_string($content, object_to_array($data), TRUE);
			}
		} // Can just run as usual
		else
		{
			// Grab the content of the view (parsed or loaded)
			$content = ($this->_parser_enabled === TRUE AND $parse_view === TRUE)

				// Parse that bad boy
				? $this->_ci->parser->parse($view, $data, TRUE)

				// None of that fancy stuff for me!
				: $this->_ci->load->view($view, $data, TRUE);
		}

		return $content;
	}

	/**
	 * @return string
	 */
	private function _guess_title()
	{
		$this->_ci->load->helper('inflector');

		// Obviously no title, lets get making one
		$title_parts = [];

		// If the method is something other than index, use that
		if ($this->_method != 'index')
		{
			$title_parts[] = $this->_method;
		}

		// Make sure controller name is not the same as the method name
		if (! in_array($this->_controller, $title_parts))
		{
			$title_parts[] = $this->_controller;
		}

		// Glue the title pieces together using the title separator setting
		$title = humanize(implode($this->_title_separator, $title_parts));

		return $title;
	}

	/**
	 * @param $file
	 *
	 * @return string
	 */
	private function _ext($file)
	{
		return pathinfo($file, PATHINFO_EXTENSION) ? '' : '.php';
	}
}

// END Template class
