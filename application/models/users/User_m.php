<?php

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class User_m
 *
 * @property Users_group_m $users_group_m
 * @property Users_log_m $users_log_m
 * @property Dcrypto $dcrypto
 */
class User_m extends MY_Model
{
    /**
     * @var string
     */
    private $_table_groups;
    private $_fk_groups_id;

    /**
     * @var int
     */
	private $_algorithm_password = PASSWORD_DEFAULT;
	private $_options_password = [];

	/**
	 * @var bool
	 */
	private $_unique_email = FALSE;
	private $_unique_phone = FALSE;

    /**
     * @var string|null
     */
	private $_recovery_privilege = TRUE;

    /**
     * @var array
     */
	protected $_cache_users = [];

	// ------------------------------------------

    /**
     * User_m constructor.
     *
     * @throws Exception
     */
	public function __construct()
	{
		parent::__construct();
        $this->load->config('auth', TRUE);
        $this->load->libraries(['form_validation', 'dcrypto']);
        $this->load->models(['permission_m', 'users/users_group_m', 'users/users_log_m']);

        $this->_table_groups = $this->users_group_m->table_name();
        $this->_fk_groups_id = $this->_table_groups . '_id';

		// unique_email
		if (is_bool($this->config->item('unique_email', 'auth')))
		{
			$this->_unique_email = $this->config->item('unique_email', 'auth');
		}

		// unique_phone
		if (is_bool($this->config->item('unique_phone', 'auth')))
		{
			$this->_unique_phone = $this->config->item('unique_phone', 'auth');
		}

		// PASSWORD_DEFAULT
		// Use the bcrypt algorithm (default as of PHP 5.5.0).
		// Note that this constant is designed to change over time as new and stronger algorithms are added to PHP.
		// For that reason, the length of the result from using this identifier can change over time.
		//$this->_algorithm_password = PASSWORD_DEFAULT;

		// PASSWORD_BCRYPT
		// The salt option has been deprecated as of PHP 7.0.0.
		/*$this->_algorithm_password = PASSWORD_BCRYPT;
		$this->_options_password = [
			'cost' => PASSWORD_BCRYPT_DEFAULT_COST
		];*/

		// PASSWORD_ARGON2I | PASSWORD_ARGON2ID
		// Argon2 passwords using PASSWORD_ARGON2I was added in PHP 7.2.0
		// Argon2 passwords using PASSWORD_ARGON2ID was added in PHP 7.3.0
		$this->_algorithm_password = PASSWORD_ARGON2ID;
		$this->_options_password = [
			'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
			'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
			'threads' => PASSWORD_ARGON2_DEFAULT_THREADS
		];

        // recovery
        if ($this->_recovery_privilege)
        {
            $this->_recovery_admin_privilege();
        }
	}

    // ------------------------------------------

    /**
     * @param null $username
     * @param null $password
     * @throws EnvironmentIsBrokenException
     */
    private function _recovery_admin_privilege($username = NULL, $password = NULL)
    {
        // get admin group
        $admin_group = $this->users_group_m->admin_group();

        // check administrator account
        $username OR $username = 'apx_admin';
        if ($this->user($username)->num_rows() < 1)
        {
            $password OR $password = '@apx_admin@';
            $dummy = [
                'username' => $username,
                'password' => base64_encode($this->_hash_password($password)),
                'private_key' => $this->dcrypto->generate_key(),
                'status' => 'active',
                'ip_address' => $this->input->ip_address(),
                'created_on' => now(),
                $this->_fk_groups_id => $admin_group->id,
                'verified_password' => 0,
            ];

            $this->insert($dummy);
        }
        else
        {
            $dummy = [];
            $user = $this->user($username)->row();
            if ($user->{$this->_fk_groups_id} != $admin_group->id)
            {
                $dummy[$this->_fk_groups_id] = $admin_group->id;
            }
            if (strcmp($user->status, 'active') !== 0)
            {
                $dummy['status'] = 'active';
            }
            if (! empty($password))
            {
                $dummy['password'] = base64_encode($this->_hash_password($password));
                $dummy['verified_password'] = 0;
            }

            if (! empty($dummy))
            {
                $this->update($user->id, $dummy);
            }
        }
    }

