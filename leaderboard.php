<?php
/**
 * Leaderboard page for UUID-based rating system
 * Shows statistics and rankings for a specific UUID configuration
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load helper functions
require_once 'functions.php';

/**
 * Validate UUID format (RFC 4122)
 */
function isValidUuid($uuid) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    return preg_match($pattern, $uuid) === 1;
}

// Get UUID from query parameter
$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : null;

// Validate UUID
if (!$uuid || !isValidUuid($uuid)) {
    http_response_code(400);
    die('Invalid or missing UUID parameter');
}

// Load configuration
$config = yaml_parse_file('config.yaml');
if (!$config || !isset($config['configs'][$uuid])) {
    http_response_code(404);
    die('Configuration not found for UUID: ' . htmlspecialchars($uuid));
}

$ratingConfig = $config['configs'][$uuid];
$configName = $ratingConfig['name'] ?? 'Unknown Configuration';
$configDescription = $ratingConfig['description'] ?? '';
$ratingsFile = $config['storage']['ratings_file'] ?? 'ratings.yaml';
$minRating = $ratingConfig['rating_scale']['min'] ?? 1;
$maxRating = $ratingConfig['rating_scale']['max'] ?? 5;
$ratingLabels = $ratingConfig['rating_scale']['labels'] ?? [];

// Load ratings data
$allRatings = [];
if (file_exists($ratingsFile)) {
    $ratingsData = yaml_parse_file($ratingsFile);
    if (is_array($ratingsData)) {
        $allRatings = $ratingsData;
    }
}

// Filter ratings for this UUID
$uuidRatings = [];
$prefix = $uuid . '::';
foreach ($allRatings as $key => $data) {
    if (strpos($key, $prefix) === 0) {
        $uuidRatings[] = $data;
    }
}

// Sort by average rating (descending), then by count
usort($uuidRatings, function($a, $b) {
    if ($a['average'] === $b['average']) {
        return $b['count'] - $a['count'];
    }
    return $b['average'] <=> $a['average'];
});

// Calculate overall statistics
$totalRatings = 0;
$totalItems = count($uuidRatings);
foreach ($uuidRatings as $item) {
    $totalRatings += $item['count'];
}

// Check for success message
$success = isset($_GET['success']) && $_GET['success'] == '1';
$submittedItemId = $_GET['item_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo htmlspecialchars($configName); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>📊 Leaderboard</h1>
        <p class="text-center" style="color: white; margin-bottom: 20px;">
            <?php echo htmlspecialchars($configName); ?>
        </p>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ Rating submitted successfully!
                <?php if ($submittedItemId): ?>
                    <strong><?php echo htmlspecialchars($submittedItemId); ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Overall Statistics</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $totalItems; ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $totalRatings; ?></div>
                    <div class="stat-label">Total Ratings</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">
                        <?php 
                        echo $totalItems > 0 
                            ? number_format($totalRatings / $totalItems, 1) 
                            : '0';
                        ?>
                    </div>
                    <div class="stat-label">Avg Ratings/Item</div>
                </div>
            </div>
        </div>

        <?php if (empty($uuidRatings)): ?>
            <div class="card">
                <p class="text-center">No ratings yet. <a href="index.php?uuid=<?php echo urlencode($uuid); ?>">Be the first to rate!</a></p>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Rankings</h2>
                <div class="leaderboard-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Item</th>
                                <th>Average Rating</th>
                                <th>Total Ratings</th>
                                <th>Latest Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uuidRatings as $index => $item): ?>
                                <?php
                                $rank = $index + 1;
                                $medal = '';
                                if ($rank === 1) $medal = '🥇';
                                elseif ($rank === 2) $medal = '🥈';
                                elseif ($rank === 3) $medal = '🥉';
                                
                                $latestRating = end($item['ratings']);
                                $isRecent = $item['item_id'] === $submittedItemId;
                                $humanName = parseIdentifier($item['item_id'], $config);
                                ?>
                                <tr class="<?php echo $isRecent ? 'highlight' : ''; ?>">
                                    <td class="rank">
                                        <?php echo $medal; ?>
                                        #<?php echo $rank; ?>
                                    </td>
                                    <td class="item-name">
                                        <strong><?php echo htmlspecialchars($humanName); ?></strong>
                                        <small class="identifier"><?php echo htmlspecialchars($item['item_id']); ?></small>
                                    </td>
                                    <td class="average-rating">
                                        <span class="rating-stars">
                                            <?php echo str_repeat('⭐', (int)round($item['average'])); ?>
                                        </span>
                                        <span class="rating-value"><?php echo $item['average']; ?></span>
                                    </td>
                                    <td class="count"><?php echo $item['count']; ?></td>
                                    <td class="latest">
                                        <?php if ($latestRating): ?>
                                            <span class="rating-badge"><?php echo $latestRating['rating']; ?>⭐</span>
                                            <small><?php echo date('M d, H:i', strtotime($latestRating['timestamp'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>Detailed Statistics</h2>
                <?php foreach ($uuidRatings as $item): ?>
                    <?php $humanName = parseIdentifier($item['item_id'], $config); ?>
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($humanName); ?></h3>
                        <div class="rating-distribution">
                            <?php
                            // Count ratings by value
                            $distribution = [];
                            for ($i = $minRating; $i <= $maxRating; $i++) {
                                $distribution[$i] = 0;
                            }
                            foreach ($item['ratings'] as $r) {
                                if (isset($distribution[$r['rating']])) {
                                    $distribution[$r['rating']]++;
                                }
                            }
                            
                            // Calculate percentages
                            $total = $item['count'];
                            ?>
                            <div class="distribution-bars">
                                <?php for ($i = $maxRating; $i >= $minRating; $i--): ?>
                                    <?php
                                    $count = $distribution[$i];
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                    $label = isset($ratingLabels[$i]) ? $ratingLabels[$i] : $i;
                                    ?>
                                    <div class="bar-row">
                                        <span class="bar-label"><?php echo $i; ?>⭐ <?php echo htmlspecialchars($label); ?></span>
                                        <div class="bar-container">
                                            <div class="bar" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <span class="bar-count"><?php echo $count; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="recent-ratings">
                            <h4>Recent Ratings:</h4>
                            <ul>
                                <?php
                                $recentRatings = array_slice(array_reverse($item['ratings']), 0, 5);
                                foreach ($recentRatings as $r):
                                    $ratingLabel = isset($ratingLabels[$r['rating']]) ? ' - ' . $ratingLabels[$r['rating']] : '';
                                ?>
                                    <li>
                                        <?php echo str_repeat('⭐', $r['rating']); ?> (<?php echo $r['rating']; ?><?php echo htmlspecialchars($ratingLabel); ?>)
                                        <small><?php echo $r['timestamp']; ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <a href="index.php?uuid=<?php echo urlencode($uuid); ?>" class="btn btn-primary">Rate Items</a>
            <a href="index.php" class="btn btn-secondary">Change Configuration</a>
        </div>
    </div>
</body>
</html>
