<?php
// Public entry: download.php moved to public/

require_once __DIR__ . '/bootstrap.php';

$data = loadYaml(data_file(get_instance_id()));

if (empty($data)) {
    die('No data available for download.');
}

header('Content-Type: application/x-yaml');
header('Content-Disposition: attachment; filename="ratings-backup-' . date('Y-m-d-His') . '.yaml"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

if (function_exists('yaml_emit')) {
    echo yaml_emit($data);
} else {
    die('ERROR: PHP YAML extension is required for data download.');
}