    // ------------------------------------------

    /**
     * @param $user_id
     * @return bool|mixed
     */
    public function user_session($user_id)
    {
        if(!is_numeric($user_id))
        {
            return FALSE;
        }

        $this->_select_join();
        $query = $this->db
            ->where($this->_table . '.id', $user_id)
            ->get($this->_table, 1);

        // check exist
        if ($query->num_rows() < 1)
        {
            return FALSE;
        }

        return $this->_cache_users[$user_id] = $query;
    }

	// ------------------------------------------

	/**
	 * Auto logs-in the user if they are remembered
	 *
	 * @return bool Whether the user is logged in
	 * @throws Exception
	 */
	public function logged_in()
	{
        $user_id = $this->session->userdata("user_id");
        if (!$user_id)
		{
			return $this->_login_remembered_user();
		}

		// check exist
        if (! $this->user_session($user_id))
        {
            $this->logout(FALSE);
            return FALSE;
        }

		return TRUE;
	}

    // ------------------------------------------

    /**
     * @return bool
     */
    private function _login_remembered_user()
    {
        // check for valid cookie
        $user_id = get_cookie('user_id');
        $remember_code = get_cookie('user_remember_code');
        if (empty($remember_code) OR ! $this->user_session($user_id))
        {
            return FALSE;
        }

        $user = $this->user($user_id)->row();
        if (strcmp($user->status, 'active') === 0 AND strcmp($user->remember_code, $remember_code) === 0)
        {
            // update last login time
            $this->_update_last_login($user);
            $this->session->set_userdata([
                'user_id' => $user_id,
                'user_login_time' => now(),
            ]);

            //extend the users cookies if the option is enabled
            if ($this->config->item('remember_extend_on_login', 'auth'))
            {
                $this->_remember_user($user);
            }

            return TRUE;
        }

        delete_cookie('user_id');
        delete_cookie('user_remember_code');

        return FALSE;
    }

    // ------------------------------------------

    /**
     * @param object $user
     */
    private function _remember_user(object $user)
    {
        if (isset($user->id))
        {
            set_cookie([
                'name' => 'user_id',
                'value' => $user->id,
                'expire' => $this->config->item('remember_expire', 'auth')
            ]);
            set_cookie([
                'name' => 'user_remember_code',
                'value' => $user->remember_code,
                'expire' => $this->config->item('remember_expire', 'auth')
            ]);
        }
    }

	// ------------------------------------------

	/**
	 * @param $identity
	 * @param $password
	 * @param bool $remember
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function login($identity, $password, $remember = FALSE)
	{
        if (! string_not_empty($identity) OR empty($password))
		{
			return FALSE;
		}

        $identity = $this->db->escape_str($identity);
		$where_c = sprintf('username = "%1$s"', $identity);
		if ($this->_unique_email)
		{
			$where_c = sprintf('(username = "%1$s" OR email = "%1$s")', $identity);
			if ($this->_unique_phone)
			{
				$where_c = sprintf('(username = "%1$s" OR email = "%1$s" OR phone = "%1$s")', $identity);
			}
		}
		elseif ($this->_unique_phone)
		{
			$where_c = sprintf('(username = "%1$s" OR phone = "%1$s")', $identity);
		}

		$query = $this->db->where($where_c)
			->where('status', 'active')
			->get($this->_table, 1);

		if ($query->num_rows() === 1)
		{
			$user = $query->row();

            // Verify stored hash against plain-text password
            if ($this->_verify_password($password, $user->id))
            {
                $this->_set_login($user, (bool) $remember);
                return TRUE;
            }
		}

		return FALSE;
	}

    // ------------------------------------------

    /**
     * Hashes the password to be stored in the database.
     *
     * @param $password
     *
     * @return bool|string
     */
    private function _hash_password($password)
    {
        if (empty($password))
        {
            return FALSE;
        }

        // default PASSWORD_DEFAULT
        return password_hash($password, $this->_algorithm_password, $this->_options_password);
    }

