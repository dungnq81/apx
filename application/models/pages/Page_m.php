<?php

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Page_m
 *
 * @property CI_Input $input
 * @property CI_URI $uri
 *
 * @property Entity_m $entity_m
 * @property Dcrypto $dcrypto
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

    // For callback
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
     * @param $input
     * @param null $lang
     *
     * @return array|bool
     */
    private function _filter_data($input, $lang = NULL)
    {
        if(!isset($input['entities_id']))
        {
            return FALSE;
        }

        $lang_code = $lang ? $lang : LANG;

        $_array = [
            'pid' => isset($input['pid']) ? (int)$input['pid'] : NULL,
            'languages_id' => $lang ? $lang : LANG, // @todo sai. get id
            'entities_id' => (int)$input['entities_id'],
            'pages_layouts_id' => (int)$input['pages_layouts_id'],
            'ip_address' => ip_address(),
            'title' => $input['title'],
            'title_label' => isset($input['title_label']) ? $input['title_label'] : NULL,
            'meta_append_name' => !empty($input['meta_append_name']) ? 1 : 0,
            'slug' => isset($input['slug']) ? url_title($input['slug']) : url_title($input['title']),
            'uri' => NULL,
            'short' => isset($input['short']) ? $input['short'] : NULL,
            'status' => $input['status'],
            'content' => $input['content'],
            'comment_enabled' => !empty($input['comment_enabled']) ? 1 : 0,
            'rss_enabled' => !empty($input['rss_enabled']) ? 1 : 0,
            'meta_title' => isset($input['meta_title']) ? $input['meta_title'] : '',
            'meta_description' 	=> isset($input['meta_description']) ? $input['meta_description'] : '',
            'canonical_url' => isset($input['canonical_url']) ? $input['canonical_url'] : NULL,
            'redirect_url' => isset($input['redirect_url']) ? $input['redirect_url'] : NULL,
        ];

        return $this->filter_data($this->_table, $_array);
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
     * Create a new page
     *
     * @param array $input The page data to insert.
     * @return bool|int
     */
    public function create(&$input)
    {
        $input['alias'] = $this->_table;
        $input['created_on'] = now();
        $input['updated_on'] = 0;
        $input['published_on'] = strtotime(str_replace('/', '-', $input['published_on']));
        if(!is_empty($input['restricted_password']))
        {
            $this->load->library('dcrypto');
            try {
                $input['restricted_key'] = $this->dcrypto->generate_key();
                $input['restricted_password'] = $this->dcrypto->encrypt($input['restricted_password'], $input['restricted_key']);
            } catch (EnvironmentIsBrokenException $ebe) {
                // print errors
                show_error("ERROR: " . $ebe->getMessage() . " (" . $ebe->getCode() . ")");
            } catch (BadFormatException $bfe) {
                show_error("ERROR: " . $bfe->getMessage() . " (" . $bfe->getCode() . ")");
            }
        }

        $entities_id = $this->entity_m->create($input);
        if($entities_id)
        {
            $this->db->trans_start();
            $input['entities_id'] = $entities_id;

            // @todo chưa check slug
            $id = $this->insert($this->_filter_data($input));

            if (!$id) return FALSE;
            $this->db->trans_complete();
            return ($this->db->trans_status() === FALSE) ? FALSE : $id;
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
            $this->_table . '.*',
            $this->_table . '.id AS ' . $this->db->protect_identifiers('pages_id'),
            $this->_table_layouts . '.slug AS ' . $this->db->protect_identifiers('layout_slug'),
            $this->_table_layouts . '.title AS ' . $this->db->protect_identifiers('layout_title'),
            $this->_table_entities . '.*',
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
