<?php
// Load configuration: try known locations (robust against moved files or legacy setups)
$__cfg_paths = [
    __DIR__ . '/../code/config.php',    // canonical location
    __DIR__ . '/../config.php',         // legacy location
    __DIR__ . '/config.php'             // fallback (same folder)
];
$__cfg_loaded = false;
foreach ($__cfg_paths as $__p) {
    if (file_exists($__p)) { require_once $__p; $__cfg_loaded = true; break; }
}
if (! $__cfg_loaded) {
    http_response_code(500);
    echo "<h2>Configuration file not found</h2><p>Expected one of: <code>code/config.php</code>, <code>config.php</code> (project root) or <code>user/config.php</code>.</p>";
    exit;
}

$conn = getDBConnection();

// 1. Get the ID from the URL
$bike_id = isset($_GET['bike_id']) ? intval($_GET['bike_id']) : 0;

// 2. Fetch only THIS bike's data
$stmt = $conn->prepare("SELECT * FROM bikes WHERE bike_id = ?");
$stmt->bind_param("i", $bike_id);
$stmt->execute();
$result = $stmt->get_result();
$bike = $result->fetch_assoc();
$stmt->close();

// 3. If bike doesn't exist, send them back
if (!$bike) {
    header("Location: packages.php");
    exit();
}

