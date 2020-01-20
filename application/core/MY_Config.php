<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Class MY_Config
 */
class MY_Config extends CI_Config
{
    /**
     * Set a config file item
     *
     * @param string $item the config item key
     * @param string $value the config item value
     * @param string $index
     */
    public function set_item($item, $value, $index = '')
    {
        if ($index == '') {
            $this->config[$item] = $value;
        } else {
            $this->config[$index][$item] = $value;
        }
    }

    /**
     * Create a local URI based on your basepath.
     *
     * @param    string $uri
     * @return    string
     */
    public function site_uri($uri = '')
    {
        $parse_url = parse_url($this->site_url($uri));
        return isset($parse_url['path']) ? $parse_url['path'] : '/';
    }
}
