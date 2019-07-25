<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Abstract Class Library
 *
 * @property MY_Loader $load
 * @author nqdung <quocdung@vietnhan.net>
 */
abstract class Library
{
	/**
	 * @var object target class
	 */
	protected $class;

	/**
	 * @var array
	 */
	protected $errors = [];
	protected $messages = [];

	/**
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @param    string $var
	 *
	 * @return    mixed
	 */
	public function __get($var)
	{
		static $ci;
		isset($ci) OR $ci =& get_instance();

		return $ci->{$var};
	}

	/**
	 * Acts as a simple way to call model methods without loads of stupid alias
	 *
	 * @param string $method
	 * @param array $arguments
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments)
	{
		$reflection = new ReflectionMethod($this->class, $method);
		if (! method_exists($this->class, $method) OR ! $reflection->isPublic() OR $reflection->isConstructor())
		{
			throw new Exception('Undefined method ' . $this->class . '::' . $method . '() called');
		}

		return call_user_func_array([$this->class, $method], $arguments);
	}

	/**
	 * Set an error message
	 *
	 * @param string $error The error to set
	 *
	 * @return string The given error
	 */
	public function set_error($error)
	{
		$this->errors[] = $error;
		return $error;
	}

	/**
	 * Get the error message
	 *
	 * @return string
	 */
	public function errors()
	{
		$_output = '';
		foreach ($this->errors as $error)
		{
			is_empty($error) OR $_output .= "<p>" . __($error) . "</p>";
		}

		return $_output;
	}

	/**
	 * Get the error messages as an array
	 *
	 * @param bool $langify
	 *
	 * @return array
	 */
	public function errors_array($langify = TRUE)
	{
		if ($langify)
		{
			$_output = [];
			foreach ($this->errors as $error)
			{
				is_empty($error) OR $_output[] = "<p>" . __($error) . "</p>";
			}

			return $_output;
		}
		else
		{
			return $this->errors;
		}
	}

	/**
	 * Clear Errors
	 *
	 * @return true
	 */
	public function clear_errors()
	{
		$this->errors = [];

		return TRUE;
	}

	/**
	 * Set a message
	 *
	 * @param string $message The message
	 *
	 * @return string The given message
	 */
	public function set_message($message)
	{
		$this->messages[] = $message;

		return $message;
	}

	/**
	 * Get the messages
	 *
	 * @return string
	 */
	public function messages()
	{
		$_output = '';
		foreach ($this->messages as $message)
		{
			is_empty($message) OR $_output .= "<p>" . __($message) . "</p>";
		}

		return $_output;
	}

	/**
	 * Get the messages as an array
	 *
	 * @param bool $langify
	 *
	 * @return array
	 */
	public function messages_array($langify = TRUE)
	{
		if ($langify)
		{
			$_output = [];
			foreach ($this->messages as $message)
			{
				is_empty($message) OR $_output[] = "<p>" . __($message) . "</p>";
			}

			return $_output;
		}
		else
		{
			return $this->messages;
		}
	}

	/**
	 * Clear messages
	 *
	 * @return true
	 */
	public function clear_messages()
	{
		$this->messages = [];

		return TRUE;
	}
}
