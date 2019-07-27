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
    protected $_cache_default;
    protected $_cache_lang;

    /**
     * Language_m constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // check default lang
        if(! $default_lang = $this->get_default())
        {
            if(! $this->lang_item(config_item('language')))
            {
                $this->add(config_item('language'));
            }

            $default_lang= $this->set_default(config_item('language'));
        }

        // update default language setting
        if(empty($this->setting->default_language))
        {
            $this->setting->default_language = $default_lang->code;
        }
    }

    /**
     * @param string $langcode
     * @return mixed
     */
    public function lang_item($langcode = '')
    {
        if($this->_cache_lang)
        {
            return $this->_cache_lang;
        }

        $this->_select_join();
        return $this->_cache_lang = $this->db
            ->where($this->_table_supports . '.code', $langcode)
            ->get($this->_table, 1)
            ->row();
    }

    /**
     * @param string $langcode
     * @param int $pos
     * @return bool|int
     */
    public function add($langcode = '', $pos = 0)
    {
        $query = $this->db
            ->where('code', $langcode)
            ->get($this->_table_supports, 1);

        if ($query->num_rows() === 1)
        {
            $dummy = [
                $this->_fk_languages_supports_id => $query->row()->id,
                'pos' => $pos, // order
                'is_default' => 0,
            ];

            return $this->insert($dummy);
        }

        return FALSE;
    }

    /**
     * @param $langcode
     * @return bool
     */
    public function remove($langcode = '')
    {
        $lang_item = $this->lang_item($langcode);

        // check default lang
        if($lang_item->is_default == 0)
        {
            return $this->delete($lang_item->languages_id);
        }

        $this->error = sprintf("Language '%s' is not deleted.", $langcode);
        return FALSE;
    }

    /**
     * @param string $langcode
     * @return bool|mixed
     */
    public function set_default($langcode = '')
    {
        $lang_item = $this->lang_item($langcode);
        if(!$lang_item)
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

        if ($query->num_rows() === 1)
        {
            return $this->_cache_default = $query->row();
        }

        return FALSE;
    }

    /**
     * @param string $type
     * @return CI_DB_query_builder
     */
    private function _select_join($type = 'INNER')
    {
        return $this->db->select([
                $this->_table . '.id AS ' . $this->db->protect_identifiers('languages_id'),
                $this->_table . '.pos',
                $this->_table . '.is_default',
                $this->_table_supports . '.*',
            ])
            ->distinct()
            ->join(
                $this->_table_supports,
                $this->_table_supports . '.id = ' . $this->_table . '.' . $this->_fk_languages_supports_id,
                $type
            );
    }
}
