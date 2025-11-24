<?php
// Public entry: submit.php moved to public/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Only the raw identifier is expected from the client
$raw = trim($_POST['identifier'] ?? '');
// Preserve fractional values from the slider: use float conversion
$rating = isset($_POST['rating']) ? floatval($_POST['rating']) : null;

if ($rating === null || $raw === '') {
    die('Error: Missing required fields. <a href="index.php">Go back</a>');
}

$config = loadYaml(config_file(get_instance_id()));
$data = loadYaml(data_file(get_instance_id()));

if (!isset($data['items'])) {
    $data['items'] = [];
}

// Server-side parse to produce a canonical display name (prevents spoofing)
$displayName = parseIdentifier($raw, $config);

// Use the raw identifier as the authoritative storage key
$key = $raw;

// Initialize item if missing
if (!isset($data['items'][$key])) {
    $item = [
        'name' => $displayName,
        'ratings' => [],
        'created' => date('Y-m-d H:i:s')
    ];
    $data['items'][$key] = $item;
}

$data['items'][$key]['ratings'][] = [
    'rating' => $rating + 0.0,
    'timestamp' => date('Y-m-d H:i:s')
];

if (saveYaml(data_file(get_instance_id()), $data)) {
    $qs = 'success=1&identifier=' . urlencode($key);
    $__iq = instance_query();
    if ($__iq) $qs .= '&' . $__iq;
    header('Location: leaderboard.php?' . $qs);
    exit;
} else {
    die('Error: Failed to save rating. <a href="index.php">Go back</a>');
}
