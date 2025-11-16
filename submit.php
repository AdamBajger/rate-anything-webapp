<?php
/**
 * Rating Submission Handler
 * 
 * Processes POST requests containing rating submissions.
 * Validates input, updates the data store, and redirects to leaderboard.
 * 
 * Expected POST parameters:
 * - identifier: Pre-selected item identifier (from dropdown)
 * - manual_identifier: Manually entered or QR-scanned identifier
 * - rating: Integer rating value within configured range
 * 
 * @package RateAnything
 */

// Set CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load core functions
require_once 'functions.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Extract and validate form data
// Priority: dropdown identifier, then manual entry/QR scan
$identifier = isset($_POST['identifier']) && $_POST['identifier'] !== '' 
    ? $_POST['identifier'] 
    : ($_POST['manual_identifier'] ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;

// Validate required fields
if (empty($identifier) || $rating === null) {
    die('Error: Missing required fields. <a href="index.php">Go back</a>');
}

// Load application configuration and existing data
$config = loadYaml('config.yaml');
$data = loadYaml('data.yaml');

// Initialize items array if it doesn't exist
if (!isset($data['items'])) {
    $data['items'] = [];
}

// Create new item entry if identifier not yet tracked
if (!isset($data['items'][$identifier])) {
    $data['items'][$identifier] = [
        'name' => parseIdentifier($identifier, $config),
        'ratings' => []
    ];
}

// Append new rating with timestamp to item's rating history
$data['items'][$identifier]['ratings'][] = [
    'rating' => $rating,
    'timestamp' => date('Y-m-d H:i:s')
];

// Persist updated data to storage
if (saveYaml('data.yaml', $data)) {
    // Redirect to leaderboard with success notification
    header('Location: leaderboard.php?success=1&identifier=' . urlencode($identifier));
    exit;
} else {
    die('Error: Failed to save rating. <a href="index.php">Go back</a>');
}
