<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

 $instance_id = get_instance_id();
 $config = loadYaml(config_file($instance_id));
 $data = loadYaml(data_file($instance_id));

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
        $trackedItems[$identifier] = $itemData['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['ui']['title'] ?? 'Rate Anything'); ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($config['ui']['title'] ?? 'Rate Anything'); ?></h1>
        
        <form id="rating-form" action="submit.php" method="POST">
        <div class="card">
            <?php if (!empty($config['ui']['instructions'])): ?>
                <p class="instructions"><?php echo htmlspecialchars($config['ui']['instructions']); ?></p>
            <?php endif; ?>
            <h2><?php echo htmlspecialchars(translate('choose_or_scan', $config)); ?></h2>

            <h3><?php echo htmlspecialchars(translate('scan_qr', $config)); ?></h3>
            <div id="qr-reader" style="width: 100%;"></div>
            <div id="qr-result" class="result-message"></div>

            <h3><?php echo htmlspecialchars(translate('select_tracked', $config)); ?></h3>
            <input type="hidden" name="instance" value="<?php echo htmlspecialchars(get_instance_id()); ?>">

                <div class="form-group">
                    <label for="parsed-select"><?php echo htmlspecialchars(translate('select_item_label', $config)); ?></label>
                    <select id="parsed-select">
                        <option value=""><?php echo htmlspecialchars(translate('choose_item_placeholder', $config)); ?></option>
                        <?php foreach ($trackedItems as $rawId => $name): ?>
                            <?php $parsed = htmlspecialchars(parseIdentifier($rawId, $config)); ?>
                            <option value="<?php echo htmlspecialchars($rawId); ?>"><?php echo $parsed; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            <div class="form-group">
                <label for="identifier"><?php echo htmlspecialchars(translate('manual_entry_label', $config)); ?></label>
                <input type="text" id="identifier" name="identifier" placeholder="<?php echo htmlspecialchars(translate('manual_placeholder', $config)); ?>" autocomplete="off">
            </div>
        </div>

        <div class="card">
                <div class="form-group rating-slider-group">
                    <label for="rating"><?php echo htmlspecialchars(translate('rating_label', $config)); ?></label>
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
                    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(translate('submit_button', $config)); ?></button>
                    <a href="leaderboard.php?<?php echo instance_query(); ?>" class="btn btn-secondary"><?php echo htmlspecialchars(translate('view_leaderboard', $config)); ?></a>
                </div>
            </div>
        </div>
    </form>

        <script>
        // Localized strings for client-side messages
        const I18N = <?php echo json_encode([
            'camera_unavailable' => translate('camera_unavailable', $config),
            'scanned_success' => translate('scanned_success', $config),
            'validation_select_or_manual' => translate('validation_select_or_manual', $config)
        ]); ?>;

        const INSTANCE_QS = '<?php echo instance_query(); ?>';

        let html5QrCode;

        function debounce(fn, wait) {
            let t;
            return function(...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        function fetchParsed(raw) {
            if (!raw) return Promise.resolve('');
            const url = 'parse.php' + (INSTANCE_QS ? ('?' + INSTANCE_QS + '&identifier=' + encodeURIComponent(raw)) : ('?identifier=' + encodeURIComponent(raw)));
            return fetch(url)
                .then(r => r.json())
                .then(data => data.parsed ?? '')
                .catch(() => '');
        }

        function updateSelectForRaw(raw, parsed) {
            const sel = document.getElementById('parsed-select');
            if (!sel) return;
            // find option with value == raw
            let found = null;
            for (let opt of sel.options) {
                if (opt.value === raw) { found = opt; break; }
            }
            if (found) {
                sel.value = raw;
            } else {
                // add a manual option
                const opt = document.createElement('option');
                opt.value = raw;
                opt.text = parsed || raw;
                opt.setAttribute('data-manual', '1');
                sel.appendChild(opt);
                sel.value = raw;
            }
        }

        function handleIdentifierInput(raw) {
            if (!raw) return;
            fetchParsed(raw).then(parsed => {
                updateSelectForRaw(raw, parsed || raw);
                // show parsed in the qr-result area as feedback
                const rr = document.getElementById('qr-result');
                if (rr) rr.innerHTML = `<span class="success">${I18N.scanned_success}: ${parsed || raw}</span>`;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            html5QrCode = new Html5Qrcode("qr-reader");

            const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };

            function onScanSuccess(decodedText, decodedResult) {
                console.log('QR Code detected:', decodedText);
                // stop scanner then process
                html5QrCode.stop().then(() => {
                    document.getElementById('identifier').value = decodedText;
                    handleIdentifierInput(decodedText);
                    document.getElementById('rating-form').scrollIntoView({ behavior: 'smooth' });
                }).catch(err => console.error('Failed to stop scanning:', err));
            }

            function onScanFailure(error) {
                // ignore
            }

            html5QrCode.start({ facingMode: 'environment' }, qrConfig, onScanSuccess, onScanFailure).catch((err) => {
                console.error('Unable to start scanning:', err);
                const rr = document.getElementById('qr-result');
                if (rr) rr.innerHTML = `<span class="error">${I18N.camera_unavailable}</span>`;
            });

            const idInput = document.getElementById('identifier');
            const sel = document.getElementById('parsed-select');
            const debouncedHandler = debounce((e) => handleIdentifierInput(e.target.value.trim()), 500);
            if (idInput) idInput.addEventListener('input', debouncedHandler);

            if (sel) sel.addEventListener('change', function() {
                const raw = sel.value || '';
                document.getElementById('identifier').value = raw;
            });

            document.getElementById('rating-form').addEventListener('submit', function(e) {
                const raw = (document.getElementById('identifier').value || '').trim();
                if (!raw) {
                    e.preventDefault();
                    alert(I18N.validation_select_or_manual);
                    return false;
                }
            });
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
