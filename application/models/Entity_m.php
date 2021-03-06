<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

/**
 * Class Entity_m
 *
 * @property MY_Upload $upload
 */
class Entity_m extends MY_Model {
	/**
	 * @var string
	 */
	private $_sub_folder = '/';

	/**
	 * Entity_m constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->load->library( 'Upload' );

		// set upload path
		$this->_sub_folder = '/' . date( 'Y' ) . '/' . date( 'm' ) . '/';
		$this->upload->set_upload_path( FCPATH . 'uploads' . $this->_sub_folder );
		$this->upload->set_thumbnail_path( FCPATH . 'thumbs' . $this->_sub_folder );

		$this->_init();
	}

	/**
	 * _init
	 */
	private function _init() {}

	/**
	 * @param array $input
	 * @param string $action - create|edit|empty
	 *
	 * @return array
	 */
	private function _filter_data( array $input = [], $action = NULL ) {
		$_array = [
			'pos'                 => (int) $input['pos'],
			'css'                 => ! empty( $input['css'] ) ? escape_css( $input['css'] ) : NULL,
			'js'                  => ! empty( $input['js'] ) ? escape_js( $input['js'] ) : NULL,
			'updated_on'          => (int) $input['updated_on'],
			'published_on'        => (int) $input['published_on'],
			'restricted_key'      => ! empty( $input['restricted_key'] ) ? $input['restricted_key'] : NULL,
			'restricted_password' => ! empty( $input['restricted_password'] ) ? $input['restricted_password'] : NULL,
			'meta_noindex'        => ! empty( $input['meta_noindex'] ) ? 1 : 0,
			'meta_nofollow'       => ! empty( $input['meta_nofollow'] ) ? 1 : 0,
			'meta_noarchive'      => ! empty( $input['meta_noarchive'] ) ? 1 : 0,
			'css_class_wrap'      => ! empty( $input['css_class_wrap'] ) ? sanitize_html_class( $input['css_class_wrap'] ) : NULL,
			'title_copy'          => isset( $input['title'] ) ? $input['title'] : NULL,
		];

		if ( empty( $action ) OR $action == self::CREATE )
		{
			$_array['alias']      = isset( $input['alias'] ) ? $input['alias'] : NULL;
			$_array['controller'] = isset( ci()->controller ) ? ci()->controller : NULL;
			$_array['created_on'] = (int) $input['created_on'];
		}

		return $this->filter_data( $this->_table, $_array );
	}

	/**
	 * @param $input
	 *
	 * @return bool|int
	 */
	public function create( array &$input ) {
		$this->db->trans_start();
		$id = $this->insert( self::_filter_data( $input ) );

		// did it pass validation?
		if ( ! $id )
		{
			return FALSE;
		}

		// images
		$_imgs = [
			'img'        => NULL,
			'img_social' => NULL,
		];
		foreach ( $_imgs as $key => &$value )
		{
			if ( isset( $_FILES[$key] ) AND $this->upload->do_upload( $key ) )
			{
				$_imgs[$key] = $value = $this->_sub_folder . $this->upload->data( 'file_name' );
			}
		}

		$this->update( $id, $_imgs );
		$this->db->trans_complete();
		return ( $this->db->trans_status() === FALSE ) ? FALSE : ( ci()->entities_id = $id );
	}

	/**
	 * @param $id
	 * @param $input
	 *
	 * @return bool
	 */
	public function edit( $id, $input ) {
		if ( ! $this->get( $id ) )
		{
			return FALSE;
		}

		$this->db->trans_start();
		$result = $this->update( $id, self::_filter_data( $input, self::EDIT ) );

		// did it pass validation?
		if ( ! $result )
		{
			return FALSE;
		}

		$this->db->trans_complete();
		return (bool) $this->db->trans_status();
	}
}