    // ------------------------------------------

    /**
     * @param $password
     * @param $user_id
     * @return bool
     */
	private function _verify_password($password, $user_id)
    {
        $user = $this->user($user_id)->row();
        if (! $user)
        {
            return FALSE;
        }

        $hash_password = base64_decode($user->password);
        // Verify stored hash against plain-text password
        if (password_verify($password, $hash_password))
        {
            // Check if a newer hashing algorithm is available, or the cost has changed
            if (password_needs_rehash($hash_password, $this->_algorithm_password, $this->_options_password))
            {
                $new_hash = $this->_hash_password($password);
                $this->update($user_id, ['password' => base64_encode($new_hash)]);
            }

            return TRUE;
        }

        return FALSE;
    }

	// ------------------------------------------

    /**
     * Used by login() function
     *
     * @param $user
     * @param bool $remember
     * @param bool $log
     * @throws Exception
     */
	private function _set_login($user, bool $remember = FALSE, $log = TRUE)
	{
		$this->_update_last_login($user);
		$this->session->set_userdata([
			'user_id' => $user->id,
            'user_login_time' => now(),
		]);

		if ($remember == TRUE && $this->config->item('remember_users', 'auth'))
		{
			if (empty($user->remember_code))
			{
				$this->update($user->id, ['remember_code' => $this->salt()]);
			}

			// save cookie
			$this->_remember_user($user);
		}

		// write logs
        if($log == TRUE AND $this->config->item('users_logs', 'auth') == TRUE)
        {
            $this->users_log_m->write_log($this->controller, $user->id, $this->method, "{$user->username} login successful");
        }
	}

	// ------------------------------------------

    /**
     * @param bool $log
     */
	public function logout($log = FALSE)
	{
		if($user_id = $this->session->userdata('user_id'))
        {
            $this->session->unset_userdata([
                'user_id',
                'user_login_time',
            ]);

            // delete the cookies if they exist
            delete_cookie('user_id');
            delete_cookie('user_remember_code');

            // remove remember code, remove on all devices
            $this->update($user_id, ['remember_code' => '']);

            // Destroy the current session when it is called
            $this->session->sess_destroy();

            // Re-create the session
            if (version_compare(PHP_VERSION, '7.0.0') >= 0)
            {
                session_start();
            }
            $this->session->sess_regenerate(TRUE);

            // logs
            if($log == TRUE AND $this->config->item('users_logs', 'auth') == TRUE)
            {
                $user = $this->user($user_id)->row();
                $this->users_log_m->write_log($this->controller, $user_id, $this->method, "{$user->username} logout successful");
            }
        }
	}

	// ------------------------------------------

	/**
	 * @param null $groups array group name
	 * @param null $limit
	 * @param null $offset
	 *
	 * @return CI_DB_result
	 */
	public function users($groups = [], $limit = NULL, $offset = NULL)
	{
	    // select join
	    $this->_select_join();

		// filter by groups id(s) if passed
		if (! empty($groups))
		{
			// build an array if only one role was passed
			if (! is_array($groups))
			{
				$groups = [$groups];
			}

			$this->db->where_in($this->_table_groups . '.name', $groups);
		}

		return $this->db->get($this->_table, $limit, $offset);
	}

	// ------------------------------------------

	/**
	 * @param null $id
	 *
	 * @return CI_DB_result
	 */
	public function user($id = NULL)
	{
		// Don't grab the user data again if we already have it
		if (is_numeric($id) and isset($this->_cache_users[$id]))
		{
			return $this->_cache_users[$id];
		}

		$pair = $this->_identity_pair($id);
		$this->db->where(sprintf('%s.%s', $this->_table, $pair['identity']), $pair['value']);
		$this->db->limit(1);

		$user = $this->users();
		if (is_numeric($id))
		{
			$this->_cache_users[$id] = $user;
		}

		// the user disappeared for a moment?
		if ($user->num_rows() < 1 && $pair['user_is_current'])
		{
			log_message('error', sprintf('End user session - reason: Could not find a user identified by %s:%s', [$pair['identity'], $pair['value']]));
			$this->session->sess_destroy();
		}

		return $user;
	}

