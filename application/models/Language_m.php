<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Language_m
 *
 * @property Setting $setting
 */
class Language_m extends MY_Model
{
    /**
     * @var string
     */
    private $_table_supports = 'languages_supports';
    private $_fk_languages_supports_id = 'languages_supports_id';

    /**
     * @var string
     */
    public $error = "";

    /**
     * @var array|object
     */
    private $_cache_default;
    private $_cache = [];

    /**
     * Language_m constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // get all languages
        $this->languages();

        // check default lang
        if(! $this->get_default())
        {
            if(! $this->lang_item(config_item('language')))
            {
                $this->add(config_item('language'));
            }

            $this->set_default(config_item('language'));
        }

        // update default language setting
        if(empty($this->setting->default_language))
        {
            $this->setting->default_language = $this->_cache_default->code;
        }
    }

    /**
     * @return array
     */
    public function languages()
    {
        if(! is_empty($this->_cache))
        {
            return $this->_cache;
        }

        $this->_select_join();
        $languages = $this->db
            ->order_by($this->_table . '.pos', 'DESC')
            ->get($this->_table)
            ->result();

        foreach ($languages as $item)
        {
            $this->_cache[$item->code] = $item;
        }

        return $this->_cache;
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function lang_item($code = '')
    {
        if(isset($this->_cache[$code]))
        {
            return $this->_cache[$code];
        }

        $this->_select_join();
        return $this->_cache[$code] = $this->db
            ->where($this->_table_supports . '.code', $code)
            ->get($this->_table, 1)
            ->row();
    }

    /**
     * @param string $code
     * @param int $pos
     * @return bool|int
     */
    public function add($code = '', $pos = 0)
    {
        $query = $this->db
            ->where('code', $code)
            ->get($this->_table_supports, 1);

        if ($query->num_rows() > 0)
        {
            $dummy = [
                $this->_fk_languages_supports_id => $query->row()->id,
                'pos' => (int) $pos, // order
                'is_default' => 0,
            ];

            $id = $this->insert($dummy);
            $this->_cache[$code] = $this->lang_item($code);
            return $id;
        }

        return FALSE;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function remove($code = '')
    {
        $lang_item = $this->lang_item($code);

        // check default lang
        if($lang_item AND $lang_item->is_default == 0)
        {
            unset($this->_cache[$code]);
            return $this->delete($lang_item->languages_id);
        }

        $this->error = sprintf("Language '%s' is not deleted.", $code);
        return FALSE;
    }

    /**
     * @param string $code
     * @return bool|mixed
     */
    public function set_default($code = '')
    {
        $lang_item = $this->lang_item($code);
        if (!$lang_item)
        {
            return FALSE;
        }

        $this->update_all('is_default', 0);
        $this->update($lang_item->languages_id, ['is_default' => 1]);

        return $this->_cache_default = $lang_item;
    }

    /**
     * get_default
     *
     * @return array|bool|mixed|object
     */
    public function get_default()
    {
        if($this->_cache_default)
        {
            return $this->_cache_default;
        }

        $this->_select_join();
        $query = $this->db
            ->where($this->_table . '.is_default', 1)
            ->get($this->_table, 1);

        if ($query->num_rows() > 0)
        {
            return $this->_cache_default = $query->row();
        }

        return FALSE;
    }

    /**
     * @return array
     */
    public function array_for_select()
    {
        $languages = $this->languages();
        $args = func_get_args();

        if (count($args) == 2) list($key, $value) = $args;
        else
        {
            $key = 'languages_id';
            $value = $args[0];
        }

        $options = [];
        foreach ($languages as $item)
        {
            $options[$item->{$key}] = $item->{$value};
        }

        return $options;
    }

    /**
     * @return CI_DB_query_builder
     */
    private function _select_join()
    {
        return $this->db->select([
                $this->_table . '.id AS ' . $this->db->protect_identifiers('languages_id'),
                $this->_table . '.languages_supports_id',
                $this->_table . '.pos',
                $this->_table . '.is_default',
                $this->_table_supports . '.*',
            ])
            ->distinct()
            ->join(
                $this->_table_supports,
                $this->_table_supports . '.id = ' . $this->_table . '.' . $this->_fk_languages_supports_id,
                'INNER'
            );
    }
}
