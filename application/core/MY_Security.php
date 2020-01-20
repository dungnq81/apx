<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class MY_Security
 */
class MY_Security extends CI_Security
{
    /**
     * MY_Security constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show CSRF Error
     *
     * http://www.johnkieken.com/how-to-handle-an-expired-csrf-token-after-a-page-is-left-open/
     *
     * @return	void
     */
    public function csrf_show_error()
    {
        //show_error('The action you have requested is not allowed.', 403);

        // force page "refresh" - redirect back to itself with sanitized URI for security
        // a page refresh restores the CSRF cookie to allow a subsequent login
        if(function_exists('redirect'))
        {
            redirect($_SERVER['REQUEST_URI'], 'refresh');
        }
        else
        {
            header('Location: ' . htmlspecialchars($_SERVER['REQUEST_URI']), TRUE, 200);
        }
    }
}
