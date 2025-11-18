<?php
/**
 * Leaderboard and Statistics Display
 * 
 * Displays comprehensive statistics and rankings for all rated items.
 * Features:
 * - Overall statistics (total items, total ratings, average)
 * - Ranked leaderboard sorted by average rating
 * - Detailed rating distributions per item
 * - Recent rating history
 * 
 * Query parameters:
 * - success: Set to 1 to show success message after rating submission
 * - identifier: Identifier of newly rated item to highlight
 * 
 * @package RateAnything
 */

// Set CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load core functions
require_once 'functions.php';

// Load application configuration and rating data
$config = loadYaml('config.yaml');
$data = loadYaml('data.yaml');

// Check for success message parameters from rating submission
$success = isset($_GET['success']) && $_GET['success'] == '1';
$submittedIdentifier = $_GET['identifier'] ?? null;

// Build leaderboard data with calculated statistics
$leaderboard = [];
if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as $identifier => $itemData) {
        $stats = calculateStats($itemData['ratings'] ?? []);
        $leaderboard[] = [
            'identifier' => $identifier,
            'name' => $itemData['name'] ?? parseIdentifier($identifier, $config),
            'stats' => $stats,
            'ratings' => $itemData['ratings'] ?? []
        ];
    }
}

// Sort leaderboard by average rating (descending)
// Secondary sort by count for items with equal averages
usort($leaderboard, function($a, $b) {
    if ($a['stats']['average'] === $b['stats']['average']) {
        return $b['stats']['count'] - $a['stats']['count'];
    }
    return $b['stats']['average'] <=> $a['stats']['average'];
});

// Calculate overall statistics across all items
$totalRatings = 0;
$totalItems = count($leaderboard);
foreach ($leaderboard as $item) {
    $totalRatings += $item['stats']['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Rate Anything</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Leaderboard</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Rating submitted successfully!
                <?php if ($submittedIdentifier): ?>
                    <strong><?php echo htmlspecialchars($submittedIdentifier); ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($leaderboard)): ?>
            <div class="card">
                <p>No ratings yet. <a href="index.php">Rate an item</a></p>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Rankings</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Identifier</th>
                            <th>Average Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($item['identifier']); ?></td>
                                <td><?php echo $item['stats']['average']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <a href="index.php" class="btn btn-primary">Rate Another Item</a>
            <a href="download.php" class="btn btn-secondary">Download Data</a>
        </div>
    </div>
</body>
</html>
