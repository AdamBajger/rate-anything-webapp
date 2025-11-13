<?php
header('Content-Type: application/json');

// Get UUID from request
$uuid = $_GET['uuid'] ?? $_POST['uuid'] ?? null;

// Validate UUID format
if (!$uuid || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or missing UUID'
    ]);
    exit;
}

// Load configuration
$config = yaml_parse_file('config.yaml');

// Check if UUID exists in configs
if (!isset($config['configs'][$uuid])) {
    echo json_encode([
        'success' => false,
        'message' => 'Configuration not found for this UUID'
    ]);
    exit;
}

$ratingConfig = $config['configs'][$uuid];
$ratingsFile = $config['storage']['ratings_file'] ?? 'ratings.yaml';

// Validate ratings file path (prevent directory traversal)
$ratingsFile = basename($ratingsFile);
if (!preg_match('/^[a-zA-Z0-9_\-]+\.yaml$/', $ratingsFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid ratings file configuration'
    ]);
    exit;
}

$minRating = $ratingConfig['rating_scale']['min'] ?? 1;
$maxRating = $ratingConfig['rating_scale']['max'] ?? 5;

// Get POST data
$itemId = $_POST['item_id'] ?? null;
$rating = $_POST['rating'] ?? null;

// Validate input
if (!$itemId) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

// Sanitize item ID to prevent any potential issues
$itemId = trim($itemId);
if (strlen($itemId) > 255) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is too long'
    ]);
    exit;
}

if (!$rating || !is_numeric($rating)) {
    echo json_encode([
        'success' => false,
        'message' => 'Valid rating is required'
    ]);
    exit;
}

$rating = intval($rating);

// Validate rating range
if ($rating < $minRating || $rating > $maxRating) {
    echo json_encode([
        'success' => false,
        'message' => "Rating must be between $minRating and $maxRating"
    ]);
    exit;
}

// Load existing ratings
$ratings = [];
if (file_exists($ratingsFile)) {
    $ratingsData = yaml_parse_file($ratingsFile);
    if (is_array($ratingsData)) {
        $ratings = $ratingsData;
    }
}

// Use UUID-specific storage key
$storageKey = $uuid . '::' . $itemId;

// Initialize item ratings if not exists
if (!isset($ratings[$storageKey])) {
    $ratings[$storageKey] = [
        'uuid' => $uuid,
        'item_id' => $itemId,
        'config_name' => $ratingConfig['name'] ?? 'Unknown',
        'ratings' => [],
        'count' => 0,
        'sum' => 0,
        'average' => 0
    ];
}

// Add new rating
$ratings[$storageKey]['ratings'][] = [
    'rating' => $rating,
    'timestamp' => date('Y-m-d H:i:s')
];
$ratings[$storageKey]['count']++;
$ratings[$storageKey]['sum'] += $rating;
$ratings[$storageKey]['average'] = round($ratings[$storageKey]['sum'] / $ratings[$storageKey]['count'], 2);

// Save ratings to YAML file
$yamlContent = yaml_emit($ratings);
file_put_contents($ratingsFile, $yamlContent);

echo json_encode([
    'success' => true,
    'message' => 'Rating submitted successfully!',
    'data' => [
        'uuid' => $uuid,
        'item_id' => $itemId,
        'rating' => $rating,
        'average' => $ratings[$storageKey]['average'],
        'count' => $ratings[$storageKey]['count']
    ]
]);