	// ------------------------------------------

    /**
     * @param $id
     * @param array $data
     * @return bool
     */
	public function update_user($id, array $data = [])
	{
        $this->db->trans_start();

        // Filter the data passed
        $data = $this->filter_data($this->_table, $data);

		// username
		if (array_key_exists('username', $data) AND ! $this->username_update_check($id, $data['username']))
		{
            unset($data['username']);
		}

		// email
		if (array_key_exists('email', $data) AND ! $this->email_update_check($id, $data['email']))
		{
            unset($data['email']);
		}

		// phone
		if (array_key_exists('phone', $data))
		{
		    if(! $this->phone_update_check($id, $data['phone']))
            {
                unset($data['phone']);
            }
            else
            {
                $data['verified_phone'] = verified_phone($data['phone']);
            }
		}

        // check group
        if (array_key_exists($this->_fk_groups_id, $data))
        {
            // check role exist
            if(! $this->users_group_m->group($data[$this->_fk_groups_id]))
            {
                // unset group id
                unset($data[$this->_fk_groups_id]);
            }
        }

		if (array_key_exists('password', $data))
		{
			if (! empty($data['password']))
			{
				$data['password'] = base64_encode($this->_hash_password($data['password']));
			}
			else
			{
				// unset password so it doesn't effect database entry if no password passed
				unset($data['password']);
			}
		}

		$this->update($id, $data);
        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : TRUE;
	}

	// ------------------------------------------

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function delete_user($id)
	{
		$this->db->trans_start();

		// delete user from users table should be placed after remove from group
		$this->delete($id);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : TRUE;
	}

	// ------------------------------------------

	/**
	 * @param $username
	 * @param $password
	 * @param bool $email
	 * @param bool $phone
	 * @param bool $group
	 *
	 * @return bool|int
	 * @throws Exception
	 */
	public function add_user($username, $password, $email = FALSE, $phone = FALSE, $group = FALSE)
	{
		$this->db->trans_start();
        $default_group = $this->users_group_m->default_group();

		// is array
		if (is_array($username))
		{
			// Filter the data passed
			$data = $this->filter_data($this->_table, $username);
			if (! array_key_exists('username', $data) OR ! $this->username_check($data['username']))
			{
				return FALSE;
			}
			if (! array_key_exists('password', $data) OR empty($data['password']))
			{
				return FALSE;
			}
			if (! array_key_exists('phone', $data) OR ! $this->phone_check($data['phone']))
			{
                return FALSE;
			}
			if (! array_key_exists('email', $data) OR ! $this->email_check($data['email']))
			{
				return FALSE;
			}

			// check group
			if (! array_key_exists($this->_fk_groups_id, $data))
			{
				$data[$this->_fk_groups_id] = $default_group->id;
			}
			else if (! $this->users_group_m->group($data[$this->_fk_groups_id]))
            {
                return FALSE;
            }

			if(array_key_exists('phone', $data))
            {
                $data['verified_phone'] = verified_phone($data['phone']);
            }

			$data['password'] = base64_encode($this->_hash_password($data['password']));
            $data['private_key'] = $this->dcrypto->generate_key();
			$insert_id = $this->insert($data);

            $this->db->trans_complete();
            return ($this->db->trans_status() === FALSE) ? FALSE : $insert_id;
		}

		//
		// is string
		//
		if (empty($password) OR ! $this->username_check($username) OR ! $this->email_check($email) OR ! $this->phone_check($phone))
		{
			return FALSE;
		}

		$dummy = [
			'username' => $username,
			'password' => base64_encode($this->_hash_password($password)),
            'private_key' => $this->dcrypto->generate_key(),
			'email' => $email,
			'phone' => $phone,
			'verified_phone' => verified_phone($phone),
			'ip_address' => $this->input->ip_address(),
			'created_on' => now(),
			'verified_password' => 0,
		];

		// check group
		if (empty($group))
		{
			$dummy[$this->_fk_groups_id] = $default_group->id;
		}
		else
		{
		    if(! $this->users_group_m->group($group))
            {
                return FALSE;
            }

            $dummy[$this->_fk_groups_id] = $this->users_group_m->group($group)->row()->id;
		}

		$insert_id = $this->insert($dummy);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $insert_id;
	}

