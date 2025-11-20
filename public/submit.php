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

// Determine which identifier to use
$selected = $_POST['identifier'] ?? '';
$manual = trim($_POST['manual_identifier'] ?? '');
$raw = trim($_POST['raw_identifier'] ?? '');
// Preserve fractional values from the slider: use float conversion
$rating = isset($_POST['rating']) ? floatval($_POST['rating']) : null;

if ($rating === null || ($selected === '' && $manual === '')) {
    die('Error: Missing required fields. <a href="index.php">Go back</a>');
}

$config = loadYaml(config_file());
$data = loadYaml(data_file());

if (!isset($data['items'])) {
    $data['items'] = [];
}

// Workflow:
// - If user selected existing item, use that key as-is.
// - If user provided manual input and a raw_identifier is present (came from QR parse),
//   treat the manual value as the canonical key and store the raw original under 'source'.
// - If user manually typed an identifier (no raw), use it as-is.
if ($selected !== '') {
    $key = $selected;
    $displayName = $data['items'][$key]['name'] ?? $key;
} else {
    // manual provided
    $key = $manual;
    $displayName = $manual;
    if ($raw !== '') {
        // keep the original raw value as metadata
        $sourceValue = $raw;
    } else {
        $sourceValue = null;
    }
}

// Initialize item if missing
if (!isset($data['items'][$key])) {
    $item = [
        'name' => $displayName,
        'ratings' => []
    ];
    if (!empty($sourceValue)) {
        $item['source'] = $sourceValue;
    }
    $data['items'][$key] = $item;
}

$data['items'][$key]['ratings'][] = [
    'rating' => $rating + 0.0,
    'timestamp' => date('Y-m-d H:i:s')
];

if (saveYaml(data_file(), $data)) {
    $qs = 'success=1&identifier=' . urlencode($key);
    $__iq = instance_query();
    if ($__iq) $qs .= '&' . $__iq;
    header('Location: leaderboard.php?' . $qs);
    exit;
} else {
    die('Error: Failed to save rating. <a href="index.php">Go back</a>');
}
