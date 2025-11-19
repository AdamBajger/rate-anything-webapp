<?php
// public/bootstrap.php
// Defines APP_ROOT and loads application bootstrap files.

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Load application functions
require_once APP_ROOT . '/src/functions.php';
