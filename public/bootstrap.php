<?php
// public/bootstrap.php
// Defines APP_ROOT and loads application bootstrap files.

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load application functions
require_once APP_ROOT . '/src/functions.php';

// Helper functions to access the per-request instance identifier and
// resolve config/data file paths. These avoid defining globals/constants
// and compute the values on demand from $_REQUEST.
function get_instance_id() {
    // If `instance` is present in the request, accept it when empty or
    // when matching the allowlist. Otherwise, default to empty string.
    if (array_key_exists('instance', $_REQUEST)) {
        $candidate = (string)$_REQUEST['instance'];
        if ($candidate === '') return '';
        if (preg_match('/^[A-Za-z0-9_-]{1,32}$/', $candidate)) {
            return $candidate;
        }
    }
    return '';
}

function instance_query() {
    return 'instance=' . urlencode(get_instance_id());
}

function config_file() {
    $id = get_instance_id();
    return APP_ROOT . "/conf/" . $id . ".yaml";
}

function data_file() {
    $id = get_instance_id();
    return APP_ROOT . "/data/" . $id . ".yaml";
}
