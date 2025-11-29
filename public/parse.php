<?php
// Simple parsing endpoint used by the client to canonicalize scanned identifiers
header('Content-Type: application/json');

require_once __DIR__ . '/bootstrap.php';

$config = loadYaml(config_file(get_instance_id()));

$identifier = $_GET['identifier'] ?? '';
if ($identifier === '') {
    $msg = translate('parse_missing_identifier', $config);
    echo json_encode(['error' => 'missing_identifier', 'message' => $msg]);
    exit;
}

$parsed = parseIdentifier($identifier, $config);

echo json_encode(['parsed' => $parsed]);