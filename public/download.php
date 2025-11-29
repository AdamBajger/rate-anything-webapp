<?php
// Public entry: download.php moved to public/

require_once __DIR__ . '/bootstrap.php';

$config = loadYaml(config_file(get_instance_id()));
$data = loadYaml(data_file(get_instance_id()));

if (empty($data)) {
    $msg = htmlspecialchars(translate('no_data_download', $config));
    $go = '<a href="index.php">' . htmlspecialchars(translate('go_back', $config)) . '</a>';
    die($msg . ' ' . $go);
}

header('Content-Type: application/x-yaml');
header('Content-Disposition: attachment; filename="ratings-backup-' . date('Y-m-d-His') . '.yaml"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

if (function_exists('yaml_emit')) {
    echo yaml_emit($data);
} else {
    die(htmlspecialchars(translate('error_yaml_required_download', $config)));
}
