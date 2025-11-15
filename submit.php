<?php
// Load helper functions
require_once 'functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Get form data
$identifier = isset($_POST['identifier']) && $_POST['identifier'] !== '' 
    ? $_POST['identifier'] 
    : ($_POST['manual_identifier'] ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;

// Validate input
if (empty($identifier) || $rating === null) {
    die('Error: Missing required fields. <a href="index.php">Go back</a>');
}

// Load configuration and data
$config = loadYaml('config.yaml');
$data = loadYaml('data.yaml');

// Initialize items array if not exists
if (!isset($data['items'])) {
    $data['items'] = [];
}

// Check if identifier is already tracked
if (!isset($data['items'][$identifier])) {
    // Create new entry
    $data['items'][$identifier] = [
        'name' => parseIdentifier($identifier, $config),
        'ratings' => []
    ];
}

// Append rating with timestamp
$data['items'][$identifier]['ratings'][] = [
    'rating' => $rating,
    'timestamp' => date('Y-m-d H:i:s')
];

// Save data
if (saveYaml('data.yaml', $data)) {
    // Redirect to leaderboard
    header('Location: leaderboard.php?success=1&identifier=' . urlencode($identifier));
    exit;
} else {
    die('Error: Failed to save rating. <a href="index.php">Go back</a>');
}
