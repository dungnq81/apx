<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Allows for email config settings to be stored in the db.
 */
class MY_Email extends CI_Email
{
	/**
	 * Constructor method
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$CI =& get_instance();

		// set mail protocol
		$config['protocol'] = $CI->setting->mail_protocol;

		// set a few config items
		$config['mailtype'] = "html";
		$config['charset'] = "utf-8";
		$config['crlf'] = $CI->setting->mail_line_endings ? "\r\n" : PHP_EOL;
		$config['newline'] = $CI->setting->mail_line_endings ? "\r\n" : PHP_EOL;

		// sendmail options
		if ($config['protocol'] == 'sendmail')
		{
			if ($CI->setting->mail_sendmail_path == '')
			{
				// set a default
				$config['mailpath'] = '/usr/sbin/sendmail';
			}
			else
			{
				$config['mailpath'] = $CI->setting->mail_sendmail_path;
			}
		}

		// smtp options
		if ($config['protocol'] == 'smtp')
		{
			$config['smtp_host'] = $CI->setting->mail_smtp_host;
			$config['smtp_user'] = $CI->setting->mail_smtp_user;
			$config['smtp_pass'] = $CI->setting->mail_smtp_pass;
			$config['smtp_port'] = $CI->setting->mail_smtp_port;
		}

		$this->initialize($config);
	}
}