	// ------------------------------------------

	/**
	 * @param int $limit
	 * @param null $groups
	 *
	 * @return CI_DB_result
	 */
	public function recent_users($limit = 10, $groups = NULL)
	{
		return $this->list_users_order($limit, $groups, 'last_login_time');
	}

	// ------------------------------------------

	/**
	 * @param int $limit
	 * @param null $groups
	 *
	 * @return CI_DB_result
	 */
	public function newest_users($limit = 10, $groups = NULL)
	{
		return $this->list_users_order($limit, $groups, 'created_on');
	}

	// ------------------------------------------

	/**
	 * @param int $limit
	 * @param null $groups
	 * @param string $column
	 * @param string $orderby
	 *
	 * @return CI_DB_result
	 */
	public function list_users_order($limit = 10, $groups = NULL, $column = 'id', $orderby = 'DESC')
	{
		! empty($column) OR $column = 'id';
		! empty($orderby) OR $orderby = 'DESC';

		$this->db->order_by($this->_table . '.' . $column, $orderby);
		return $this->users($groups, $limit);
	}

	// ------------------------------------------

	/**
	 * @param null $limit
	 * @param null $offset
	 * @param null $groups
	 *
	 * @return CI_DB_result
	 */
	public function active_users($limit = NULL, $offset = NULL, $groups = NULL)
	{
		$this->db->where($this->_table . '.status', 'active');
		return $this->users($groups, $limit, $offset);
	}

	// ------------------------------------------

	/**
	 * @param null $limit
	 * @param null $offset
	 * @param null $groups
	 *
	 * @return CI_DB_result
	 */
	public function inactive_users($limit = NULL, $offset = NULL, $groups = NULL)
	{
		$this->db->where($this->_table . '.status', 'inactive');
		return $this->users($groups, $limit, $offset);
	}

    // ------------------------------------------

    /**
     * @param null $limit
     * @param null $offset
     * @param null $groups
     *
     * @return CI_DB_result
     */
    public function lock_users($limit = NULL, $offset = NULL, $groups = NULL)
    {
        $this->db->where($this->_table . '.status', 'lock');
        return $this->users($groups, $limit, $offset);
    }

	// ------------------------------------------

	/**
	 * @param string $username
	 *
	 * @return bool
	 */
	public function username_check($username = '')
	{
		if (empty($username))
		{
			return FALSE;
		}

		return $this->db->where('username', $username)
			       ->limit(1)
			       ->count_all_results($this->_table) === 0;
	}

	// ------------------------------------------

	/**
	 * @param $id
	 * @param string $username
	 *
	 * @return bool
	 */
	public function username_update_check($id, $username = '')
	{
		if (empty($id) OR empty($username))
		{
			return FALSE;
		}

		$this->db->where('id <>', $id);
		$this->db->where('username', $username);

		return $this->db->limit(1)->count_all_results($this->_table) === 0;
	}

	// ------------------------------------------

	/**
	 * @param string $email
	 *
	 * @return bool
	 */
	public function email_check($email = '')
	{
		if ($this->_unique_email === FALSE)
		{
			return TRUE;
		}

		if (empty($email) OR ! $this->form_validation->valid_email($email))
		{
			return FALSE;
		}

		return $this->db->where('email', $email)
			       ->limit(1)
			       ->count_all_results($this->_table) === 0;
	}

	// ------------------------------------------

	/**
	 * @param $id
	 * @param string $email
	 *
	 * @return bool
	 */
	public function email_update_check($id, $email = '')
	{
		if ($this->_unique_email === FALSE)
		{
			return TRUE;
		}

		if (empty($id) OR empty($email) OR ! $this->form_validation->valid_email($email))
		{
			return FALSE;
		}

		$this->db->where('id <>', $id);
		$this->db->where('email', $email);

		return $this->db->limit(1)->count_all_results($this->_table) === 0;
	}

