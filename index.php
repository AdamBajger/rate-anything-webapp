<?php
// Load configuration
$config = yaml_parse_file('config.yaml');
$appTitle = $config['app']['title'] ?? 'Rate Anything';
$appDescription = $config['app']['description'] ?? 'Scan QR code to rate';
$minRating = $config['rating_scale']['min'] ?? 1;
$maxRating = $config['rating_scale']['max'] ?? 5;
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
            margin-bottom: 30px;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($appTitle); ?></h1>
        <p class="description"><?php echo htmlspecialchars($appDescription); ?></p>
        
        <div id="scanner-section">
            <div id="reader"></div>
            <div class="scanner-controls">
                <button id="start-scan-btn" onclick="startScanning()">Start Scanning</button>
                <button id="stop-scan-btn" onclick="stopScanning()" style="display:none;">Stop Scanning</button>
            </div>
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
            formData.append('item_id', currentItemId);
            formData.append('rating', selectedRating);

            fetch('rate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Rating submitted successfully!', 'success');
                    setTimeout(() => {
                        backToScanner();
                    }, 2000);
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
