<?php
/**
 * Main Rating Interface
 * 
 * This page provides the primary interface for submitting ratings.
 * Features:
 * - QR code scanner for automatic identifier capture
 * - Dropdown selection of previously rated items
 * - Manual identifier entry
 * - Rating scale based on configuration
 * 
 * @package RateAnything
 */

// Set CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load core functions
require_once 'functions.php';

// Load application configuration and existing data
$config = loadYaml('config.yaml');
$data = loadYaml('data.yaml');

// Build list of previously tracked items for dropdown selection
$trackedItems = [];
if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as $identifier => $itemData) {
        $trackedItems[$identifier] = $itemData['name'] ?? parseIdentifier($identifier, $config);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Anything</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Rate Anything</h1>
        
        <div class="card">
            <h2>Scan QR Code</h2>
            <div id="qr-reader" style="width: 100%;"></div>
            <div id="qr-result" class="result-message"></div>
        </div>

        <div class="card">
            <h2>Or Select from Tracked Items</h2>
            <form id="rating-form" action="submit.php" method="POST">
                <div class="form-group">
                    <label for="identifier">Select Item:</label>
                    <select name="identifier" id="identifier">
                        <option value="">-- Choose an item --</option>
                        <?php foreach ($trackedItems as $id => $name): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>">
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="input-group">
                        <label for="manual-identifier">Or enter identifier manually:</label>
                        <input type="text" id="manual-identifier" name="manual_identifier" placeholder="e.g., item-001-coffee-machine">
                    </div>
                </div>

                <div class="form-group">
                    <label>Rating:</label>
                    <div class="rating-scale">
                        <?php
                        $minRating = $config['rating']['min'] ?? 1;
                        $maxRating = $config['rating']['max'] ?? 5;
                        $labels = $config['rating']['labels'] ?? [];
                        
                        for ($i = $minRating; $i <= $maxRating; $i++):
                            $label = $labels[$i] ?? $i;
                        ?>
                            <div class="rating-option">
                                <input type="radio" 
                                       id="rating-<?php echo $i; ?>" 
                                       name="rating" 
                                       value="<?php echo $i; ?>" 
                                       required>
                                <label for="rating-<?php echo $i; ?>">
                                    <span class="rating-number"><?php echo $i; ?></span>
                                    <span class="rating-label"><?php echo htmlspecialchars($label); ?></span>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Rating</button>
                    <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let html5QrCode;
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log(`QR Code detected: ${decodedText}`);
            
            // Stop scanning
            html5QrCode.stop().then(() => {
                // Set the scanned value as identifier
                document.getElementById('manual-identifier').value = decodedText;
                document.getElementById('identifier').value = '';
                
                // Show success message
                document.getElementById('qr-result').innerHTML = 
                    `<span class="success">Scanned successfully: ${decodedText}</span>`;
                
                // Scroll to rating form
                document.getElementById('rating-form').scrollIntoView({ behavior: 'smooth' });
            }).catch((err) => {
                console.error('Failed to stop scanning:', err);
            });
        }

        function onScanFailure(error) {
            // Handle scan failure silently
        }

        // Initialize QR Code scanner
        document.addEventListener('DOMContentLoaded', function() {
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
            // Start scanning
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanFailure
            ).catch((err) => {
                console.error('Unable to start scanning:', err);
                document.getElementById('qr-result').innerHTML = 
                    '<span class="error">Camera not available. Please use manual entry.</span>';
            });
        });

        // Clear identifier dropdown when manual entry is used
        document.getElementById('manual-identifier').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('identifier').value = '';
            }
        });

        // Clear manual entry when dropdown is used
        document.getElementById('identifier').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('manual-identifier').value = '';
            }
        });

        // Ensure at least one identifier is provided before submitting
        document.getElementById('rating-form').addEventListener('submit', function(e) {
            const selectVal = document.getElementById('identifier').value.trim();
            const manualVal = document.getElementById('manual-identifier').value.trim();
            if (!selectVal && !manualVal) {
                e.preventDefault();
                alert('Please select an item or enter/scan an identifier before submitting.');
                return false;
            }
        });
    </script>
</body>
</html>