	// ------------------------------------------

	/**
	 * @param string $phone
	 *
	 * @return bool
	 */
	public function phone_check($phone = '')
	{
		if ($this->_unique_phone === FALSE)
		{
			return TRUE;
		}

		if (empty($phone))
		{
			return FALSE;
		}

		return $this->db->where('verified_phone', verified_phone($phone))
			       ->limit(1)
			       ->count_all_results($this->_table) === 0;
	}

	// ------------------------------------------

	/**
	 * @param $id
	 * @param string $phone
	 *
	 * @return bool
	 */
	public function phone_update_check($id, $phone = '')
	{
		if ($this->_unique_phone === FALSE)
		{
			return TRUE;
		}

		if (empty($id) OR empty($phone))
		{
			return FALSE;
		}

		$this->db->where('id <>', $id);
		$this->db->where('verified_phone', verified_phone($phone));

		return $this->db->limit(1)->count_all_results($this->_table) === 0;
	}

	// ------------------------------------------

	/**
	 * Validates and removes activation code.
	 *
	 * @param int|string $id
	 * @param bool $code
	 *
	 * @return bool
	 */
	public function activate($id, $code = FALSE)
	{
		$dummy = [
			'activation_code' => NULL,
			'status' => 'active',
		];

		if ($code !== FALSE)
		{
			$query = $this->db
                ->where('activation_code', $code)
				->where('id', $id)
				->get($this->_table, 1);

			if ($query->num_rows() == 1)
			{
				$this->update($id, $dummy);
			}
		}
		else
		{
			$this->update($id, $dummy);
		}

		return $this->db->affected_rows() === 1;
	}

	// ------------------------------------------

	/**
	 * deactivate a user row with an activation code.
	 *
	 * @param int|string|null $id
	 *
	 * @param bool $init_code
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function deactivate($id = NULL, $init_code = TRUE)
	{
		if (! isset($id) OR $this->user()->row()->id == $id)
		{
			return FALSE;
		}

		$data['status'] = 'inactive';
		if ($init_code === TRUE)
		{
			$activation_code = $this->salt();
			$data['activation_code'] = $activation_code;
		}

		$this->update($id, $data);
		return $this->db->affected_rows() === 1;
	}

	// ------------------------------------------

	/**
	 * @param $code
	 *
	 * @return bool
	 */
	public function delete_forgotten_code($code)
	{
		if (empty($code))
		{
			return FALSE;
		}

		$this->db->where('forgotten_code', $code);
		if ($this->db->count_all_results($this->_table) > 0)
		{
			$data = [
				'forgotten_code' => NULL,
				'forgotten_code_time' => NULL
			];

			$this->update_by('forgotten_code', $code, $data);
			return TRUE;
		}

		return FALSE;
	}

	// ------------------------------------------

	/**
	 * @param $username
	 * @param $new_password
	 *
	 * @return bool
	 */
	public function reset_password($username, $new_password)
	{
		$query = $this->db->get_where($this->_table, ['username' => $username], 1);
		if ($query->num_rows() < 1)
		{
			return FALSE;
		}

		// store the new password and reset the remember code so all remembered instances have to re-login
		// also clear the forgotten password code
        $user = $query->row();
		$data = [
			'password' => base64_encode($this->_hash_password($new_password)),
            'old_password' => $user->password,
			'remember_code' => NULL,
			'forgotten_code' => NULL,
			'forgotten_code_time' => 0,
		];

		$this->update($user->id, $data);
		return $this->db->affected_rows() == 1;
	}

	// ------------------------------------------

	/**
	 * @param $username
	 * @param $old_password
	 * @param $new_password
	 *
	 * @return bool
	 */
	public function change_password($username, $old_password, $new_password)
	{
		$query = $this->db->get_where($this->_table, ['username' => $username], 1);
		if ($query->num_rows() < 1)
		{
			return FALSE;
		}

		$user = $query->row();
        if ($this->_verify_password($old_password, $user->id))
        {
            // store the new password and reset the remember code so all remembered instances have to re-login
            $data = [
                'password' => base64_encode($this->_hash_password($new_password)),
                'old_password' => $user->password,
                'last_pass_change_time' => now(),
                'remember_code' => NULL,
                'forgotten_code' => NULL,
                'forgotten_code_time' => 0,
            ];

            $this->update($user->id, $data);
            return $this->db->affected_rows() == 1;
        }

		return FALSE;
	}

