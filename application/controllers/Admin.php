<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Admin
 *
 * @property Template $template
 * @property Auth $auth
 */
class Admin extends Admin_Controller
{
	/**
	 * The control panel
	 */
	public function index()
	{
		$this->template
			->enable_parser(TRUE)
			->title('Dashboard')
			->build('dashboard');
	}

    /**
     * Login
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
	public function login()
	{
		// Set the validation rules
		$validation_rules = [
			[
				'field' => 'identity',
				'label' => 'Tên đăng nhập',
				'rules' => 'trim|required|callback__check_login'
			],
			[
				'field' => 'password',
				'label' => 'Mật khẩu',
				'rules' => 'trim|required'
			]
		];

		// Call validation and set rules
		$this->form_validation->set_rules($validation_rules);

		// If the validation worked, or the user is already logged in
		if ($this->auth->logged_in() OR $this->form_validation->run())
		{
			// if they were trying to go someplace besides the dashboard we'll have stored it in the session
			$redirect = $this->session->userdata('admin_redirect');
			$this->session->unset_userdata('admin_redirect');

			redirect($redirect ? $redirect : 'admin');
		}

		$this->template
			->set_layout(FALSE)
			->build('login');
	}

	/**
	 * Logout
	 */
	public function logout()
	{
		$this->auth->logout();
		$this->session->set_flashdata('success', 'Đã thoát');

		redirect('admin/login');
	}

	/**
	 * Callback From: login()
	 *
	 * @param string $identity The identity to validate
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function _check_login($identity)
	{
	    // recapcha
        $verify = recaptcha_verify($this->input->post('g-recaptcha-response'));
        if($verify)
        {
            if ($this->auth->login($identity, $this->input->post('password'), str_to_bool($this->input->post('remember'))))
            {
                return TRUE;
            }
            else
            {
                $this->form_validation->set_message('_check_login', $this->auth->errors());
                return FALSE;
            }
        }

        $this->form_validation->set_message('_check_login', __("Something_went_wrong"));
        return FALSE;
	}
}
