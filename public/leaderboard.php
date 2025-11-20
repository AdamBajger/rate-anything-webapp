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

$config = loadYaml(__DIR__ . '/../config.yaml');
$data = loadYaml(__DIR__ . '/../data.yaml');

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
