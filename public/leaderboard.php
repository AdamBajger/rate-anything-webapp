<?php
// Public entry: leaderboard.php moved to public/
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$config = loadYaml(config_file());
$data = loadYaml(data_file());

$success = isset($_GET['success']) && $_GET['success'] == '1';
$submittedIdentifier = $_GET['identifier'] ?? null;

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

usort($leaderboard, function($a, $b) {
    if ($a['stats']['average'] === $b['stats']['average']) {
        return $b['stats']['count'] - $a['stats']['count'];
    }
    return $b['stats']['average'] <=> $a['stats']['average'];
});

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
                                                <th>Popularity</th>
                                                <th>Average Rating</th>
                                            </tr>
                                        </thead>
                    <tbody>
                        <?php
                        // Determine rating bounds from config
                        $labels = $config['rating']['labels'] ?? [];
                        $labelKeys = [];
                        if (!empty($labels) && is_array($labels)) {
                            $labelKeys = array_map('intval', array_keys($labels));
                            sort($labelKeys);
                            $ratingMin = $labelKeys[0];
                            $ratingMax = $labelKeys[count($labelKeys)-1];
                        } else {
                            $ratingMin = $config['rating']['min'] ?? 1;
                            $ratingMax = $config['rating']['max'] ?? 5;
                        }

                        foreach ($leaderboard as $index => $item):
                            // fraction between 0 and 1 relative to configured range
                            $avg = $item['stats']['average'] ?? 0;
                            $range = max(1, ($ratingMax - $ratingMin));
                            $fraction = ($avg - $ratingMin) / $range;
                            if ($fraction < 0) $fraction = 0;
                            if ($fraction > 1) $fraction = 1;
                            $percent = round($fraction * 100, 2);
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($item['identifier']); ?></td>
                                <td>
                                    <div class="stars-wrapper" title="Average <?php echo htmlspecialchars($avg); ?>">
                                        <div class="stars" aria-hidden="true">
                                            <div class="stars-top" style="width: <?php echo $percent; ?>%"><span>★★★★★</span></div>
                                            <div class="stars-bottom"><span>★★★★★</span></div>
                                        </div>
                                        <div class="stars-small"><?php echo $item['stats']['count']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo $item['stats']['average']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <a href="index.php?<?php echo instance_query(); ?>" class="btn btn-primary">Rate Another Item</a>
            <a href="download.php?<?php echo instance_query(); ?>" class="btn btn-secondary">Download Data</a>
        </div>
    </div>
    
    
</body>
</html>
