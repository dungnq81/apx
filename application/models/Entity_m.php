<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Entity_m
 */
class Entity_m extends MY_Model
{
    /**
     * Entity_m constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_init();
    }

    /**
     * _init
     */
    private function _init() {}

    /**
     * @param $input
     * @return array
     */
    private function _filter_data($input)
    {
        $_array = [
            'alias' => isset($input['alias']) ? $input['alias'] : NULL,
            'controller' => isset(ci()->controller) ? ci()->controller : NULL,
            'pos' => (int)$input['pos'],
            'css' => !empty($input['css']) ? escape_css($input['css']) : NULL,
            'js' => !empty($input['js']) ? escape_js($input['js']) : NULL,
            //'img' => isset($input['img']) ? $input['img'] : NULL,
            //'img_social' => isset($input['img_social']) ? $input['img_social'] : NULL,
            'created_on' => (int)$input['created_on'],
            'updated_on' => (int)$input['updated_on'],
            'published_on' => (int)$input['published_on'],
            'restricted_key' => !empty($input['restricted_key']) ? $input['restricted_key'] : NULL,
            'restricted_password' => !empty($input['restricted_password']) ? $input['restricted_password'] : NULL,
            'meta_noindex' => !empty($input['meta_noindex']) ? 1 : 0,
            'meta_nofollow' => !empty($input['meta_nofollow']) ? 1 : 0,
            'meta_noarchive' => !empty($input['meta_noarchive']) ? 1 : 0,
            'css_class_wrap' => !empty($input['css_class_wrap']) ? sanitize_html_class($input['css_class_wrap']) : NULL,
            'title_copy' => isset($input['title']) ? $input['title'] : NULL,
        ];

        return $this->filter_data($this->_table, $_array);
    }

    /**
     * @param $input
     * @return bool|int
     */
    public function create($input)
    {
        $this->db->trans_start();
        $id = $this->insert($this->_filter_data($input));

        // did it pass validation?
        if (!$id) return FALSE;

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : (ci()->entities_id = $id);
    }
}
