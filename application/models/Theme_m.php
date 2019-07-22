<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Theme_m
 *
 * @property Setting $setting
 * @property Template $template
 */
class Theme_m extends MY_Model implements Countable
{
    /**
     * Default Theme
     *
     * @var string
     */
    private $_theme = NULL;

    /**
     * Default Admin Theme
     *
     * @var string
     */
    private $_admin_theme = NULL;

    /**
     * Available themes
     *
     * @var array
     */
    private $_themes = NULL;

    /**
     * Sets the current default theme
     */
    public function __construct()
    {
        parent::__construct();

        $this->_theme = $this->setting->default_theme;
        $this->_admin_theme = $this->setting->admin_theme;
    }

    /**
     * Count the number of available themes
     *
     * @return int
     */
    public function count()
    {
        return count($this->get_all());
    }

    /**
     * Get all available themes
     *
     * @return array
     */
    public function get_all()
    {
        foreach ($this->template->theme_locations() as $location) {
            if (!$themes = glob($location . '*', GLOB_ONLYDIR)) {
                continue;
            }

            foreach ($themes as $theme_path) {
                $this->_get_details(dirname($theme_path) . '/', basename($theme_path));
            }
        }

        ksort($this->_themes);
        return $this->_themes;
    }

    /**
     * Get a specific theme
     *
     * @param string $slug
     *
     * @return bool|object
     */
    public function get($slug = '')
    {
        $slug OR $slug = $this->_theme;

        foreach ($this->template->theme_locations() as $location) {
            if (is_dir($location . $slug)) {
                $theme = $this->_get_details($location, $slug);

                if ($theme !== FALSE) {
                    return $theme;
                }
            }
        }

        return FALSE;
    }

    /**
     * Get the admin theme
     *
     * @param string $slug
     *
     * @return bool|object
     */
    public function get_admin($slug = '')
    {
        $slug OR $slug = $this->_admin_theme;

        foreach ($this->template->theme_locations() as $location)
        {
            if (is_dir($location . $slug))
            {
                $theme = $this->_get_details($location, $slug);
                if ($theme)
                {
                    return $theme;
                }
            }
        }

        return FALSE;
    }

    /**
     * Get details about a theme
     *
     * @param $location
     * @param $slug
     *
     * @return bool|object
     */
    private function _get_details($location, $slug)
    {
        // If it exists already, use it
        if (!empty($this->_themes[$slug]))
        {
            return $this->_themes[$slug];
        }

        if (is_dir($path = $location . $slug) AND is_file($path . DIRECTORY_SEPARATOR . 'theme.php'))
        {
            $web_path = str_replace_first(FCPATH, '', $path);
            $web_path = str_replace('\\', '/', $web_path);

            $theme = new stdClass();
            $theme->slug = $slug;
            $theme->path = $path;
            $theme->web_path = $web_path;
            $theme->screenshot = $web_path . '/screenshot.png';

            // lets make some assumptions first just in case there is a typo in details class
            $theme->name = $slug;
            $theme->author = '???';
            $theme->website = NULL;
            $theme->description = '';
            $theme->version = '???';
            $theme->type = '';

            // load the theme
            $details = $this->_spawn_class($location, $slug);

            // assign values
            if ($details)
            {
                foreach (get_object_vars($details) as $key => $val)
                {
                    $theme->{$key} = $val;
                }
            }

            // Save for later
            $this->_themes[$slug] = $theme;
            return $theme;
        }

        return FALSE;
    }

    /**
     * Set a new default theme
     *
     * @param array $input
     *
     * @return boolean
     */
    public function set_default($input)
    {
        if ($input['method'] == 'index')
        {
            return $this->setting->set('default_theme', $input['theme']);
        }
        elseif ($input['method'] == 'admin_theme')
        {
            return $this->setting->set('admin_theme', $input['theme']);
        }

        return FALSE;
    }

    /**
     * Spawn Class
     *
     * Checks to see if theme file exists and returns a class
     *
     * @param string $slug The folder name of the theme
     *
     * @return bool|stdClass
     */
    private function _spawn_class($location, $slug)
    {
        $details_file = $location . $slug . DIRECTORY_SEPARATOR . 'theme.php';

        // Check the details file exists
        if (!is_file($details_file))
        {
            return FALSE;
        }

        // Sweet, include the file
        include_once $details_file;

        // Now call the details class
        $class = 'Theme_' . ucfirst(strtolower($slug));

        // Now we need to talk to it
        return class_exists($class) ? new $class : FALSE;
    }
}
