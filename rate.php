<?php
header('Content-Type: application/json');

// Load configuration
$config = yaml_parse_file('config.yaml');
$ratingsFile = $config['storage']['ratings_file'] ?? 'ratings.yaml';
$minRating = $config['rating_scale']['min'] ?? 1;
$maxRating = $config['rating_scale']['max'] ?? 5;

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

// Initialize item ratings if not exists
if (!isset($ratings[$itemId])) {
    $ratings[$itemId] = [
        'ratings' => [],
        'count' => 0,
        'sum' => 0,
        'average' => 0
    ];
}

// Add new rating
$ratings[$itemId]['ratings'][] = [
    'rating' => $rating,
    'timestamp' => date('Y-m-d H:i:s')
];
$ratings[$itemId]['count']++;
$ratings[$itemId]['sum'] += $rating;
$ratings[$itemId]['average'] = round($ratings[$itemId]['sum'] / $ratings[$itemId]['count'], 2);

// Save ratings to YAML file
$yamlContent = yaml_emit($ratings);
file_put_contents($ratingsFile, $yamlContent);

echo json_encode([
    'success' => true,
    'message' => 'Rating submitted successfully!',
    'data' => [
        'item_id' => $itemId,
        'rating' => $rating,
        'average' => $ratings[$itemId]['average'],
        'count' => $ratings[$itemId]['count']
    ]
]);
