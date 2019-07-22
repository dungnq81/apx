<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fix iPad needs special consideration to be considered a desktop device, as stated in the online documentation
 *
 * @author  Brennon Loveless
 */
class MY_User_agent extends CI_User_agent
{
	/**
	 * @param null $key
	 *
	 * @return bool
	 */
	public function is_mobile($key = NULL)
	{
		// Dumb, but iPad needs special consideration to be considered a desktop device.
		if (parent::browser() == 'iPad')
		{
			return FALSE;
		}

		return parent::is_mobile($key);
	}
}
