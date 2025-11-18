<?php
/**
 * Data Download Handler
 * 
 * Provides an endpoint to download the ratings data as YAML file for backup purposes.
 * This allows administrators to back up all ratings data.
 * 
 * @package RateAnything
 */

// Load core functions
require_once 'functions.php';

// Load the ratings data
$data = loadYaml('data.yaml');

// Check if data exists
if (empty($data)) {
    die('No data available for download.');
}

// Set headers for file download
header('Content-Type: application/x-yaml');
header('Content-Disposition: attachment; filename="ratings-backup-' . date('Y-m-d-His') . '.yaml"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output the YAML content
if (function_exists('yaml_emit')) {
    echo yaml_emit($data);
} else {
    die('ERROR: PHP YAML extension is required for data download.');
}
