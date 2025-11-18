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

$identifier = isset($_POST['identifier']) && $_POST['identifier'] !== '' 
    ? $_POST['identifier'] 
    : ($_POST['manual_identifier'] ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;

if (empty($identifier) || $rating === null) {
    die('Error: Missing required fields. <a href="index.php">Go back</a>');
}

$config = loadYaml(__DIR__ . '/../config.yaml');
$data = loadYaml(__DIR__ . '/../data.yaml');

if (!isset($data['items'])) {
    $data['items'] = [];
}

if (!isset($data['items'][$identifier])) {
    $data['items'][$identifier] = [
        'name' => parseIdentifier($identifier, $config),
        'ratings' => []
    ];
}

$data['items'][$identifier]['ratings'][] = [
    'rating' => $rating,
    'timestamp' => date('Y-m-d H:i:s')
];

if (saveYaml(__DIR__ . '/../data.yaml', $data)) {
    header('Location: leaderboard.php?success=1&identifier=' . urlencode($identifier));
    exit;
} else {
    die('Error: Failed to save rating. <a href="index.php">Go back</a>');
}
