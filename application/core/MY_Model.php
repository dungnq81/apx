<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * A base model to provide the basic CRUD actions for all models that inherit from it.
 *
 * Class MY_Model
 *
 * @property MY_Loader $load
 * @property CI_DB_mysql_driver|CI_DB_query_builder $db
 * @property MY_Form_validation $form_validation
 * @property MY_Config $config
 */
abstract class MY_Model extends CI_Model
{
	/**
	 * The database table to use, only set if you want to bypass the magic.
	 *
	 * @var string
	 */
	protected $_table;

	/**
	 * The primary key, by default set to `id`, for use in some functions.
	 *
	 * @var string
	 */
	protected $_primary_key = 'id';

	/**
	 * An array of functions to be called before a record is created.
	 *
	 * @var array
	 */
	protected $_before_create = [];

	/**
	 * An array of functions to be called after a record is created.
	 *
	 * @var array
	 */
	protected $_after_create = [];

    /**
     * Skip the validation
     *
     * @var bool
     */
    protected $skip_validation = FALSE;

	/**
	 * An array of validation rules
     * https://www.codeigniter.com/user_guide/libraries/form_validation.html
	 *
	 * @var array
	 */
	public $validate = [];

    /**
     * define const
     */
    const CREATE = 'create';
    const EDIT = 'edit';
    const REMOVE = 'remove';

    /**
     * MY_Model constructor.
     */
	public function __construct()
	{
		parent::__construct();

		$this->load->helper('inflector');
		$this->_fetch_table();
	}

	/**
	 * @param $method
	 * @param $arguments
	 *
	 * @return $this|mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments)
	{
		if (is_callable([$this->db, $method]))
		{
			$result = call_user_func_array([$this->db, $method], $arguments);
			if (is_object($result) && $result === $this->db)
			{
				return $this;
			}

			return $result;
		}

		throw new Exception("class '" . get_class($this) . "' does not have a method '" . $method . "'");
	}

	/**
	 * Get table name
	 *
	 * @param boolean $prefix Whether the table name should be prefixed or not.
	 *
	 * @return string
	 */
	public function table_name($prefix = TRUE)
	{
		return $prefix ? $this->db->dbprefix($this->_table) : $this->_table;
	}

	/**
	 * Set table name
	 *
	 * @param string $name The name for the table.
	 *
	 * @return string
	 */
	public function set_table_name($name = NULL)
	{
		return $this->_table = $name;
	}

	/**
	 * Get records by creating a WHERE clause with a value for your primary key.
	 *
	 * @param string $id The value of your primary key
	 * @return CI_DB_result
	 */
	public function get($id)
	{
	    return $this->db
            ->where($this->_primary_key, $id)
            ->get($this->_table);
	}

	/**
	 * Get records by creating a WHERE clause with the key of $key and the value of $val.
	 *
	 * The function accepts ghost parameters, fetched via func_get_args().
	 * Those are:
	 *  1. string `$key` The key to search by.
	 *  2. string `$value` The value of that key.
	 *
	 * They are used in the query in the where statement something like:
	 *   <code>[...] WHERE {$key}={$value} [...]</code>
	 *
     *
     * Get a single record : ->row()
     * Get many records : ->result()
	 * @return CI_DB_result
	 */
	public function get_by()
	{
		$where = func_get_args();
		$this->_set_where($where);

		return $this->db->get($this->_table);
	}

	/**
	 * Returns a result array of many result objects.
	 *
	 * The function accepts ghost parameters, fetched via func_get_args().
	 * Those are:
	 *  1. string `$key` The key to search by.
	 *  2. string `$value` The value of that key.
	 *
	 * They are used in the query in the where statement something like:
	 *   <code>[...] WHERE {$key}={$value} [...]</code>
	 *
	 * @author Phil Sturgeon
	 * @return int
	 */
	public function count_by()
	{
		$where = func_get_args();
		$this->_set_where($where);

		return $this->db->count_all_results($this->_table);
	}

	/**
	 * Get all records in the database
     *
	 * @return int
	 */
	public function count_all()
	{
		return $this->db->count_all($this->_table);
	}

    /**
     * Insert a new record into the database, calling the before and after create callbacks.
     *
     * @param array $data Information
     * @param boolean $skip_validation Whether we should skip the validation of the data.
     * @param null $escape
     * @return integer|bool The insert ID
     */
	public function insert($data, $skip_validation = FALSE, $escape = NULL)
	{
		if ($skip_validation === FALSE)
		{
            if (!$this->_run_validation($data))
			{
				return FALSE;
			}
		}

		$data = $this->_run_before_create($data);
		$this->db->insert($this->_table, $data, $escape);
		$this->_run_after_create($data, $this->db->insert_id());

        $this->skip_validation = FALSE;
		return $this->db->insert_id();
	}

