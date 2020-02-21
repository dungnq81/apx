<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

/**
 * Class Pages_layout_m
 */
class Pages_layout_m extends MY_Model {
	/**
	 * @var array
	 */
	private $_cache_layouts = [];

	/**
	 * Pages_layout_m constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->_init();
	}

	/**
	 * check default layout
	 */
	private function _init() {
		$query = $this->db
			->where( 'slug', __return_empty_string() )
			->get( $this->_table, 1 );

		if ( $query->num_rows() < 1 )
		{
			$dummy = [
				'title' => json_encode_uni( [
					'vi' => 'Giao diện mặc định',
					'en' => 'Default layout',
				] ),
				'slug'  => __return_empty_string(),
			];

			$this->insert( $dummy );
		}
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function slug( $id ) {
		return $this->get( $id )
		            ->row()
					->slug;
	}

	/**
	 * @param $id
	 * @param string $lang
	 *
	 * @return bool
	 */
	public function title( $id, $lang = '' ) {
		$query = $this->get( $id );
		if ( $query->num_rows() < 1 )
		{
			return FALSE;
		}

		$lang OR $lang = lang_code();

		return json_decode( $query->row()->title )->{$lang};
	}

	/**
	 * @return array|bool
	 */
	public function array_for_select() {
		$layouts = $this->layouts();
		$args    = func_get_args();
		$return  = [];

		switch ( count( $args ) )
		{
			case 2:
				foreach ( $layouts as $item )
				{
					$title = $this->title( $item->id );
					if ( 'title' == $args[0] )
					{
						$return[$title] = $item->{$args[1]};
					} else if ( 'title' == $args[1] )
					{
						$return[$item->{$args[0]}] = $title;
					}
				}
				break;

			default:
				return FALSE;
		}

		return $return;
	}

	/**
	 * Gets pages layouts based on the $where param.
	 *
	 * @param array|null $where
	 *
	 * @return array
	 */
	public function layouts( $where = NULL ) {
		if ( $this->_cache_layouts )
		{
			return $this->_cache_layouts;
		}
		if ( is_array( $where ) )
		{
			$this->db->where( $where );
		}

		$this->db->order_by( 'pos', 'DESC' );

		return $this->_cache_layouts = $this->db
			->get( $this->_table )
			->result();
	}
}
