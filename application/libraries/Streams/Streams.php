<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class Streams
 */
class Streams extends CI_Driver_Library
{
    /**
     * Enables the use of CI super-global without having to define an extra variable.
     *
     * @param    string $var
     *
     * @return    mixed
     */
    public function __get($var)
    {
        static $ci;
        isset($ci) OR $ci =& get_instance();

        return $ci->{$var};
    }
}
