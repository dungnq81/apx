<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class MY_Session
 *
 */
class MY_Session extends CI_Session
{
    /**
     * MY_Session constructor.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (is_cli() === TRUE && ENVIRONMENT === 'testing')
        {
            log_message('debug', 'Session: Initialization under testing aborted.');
            return;
        }

        parent::__construct($params);
    }

    /**
     * sess_destroy
     */
    public function sess_destroy()
    {
        if (is_cli() === TRUE && ENVIRONMENT === 'testing')
        {
            log_message('debug', 'Session: calling session_destroy() skipped under testing.');
            return;
        }

        parent::sess_destroy();
    }
}