// Session messages (from booking process)
$booking_error = $_SESSION['booking_error'] ?? '';
$booking_success = $_SESSION['booking_success'] ?? '';
unset($_SESSION['booking_error'], $_SESSION['booking_success']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($bike['bike_name']); ?> | MeroRide</title>
    <link rel="stylesheet" href="../code/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8f9fa; }
        .container { 
            max-width: 1200px; 
            margin: 50px auto; 
            padding: 20px; 
            display: grid; 
            grid-template-columns: 1fr 420px; 
            gap: 40px; 
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
        }
        
        @media (max-width: 968px) {
            .container { grid-template-columns: 1fr; gap: 20px; }
        }
        
        .bike-img img { 
            width: 100%; 
            border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
            display: block;
        }
        
        .bike-info h1 { 
            margin: 0 0 8px; 
            font-size: 2rem; 
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .price { 
            color: #25D366; 
            font-size: 1.8rem; 
            font-weight: 700; 
            margin-top: 12px; 
        }
        
        .specs { 
            margin-top: 24px; 
            color: #555; 
        }
        
        .specs dl { margin: 0; }
        .specs dt { 
            font-weight: 600; 
            margin-top: 14px; 
            color: #333;
            font-size: 0.95rem;
        }
        .specs dd { 
            margin: 4px 0 0 0; 
            color: #666;
            font-size: 1rem;
        }
        
        .btn-primary { 
            display: inline-block;
            padding: 12px 20px;
            background: #4a6cf7;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #3d5fd6;
        }
        
        .card { 
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-group input, 
        .form-group select { 
            width: 100%; 
            padding: 12px 14px; 
            border-radius: 8px; 
            border: 1px solid #ddd;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a6cf7;
        }
        
        .summary-top { 
            display: flex; 
            gap: 16px; 
            align-items: center; 
            margin-bottom: 20px; 
        }
        
        .summary-top img { 
            width: 100px; 
            height: 70px; 
            object-fit: cover; 
            border-radius: 8px; 
        }
        
        .summary-bike-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .summary-bike-model {
            color: #666;
            font-size: 0.9rem;
        }
        
        .line { 
            border-top: 1px solid #e8e8e8; 
            margin: 20px 0; 
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #555;
        }
        
        .total-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            font-weight: 700;
            font-size: 1.1rem;
            color: #1a1a1a;
        }
        
        .total-amount {
            font-size: 1.5rem;
            color: #25D366;
            font-weight: 800;
        }
        
        .alert { 
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .alert.error { 
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545; 
        }
        
        .alert.success { 
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745; 
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #4a6cf7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .submit-btn:hover {
            background: #3d5fd6;
        }
        
        .submit-btn:active {
            transform: translateY(1px);
        }
        
        .form-note {
            margin-top: 12px;
            font-size: 0.85rem;
            color: #666;
            text-align: center;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4a6cf7;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }

        /* QR & screenshot preview styles */
        .qr-frame { box-shadow: 0 6px 20px rgba(0,0,0,0.04); }
        #paymentScreenshotPreview { display: none; }
        #paymentScreenshotPreview.visible { display: block; }
        #paymentScreenshotPreviewImg { border-radius:6px; }
        
        /* QR Button hover effect */
        .qr-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,209,95,0.25) !important;
        }
        
        .qr-btn:hover .tooltip {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Left: bike image + details -->
    <div>
        <?php if ($booking_error): ?>
            <div class="alert error"><?php echo htmlspecialchars($booking_error); ?></div>
        <?php endif; ?>
        <?php if ($booking_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($booking_success); ?></div>
        <?php endif; ?>

        <div class="card bike-img">
            <?php
                // Resolve bike image from multiple locations (prefer code/image)
                $filename = basename($bike['bike_image'] ?? '');
                // Prefer real files; fall back to an inline SVG placeholder
                if (file_exists(__DIR__ . '/../code/image/placeholder.svg')) {
                    $placeholder = '../code/image/placeholder.svg';
                } elseif (file_exists(__DIR__ . '/../image/placeholder.svg')) {
                    $placeholder = '../image/placeholder.svg';
                } elseif (file_exists(__DIR__ . '/../../image/placeholder.svg')) {
                    $placeholder = '../../image/placeholder.svg';
                } else {
                    $placeholder = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#999" font-size="18">No image</text></svg>');
                }

                if ($filename) {
                    if (file_exists(__DIR__ . '/../code/image/' . $filename)) {
                        $imgfile = '../code/image/' . rawurlencode($filename);
                    } elseif (file_exists(__DIR__ . '/../image/' . $filename)) {
                        $imgfile = '../image/' . rawurlencode($filename);
                    } elseif (file_exists(__DIR__ . '/../../image/' . $filename)) {
                        $imgfile = '../../image/' . rawurlencode($filename);
                    } else {
                        $imgfile = $placeholder;
                    }
                } else {
                    $imgfile = $placeholder;
                }
            ?>
            <img src="<?php echo $imgfile; ?>" alt="<?php echo htmlspecialchars($bike['bike_name']); ?>">
        </div>

        <div class="card" style="margin-top:24px;">
            <div class="bike-info">
                <h1><?php echo htmlspecialchars($bike['bike_name']); ?></h1>
                <div class="price">Rs. <?php echo number_format($bike['price_per_day'], 2); ?> / day</div>
                <div class="specs">
                    <dl>
                        <dt>Model</dt>
                        <dd><?php echo htmlspecialchars($bike['bike_model'] ?? '—'); ?></dd>
                        <dt>Type</dt>
                        <dd><?php echo htmlspecialchars($bike['bike_type'] ?? '—'); ?></dd>
                        <dt>Availability Status</dt>
                        <dd><?php echo ucfirst($bike['availability_status']); ?></dd>
                    </dl>
                </div>
            </div>

            <a href="packages.php" class="back-link">← Back to All Bikes</a>
        </div>
    </div>

    <!-- Right: reservation card -->
    <aside class="card">
        <form id="reserveForm" method="POST" action="/project/code/process-booking.php">
            <input type="hidden" name="bike_id" value="<?php echo $bike['bike_id']; ?>">
            <input type="hidden" name="total_days" id="total_days" value="1">
            <input type="hidden" name="total_price" id="total_price" value="<?php echo number_format((float)$bike['price_per_day'],2,'.',''); ?>">

            <div class="summary-top">
                <img src="<?php echo $imgfile; ?>" alt="">
                <div>
                    <div class="summary-bike-name"><?php echo htmlspecialchars($bike['bike_name']); ?></div>
                    <div class="summary-bike-model"><?php echo htmlspecialchars($bike['bike_model'] ?? ''); ?></div>
                </div>
            </div>

            <div class="line"></div>

            <div>
                <div class="form-group">
                    <label for="customer_name">Driver's Name *</label>
                    <input id="customer_name" name="customer_name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="customer_email">Email Address *</label>
                    <input id="customer_email" name="customer_email" type="email" required placeholder="your.email@example.com">
                </div>

                <div class="form-group">
                    <label for="customer_phone">Phone Number *</label>
                    <input id="customer_phone" name="customer_phone" required placeholder="+977 98XXXXXXXX">
                </div>

                <div class="form-group">
                    <label for="license_number">Driver's License Number *</label>
                    <input id="license_number" name="license_number" required placeholder="Enter your license number">
                </div>

                <div class="form-group">
                    <label for="start_date">Pickup Date *</label>
                    <input id="start_date" name="start_date" type="date" required>
                </div>

                <div class="form-group">
                    <label for="end_date">Drop-off Date *</label>
                    <input id="end_date" name="end_date" type="date" required>
                </div>

                <div class="form-group">
                    <label for="pickup_location">Pickup Location</label>
                    <!-- flex: select (full width) + fixed 42px QR button -->
                    <div class="pickup-qr-wrap" data-total-source="#ui_total" style="display:flex;gap:8px;align-items:center;min-width:0;">
                        <select id="pickup_location" name="pickup_location" class="pickup-select" style="flex:1;min-width:0;height:42px;padding:8px 10px;border:1px solid #e6e6e6;border-radius:8px;background:#fff;font-size:0.95rem;">
                            <option>Rental Office</option>
                            <option>Airport</option>
                            <option>City Center</option>
                        </select>

                        <a id="scanQrBtn" class="qr-btn" href="javascript:void(0)" role="button" aria-haspopup="dialog" title="Open QR page" style="width:42px;height:42px;flex:0 0 42px;background:#00D95F;border:0;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 6px 14px rgba(2,6,23,0.08);transition:transform .18s,box-shadow .18s;position:relative;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="6" height="6" rx="1" fill="#fff"/><rect x="15" y="3" width="6" height="6" rx="1" fill="#fff"/><rect x="3" y="15" width="6" height="6" rx="1" fill="#fff"/><path d="M15 11h2v2h-2zM11 7h2v2h-2zM11 11h2v2h-2zM7 11h2v2H7z" fill="#fff"/></svg>
                            <span class="tooltip" role="tooltip" style="position:absolute;top:-36px;left:50%;transform:translateX(-50%) translateY(-6px);background:rgba(0,0,0,0.85);color:#fff;padding:6px 8px;font-size:12px;border-radius:6px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .12s, transform .12s;">Scan for Payment</span>
                        </a>
                    </div>
                </div>

                <!-- Payment screenshot replaces the visible "Drop-off location" input to enable in-page pay+upload -->
                <div class="form-group">
                    <label for="payment_screenshot">eSewa payment screenshot *</label>
                    <input id="payment_screenshot" name="payment_screenshot" type="file" accept="image/*">
                    <small class="form-note">Scan QR (shown below), complete payment in eSewa, then upload the confirmation screenshot here.</small>
                    <!-- Preview / frame for uploaded payment screenshot -->
                    <div id="paymentScreenshotPreview" style="margin-top:12px;display:none;">
                        <div style="width:110px;height:110px;border-radius:8px;overflow:hidden;border:1px solid #ececec;display:flex;align-items:center;justify-content:center;background:#fafafa;">
                            <img id="paymentScreenshotPreviewImg" src="" alt="Screenshot preview" style="width:100%;height:100%;object-fit:cover;display:block;">
                        </div>
                        <div style="font-size:12px;color:#666;margin-top:6px;">Uploaded screenshot</div>
                    </div>
                </div>
                <input type="hidden" id="drop_location" name="drop_location" value="Rental Office"> <!-- keep for server compatibility -->
            </div>

            <div class="line"></div>
            
            

            <div class="price-row">
                <div>Daily Rate</div>
                <div>Rs. <?php echo number_format($bike['price_per_day'],2); ?></div>
            </div>
            <div class="price-row">
                <div>Number of Days</div>
                <div id="ui_days">1</div>
            </div>

            <div class="line"></div>

            <div class="total-row" style="margin-bottom:20px;">
                <div>Total Amount</div>
                <div class="total-amount" id="ui_total">Rs. <?php echo number_format($bike['price_per_day'],2); ?></div>
            </div>

            <button type="button" id="openPayBtn" class="submit-btn">confirm Payment (eSewa)</button>
            <div class="form-note">Payment happens in this page — upload eSewa screenshot and click "Confirm & Book".</div>
        </form>
    </aside>
</div>

<script>
(function(){
    const pricePerDay = <?php echo json_encode((float)$bike['price_per_day']); ?>;
    const startEl = document.getElementById('start_date');
    const endEl = document.getElementById('end_date');
    const uiDays = document.getElementById('ui_days');
    const uiTotal = document.getElementById('ui_total');
    const payInput = document.getElementById('total_price');
    const daysInput = document.getElementById('total_days');

    // Set minimum date to today
    const today = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(today.getDate() + 1);
    
    const todayStr = today.toISOString().split('T')[0];
    const tomorrowStr = tomorrow.toISOString().split('T')[0];
    
    startEl.setAttribute('min', todayStr);
    endEl.setAttribute('min', todayStr);
    
    // Default dates: today & tomorrow
    startEl.value = todayStr;
    endEl.value = tomorrowStr;

    function update() {
        const sVal = startEl.value ? new Date(startEl.value) : null;
        const eVal = endEl.value ? new Date(endEl.value) : null;
        let days = 1;
        
        if (sVal && eVal) {
            const diff = eVal - sVal;
            days = Math.ceil(diff / (1000*60*60*24));
            if (days <= 0) days = 1;
        }
        
        uiDays.textContent = days;
        const total = days * pricePerDay;
        uiTotal.textContent = 'Rs. ' + total.toLocaleString('en-NP', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        payInput.value = total.toFixed(2);
        daysInput.value = days;
        
        // Update minimum end date based on start date
        if (sVal) {
            const minEnd = new Date(sVal);
            minEnd.setDate(minEnd.getDate() + 1);
            endEl.setAttribute('min', minEnd.toISOString().split('T')[0]);
        }
    }

    startEl.addEventListener('change', update);
    endEl.addEventListener('change', update);
    update();

    // Show thumbnail preview when a payment screenshot is selected
    const paymentFileEl = document.getElementById('payment_screenshot');
    const paymentPreviewWrap = document.getElementById('paymentScreenshotPreview');
    const paymentPreviewImg = document.getElementById('paymentScreenshotPreviewImg');
    if (paymentFileEl) {
        paymentFileEl.addEventListener('change', function(evt){
            const f = (evt.target.files && evt.target.files[0]) || null;
            if (!f) {
                paymentPreviewWrap.classList.remove('visible');
                paymentPreviewImg.src = '';
                return;
            }
            // Validate size (reuse same 5MB rule)
            if (f.size > 5 * 1024 * 1024) { alert('Screenshot must be under 5MB'); paymentFileEl.value = ''; return; }
            const reader = new FileReader();
            reader.onload = function(e){
                paymentPreviewImg.src = e.target.result;
                paymentPreviewWrap.classList.add('visible');
            };
            reader.readAsDataURL(f);
        });
    }

    // Function to show payment done notification
    function showPaymentNotification() {
        let popup = document.getElementById('paymentDonePopup');
        if (!popup) {
            // Create the popup if it doesn't exist
            popup = document.createElement('div');
            popup.id = 'paymentDonePopup';
            popup.style.cssText = 'position:fixed;left:50%;top:24px;transform:translateX(-50%) translateY(-20px);background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%);color:#fff;padding:16px 28px;border-radius:12px;box-shadow:0 10px 30px rgba(34,197,94,0.4);opacity:0;transition:all .5s cubic-bezier(0.68, -0.55, 0.265, 1.55);z-index:1300;font-weight:600;font-size:16px;display:flex;align-items:center;gap:12px;';
            popup.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><circle cx="12" cy="12" r="10" fill="white" opacity="0.2"/><path d="M16.7 8.3a1 1 0 010 1.4l-5 5a1 1 0 01-1.4 0l-2.5-2.5a1 1 0 111.4-1.4L11 12.58l4.3-4.3a1 1 0 011.4 0z" fill="white"/></svg><span style="letter-spacing:0.3px;">✓ Payment Done! You can now upload your screenshot.</span>';
            document.body.appendChild(popup);
        }
        
        // Show popup with bounce effect
        setTimeout(function() {
            popup.style.opacity = '1';
            popup.style.transform = 'translateX(-50%) translateY(0) scale(1)';
        }, 100);
        
        // Hide after 4 seconds
        setTimeout(function() {
            popup.style.opacity = '0';
            popup.style.transform = 'translateX(-50%) translateY(-20px) scale(0.95)';
        }, 4000);
    }

    // Open QR modal and show payment notification when user clicks Proceed
    document.getElementById('openPayBtn').addEventListener('click', function(){
        // Basic validation before showing payment panel
        const name = document.getElementById('customer_name').value.trim();
        const email = document.getElementById('customer_email').value.trim();
        const phone = document.getElementById('customer_phone').value.trim();
        const license = document.getElementById('license_number').value.trim();
        if (!name || !email || !phone || !license) {
            alert('Please fill in all required booking fields before proceeding to payment.');
            return;
        }
        
        // Open the QR modal
        if (window._qrModal) {
            window._qrModal.open();
            
            // Show "Payment Done" notification after 2.5 seconds (simulating payment completion)
            setTimeout(function() {
                showPaymentNotification();
                // Also close the QR modal after notification appears
                setTimeout(function() {
                    if (window._qrModal) {
                        window._qrModal.close();
                    }
                }, 1000);
            }, 2500);
        }
    });

    // Small helper to show in-page success notification
    function showBookingSuccess(bookingId) {
        let popup = document.getElementById('bookingSuccessPopup');
        if (!popup) return;
        popup.querySelector('.msg').textContent = 'Booking confirmed — ID #' + bookingId;
        popup.classList.add('visible');
        setTimeout(() => popup.classList.remove('visible'), 2500);
    }
})();
    </script>

    <!-- Inline booking success popup -->
    <div id="bookingSuccessPopup" style="position:fixed;left:50%;top:24px;transform:translateX(-50%);background:#22c55e;color:#fff;padding:12px 18px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,0.12);opacity:0;transition:all .3s;z-index:1100;" class="">
        <span class="msg">Booking confirmed</span>
    </div>

    <!-- QR modal (opens when QR button clicked) -->
    <div id="qrModal" class="qr-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle" style="position:fixed;inset:0;align-items:center;justify-content:center;z-index:1200;">
      <div class="qr-modal__backdrop" data-close="true" style="position:absolute;inset:0;background:rgba(2,6,23,0.45);backdrop-filter:blur(6px);"></div>
      <div class="qr-modal__content" role="document" style="position:relative;width:92%;max-width:420px;background:#fff;border-radius:12px;padding:18px;box-shadow:0 18px 60px rgba(2,6,23,0.18);transform:translateY(20px);opacity:0;transition:transform .28s,opacity .28s;">
        <button class="qr-modal__close" id="qrCloseBtn" aria-label="Close" style="position:absolute;right:12px;top:12px;border:0;background:transparent;font-size:18px;cursor:pointer;color:#555;">✕</button>
        <h3 id="qrModalTitle" style="margin:0 0 12px;font-size:1.1rem;color:#111827;">Scan to Pay</h3>
        <div class="qr-modal__body" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap;">
          <div class="qr-modal__qrwrap" style="flex:0 0 200px;width:200px;height:200px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;">
            <!-- placeholder QR (200x200) -->
            <svg width="200" height="200" viewBox="0 0 200 200" role="img" aria-label="QR code placeholder"><rect width="200" height="200" fill="#f3f4f6"/><rect x="18" y="18" width="46" height="46" fill="#111827"/><rect x="136" y="18" width="46" height="46" fill="#111827"/><rect x="18" y="136" width="46" height="46" fill="#111827"/><rect x="90" y="90" width="20" height="20" fill="#111827"/></svg>
          </div>
          <div class="qr-modal__info" style="flex:1;min-width:180px;">
            <p class="instructions" style="margin:0 0 12px;color:#444;font-size:0.95rem;">Open your eSewa app and scan the QR code. After payment, upload the payment screenshot to confirm your booking.</p>
            <div class="amount-row" style="font-weight:700;color:#111827;font-size:1.05rem;">Amount: <strong id="qrModalTotal">रु 0.00</strong></div>
          </div>
        </div>
      </div>
    </div>

    <style>
      /* modal visibility (hidden by default, shown when .visible is added) */
      .qr-modal { display: none; }
      .qr-modal.visible { display:flex; }
      .qr-modal.visible .qr-modal__content { transform:translateY(0); opacity:1; }
      @media (max-width:520px) { .qr-modal__body{flex-direction:column;align-items:center;} .qr-modal__qrwrap{width:180px;height:180px;} }
      #bookingSuccessPopup.visible { opacity:1; transform:translateX(-50%) translateY(0); }
    </style>

    <script>
      (function(){
        const scanBtn = document.getElementById('scanQrBtn');
        const modal = document.getElementById('qrModal');
        const closeBtn = document.getElementById('qrCloseBtn');
        const backdrop = modal.querySelector('.qr-modal__backdrop');
        const pickupWrap = document.querySelector('.pickup-qr-wrap');
        const totalSource = pickupWrap && pickupWrap.dataset.totalSource;
        const totalEl = document.getElementById('qrModalTotal');

        function readTotal() {
          let val = 'रु 0.00';
          try {
            if (totalSource) {
              const src = document.querySelector(totalSource);
              if (src && src.textContent.trim()) val = src.textContent.trim();
            } else {
              const ui = document.getElementById('ui_total');
              if (ui && ui.textContent.trim()) val = ui.textContent.trim();
            }
          } catch(e){}
          totalEl.textContent = val;
        }

        function openModal(){ readTotal(); modal.classList.add('visible'); modal.setAttribute('aria-hidden','false'); closeBtn.focus(); document.addEventListener('keydown', onEsc); }
        function closeModal(){ modal.classList.remove('visible'); modal.setAttribute('aria-hidden','true'); scanBtn.focus(); document.removeEventListener('keydown', onEsc); }
        function onEsc(e){ if (e.key === 'Escape') closeModal(); }

        scanBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal();
        });
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        modal.querySelector('.qr-modal__content').addEventListener('click', function(e){ e.stopPropagation(); });
        modal.addEventListener('click', function(e){ if (e.target && e.target.dataset && e.target.dataset.close) closeModal(); });
        window._qrModal = { open: openModal, close: closeModal };
      })();
    </script>
  </body>
</html>