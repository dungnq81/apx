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
 * @property Pages_layout_m $pages_layout_m
 * @property Users_log_m $users_log_m
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
            'rules' => 'trim|alpha_dash|max_length[250]|callback__check_slug_callback',
        ],
        [
            'field' => 'img',
            'label' => 'Ảnh đại diện',
            'rules' => 'regex_match[/^.+?\.(png|PNG|jpg|JPG|jpeg|JPEG|gif|GIF)$/]',
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
            'rules' => 'trim'
        ],
        [
            'field' => 'img_social',
            'label' => 'Social Image',
            'rules' => 'regex_match[/^.+?\.(png|PNG|jpg|JPG|jpeg|JPEG|gif|GIF)$/]',
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
     * @param null $action
     *
     * @return array|bool
     */
    private function _filter_data($input, $action = NULL)
    {
        if(!isset($input['entities_id']))
        {
            return FALSE;
        }

        $_array = [
            'pid' => !empty($input['pid']) ? (int)$input['pid'] : NULL,
            'languages_id' => (int)$input['languages_id'],
            'entities_id' => (int)$input['entities_id'],
            'pages_layouts_id' => (int)$input['pages_layouts_id'],
            'ip_address' => ip_address(),
            'title' => $input['title'],
            'title_label' => !empty($input['title_label']) ? $input['title_label'] : NULL,
            'meta_append_name' => !empty($input['meta_append_name']) ? 1 : 0,
            'slug' => !empty($input['slug']) ? trim($input['slug']) : url_title($input['title']),
            'uri' => NULL,
            'short' => !empty($input['short']) ? strip_all_tags($input['short']) : NULL,
            'status' => $input['status'],
            'content' => $input['content'],
            'comment_enabled' => !empty($input['comment_enabled']) ? 1 : 0,
            'rss_enabled' => !empty($input['rss_enabled']) ? 1 : 0,
            'meta_title' => !empty($input['meta_title']) ? $input['meta_title'] : $input['title'],
            'meta_description' 	=> !empty($input['meta_description']) ? html_excerpt($input['meta_description'], 320, '...') : $input['title'],
            'canonical_url' => !empty($input['canonical_url']) ? $input['canonical_url'] : NULL,
            'redirect_url' => !empty($input['redirect_url']) ? $input['redirect_url'] : NULL,
        ];

        if(empty($action) OR $action == self::CREATE)
        {

        }

        // check slug
        if(empty($input['slug']))
        {
            $_array['slug'] = $this->_sibling_slug($_array['slug'], $_array['pid']);
            // @todo sai
        }

        return $this->filter_data($this->_table, $_array);
    }

    /**
     * Build a lookup - update page uri
     *
     * @param int $id The id of the page to build the lookup for.
     * @return bool
     */
    public function build_lookup($id)
    {
        $current_id = $id;
        $segments = [];
        do
        {
            $page = $this->db
                ->select('slug, pid')
                ->where('id', $current_id)
                ->get($this->_table)
                ->row();

            $current_id = $page->pid;
            array_unshift($segments, $page->slug);
        }
        while ($page->pid > 0);

        return $this->update($id, ['uri' => implode('/', $segments)], TRUE);
    }

    /**
     * @param $slug
     * @param null $pid
     * @param int $current_id
     *
     * @return string
     */
    private function _sibling_slug($slug, $pid = NULL, $current_id = 0)
    {
        // See if this slug exists already
        if(! $this->_unique_slug($slug, $pid, (int) $current_id))
        {
            return $slug;
        }

        $i = 1;
        $flag = FALSE;
        $new_slug = $slug;
        while ($flag == FALSE)
        {
            $new_slug = $slug . '_' . $i;
            if(! $this->_unique_slug($new_slug, $pid, (int) $current_id))
            {
                $flag = TRUE;
            }
            $i++;
        }

        return $new_slug;
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
        $query = $this->db
            ->where($this->_table . '.id', $id)
            ->get($this->_table, 1);

        if($query->num_rows() < 1)
        {
            return FALSE;
        }

        return $query->row();
    }

    /**
     * Create a new page
     *
     * @param array $input The page data to insert.
     * @return bool|int
     *
     * @throws Exception
     */
    public function create(&$input)
    {
        $input['alias'] = $this->_table;
        $input['created_on'] = now();
        $input['updated_on'] = 0;
        $input['published_on'] = strtotimetz(str_replace('/', '-', $input['published_on']));
        if(!is_empty($input['restricted_password']))
        {
            $pass = $this->_password_generator($input['restricted_password']);
            $input['restricted_key'] = $pass['key'];
            $input['restricted_password'] = $pass['password'];
        }

        $entities_id = $this->entity_m->create($input);
        if($entities_id)
        {
            $this->db->trans_start();

            $input['entities_id'] = (int)$entities_id;
            $id = $this->insert($this->_filter_data($input));

            // did it pass validation?
            if (!$id) return FALSE;

            // calc uri
            $this->build_lookup($id);

            $this->db->trans_complete();
            if($this->db->trans_status() === FALSE)
            {
                return FALSE;
            }

            if($this->config->item('users_logs', 'auth') == TRUE)
            {
                $this->load->model('users/users_log_m');
                $this->users_log_m->write_log($this->controller, $id, $this->method, "\"{$input['title']}\" created successful");
            }

            return ci()->pages_id = $id;
        }

        return FALSE;
    }

    /**
     * Update a Page
     *
     * @param int $id The ID of the page to update
     * @return bool
     * @throws Exception
     */
    public function edit($id, &$input)
    {
        // check page exist
        if (! $page = $this->get($id))
        {
            return FALSE;
        }

        $input['updated_on'] = now();
        $input['published_on'] = strtotimetz(str_replace('/', '-', $input['published_on']));

        // update password
        if(!is_empty($input['restricted_password']))
        {
            $pass = $this->_password_generator($input['restricted_password']);
            $input['restricted_key'] = $pass['key'];
            $input['restricted_password'] = $pass['password'];
        }

        $entity = $this->entity_m->edit($page->{$this->_fk_entities_id}, $input);
        if($entity)
        {
            $this->db->trans_start();

            // validate the data and update

        }
    }

    /**
     * @param $password
     * @return array
     */
    private function _password_generator($password)
    {
        $arr = [
            'key' => NULL,
            'password' => NULL,
        ];

        if(!is_empty($password))
        {
            $this->load->library('dcrypto');
            try {
                $arr['key'] = $this->dcrypto->generate_key();
                $arr['password'] = $this->dcrypto->encrypt($password, $arr['key']);
            } catch (EnvironmentIsBrokenException $ebe) {
                // print errors
                show_error("ERROR: " . $ebe->getMessage() . " (" . $ebe->getCode() . ")");
            } catch (BadFormatException $bfe) {
                show_error("ERROR: " . $bfe->getMessage() . " (" . $bfe->getCode() . ")");
            }
        }

        return $arr;
    }

    /**
     * Callback to check uniqueness of slug + parent
     *
     * @param $slug string to check
     * @return bool
     */
    public function _check_slug_callback($slug)
    {
        // This is only going to be set on Edit
        $page_id = $this->uri->segment(4);

        // This might be set if there is a page
        // NULL or interger format
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

            $this->form_validation->set_message(__FUNCTION__, sprintf('A page with the URL "%s" already exists in %s.', $url, $parent_folder));
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

    /**
     * @return CI_DB_query_builder
     */
    private function _select_join()
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
                'INNER'
            )
            ->join(
                $this->_table_layouts,
                $this->_table_layouts . '.id = ' . $this->_table . '.' . $this->_fk_pages_layouts_id,
                'INNER'
            );
    }
}