	// ------------------------------------------

	/**
	 * @param $username
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function forgotten_password($username)
	{
		$query = $this->db->get_where($this->_table, ['username' => $username], 1);
		if ($query->num_rows() < 1)
		{
			return FALSE;
		}

		$user = $query->row();
		$update = [
			'forgotten_code' => $this->salt(),
			'forgotten_code_time' => now()
		];

		$this->update($user->id, $update);
		return $this->db->affected_rows() == 1;
	}

	// ------------------------------------------

	/**
	 * @param $forgotten_code
	 *
	 * @return array|bool
	 * @throws Exception
	 */
	public function forgotten_password_complete($forgotten_code)
	{
		$query = $this->db->get_where($this->_table, ['forgotten_code' => $forgotten_code], 1);
		if ($query->num_rows() < 1)
		{
			return FALSE;
		}

		$user = $query->row();
		if ($this->config->item('forgot_password_expired', 'auth') > 0)
		{
			// Make sure it isn't expired
			$expiration = $this->config->item('forgot_password_expired', 'auth');
			if (now() - $user->forgotten_code_time > $expiration)
			{
				// it has expired
				return FALSE;
			}
		}

		$password = $this->salt(6);
		$data = [
			'password' => base64_encode($this->_hash_password($password)),
			'old_password' => $user->password,
			'forgotten_code' => NULL,
			'remember_code' => NULL,
			'forgotten_code_time' => 0,
			'last_pass_change_time' => now()
		];

		$this->update($user->id, $data);
		return ($this->db->affected_rows() == 1) ? [
			'username' => $user->username,
			'email' => $user->email,
			'password' => $password
		] : FALSE;

	}

    // ------------------------------------------

    /**
     * @param null $id
     *
     * @return array
     */
    private function _identity_pair($id = NULL)
    {
        $_user_is_current = FALSE;

        // args null, get from session
        if (is_null($id) OR is_bool($id))
        {
            $identity = 'id';
            $id = $this->session->userdata("user_id");

            // we'll use it bellow.. before returning
            $_user_is_current = is_scalar($id) && $id
                ? [$id]    // as bool is true, as array pass the value to log
                : ($id = NULL);    // as bool is false and $id is null
        }
        elseif (is_scalar($id))
        {
            $identity = (is_numeric($id) OR empty($id)) ? 'id' : 'username';
        }
        else
        {
            $identity = 'username';
            $id = NULL;
        }

        return [
            'identity' => $identity,
            'value' => $id,
            'user_is_current' => $_user_is_current // array|bool
        ];
    }

    // ------------------------------------------

    /**
     * @param $user
     *
     * @return bool
     */
    private function _update_last_login($user)
    {
        if(isset($user->id))
        {
            $this->update($user->id, ['last_login_time' => now()]);
            return $this->db->affected_rows() === 1;
        }

        return FALSE;
    }

    // ------------------------------------------

    /**
     * @return CI_DB_query_builder
     */
    private function _select_join()
    {
        $this->db->select([
            $this->_table . '.*',
            $this->_table . '.id AS ' . $this->db->protect_identifiers('user_id'),
            $this->_table_groups . '.id AS ' . $this->db->protect_identifiers('group_id'),
            $this->_table_groups . '.name AS ' . $this->db->protect_identifiers('group_name'),
            $this->_table_groups . '.description AS ' . $this->db->protect_identifiers('group_description')
        ]);

        // join and then run a where_in against the roles ids
        $this->db->distinct();
        return $this->db->join(
            $this->_table_groups,
            $this->_table_groups . '.id = ' . $this->_table . '.' . $this->_fk_groups_id,
            'INNER'
        );
    }
}

/* end of file */
