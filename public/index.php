<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$config = loadYaml(__DIR__ . '/../config.yaml');
$data = loadYaml(__DIR__ . '/../data.yaml');

// Prepare rating bounds and labels for static rendering
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
$ratingStep = ($ratingMax - $ratingMin) / 20;

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
        
        <form id="rating-form" action="submit.php" method="POST">
        <div class="card">
            <h2>Scan QR Code</h2>
            <div id="qr-reader" style="width: 100%;"></div>
            <div id="qr-result" class="result-message"></div>
       

            <h2>Or Select from Tracked Items</h2>
                <input type="hidden" id="raw-identifier" name="raw_identifier" value="">
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
                        <input type="text" id="manual-identifier" name="manual_identifier" placeholder="e.g., Peru Light Roast">
                    </div>
                </div>
        </div>

        <div class="card">
                <div class="form-group rating-slider-group">
                    <label for="rating">Rating</label>
                    <div class="rating-labels-row">
                        <?php if (!empty($labelKeys)): ?>
                            <?php foreach ($labelKeys as $k): ?>
                                <div class="slider-label" data-value="<?php echo $k; ?>"><?php echo htmlspecialchars($labels[(string)$k] ?? $k); ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for ($i = $ratingMin; $i <= $ratingMax; $i += max(1, (int)$ratingStep)): ?>
                                <div class="slider-label" data-value="<?php echo $i; ?>"><?php echo $i; ?></div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                    <input type="range" id="rating" name="rating" min="<?php echo htmlspecialchars($ratingMin); ?>" max="<?php echo htmlspecialchars($ratingMax); ?>" step="<?php echo htmlspecialchars($ratingStep); ?>" value="<?php echo intval(($ratingMin + $ratingMax) / 2); ?>">
                    <div id="rating-value" class="rating-value"><?php echo intval(($ratingMin + $ratingMax) / 2); ?></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Rating</button>
                    <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
                </div>
            </div>
        </div>
    </form>

        <script>
        let html5QrCode;
        
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log(`QR Code detected: ${decodedText}`);

            // Parse the scanned identifier on the server to get the canonical value
            fetch('parse.php?identifier=' + encodeURIComponent(decodedText))
                .then(resp => resp.json())
                .then(data => {
                    const parsed = data.parsed ?? decodedText;
                    html5QrCode.stop().then(() => {
                        // Set parsed value into manual input and store raw identifier in a hidden field
                        document.getElementById('manual-identifier').value = parsed;
                        document.getElementById('raw-identifier').value = decodedText;
                        document.getElementById('identifier').value = '';
                        document.getElementById('qr-result').innerHTML = 
                            `<span class="success">Scanned successfully: ${parsed}</span>`;
                        document.getElementById('rating-form').scrollIntoView({ behavior: 'smooth' });
                    }).catch((err) => {
                        console.error('Failed to stop scanning:', err);
                    });
                }).catch(err => {
                    console.error('Parsing failed, using raw value:', err);
                    html5QrCode.stop().then(() => {
                        document.getElementById('manual-identifier').value = decodedText;
                        document.getElementById('raw-identifier').value = '';
                        document.getElementById('identifier').value = '';
                        document.getElementById('qr-result').innerHTML = 
                            `<span class="success">Scanned successfully: ${decodedText}</span>`;
                        document.getElementById('rating-form').scrollIntoView({ behavior: 'smooth' });
                    });
                });
        }

        function onScanFailure(error) {
        }

        document.addEventListener('DOMContentLoaded', function() {
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
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

        document.getElementById('manual-identifier').addEventListener('input', function() {
            if (this.value) {
                document.getElementById('identifier').value = '';
            }
            // If the user manually edits the field, clear any stored raw identifier
            document.getElementById('raw-identifier').value = '';
        });

        document.getElementById('identifier').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('manual-identifier').value = '';
            }
            // selecting an existing item clears any raw parsed value
            document.getElementById('raw-identifier').value = '';
        });

        document.getElementById('rating-form').addEventListener('submit', function(e) {
            const selectVal = document.getElementById('identifier').value.trim();
            const manualVal = document.getElementById('manual-identifier').value.trim();
            if (!selectVal && !manualVal) {
                e.preventDefault();
                alert('Please select an item or enter/scan an identifier before submitting.');
                return false;
            }
        });

        // Minimal JS: update the big rating indicator when slider moves
        (function(){
            const slider = document.getElementById('rating');
            const display = document.getElementById('rating-value');
            if (!slider || !display) return;
            const labelEls = Array.from(document.querySelectorAll('.slider-label'));
            function update() {
                // show integer if step >=1, otherwise show one decimal
                const step = Number(slider.step) || 1;
                const val = Number(slider.value);
                display.textContent = (step >= 1) ? String(Math.round(val)) : val.toFixed(1);
                // if the slider value exactly matches a label value, mark that label active;
                // otherwise clear any active label highlight
                let matched = false;
                labelEls.forEach(l => {
                    const lv = Number(l.dataset.value);
                    if (!Number.isNaN(lv) && Math.abs(lv - val) < 1e-6) {
                        l.classList.add('active');
                        matched = true;
                    } else {
                        l.classList.remove('active');
                    }
                });
                if (!matched) {
                    // ensure no active remains (already removed above)
                }
            }
            slider.addEventListener('input', update, {passive:true});
            // init
            update();
        })();

        // Make labels clickable: clicking a label sets the slider value
        (function(){
            const labels = document.querySelectorAll('.slider-label');
            const slider = document.getElementById('rating');
            if (!labels.length || !slider) return;
            labels.forEach(lbl => {
                lbl.addEventListener('click', function(){
                    const v = this.dataset.value;
                    if (typeof v === 'undefined') return;
                    slider.value = v;
                    // trigger input so the display updates
                    slider.dispatchEvent(new Event('input', { bubbles: true }));
                    // mark active
                    labels.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        })();

        
    </script>
</body>
</html>