    /**
     * Insert multiple rows at once.
     *
     * Similar to insert(), just passing an array to insert multiple rows at
     * once.
     *
     * @param array $data Array of arrays to insert
     * @param boolean $skip_validation Whether we should skip the validation of the data.
     * @param null $escape
     * @return array An array of insert IDs.
     */
	public function insert_many($data, $skip_validation = FALSE, $escape = NULL)
	{
		$ids = [];
		foreach ($data as $row)
		{
			if ($skip_validation === FALSE)
			{
				if (! $this->_run_validation($row))
				{
					$ids[] = FALSE;
					continue;
				}
			}

			$row = $this->_run_before_create($row);
			$this->db->insert($this->_table, $row, $escape);
			$this->_run_after_create($row, $this->db->insert_id());

            $this->skip_validation = FALSE;
			$ids[] = $this->db->insert_id();
		}

		return $ids;
	}

	/**
	 * Update a record, specified by an ID.
	 *
	 * @author Jamie Rumbelow
	 *
	 * @param integer $primary_value The primary key basically the row's ID.
	 * @param array $data The data to update.
	 * @param boolean $skip_validation Whether we should skip the validation of the data.
	 *
	 * @return boolean
	 */
	public function update($primary_value, $data, $skip_validation = FALSE)
	{
		if ($skip_validation === FALSE)
		{
			if (! $this->_run_validation($data))
			{
				return FALSE;
			}
		}

        $this->skip_validation = FALSE;
        return $this->db->update($this->_table, $data, [$this->_primary_key => $primary_value]);
	}

	/**
	 * Update a record, specified by $key and $val.
	 *
	 * The function accepts ghost parameters, fetched via func_get_args().
	 * Those are:
	 *  1. string `$key` The key to update with.
	 *  2. string `$value` The value to match.
	 *  3. array  `$data` The data to update with.
	 * The first two are used in the query in the where statement something like:
	 *   <code>UPDATE {table} SET {$key}={$data} WHERE {$key}={$value}</code>
	 *
	 * @author Jamie Rumbelow
	 * @return boolean
	 */
	public function update_by()
	{
		$args = func_get_args();
		$data = array_pop($args);
		$this->_set_where($args);

		if (! $this->_run_validation($data))
		{
			return FALSE;
		}

        $this->skip_validation = FALSE;
		return $this->db->update($this->_table, $data);
	}

	/**
	 * Updates many records, specified by an array of IDs.
	 *
	 * @param array $primary_values The array of IDs
	 * @param array $data The data to update
	 * @param boolean $skip_validation Whether we should skip the validation of the data.
	 *
	 * @return boolean
	 */
	public function update_many($primary_values, $data, $skip_validation = FALSE)
	{
		if ($skip_validation === FALSE)
		{
			if (! $this->_run_validation($data))
			{
				return FALSE;
			}
		}

        $this->skip_validation = FALSE;
		return $this->db
            ->where_in($this->_primary_key, $primary_values)
            ->update($this->_table, $data);
	}

    /**
     * Updates all records
     *
     * @param $key
     * @param string $value
     * @return bool
     */
	public function update_all($key, $value = '')
	{
		return $this->db->update($this->_table, [$key => $value]);
	}

    /**
     * Delete a row from the database table by ID.
     *
     * @param $id
     * @return mixed
     */
	public function delete($id)
	{
		return $this->db->delete($this->_table, [$this->_primary_key => $id]);
	}

	/**
	 * Delete a row from the database table by the key and value.
	 *
	 * @author Phil Sturgeon
	 * @return bool
	 */
	public function delete_by()
	{
		$where = func_get_args();
		$this->_set_where($where);

		return $this->db->delete($this->_table);
	}

	/**
	 * Delete many rows from the database table by an array of IDs passed.
	 *
	 * @param array $primary_values
	 * @return bool
	 */
	public function delete_many($primary_values)
	{
		return $this->db
            ->where_in($this->_primary_key, $primary_values)
            ->delete($this->_table);
	}

	/**
	 * Generate the dropdown options.
	 *
	 * @return array The options for the dropdown.
	 */
	public function dropdown()
	{
		$args = func_get_args();
		if (count($args) == 2)
		{
			list($key, $value) = $args;
		}
		else
		{
			$key = $this->_primary_key;
			$value = $args[0];
		}

		$query = $this->db
			->select([$key, $value])
			->get($this->_table);

		$options = [];
		foreach ($query->result() as $row)
		{
			$options[$row->{$key}] = $row->{$value};
		}

		return $options;
	}

	/**
	 * Orders the result set by the criteria, using the same format as CodeIgniter's AR library.
	 *
	 * @author Jamie Rumbelow
	 *
	 * @param string $criteria The criteria to order by
	 * @param string $order the order direction
	 *
	 * @return \MY_Model
	 */
	public function order_by($criteria, $order = 'ASC')
	{
		$this->db->order_by($criteria, $order);
		return $this;
	}

	/**
	 * Limits the result set.
	 *
	 * Pass an integer to set the actual result limit.
	 * Pass a second integer set the offset.
	 *
	 * @author Jamie Rumbelow
	 *
	 * @param int $limit The number of rows
	 * @param int $offset The offset
	 *
	 * @return \MY_Model
	 */
	public function limit($limit, $offset = 0)
	{
		$limit = func_get_args();
		$this->_set_limit($limit);

		return $this;
	}

