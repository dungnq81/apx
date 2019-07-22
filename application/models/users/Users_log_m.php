<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Users_log_m
 */
class Users_log_m extends MY_Model
{
    /**
     * Users_log_m constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('user_agent');
        $this->load->model('users/user_m');
    }

    /**
     * @param string $controller
     * @param int $item_id
     * @param string $method
     * @param string $message
     */
    public function write_log($controller = '', $item_id = 0, $method = 'index', $message = '')
    {
        $user_id = $this->session->userdata("user_id");
        if($this->user_m->user($user_id)->num_rows() === 1)
        {
            $dummy = [
                $this->user_m->table_name() . '_id' => $user_id,
                'created_on' => now(),
                'ip_address' => $this->input->ip_address(),
                'data' => json_encode_uni([
                    'controller' => $controller,
                    'item_id' => $item_id,
                    'method' => $method,
                    'message' => $this->db->escape_str($message)
                ]),
                'agent' => $this->agent->platform . ', ' . $this->agent->browser . ', ' . $this->agent->version,
            ];

            $this->insert($dummy);
        }
    }
}
