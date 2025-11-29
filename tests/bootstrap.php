<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Sets up the test environment including APP_ROOT constant
 * and required function files.
 */

// Define APP_ROOT for tests
define('APP_ROOT', dirname(__DIR__));

// Load the core functions
require_once APP_ROOT . '/src/functions.php';

// Define bootstrap functions for testing (from public/bootstrap.php)
// These are duplicated here to avoid loading the full bootstrap which
// modifies global state

if (!function_exists('config_file')) {
    function config_file($instance_id) {
        return APP_ROOT . '/conf/' . $instance_id . '.yaml';
    }
}

if (!function_exists('data_file')) {
    function data_file($instance_id) {
        return APP_ROOT . '/data/' . $instance_id . '.yaml';
    }
}
