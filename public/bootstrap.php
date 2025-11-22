<?php
// public/bootstrap.php
// Defines APP_ROOT and loads application bootstrap files.

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load application functions
require_once APP_ROOT . '/src/functions.php';

// Return the requested instance identifier (may be empty string).
// Accepts an explicit empty identifier when `instance` is present and empty.
function get_instance_id() {
    if (array_key_exists('instance', $_REQUEST)) {
        $candidate = (string)$_REQUEST['instance'];
        if ($candidate === '') {
            return '';
        }
        if (preg_match('/^[A-Za-z0-9_-]{1,32}$/', $candidate)) {
            return (string)$candidate;
        }
        error_log('WARNING: Invalid instance identifier received: ' . $candidate);
        return '';
    }
    return '';
}

function instance_query() {
    return 'instance=' . urlencode(get_instance_id());
}

// Resolve paths for per-instance config/data files. These functions
// explicitly accept the instance identifier as an argument as requested.
function config_file($instance_id) {
    return APP_ROOT . '/conf/' . $instance_id . '.yaml';
}

function data_file($instance_id) {
    return APP_ROOT . '/data/' . $instance_id . '.yaml';
}
