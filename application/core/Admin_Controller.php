<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * This is the basis for the Admin class. Code here is run before admin controllers
 *
 * Class Admin_Controller
 *
 * @property MY_Form_validation $form_validation
 * @property CI_Session $session
 * @property Theme_m $theme_m
 * @property CI_URI $uri
 */
class Admin_Controller extends MY_Controller
{
	/**
	 * Admin controllers can have sections, normally an arbitrary string
	 *
	 * @var string
	 */
	protected $section = NULL;

	/**
	 * Admin_Controller constructor.
	 *
	 * @throws Asset_Exception
	 * @throws Exception
	 */
	public function __construct()
	{
		parent::__construct();

        $this->load->library('form_validation');
		$this->load->helper('theme');

		// Show error and exit if the user does not have sufficient permissions
		if (! self::_check_access())
		{
			$this->session->set_flashdata('error', 'Access Denied');
            redirect('admin/login');
		}

		// If the setting is enabled redirect request to HTTPS
		if ($this->setting->admin_force_https AND strtolower(substr(current_url(), 4, 1)) != 's')
		{
			redirect(str_replace('http:', 'https:', current_url()) . '?session=' . session_id());
		}

		ci()->admin_theme = $this->admin_theme = $this->theme_m->get_admin();

		// Using a bad slug?
		if (empty($this->admin_theme->slug))
		{
			show_error('This site has been set to use an admin theme that does not exist.');
		}

		// make a constant as this is used in a lot of places
		defined('ADMIN_THEME') OR define('ADMIN_THEME', $this->admin_theme->slug);

		// Set the theme location of assets
		Asset::add_path('theme', $this->admin_theme->web_path . '/');
		Asset::set_path('theme');

		// Active Admin Section (might be null, but who cares)
		$this->template->active_section = $this->section;

		// Build Admin Navigation
		if (isset($this->current_user->id))
		{
			// Here's our admin menu array.
			$menu_items = array(
				'cp:nav_dashboard' => 'admin',
				'cp:nav_settings' =>  'admin/settings',
				'cp:nav_users' => array(
					'cp:nav_groups' => 'admin/groups',
					'cp:nav_permissions' => 'admin/permissions',
					'cp:nav_users' => 'admin/users'
				),
				'cp:nav_pages' => 'admin/pages',
				'cp:nav_profiles' => [
					'cp:profiles' => 'admin/profiles',
					'cp:logout_label' => 'admin/logout'
				]
			);

			// The admin menu items.
			$this->template->menu_items = $menu_items;
		}

		// Template configuration default
        // 'admin' - default folder
		$this->template
			->enable_parser(FALSE)
			->set_theme(ADMIN_THEME)
			->set_layout('default', 'admin');

		// trigger the run() method in the selected admin theme
		$class = 'Theme_' . ucfirst($this->admin_theme->slug);
		call_user_func([new $class, 'run']);
	}

	/**
	 * Checks to see if a user object has access rights to the admin area.
	 *
	 * @return boolean
	 */
	private function _check_access()
	{
		// These pages get pass permission checks
		$ignored_pages = ['admin/login', 'admin/logout', 'admin/help'];

		// Check if the current page is to be ignored
		$current_page = $this->uri->segment(1, '') . '/' . $this->uri->segment(2, 'index');

		// Dont need to log in, this is an open page
		if (in_array($current_page, $ignored_pages))
		{
			return TRUE;
		}

		// check user login ok!
		if (! $this->current_user)
		{
			// save the location they were trying to get to
			$this->session->set_userdata('admin_redirect', $this->uri->uri_string());
			redirect('admin/login');
		}

		// Admins can go straight in
		if ('administrator' == $this->current_user->group_name)
		{
			return TRUE;
		}

		// Well they at least better have permissions!
		if ($this->current_user)
		{
			// We are looking at the index page. Show it if they have ANY admin access at all
			if ($current_page == 'admin/index' AND $this->permissions)
			{
				return TRUE;
			}

			// Check if the current user can view that page
			return array_key_exists($this->controller, $this->permissions);
		}

		// god knows what this is... erm...
		return FALSE;
	}
}
