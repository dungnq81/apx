<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Class Field_choice
 */
class Field_choice
{
    public $version = '1.0.0';

    /**
     * @var string
     */
    public $slug = 'choice';

    /**
     * Valid input types for the choices field type. The default is "dropdown".
     *
     * @var array
     */
    public $input_types = ['dropdown', 'multiselect', 'radio', 'checkboxes'];


}
