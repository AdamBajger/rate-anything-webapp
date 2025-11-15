<?php
// Load helper functions
require_once 'functions.php';

// Load configuration and data
$config = loadYaml('config.yaml');
$data = loadYaml('data.yaml');

// Get success message if redirected from submit
$success = isset($_GET['success']) && $_GET['success'] == '1';
$submittedIdentifier = $_GET['identifier'] ?? null;

// Calculate statistics for all items
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

// Sort by average rating (descending)
usort($leaderboard, function($a, $b) {
    if ($a['stats']['average'] === $b['stats']['average']) {
        return $b['stats']['count'] - $a['stats']['count'];
    }
    return $b['stats']['average'] <=> $a['stats']['average'];
});

// Calculate overall statistics
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
        <h1>📊 Leaderboard</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ Rating submitted successfully!
                <?php if ($submittedIdentifier): ?>
                    <strong><?php echo htmlspecialchars($submittedIdentifier); ?></strong>
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

        <?php if (empty($leaderboard)): ?>
            <div class="card">
                <p class="text-center">No ratings yet. <a href="index.php">Be the first to rate!</a></p>
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
                                <th>Min/Max</th>
                                <th>Latest Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $index => $item): ?>
                                <?php
                                $rank = $index + 1;
                                $medal = '';
                                if ($rank === 1) $medal = '🥇';
                                elseif ($rank === 2) $medal = '🥈';
                                elseif ($rank === 3) $medal = '🥉';
                                
                                $latestRating = end($item['ratings']);
                                $isRecent = $item['identifier'] === $submittedIdentifier;
                                ?>
                                <tr class="<?php echo $isRecent ? 'highlight' : ''; ?>">
                                    <td class="rank">
                                        <?php echo $medal; ?>
                                        #<?php echo $rank; ?>
                                    </td>
                                    <td class="item-name">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <small class="identifier"><?php echo htmlspecialchars($item['identifier']); ?></small>
                                    </td>
                                    <td class="average-rating">
                                        <span class="rating-stars">
                                            <?php echo str_repeat('⭐', (int)round($item['stats']['average'])); ?>
                                        </span>
                                        <span class="rating-value"><?php echo $item['stats']['average']; ?></span>
                                    </td>
                                    <td class="count"><?php echo $item['stats']['count']; ?></td>
                                    <td class="min-max">
                                        <?php echo $item['stats']['min']; ?> / <?php echo $item['stats']['max']; ?>
                                    </td>
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
                <?php foreach ($leaderboard as $item): ?>
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="rating-distribution">
                            <?php
                            // Count ratings by value
                            $distribution = [];
                            $maxRating = $config['rating']['max'] ?? 5;
                            for ($i = 1; $i <= $maxRating; $i++) {
                                $distribution[$i] = 0;
                            }
                            foreach ($item['ratings'] as $r) {
                                $distribution[$r['rating']]++;
                            }
                            
                            // Calculate percentages
                            $total = $item['stats']['count'];
                            ?>
                            <div class="distribution-bars">
                                <?php for ($i = $maxRating; $i >= 1; $i--): ?>
                                    <?php
                                    $count = $distribution[$i];
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                    ?>
                                    <div class="bar-row">
                                        <span class="bar-label"><?php echo $i; ?>⭐</span>
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
                                ?>
                                    <li>
                                        <?php echo str_repeat('⭐', $r['rating']); ?>
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
            <a href="index.php" class="btn btn-primary">Rate Another Item</a>
        </div>
    </div>
</body>
</html>
