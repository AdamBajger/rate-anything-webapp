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
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;

if ($rating === null || ($selected === '' && $manual === '')) {
    die('Error: Missing required fields. <a href="index.php">Go back</a>');
}

$config = loadYaml(__DIR__ . '/../config.yaml');
$data = loadYaml(__DIR__ . '/../data.yaml');

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
    'rating' => $rating,
    'timestamp' => date('Y-m-d H:i:s')
];

if (saveYaml(__DIR__ . '/../data.yaml', $data)) {
    header('Location: leaderboard.php?success=1&identifier=' . urlencode($key));
    exit;
} else {
    die('Error: Failed to save rating. <a href="index.php">Go back</a>');
}
