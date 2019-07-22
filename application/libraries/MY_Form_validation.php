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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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
}
