<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Setting_m
 *
 * Allows for an easy interface for site settings
 */
class Setting_m extends MY_Model
{
    /**
     * Get
     *
     * Gets a setting based on the $where param.  $where can be either a string
     * containing a slug name or an array of WHERE options.
     *
     * @access    public
     *
     * @param    mixed $where
     *
     * @return mixed
     */
    public function get($where)
    {
        if (!is_array($where))
        {
            $where = ['slug' => $where];
        }

        return $this->db->select('*, IF(`value` = "", `default`, `value`) AS `value`', FALSE)
            ->where($where)
            ->get($this->_table)
            ->row();
    }

    /**
     * Get Many By
     *
     * Gets all settings based on the $where param.
     *
     * @access    public
     *
     * @param    mixed $where
     *
     * @return array
     */
    public function get_many_by($where = [])
    {
        if (!is_array($where))
        {
            $where = ['slug' => $where];
        }

        return $this->db->select('*, IF(`value` = "", `default`, `value`) AS `value`', FALSE)
            ->where($where)
            ->order_by('pos', 'DESC')
            ->get($this->_table)
            ->result();
    }

    /**
     * Update
     *
     * Updates a setting for a given $slug.
     *
     * @access    public
     *
     * @param    string $slug
     * @param    array $params
     * @param bool $skip_validation
     *
     * @return    bool
     */
    public function update($slug = '', $params = [], $skip_validation = FALSE)
    {
        return $this->db->update($this->_table, $params, ['slug' => $slug]);
    }
}