	/**
	 * Removes duplicate entries from the result set.
	 *
	 * @author Phil Sturgeon
	 * @return \MY_Model
	 */
	public function distinct()
	{
		$this->db->distinct();
		return $this;
	}

	/**
	 * Run validation only using the
	 * same rules as insert/update will
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validate($data)
	{
		return $this->_run_validation($data);
	}

	/**
	 * Return only the keys from the validation array
	 *
	 * @return array
	 */
	public function fields()
	{
		$keys = [];
		if ($this->validate)
		{
			foreach ($this->validate as $key)
			{
				$keys[] = $key['field'];
			}
		}

		return $keys;
	}

	/**
	 * @param int $length
	 *
	 * @return string with safe characters
	 * @throws Exception
	 */
	public function salt($length = 32)
	{
		return $this->safe_characters(salt($length));
	}

	/**
	 * safe characters
	 *
	 * @param $key
	 *
	 * @return null|string
	 */
	public function safe_characters($key)
	{
		// If enable query strings is set, then we need to replace any unsafe characters so that the code can still work
		if ($key != '' && $this->config->item('permitted_uri_chars') != '' && $this->config->item('enable_query_strings') == FALSE)
		{
			// preg_quote() in PHP 5.3 escapes -, so the str_replace() and addition of - to preg_quote() is to maintain backwards
			// compatibility as many are unaware of how characters in the permitted_uri_chars will be parsed as a regex pattern
			if (! preg_match("|^[" . str_replace(['\\-', '\-'], '-', preg_quote($this->config->item('permitted_uri_chars'), '-')) . "]+$|i", $key))
			{
				return preg_replace("/[^" . $this->config->item('permitted_uri_chars') . "]+/i", "-", $key);
			}
		}

		return $key;
	}

	/**
	 * @param $table
	 * @param $data
	 *
	 * @return array
	 */
	public function filter_data($table, $data)
	{
		$filtered_data = [];
		$columns = $this->db->list_fields($table);

		if (is_array($data))
		{
			foreach ($columns as $column)
			{
				if (array_key_exists($column, $data))
				{
					$filtered_data[$column] = $data[$column];
				}
			}
		}

		return $filtered_data;
	}

    /**
     * Fetches the table from the pluralised model name.
     *
     * @author Jamie Rumbelow
     */
    private function _fetch_table()
    {
        if ($this->_table == NULL)
        {
            $class = preg_replace('/(_m|_model|_class|_cl)?$/', '', get_class($this));
            $this->_table = plural(strtolower($class));
        }
    }

    /**
     * Sets where depending on the number of parameters
     *
     * @author Phil Sturgeon
     *
     * @param array $params
     */
    private function _set_where($params)
    {
        if (count($params) == 1)
        {
            $this->db->where($params[0]);
        }
        else
        {
            $this->db->where($params[0], $params[1]);
        }
    }

    /**
     * Sets limit depending on the number of parameters
     *
     * @param array $params
     */
    private function _set_limit($params)
    {
        if (count($params) == 1)
        {
            if (is_array($params[0]))
            {
                $this->db->limit($params[0][0], $params[0][1]);
            }
            else
            {
                $this->db->limit($params[0]);
            }
        }
        else
        {
            $this->db->limit((int) $params[0], (int) $params[1]);
        }
    }

    /**
     * Runs the before create actions.
     *
     * @author Jamie Rumbelow
     *
     * @param array $data The array of actions
     *
     * @return mixed
     */
    private function _run_before_create($data)
    {
        foreach ($this->_before_create as $method)
        {
            $data = call_user_func_array([$this, $method], [$data]);
        }

        return $data;
    }

    /**
     * Runs the after create actions.
     *
     * @author Jamie Rumbelow
     *
     * @param array $data The array of actions
     * @param int $id
     */
    private function _run_after_create($data, $id)
    {
        foreach ($this->_after_create as $method)
        {
            call_user_func_array([$this, $method], [$data, $id]);
        }
    }

    /**
     * Runs validation on the passed data.
     *
     * @author Dan Horrigan
     * @author Jerel Unruh
     *
     * @param array $data
     *
     * @return boolean
     */
    private function _run_validation($data)
    {
        if ($this->skip_validation)
        {
            return TRUE;
        }

        if (empty($this->validate))
        {
            return TRUE;
        }

        $this->load->library('form_validation');

        // only set the model if it can be used for callbacks
        if ($class = get_class($this) AND $class !== 'MY_Model')
        {
            // make sure their MY_Form_validation is set up for it
            if (method_exists($this->form_validation, 'set_model'))
            {
                $this->form_validation->set_model($class);
            }
        }

        $this->form_validation->set_data($data);
        if (is_array($this->validate))
        {
            $this->form_validation->set_rules($this->validate);
            return $this->form_validation->run();
        }

        return $this->form_validation->run($this->validate);
    }
}
