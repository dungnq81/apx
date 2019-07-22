<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 | -----------------------------------------------------
 | Authentication options.
 | -----------------------------------------------------
 */

/**
 * identity login column
 */
$config['unique_email'] = FALSE;
$config['unique_phone'] = FALSE;

/**
 * Default group, use name
 */
$config['default_group'] = 'member';

/**
 * Allow users to be remembered and enable auto-login
 **/
$config['remember_users'] = TRUE;

/**
 * How long to remember the user (seconds)
 **/
$config['remember_expire'] = 604800; // 7 ngày

/**
 * Extend the users cookies everytime they auto-login
 **/
$config['remember_extend_on_login'] = TRUE;

/**
 * Email Activation for registration
 * auto|email|none
 *
 * auto: auto activation
 * email: active by email
 * none: active by admin
 */
$config['user_activation_method'] = 'auto';

// user logs
$config['users_logs'] = TRUE;
