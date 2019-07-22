<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Users_group_m
 */
class Users_group_m extends MY_Model
{
    /**
     * Users_group_m constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->config('auth', TRUE);
        $this->load->model('users/user_m');

        $this->_init_group();
    }

    /**
     * Check admin and default group
     */
    private function _init_group()
    {
        // check default role
        $default_group = $this->config->item('default_group', 'auth');
        if (! string_not_empty($default_group))
        {
            $default_group = 'member';
            $this->config->set_item('default_group', $default_group, 'auth');
        }

        $query = $this->db->get_where($this->_table, ['name' => $default_group], 1);
        if ($query->num_rows() < 1)
        {
            $dummy = [
                'not_deleted' => 0,
                'name' => $default_group,
                'description' => 'Member Group'
            ];

            $this->insert($dummy);
        }

        // check admin group
        $query = $this->db->get_where($this->_table, ['name' => 'administrator'], 1);
        if ($query->num_rows() < 1)
        {
            $dummy = [
                'not_deleted' => 1,
                'name' => 'administrator',
                'description' => 'Administrator Group'
            ];

            $this->insert($dummy);
        }
        else
        {
            $group = $query->row();
            if ($group->not_deleted == 0)
            {
                $this->update($group->id, ['not_deleted' => 1]);
            }
        }
    }

    /**
     * Get default group
     *
     * @return mixed
     */
    public function default_group()
    {
        return $this->db
            ->get_where($this->_table, ['name' => $this->config->item('default_group', 'auth')], 1)
            ->row();
    }

    /**
     * Get admin group
     *
     * @return mixed
     */
    public function admin_group()
    {
        return $this->db
            ->get_where($this->_table, ['name' => 'administrator'], 1)
            ->row();
    }

    /**
     * @param $group_id
     * @return bool|CI_DB_result
     */
    public function group($group_id)
    {
        $identity = is_numeric($group_id) ? 'id' : 'name';
        $query = $this->db
            ->where($identity, $group_id)
            ->get($this->_table, 1);

        if($query->num_rows() < 1)
        {
            return FALSE;
        }

        return $query;
    }

    /**
     * @param null $limit
     * @param null $offset
     *
     * @return CI_DB_result
     */
    public function groups($limit = NULL, $offset = NULL)
    {
        return $this->db->get($this->_table, $limit, $offset);
    }

    /**
     * @param bool $group_id
     *
     * @return bool
     */
    public function delete_group($group_id = FALSE)
    {
        // bail if mandatory param not set
        if (! $group_id OR empty($group_id))
        {
            return FALSE;
        }

        $group = $this->group($group_id)->row();

        // check administrator group
        if ($group->not_deleted === 0 AND 'administrator' !== $group->name)
        {
            $this->db->trans_begin();

            // check user in groups
            if ($this->user_m->users($group->name)->num_rows() < 1)
            {
                $this->delete($group_id);
                if ($this->db->trans_status() === FALSE)
                {
                    $this->db->trans_rollback();
                    return FALSE;
                }
            }

            $this->db->trans_commit();
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param bool $group_name
     * @param array $additional_data
     *
     * @return bool|int
     */
    public function add_group($group_name = FALSE, $additional_data = [])
    {
        if (! $group_name)
        {
            return FALSE;
        }

        // bail if the group name already exists
        $existing_group = $this->group($group_name)->num_rows();
        if ($existing_group === 1)
        {
            return FALSE;
        }

        $data = [
            'name' => $group_name,
            'not_deleted' => 0,
        ];

        if (! empty($additional_data))
        {
            $data = array_merge($this->filter_data($this->_table, $additional_data), $data);
        }

        return $this->insert($data);
    }

    /**
     * @param bool $group_id
     * @param bool $group_name
     * @param array $additional_data
     *
     * @return bool
     */
    public function update_group($group_id = FALSE, $group_name = FALSE, $additional_data = [])
    {
        if (! $group_id)
        {
            return FALSE;
        }

        $data = [];
        if ($group_name)
        {
            // bail if the group name already exists
            $existing_group = $this->group($group_name)->row();
            if (isset($existing_group->id) AND $existing_group->id != $group_id)
            {
                return FALSE;
            }

            $data['name'] = $group_name;
        }

        // restrict change of name of the admin group
        $group = $this->group($group_id)->row();
        if ('administrator' === $group->name AND $group_name !== $group->name)
        {
            return FALSE;
        }

        if (! empty($additional_data))
        {
            $data = array_merge($this->filter_data($this->_table, $additional_data), $data);
        }

        return $this->update($group_id, $data);
    }
}
