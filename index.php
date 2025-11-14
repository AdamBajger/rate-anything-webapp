<?php
/**
 * Rate Anything Webapp - UUID-based Rating System Entry Point
 * 
 * This file serves as the entrypoint for the rating application.
 * It loads a UUID from the URL-encoded GET request and uses it to
 * load a specific rating setup from the config.yaml file.
 * The loaded configuration is then used to display a QR code scanner
 * that allows rating items using the UUID-specific rating scale and categories.
 */

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Validate UUID format (RFC 4122)
 * 
 * @param string $uuid The UUID to validate
 * @return bool True if valid, false otherwise
 */
function isValidUuid($uuid) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    return preg_match($pattern, $uuid) === 1;
}

/**
 * Display error message
 * 
 * @param string $message The error message to display
 */
function displayError($message) {
    http_response_code(400);
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Error - Rate Anything</title>\n";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    echo "<style>body { font-family: Arial, sans-serif; margin: 40px; } .error { color: #d32f2f; background: #ffebee; padding: 20px; border-radius: 4px; }</style>\n";
    echo "</head>\n<body>\n";
    echo "<h1>Error</h1>\n";
    echo "<div class='error'>" . htmlspecialchars($message) . "</div>\n";
    echo "<p><a href='?'>Try again</a></p>\n";
    echo "</body>\n</html>";
    exit;
}

/**
 * Display help/instructions page with list of available configurations
 */
function displayHelp($config) {
    echo "<!DOCTYPE html>\n";
    echo "<html>\n<head>\n";
    echo "<title>Rate Anything - Select Configuration</title>\n";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    echo "<style>\n";
    echo "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }\n";
    echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }\n";
    echo "h1 { color: #333; margin-top: 0; }\n";
    echo "p { color: #666; line-height: 1.6; }\n";
    echo ".config-list { list-style: none; padding: 0; margin: 20px 0; }\n";
    echo ".config-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #667eea; }\n";
    echo ".config-item h3 { margin: 0 0 5px 0; color: #333; }\n";
    echo ".config-item p { margin: 5px 0; font-size: 14px; }\n";
    echo ".config-item a { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; }\n";
    echo ".config-item a:hover { background: #5568d3; }\n";
    echo ".uuid { font-family: monospace; font-size: 12px; color: #999; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    echo "<div class='container'>\n";
    echo "<h1>Welcome to Rate Anything</h1>\n";
    echo "<p>This application allows you to rate different things based on QR codes. Each configuration has a unique UUID that determines the rating scale and categories.</p>\n";
    echo "<h2>Available Configurations</h2>\n";
    
    if (isset($config['configs']) && is_array($config['configs'])) {
        echo "<ul class='config-list'>\n";
        foreach ($config['configs'] as $uuid => $cfg) {
            echo "<li class='config-item'>\n";
            echo "<h3>" . htmlspecialchars($cfg['name'] ?? 'Unnamed Configuration') . "</h3>\n";
            if (isset($cfg['description'])) {
                echo "<p>" . htmlspecialchars($cfg['description']) . "</p>\n";
            }
            echo "<p class='uuid'>UUID: " . htmlspecialchars($uuid) . "</p>\n";
            echo "<a href='?uuid=" . urlencode($uuid) . "'>Start Rating</a>\n";
            echo "</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>No configurations available.</p>\n";
    }
    
    echo "</div>\n";
    echo "</body>\n</html>";
}

// Main application logic
try {
    // Load configuration
    $config = yaml_parse_file('config.yaml');
    
    if (!$config) {
        displayError("Configuration file not found or could not be parsed.");
    }
    
    // Get UUID from GET request
    $uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : null;
    
    // If no UUID provided, show help page with available configurations
    if ($uuid === null || $uuid === '') {
        displayHelp($config);
        exit;
    }
    
    // Validate UUID format
    if (!isValidUuid($uuid)) {
        displayError("Invalid UUID format. Please provide a valid UUID (e.g., 550e8400-e29b-41d4-a716-446655440000).");
    }
    
    // Get rating configuration for the UUID
    if (!isset($config['configs'][$uuid])) {
        displayError("No rating configuration found for UUID: " . htmlspecialchars($uuid));
    }
    
    $ratingConfig = $config['configs'][$uuid];
    $appTitle = $ratingConfig['name'] ?? 'Rate Anything';
    $appDescription = $ratingConfig['description'] ?? 'Scan QR code to rate';
    $minRating = $ratingConfig['rating_scale']['min'] ?? 1;
    $maxRating = $ratingConfig['rating_scale']['max'] ?? 5;
    
} catch (Exception $e) {
    displayError("An error occurred: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appTitle); ?></title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 30px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .description {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .config-info {
            background: #f0f7ff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .config-info .uuid {
            font-family: monospace;
            color: #667eea;
            font-weight: bold;
        }
        #reader {
            border: 2px solid #667eea;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .scanner-controls {
            text-align: center;
            margin-bottom: 20px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        #rating-section {
            display: none;
            text-align: center;
        }
        .item-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .item-id {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            word-break: break-all;
        }
        .rating-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .rating-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 20px;
            font-weight: bold;
            background: #f0f0f0;
            color: #333;
            border: 2px solid #ddd;
        }
        .rating-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .rating-btn.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .submit-btn {
            width: 100%;
            background: #10b981;
            margin-top: 10px;
        }
        .submit-btn:hover {
            background: #059669;
        }
        .back-btn {
            width: 100%;
            background: #6b7280;
            margin-top: 10px;
        }
        .back-btn:hover {
            background: #4b5563;
        }
        .change-config-btn {
            width: 100%;
            background: #8b5cf6;
            margin-top: 10px;
        }
        .change-config-btn:hover {
            background: #7c3aed;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .categories {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .categories h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .category {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($appTitle); ?></h1>
        <p class="description"><?php echo htmlspecialchars($appDescription); ?></p>
        
        <div class="config-info">
            Configuration UUID: <span class="uuid"><?php echo htmlspecialchars($uuid); ?></span>
        </div>
        
        <?php if (isset($ratingConfig['categories']) && is_array($ratingConfig['categories'])): ?>
        <div class="categories">
            <h3>Rating Categories</h3>
            <?php foreach ($ratingConfig['categories'] as $category): ?>
                <div class="category">
                    • <?php echo htmlspecialchars($category['name']); ?>
                    <?php if (isset($category['weight'])): ?>
                        (<?php echo ($category['weight'] * 100); ?>%)
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div id="scanner-section">
            <div id="reader"></div>
            <div class="scanner-controls">
                <button id="start-scan-btn" onclick="startScanning()">Start Scanning</button>
                <button id="stop-scan-btn" onclick="stopScanning()" style="display:none;">Stop Scanning</button>
            </div>
            <button class="change-config-btn" onclick="window.location.href='?'">Change Configuration</button>
        </div>

        <div id="rating-section">
            <div class="item-info">
                <p style="font-size: 14px; color: #666; margin-bottom: 5px;">Item ID:</p>
                <div class="item-id" id="item-id"></div>
            </div>
            
            <p style="margin-bottom: 15px; color: #666;">Select your rating:</p>
            
            <div class="rating-controls" id="rating-controls">
                <?php for ($i = $minRating; $i <= $maxRating; $i++): ?>
                    <button class="rating-btn" onclick="selectRating(<?php echo $i; ?>)"><?php echo $i; ?></button>
                <?php endfor; ?>
            </div>
            
            <button class="submit-btn" id="submit-btn" onclick="submitRating()" disabled>Submit Rating</button>
            <button class="back-btn" onclick="backToScanner()">Scan Another Item</button>
        </div>

        <div id="message-container"></div>
    </div>

    <script>
        const uuid = '<?php echo addslashes($uuid); ?>';
        let html5QrcodeScanner;
        let currentItemId = null;
        let selectedRating = null;

        function startScanning() {
            const config = { 
                fps: 10,
                qrbox: { width: 250, height: 250 }
            };
            
            html5QrcodeScanner = new Html5Qrcode("reader");
            
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).then(() => {
                document.getElementById('start-scan-btn').style.display = 'none';
                document.getElementById('stop-scan-btn').style.display = 'inline-block';
            }).catch((err) => {
                showMessage(`Error starting scanner: ${err}`, 'error');
            });
        }

        function stopScanning() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    document.getElementById('start-scan-btn').style.display = 'inline-block';
                    document.getElementById('stop-scan-btn').style.display = 'none';
                }).catch((err) => {
                    console.error('Error stopping scanner:', err);
                });
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            currentItemId = decodedText;
            stopScanning();
            showRatingSection();
        }

        function onScanError(errorMessage) {
            // Handle scan error silently during continuous scanning
        }

        function showRatingSection() {
            document.getElementById('scanner-section').style.display = 'none';
            document.getElementById('rating-section').style.display = 'block';
            document.getElementById('item-id').textContent = currentItemId;
            selectedRating = null;
            document.getElementById('submit-btn').disabled = true;
            
            // Reset rating buttons
            document.querySelectorAll('.rating-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
        }

        function selectRating(rating) {
            selectedRating = rating;
            document.getElementById('submit-btn').disabled = false;
            
            // Update button states
            document.querySelectorAll('.rating-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            event.target.classList.add('selected');
        }

        function submitRating() {
            if (!selectedRating || !currentItemId) {
                showMessage('Please select a rating', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('uuid', uuid);
            formData.append('item_id', currentItemId);
            formData.append('rating', selectedRating);

            fetch('rate.php?uuid=' + encodeURIComponent(uuid), {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = data.message || 'Rating submitted successfully!';
                    if (data.data && data.data.average && data.data.count) {
                        message += ` (Average: ${data.data.average} from ${data.data.count} rating${data.data.count > 1 ? 's' : ''})`;
                    }
                    showMessage(message, 'success');
                    setTimeout(() => {
                        backToScanner();
                    }, 3000);
                } else {
                    showMessage(data.message || 'Error submitting rating', 'error');
                }
            })
            .catch(error => {
                showMessage('Error submitting rating: ' + error, 'error');
            });
        }

        function backToScanner() {
            document.getElementById('rating-section').style.display = 'none';
            document.getElementById('scanner-section').style.display = 'block';
            document.getElementById('message-container').innerHTML = '';
            currentItemId = null;
            selectedRating = null;
        }

        function showMessage(message, type) {
            const messageContainer = document.getElementById('message-container');
            messageContainer.innerHTML = `<div class="message ${type}">${message}</div>`;
            
            setTimeout(() => {
                messageContainer.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
