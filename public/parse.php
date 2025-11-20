<?php
// Simple parsing endpoint used by the client to canonicalize scanned identifiers
header('Content-Type: application/json');

require_once __DIR__ . '/bootstrap.php';

$identifier = $_GET['identifier'] ?? '';
if ($identifier === '') {
    echo json_encode(['error' => 'missing identifier']);
    exit;
}

$config = loadYaml(config_file());
$parsed = parseIdentifier($identifier, $config);

echo json_encode(['parsed' => $parsed]);
