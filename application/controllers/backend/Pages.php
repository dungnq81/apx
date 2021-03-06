<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

/**
 * Class Pages
 *
 * @property Template $template
 * @property MY_Loader $load
 *
 * @property Page_m $page_m
 * @property Pages_layout_m $pages_layout_m
 */
class Pages extends Admin_Controller {
	/**
	 * The current active section
	 *
	 * @var string
	 */
	protected $section = 'pages';

	/**
	 * Pages constructor.
	 *
	 * @throws Asset_Exception
	 */
	public function __construct() {
		parent::__construct();

		// @todo load library
		// Load the required classes
		$this->load->models( [ 'pages/page_m', 'pages/pages_layout_m' ] );
	}

	/**
	 * Index methods, lists all pages
	 */
	public function index() {
		// The user needs to be able to view pages.
		role_or_die( 'pages', 'index_page' );
		$this->template
			->title( __( 'pages_index_name' ) )
			->build( 'pages/admin/index' );
	}

	/**
	 * Create a new page
	 *
	 * @throws Exception
	 */
	public function add() {
		// The user needs to be able to add pages.
		role_or_die( 'pages', 'add_page' );

		// validation the callbacks in page_m
		$this->form_validation->set_model( 'page_m' );
		$this->form_validation->set_rules( $this->page_m->validate );

		// Run our compiled validation
		if ( $this->form_validation->run() )
		{
			$input = $this->input->post();

			// do they have permission to proceed?
			if ( $input['status'] == 'live' )
			{
				role_or_die( 'pages', 'live_page' );
			}

			// Insert the page data
			if ( $id = $this->page_m->create( $input ) )
			{
				$this->session->set_flashdata( 'success', 'Thêm trang mới thành công.' );

				// Redirect back to the form
				redirect( 'admin/pages/edit/' . $id );
			}
		}

		// Set some data that create forms will need
		$this->_form_data();

		// Load wysiwyg editor
		// Load codemirror
		$this->template
			->title( 'Thêm trang mới' )
			->append_metadata( $this->load->view( 'fragments/wysiwyg', [], TRUE ) )
			->append_metadata( $this->load->view( 'fragments/codemirror', [], TRUE ) )
			->build( 'pages/admin/add' );
	}

	/**
	 * Edit an existing page
	 *
	 * @param int $id The id of the page.
	 */
	public function edit( int $id = 0 ) {
		// We are lost without an id. Redirect to the pages add.
		$id OR redirect( 'admin/pages/add' );

		// @todo check invalid $id

		// The user needs to be able to edit pages.
		role_or_die( 'pages', 'edit_page' );

		// Set some data that create forms will need
		$this->_form_data();
		$this->template
			->title( 'Chỉnh sửa trang' )
			->append_metadata( $this->load->view( 'fragments/wysiwyg', [], TRUE ) )
			->append_metadata( $this->load->view( 'fragments/codemirror', [], TRUE ) )
			->build( 'pages/admin/edit' );
	}

	/**
	 * Delete a page.
	 *
	 * @param int $id The id of the page to delete.
	 */
	public function delete( $id = NULL ) {
		redirect( 'admin/pages' );
	}

	/**
	 * Sets up common form inputs.
	 * This is used in the creation forms.
	 */
	private function _form_data() {
		$_lang_options    = $this->language_m->array_for_select( 'code', 'name' );
		$_layout_options = $this->pages_layout_m->array_for_select( 'id', 'title' );

		// @todo load parent page

		// load page scripts
		$this->template
			->set( 'languages', $_lang_options )
			->set( 'layouts', $_layout_options )
			->append_js( [ 'admin/pages.js' ] );
	}

	/**
	 * ajax change language
	 */
	public function change_language() {
		$_code      = $this->input->post( 'code' );
		$_lang_item = $this->language_m->lang_item( $_code );
		if ( is_ajax_request() )
		{
			echo json_encode( [
				'flag' => $_lang_item->flag,
				'code' => $_code,
			] );
			exit();
			// @todo load parent page
		}
	}
}
