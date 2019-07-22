<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Auth
 *
 * @property User_m $user_m
 * @property MY_Email $email
 * @property Setting $setting
 */
class Auth extends Library
{
	/**
	 * @var string
	 */
	protected $user_activation = 'auto';

	/**
	 * Auth constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		$this->load->library('email');
		$this->load->models(['users/user_m', 'users/users_group_m', 'users/users_log_m']);

		// Mapping to User_m class
        // enable call directly a func of User_m class from Auth class
		$this->class = $this->user_m;

		// user_activation_method
		if (is_string($activation = $config['user_activation_method']))
		{
			// auto|email|none
			if (in_array($activation, ['auto', 'email', 'none']))
			{
				$this->user_activation = $activation;
			}
		}
	}

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
        if ($this->user_m->login($identity, $password, $remember))
        {
            $this->set_message('login_successful');

            return TRUE;
        }

        $this->set_error('login_unsuccessful');
        return FALSE;
    }

	/**
	 * @param $username
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function forgotten_password($username)
	{
		if ($this->user_m->forgotten_password($username))
		{
			// Get user information
			$user = $this->user_m->user($username)->row();
			if ($user AND $user->active == 1)
			{
				$data = [
					'username' => $user->username,
					'forgotten_code' => $user->forgotten_code
				];

				$message_content = $this->load->view('auth/email/forgot_password.tpl.php', $data, TRUE);
				$this->email->clear();
				$this->email->from($this->setting->server_email, $this->setting->site_name);
				$this->email->to($user->email);
				$this->email->subject($this->setting->site_name . ' - ' . __('email_forgotten_password_subject'));
				$this->email->message($message_content);
				if ($this->email->send())
				{
					$this->set_message('forgot_password_successful');

					return TRUE;
				}
			}
		}

		$this->set_error('forgot_password_unsuccessful');

		return FALSE;
	}

	/**
	 * @param $code
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function forgotten_password_complete($code)
	{
		$result_arr = $this->user_m->forgotten_password_complete($code);
		if ($result_arr !== FALSE)
		{
			$data = [
				'username' => $result_arr['username'],
				'new_password' => $result_arr['password']
			];

			$message = $this->load->view('auth/email/new_password.tpl.php', $data, TRUE);
			$this->email->clear();
			$this->email->from($this->setting->server_email, $this->setting->site_name);
			$this->email->to($result_arr['email']);
			$this->email->subject($this->setting->site_name . ' - ' . __('email_new_password_subject'));
			$this->email->message($message);

			if ($this->email->send())
			{
				$this->set_message('password_change_successful');

				return TRUE;
			}
		}

		$this->set_error('password_change_unsuccessful');

		return FALSE;
	}

	/**
	 * @param $username
	 * @param $password
	 * @param bool $email
	 * @param bool $phone
	 * @param bool $role
	 *
	 * @return bool|int
	 * @throws Exception
	 */
	public function add_user($username, $password, $email = FALSE, $phone = FALSE, $role = FALSE)
	{
		$id = $this->user_m->add_user($username, $password, $email, $phone, $role);
		if ($id !== FALSE)
		{
			if ($this->user_activation !== 'email')
			{
				$this->set_message('account_creation_successful');
				if ($this->user_activation === 'auto')
				{
					$this->user_m->activate($id);
				}
				else if ($this->user_activation === 'none')
				{
					$this->user_m->deactivate($id, FALSE);
				}

				return $id;
			}

			if (is_array($username) AND array_key_exists('email', $username))
			{
				$email = $username['email'];
			}

			if ($email == FALSE)
			{
				$this->set_error('account_creation_unsuccessful');

				return FALSE;
			}

			//
			$this->user_m->deactivate($id);
			$user = $this->user_m->user($id)->row();
			$data = [
				'username' => $user->username,
				'id' => $user->id,
				'email' => $email,
				'activation' => $user->activation_code,
			];

			$message = $this->load->view('auth/email/activate.tpl.php', $data, TRUE);
			$this->email->clear();
			$this->email->from($this->setting->server_email, $this->setting->site_name);
			$this->email->to($email);
			$this->email->subject($this->setting->site_name . ' - ' . __('email_activation_subject'));
			$this->email->message($message);

			if ($this->email->send() === TRUE)
			{
				$this->set_message('activation_email_successful');

				return $id;
			}
		}

		$this->set_error('account_creation_unsuccessful');

		return FALSE;
	}
}

/* end of file */
