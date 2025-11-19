<?php
// Public entry: index.php moved to public/
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
                        <input type="text" id="manual-identifier" name="manual_identifier" placeholder="e.g., item-001-coffee-machine">
                    </div>
                </div>

                <div class="form-group compact-rating-group">
                    <label for="rating" class="sr-only">Rating</label>
                    <div class="rating-label-buttons">
                        <?php if (!empty($config['rating']['labels']) && is_array($config['rating']['labels'])): ?>
                            <?php foreach ($config['rating']['labels'] as $val => $label): ?>
                                <button type="button" class="rating-label-button" data-value="<?php echo (int)$val; ?>"><?php echo htmlspecialchars($label); ?></button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="compact-rating" class="compact-rating" role="slider" aria-valuemin="<?php echo $config['rating']['min'] ?? 1; ?>" aria-valuemax="<?php echo $config['rating']['max'] ?? 5; ?>" tabindex="0">
                        <div class="track">
                            <div class="fill" style="width:0%"></div>
                            <div class="ticks"></div>
                        </div>
                        <div class="value">&ndash;</div>
                    </div>
                    <input type="hidden" id="rating" name="rating" required>
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
            initCompactRating();
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

        // Compact rating implementation
        function initCompactRating() {
            const el = document.getElementById('compact-rating');
            if (!el) return;
            const track = el.querySelector('.track');
            const fill = el.querySelector('.fill');
            const valueEl = el.querySelector('.value');
            const input = document.getElementById('rating');

            const min = Number(<?php echo json_encode($config['rating']['min'] ?? 1); ?>);
            const max = Number(<?php echo json_encode($config['rating']['max'] ?? 5); ?>);
            const step = 0.1; // floating precision

            function setByRatio(ratio) {
                ratio = Math.max(0, Math.min(1, ratio));
                const raw = min + ratio * (max - min);
                const val = Math.round(raw / step) * step;
                input.value = val;
                const pct = ( (val - min) / (max - min) ) * 100;
                fill.style.width = pct + '%';
                valueEl.textContent = val.toFixed(1);
                el.setAttribute('aria-valuenow', val);
            }

            function setByValue(value) {
                const v = Number(value);
                const clamped = Math.max(min, Math.min(max, v));
                const ratio = (clamped - min) / (max - min);
                setByRatio(ratio);
                // mark active label button
                const btns = document.querySelectorAll('.rating-label-button');
                btns.forEach(b => b.classList.toggle('active', Number(b.dataset.value) === Math.round(clamped)));
            }

            // click or touch
            function handlePointer(e) {
                const rect = track.getBoundingClientRect();
                const clientX = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
                const ratio = (clientX - rect.left) / rect.width;
                setByRatio(ratio);
            }

            el.addEventListener('click', handlePointer);
            el.addEventListener('touchstart', function(e){ handlePointer(e); e.preventDefault(); });

            // label buttons: position them above the track according to value and wire clicks
            const labelButtons = Array.from(document.querySelectorAll('.rating-label-button'));
            function positionLabelButtons() {
                const rect = track.getBoundingClientRect();
                const count = labelButtons.length;
                if (count === 0) return;
                // ensure buttons are ordered by numeric value (ascending)
                labelButtons.sort((a,b) => Number(a.dataset.value) - Number(b.dataset.value));
                labelButtons.forEach((btn, idx) => {
                    const pct = (count === 1) ? 50 : (idx / (count - 1)) * 100;
                    // Dock first to left edge, last to right edge, others centered
                    if (idx === 0) {
                        btn.style.left = '0%';
                        btn.style.transform = 'translateX(0)';
                        btn.style.textAlign = 'left';
                    } else if (idx === count - 1) {
                        btn.style.left = '100%';
                        btn.style.transform = 'translateX(-100%)';
                        btn.style.textAlign = 'right';
                    } else {
                        btn.style.left = pct.toFixed(6) + '%';
                        btn.style.transform = 'translateX(-50%)';
                        btn.style.textAlign = 'center';
                    }
                });
            }
            // attach click handlers
            labelButtons.forEach(btn => {
                btn.addEventListener('click', function(){
                    const v = Number(this.dataset.value);
                    setByValue(v);
                });
            });
            // position initially and on resize
            positionLabelButtons();
            window.addEventListener('resize', function(){ positionLabelButtons(); });

            // keyboard accessibility: left/right to change
            el.addEventListener('keydown', function(ev){
                const cur = parseFloat(input.value || min);
                if (ev.key === 'ArrowLeft' || ev.key === 'ArrowDown') {
                    setByRatio( ((cur - step) - min) / (max - min) );
                    ev.preventDefault();
                } else if (ev.key === 'ArrowRight' || ev.key === 'ArrowUp') {
                    setByRatio( ((cur + step) - min) / (max - min) );
                    ev.preventDefault();
                } else if (ev.key === 'Home') {
                    setByRatio(0); ev.preventDefault();
                } else if (ev.key === 'End') { setByRatio(1); ev.preventDefault(); }
            });

            // init to middle value if not set
            setByRatio(0.5);
        }
    </script>
</body>
</html>
