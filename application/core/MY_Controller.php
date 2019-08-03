<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class MY_Controller
 *
 * @property CI_Benchmark $benchmark
 * @property CI_Router $router
 * @property CI_Input $input
 *
 * @property MY_Config $config
 * @property Setting $setting
 * @property Language_m $language_m
 * @property MY_Loader $load
 * @property Permission_m $permission_m
 */
class MY_Controller extends CI_Controller
{
	/**
	 * The name of the controller class for the current class instance.
	 *
	 * @var string
	 */
	public $controller;

	/**
	 * The name of the method for the current request.
	 *
	 * @var string
	 */
	public $method;

	/**
	 * The sub-directory (if any) that contains the requested controller class.
	 *
	 * @var string
	 */
	public $directory;

    /**
     * MY_Controller constructor.
     *
     * @throws Asset_Exception
     */
	public function __construct()
	{
		parent::__construct();

		$this->benchmark->mark('my_controller_start');

		// Use this to define hooks with a nicer syntax
		ci()->hooks =& $GLOBALS['EXT'];

		// Work out controller, method
        // and make them accessable throught the CI instance
		ci()->controller = $this->controller = $this->router->class;
		ci()->method = $this->method = $this->router->method;
		ci()->directory = $this->directory = $this->router->directory;

		$this->load->library('Apx/auth');
		ci()->current_user = $this->template->current_user = $this->current_user = $this->auth->user()->row();

		// Loaded after $this->current_user is set so that data can be used everywhere
		$this->load->models(['language_m', 'permission_m', 'theme_m']);

		// List available controller permissions for this user
		ci()->permissions = $this->permissions = $this->current_user ? $this->permission_m->get_group($this->current_user->group_id) : [];

		//
		// LANGUAGE
        //
		$lang = $this->language_m->get_default();
        if (! empty($_SESSION['lang_code']))
        {
            $lang = $_SESSION['lang_code'];
        }
        elseif (! empty($_COOKIE['lang_code']))
        {
            // Lang has is picked by a user.
            $lang = strtolower($_COOKIE['lang_code']);
        }

        // lang object
        $lang_item = $this->language_m->lang_item($lang);

        // Lock back-end language
        if (is_a($this, 'Admin_Controller'))
        {
            $lang = $this->setting->default_language;
            $lang_item = $this->language_m->lang_item($lang);

            define('ADMIN_LANG', $lang);
            if (! empty($_GET['lang']))
            {
                $lang = strtolower($this->input->get('lang'));
            }
        }

        // define default site language code
        // or lang code by query in admin page
        define('LANG', $lang);

        // Whatever we decided the lang was,
        // save it for next time to avoid working it out again
        $_SESSION['lang_code'] = $lang;
        $this->load->vars(['lang' => $lang_item]);
        unset($lang);

        // Set php locale time
        $locale = [
            $lang_item->code,
            $lang_item->folder,
            $lang_item->locale
        ];

        array_unshift($locale, LC_TIME);
        call_user_func_array('setlocale', $locale);
        if ($lang_item->code != config_item('language'))
        {
            $this->config->set_item('language', $lang_item->code);
        }

        // add addon asset path and set base url
        Asset::add_path('addon', site_url('addons/'));
        Asset::set_url(base_url());

        //$this->output->set_header('Content-Type: text/html; charset=UTF-8');
        //$this->output->enable_profiler(TRUE);
        $this->benchmark->mark('my_controller_end');
	}
}

/* PHP5 spl_autoload */
spl_autoload_register('_autoload');

/**
 * Returns the CodeIgniter object.
 *
 * Example: ci()->db->get('table');
 *
 * @return \CI_Controller
 */
function &ci()
{
	return get_instance();
}

/**
 * Library base autoload for core class and libraries extends
 *
 * @param $class
 */
function _autoload($class)
{
	// don't autoload CI_ prefixed classes
    // or those using the config subclass_prefix
	if (strstr($class, 'CI_') OR strstr($class, config_item('subclass_prefix')))
	{
		return;
	}

	// autoload core classes
	if (is_file($location = APPPATH . 'core/' . ucfirst($class) . '.php'))
	{
		include_once $location;
		return;
	}

	// autoload library classes
	if (is_file($location = APPPATH . 'libraries/' . ucfirst($class) . '.php'))
	{
		include_once $location;
		return;
	}
}
