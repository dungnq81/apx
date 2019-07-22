<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission model
 *
 * Class Permission_m
 */
class Permission_m extends MY_Model
{
	/**
	 * @var array
	 */
	private $_groups = [];
	private $_fk_groups_id = 'users_groups_id';

	/**
	 * Get the permission rules for a group.
	 *
	 * @param int $group_id The id for the group.
	 *
	 * @return array
	 */
	public function get_group($group_id)
	{
		// Save a query if you can
		if (isset($this->_groups[$group_id]))
		{
			return $this->_groups[$group_id];
		}

		// Execute the query
		$result = $this->db
            ->get_where($this->_table, [$this->_fk_groups_id => $group_id])
			->result();

		// Store the final rules here
		$rules = [];
		foreach ($result as $row)
		{
			// Either pass roles or just true
			$rules[$row->controller] = $row->roles ? json_decode($row->roles, TRUE) : TRUE;
		}

		// Save this result for later
		$this->_groups[$group_id] = $rules;

		return $rules;
	}

	/**
	 * Get a role based on the group slug
	 *
	 * @param string|array $roles Either a single role or an array
	 * @param null|string $controller The class to check access against
	 * @param bool $strict If set to true the user must have every role in $roles. Otherwise having one role is sufficient
	 *
	 * @return bool
	 */
	public function has_role($roles, $controller = NULL, $strict = FALSE)
	{
		$access = [];
		is_null($controller) AND $controller = $this->controller;

		// must be logged in
		if (! $this->current_user)
		{
			return FALSE;
		}

		// admins can have anything
		if ('administrator' == $this->current_user->group_name)
		{
			return TRUE;
		}

		// do they even have access to the class?
		if (! isset($this->permissions[$controller]))
		{
			return FALSE;
		}

		if (is_array($roles))
		{
			foreach ($roles as $role)
			{
				if (array_key_exists($role, $this->permissions[$controller]))
				{
					// if not strict then one role is enough to get them in the door
					if (! $strict)
					{
						return TRUE;
					}
					else
					{
						array_push($access, FALSE);
					}
				}
			}

			// we have to have a non-empty array but one false in the array gets them canned
			return $access AND ! in_array(FALSE, $access);
		}
		else
		{
			// they just passed one role to check
			return array_key_exists($roles, $this->permissions[$controller]);
		}
	}

	/**
	 * Get a rule based on the ID
	 *
	 * @param int $group_id The id for the group to get the rule for.
	 * @param null|string $controller The class to check access against
	 *
	 * @return bool
	 */
	public function check_access($group_id, $controller = NULL)
	{
		// If no class is set, just make sure they have SOMETHING
		if (!is_null($controller))
		{
			$this->db->where('controller', $controller);
		}

		return $this->db->where($this->_fk_groups_id, $group_id)
			       ->count_all_results($this->_table) > 0;
	}

	/**
	 * Save the permissions passed
	 *
	 * @param int $group_id
	 * @param array $controllers
	 * @param array $roles
	 *
	 * @return bool
	 */
	public function save($group_id, $controllers, $roles)
	{
		// Clear out the old permissions
		$this->db
			->where($this->_fk_groups_id, $group_id)
			->delete($this->_table);

		if ($controllers)
		{
			// For each class mentioned (with a value of 1 for most browser compatibility).
			foreach ($controllers as $controller => $permission)
			{
				if (! empty($permission))
				{
					$data = [
						'controller' => $controller,
						$this->_fk_groups_id => $group_id,
						'roles' => ! empty($roles[$controller]) ? json_encode($roles[$controller]) : NULL,
					];

					// Save this module in the list of "allowed modules"
					if (! $result = $this->db->insert($this->_table, $data))
					{
						// Fail, give up trying
						return FALSE;
					}
				}
			}

			// All done!
			return TRUE;
		}

		return FALSE;
	}
}
