<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class MY_Form_validation
 *
 * Extending the Form Validation class to add extra rules and model validation
 *
 */
class MY_Form_validation extends CI_Form_validation
{
	/**
	 * The model class to call with callbacks
	 */
	private $_model;

    /**
     * MY_Form_validation constructor.
     *
     * @param array $rules
     */
    public function __construct($rules = [])
    {
        parent::__construct($rules);
    }

    /**
     * https://stackoverflow.com/questions/41747369/callback-function-is-calling-before-all-validation
     *
     * @param array $rules
     * @return array
     */
    protected function _prepare_rules($rules)
    {
        $new_rules = array();
        $callbacks = array();

        foreach ($rules as &$rule)
        {
            // Let 'required' always be the first (non-callback) rule
            if ($rule === 'required')
            {
                array_unshift($new_rules, 'required');
            }
            // 'isset' is a kind of a weird alias for 'required' ...
            elseif ($rule === 'isset' && (empty($new_rules) OR $new_rules[0] !== 'required'))
            {
                array_unshift($new_rules, 'isset');
            }
            // The old/classic 'callback_'-prefixed rules
            elseif (is_string($rule) && strncmp('callback_', $rule, 9) === 0)
            {
                $callbacks[] = $rule;
            }
            // Proper callables
            elseif (is_callable($rule))
            {
                $callbacks[] = $rule;
            }
            // "Named" callables; i.e. array('name' => $callable)
            elseif (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1]))
            {
                $callbacks[] = $rule;
            }
            // Everything else goes at the end of the queue
            else
            {
                $new_rules[] = $rule;
            }
        }

        //return array_merge($callbacks, $new_rules);
        return array_merge($new_rules, $callbacks);
    }

	/**
	 * Alpha-numeric with underscores dots and dashes
	 *
	 * @param    string
	 *
	 * @return    bool
	 */
	public function alpha_dot_dash($str)
	{
		return (bool) preg_match("/^([-a-z0-9_\-\.])+$/i", $str);
	}

	/**
	 * Sneaky function to get field data from
	 * the form validation library
	 *
	 * @param    string
	 *
	 * @return    bool
	 */
	public function field_data($field)
	{
		return (isset($this->_field_data[$field])) ? $this->_field_data[$field] : NULL;
	}

    /**
     * Formats an UTF-8 string and removes potential harmful characters
     *
     * @param	string
     * @return	string
     */
    public function utf8($str)
    {
        // If they don't have mbstring enabled (suckers) then we'll have to do with what we got
        if ( ! function_exists('mb_convert_encoding'))
        {
            return $str;
        }

        $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }

	/**
	 * Sets the model to be used for validation callbacks. It's set dynamically in MY_Model
	 *
	 * @param    string    The model class name
	 *
	 * @return    void
	 */
	public function set_model($model)
	{
		if ($model)
		{
			$this->_model = strtolower($model);
		}
	}

	/**
	 * Format an error in the set error delimiters
	 *
	 * @param    string
	 *
	 * @return string
	 */
	public function format_error($error)
	{
		return $this->_error_prefix . $error . $this->_error_suffix;
	}

    /**
     * Executes the Validation routines
     *
     * Modified to work with callbacks in the calling model -- Jerel Unruh
     *
     * @param	array
     * @param	array
     * @param	mixed
     * @param	int
     */
    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0)
    {
        // If the $_POST data is an array we will run a recursive call
        //
        // Note: We MUST check if the array is empty or not!
        //       Otherwise empty arrays will always pass validation.
        if (is_array($postdata) && ! empty($postdata))
        {
            foreach ($postdata as $key => $val)
            {
                $this->_execute($row, $rules, $val, $key);
            }

            return;
        }

        $rules = $this->_prepare_rules($rules);
        foreach ($rules as $rule)
        {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] === TRUE && is_array($this->_field_data[$row['field']]['postdata']))
            {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if ( ! isset($this->_field_data[$row['field']]['postdata'][$cycles]))
                {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            }
            else
            {
                // If we get an array field, but it's not expected - then it is most likely
                // somebody messing with the form on the client side, so we'll just consider
                // it an empty field
                $postdata = is_array($this->_field_data[$row['field']]['postdata'])
                    ? NULL
                    : $this->_field_data[$row['field']]['postdata'];
            }

            // Is the rule a callback?
            $callback = $callable = FALSE;
            if (is_string($rule))
            {
                if (strpos($rule, 'callback_') === 0)
                {
                    $rule = substr($rule, 9);
                    $callback = TRUE;
                }
            }
            elseif (is_callable($rule))
            {
                $callable = TRUE;
            }
            elseif (is_array($rule) && isset($rule[0], $rule[1]) && is_callable($rule[1]))
            {
                // We have a "named" callable, so save the name
                $callable = $rule[0];
                $rule = $rule[1];
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if ( ! $callable && preg_match('/(.*?)\[(.*)\]/', $rule, $match))
            {
                $rule = $match[1];
                $param = $match[2];
            }

            // Ignore empty, non-required inputs with a few exceptions ...
            if (
                ($postdata === NULL OR $postdata === '')
                && $callback === FALSE
                && $callable === FALSE
                && ! in_array($rule, array('required', 'isset', 'matches'), TRUE)
            )
            {
                continue;
            }

            // Call the function that corresponds to the rule
            if ($callback OR $callable !== FALSE)
            {
                // @edit by nqdung
                if($callback)
                {
                    if ( method_exists($this->CI, $rule))
                    {
                        // Run the function and grab the result
                        $result = $this->CI->$rule($postdata, $param);
                    }
                    // Did MY_Model specify a valid model in use?
                    elseif ($this->_model)
                    {
                        // moment of truth. Does the callback itself exist?
                        if (method_exists($this->CI->{$this->_model}, $rule))
                        {
                            $result = call_user_func([$this->CI->{$this->_model}, $rule], $postdata, $param);
                        }
                        else
                        {
                            log_message('debug', 'Undefined callback ' . $rule . ' Not found in ' . $this->_model);
                            $result = FALSE;
                        }
                    }
                    else
                    {
                        log_message('debug', 'Unable to find callback validation rule: ' . $rule);
                        $result = FALSE;
                    }
                }
                else
                {
                    $result = is_array($rule)
                        ? $rule[0]->{$rule[1]}($postdata)
                        : $rule($postdata);

                    // Is $callable set to a rule name?
                    if ($callable !== FALSE)
                    {
                        $rule = $callable;
                    }
                }

                // Re-assign the result to the master data array
                if ($_in_array === TRUE)
                {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                }
                else
                {
                    $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                }
            }
            elseif ( ! method_exists($this, $rule))
            {
                // If our own wrapper function doesn't exist we see if a native PHP function does.
                // Users can use any native PHP function call that has one param.
                if (function_exists($rule))
                {
                    // Native PHP functions issue warnings if you pass them more parameters than they use
                    $result = ($param !== FALSE) ? $rule($postdata, $param) : $rule($postdata);

                    if ($_in_array === TRUE)
                    {
                        $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                    }
                    else
                    {
                        $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                    }
                }
                else
                {
                    log_message('debug', 'Unable to find validation rule: '.$rule);
                    $result = FALSE;
                }
            }
            else
            {
                $result = $this->$rule($postdata, $param);

                if ($_in_array === TRUE)
                {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = is_bool($result) ? $postdata : $result;
                }
                else
                {
                    $this->_field_data[$row['field']]['postdata'] = is_bool($result) ? $postdata : $result;
                }
            }

            // Did the rule test negatively? If so, grab the error.
            if ($result === FALSE)
            {
                // Callable rules might not have named error messages
                if ( ! is_string($rule))
                {
                    $line = $this->CI->lang->line('form_validation_error_message_not_set').'(Anonymous function)';
                }
                else
                {
                    $line = $this->_get_error_message($rule, $row['field']);
                }

                // Is the parameter we are inserting into the error message the name
                // of another field? If so we need to grab its "field label"
                if (isset($this->_field_data[$param], $this->_field_data[$param]['label']))
                {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = $this->_build_error_msg($line, $this->_translate_fieldname($row['label']), $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if ( ! isset($this->_error_array[$row['field']]))
                {
                    $this->_error_array[$row['field']] = $message;
                }

                return;
            }
        }
    }
}
