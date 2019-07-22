<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Page_m
 *
 * @property CI_Input $input
 * @property CI_URI $uri
 */
class Page_m extends MY_Model
{
    /**
     * @var string
     */
    private $_fk_languages_id = 'languages_id';
    private $_fk_entities_id;
    private $_fk_pages_layouts_id;

    /**
     * @var string
     */
    private $_table_entities;
    private $_table_layouts;

    /**
     * Array containing the validation rules
     * https://www.codeigniter.com/user_guide/libraries/form_validation.html
     *
     * @var array
     */
    public $validate = [
        [
            'field' => 'title',
            'label' => 'Tiêu đề',
            'rules' => 'trim|required|max_length[250]',
        ],
        [
            'field' => 'slug',
            'label' => 'Slug',
            'rules' => 'trim|alpha_dash|max_length[250]|callback__check_slug',
        ],
        [
            'field' => 'img',
            'label' => 'Ảnh đại diện',
            'rules' => 'regex_match[/^.+?\.(png|PNG|jpg|JPG|jpeg|JPEG)$/]',
        ],
        [
            'field' => 'pos',
            'label' => 'Thứ tự',
            'rules' => 'numeric',
        ],
        [
            'field' => 'title_label',
            'label' => 'Tiêu đề thay thế',
            'rules' => 'trim|max_length[250]',
        ],
        [
            'field' => 'status',
            'label' => 'Status',
            'rules' => 'in_list[draft,live,hide]',
        ],
        [
            'field' => 'meta_title',
            'label' => 'Meta title',
            'rules' => 'trim|max_length[250]'
        ],
        [
            'field' => 'meta_description',
            'label' => 'Meta description',
            'rules' => 'trim|max_length[320]'
        ],
        [
            'field' => 'img_social',
            'label' => 'Social Image',
            'rules' => 'regex_match[/^.+?\.(png|PNG|jpg|JPG|jpeg|JPEG)$/]',
        ],
    ];

    // For custom field
    public $compiled_validate = [];

    /**
     * Page_m constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->models(['pages/pages_layout_m', 'entity_m']);

        // table name
        $this->_table_entities = $this->entity_m->table_name();
        $this->_table_layouts = $this->pages_layout_m->table_name();

        // fk
        $this->_fk_entities_id = $this->_table_entities . '_id';
        $this->_fk_pages_layouts_id = $this->_table_layouts . '_id';
    }

    /**
     * Get a page from the database.
     *
     * @param string $id
     * @return mixed|null
     */
    public function get($id)
    {
        $this->_select_join();
        $page = $this->db
            ->where($this->_table . '.id', $id)
            ->get($this->_table, 1)
            ->row();

        if (!$page)
        {
            return FALSE;
        }

        // save current-page vars
        $this->load->vars(['page' => $page]);
        return $page;
    }

    /**
     * Build a multi-array of parent > children.
     *
     * @param bool $check_lang
     * @return array
     */
    public function get_page_tree($check_lang = TRUE)
    {
        $this->db->select('id, pid, title, slug, active');
        if($check_lang == TRUE)
        {
            $this->db->where($this->_fk_languages_id, $this->language_m->lang_item(LANG)->id);
        }

        $all_pages = $this->db
            ->get($this->_table)
            ->result_array();

        // re-index the array
        foreach ($all_pages as $row)
        {
            $pages[$row['id']] = $row;
        }

        unset($all_pages);

        // Build a multidimensional array of parent > children.
        foreach ($pages as $row)
        {
            if (array_key_exists($row['pid'], $pages))
            {
                // Add this page to the children array of the parent page.
                $pages[$row['pid']]['children'][] =& $pages[$row['id']];
            }

            // This is a root page.
            if ($row['pid'] == 0)
            {
                $page_array[] =& $pages[$row['id']];
            }
        }

        return $page_array;
    }

    /**
     * @param string $type
     * @return CI_DB_query_builder
     */
    private function _select_join($type = 'INNER')
    {
        return $this->db->select([
            $this->_table . '.*',
            $this->_table . '.id AS ' . $this->db->protect_identifiers('pages_id'),
            $this->_table_layouts . '.slug AS ' . $this->db->protect_identifiers('layout_slug'),
            $this->_table_layouts . '.title AS ' . $this->db->protect_identifiers('layout_title'),
            $this->_table_entities . '.pos',
            $this->_table_entities . '.css',
            $this->_table_entities . '.js',
            $this->_table_entities . '.img',
            $this->_table_entities . '.img_social',
            $this->_table_entities . '.redirect_url',
            $this->_table_entities . '.created_on',
            $this->_table_entities . '.updated_on',
        ])
            ->distinct()
            ->join(
                $this->_table_entities,
                $this->_table_entities . '.id = ' . $this->_table . '.' . $this->_fk_entities_id,
                $type
            )
            ->join(
                $this->_table_layouts,
                $this->_table_entities . '.id = ' . $this->_table . '.' . $this->_fk_pages_layouts_id,
                $type
            );
    }

    /**
     * Callback to check uniqueness of slug + parent
     *
     *
     * @param $slug string to check
     * @return bool
     */
    public function _check_slug($slug)
    {
        // This is only going to be set on Edit
        $page_id = $this->uri->segment(4);

        // This might be set if there is a page
        $pid = $this->input->post('pid');

        // See if this slug exists already
        if ($this->_unique_slug($slug, $pid, (int) $page_id))
        {
            // Root Level
            if (empty($pid))
            {
                $parent_folder = 'the top level';
                $url = '/' . $slug;
            }
            else // Child of a Page (find by parent)
            {
                $parent = $this->get($pid);
                $url = $slug;
                $parent_folder = '/' . $parent->uri;
            }

            $this->form_validation->set_message('_check_slug', sprintf('A page with the URL "%s" already exists in %s.', $url, $parent_folder));
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check Slug for Uniqueness
     *
     * Slugs should be unique among sibling pages.
     *
     * @param string $slug The slug to check for.
     * @param int $pid The pid if any.
     * @param int $id The id of the page.
     *
     * @return bool
     */
    private function _unique_slug($slug, $pid, $id = 0)
    {
        return (bool) parent::count_by([
                'id !=' => $id,
                'slug' => $slug,
                'pid' => $pid
            ]) > 0;
    }
}
