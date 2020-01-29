<?php ( defined( 'BASEPATH' ) ) OR exit( 'No direct script access allowed' );

/**
 * Class MY_Loader
 */
class MY_Loader extends CI_Loader {
	/**
	 * Since parent::_ci_view_paths is protected we use this setter to allow
	 * things like plugins to set a view location.
	 *
	 * @param string $path
	 */
	public function set_view_path( $path ) {
		if ( is_array( $path ) )
		{
			// if we're restoring saved paths we'll do them all
			$this->_ci_view_paths = $path;
		} else
		{
			// otherwise we'll just add the specified one
			$this->_ci_view_paths = [ $path => TRUE ];
		}
	}

	/**
	 * Since parent::_ci_view_paths is protected we use this to retrieve them.
	 *
	 * @return array
	 */
	public function get_view_paths() {
		// return the full array of paths
		return $this->_ci_view_paths;
	}

	/**
	 * View Loader
	 *
	 * Loads "view" files.
	 *
	 * @param $path
	 * @param array $vars
	 * @param bool $return
	 *
	 * @return object
	 */
	public function view_with_path( $path, $vars = [], $return = FALSE ) {
		return $this->_ci_load( [
			'_ci_path'   => $path,
			'_ci_vars'   => $this->_ci_prepare_view_vars( $vars ),
			'_ci_return' => $return,
		] );
	}

	/**
	 * An alias for the library()
	 *
	 * @param $libraries
	 * @param null $params
	 * @param null $object_name
	 *
	 * @return object
	 */
	public function libraries( array $libraries = [], $params = NULL, $object_name = NULL ) {
		return $this->library( $libraries, $params, $object_name );
	}

	/**
	 * @param $models
	 * @param string $name
	 * @param bool $db_conn
	 *
	 * @return mixed
	 */
	public function models( array $models = [], $name = '', $db_conn = FALSE ) {
		return $this->model( $models, $name, $db_conn );
	}

	/**
	 * An alias for the language()
	 *
	 * @param $files
	 * @param string $lang
	 *
	 * @return object
	 */
	public function languages( array $files = [], $lang = '' ) {
		return $this->language( $files, $lang );
	}
}
