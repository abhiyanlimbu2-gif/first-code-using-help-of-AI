<?php
require_once __DIR__ . '/code/config.php';
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) { header("Location: packages.php"); exit; }

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT b.*, bk.bike_name, bk.bike_model, bk.price_per_day FROM bookings b JOIN bikes bk ON b.bike_id=bk.bike_id WHERE b.booking_id=? LIMIT 1");
$stmt->bind_param("i",$booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) { header("Location: packages.php"); exit; }
if ($booking['payment_status'] === 'paid') {
    // already paid -> go to confirm
    header("Location: bookingconfirm.php?booking_id={$booking_id}"); exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | MeroRide</title>
    <link rel="stylesheet" href="code/css/paymentstyle.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="payment-header">
            <h1>Complete Your Payment</h1>
            <p>Secure payment gateway</p>
        </div>

        <!-- Booking Details / Invoice -->
        <div class="booking-details invoice">
            <div class="invoice-header">
                <h2>Invoice</h2>
                <div class="invoice-meta">Booking #<?php echo htmlspecialchars($booking['booking_id']); ?></div>
            </div>
            <div class="invoice-body">
                <div class="detail-row"><span class="detail-label">Customer:</span><span class="detail-value"><?php echo htmlspecialchars($booking['customer_name'] ?? ''); ?></span></div>
                <div class="detail-row"><span class="detail-label">Bike:</span><span class="detail-value"><?php echo htmlspecialchars($booking['bike_name']); ?> <?php echo !empty($booking['bike_model']) ? '('.htmlspecialchars($booking['bike_model']).')' : ''; ?></span></div>
                <div class="detail-row"><span class="detail-label">Pickup:</span><span class="detail-value"><?php echo htmlspecialchars($booking['start_date']); ?> at <?php echo htmlspecialchars($booking['pickup_location'] ?? ''); ?></span></div>
                <div class="detail-row"><span class="detail-label">Drop-off:</span><span class="detail-value"><?php echo htmlspecialchars($booking['end_date']); ?> at <?php echo htmlspecialchars($booking['drop_location'] ?? ''); ?></span></div>
                <div class="detail-row"><span class="detail-label">Duration:</span><span class="detail-value"><?php echo intval($booking['total_days'] ?? 1); ?> day(s)</span></div>
                <div class="detail-row"><span class="detail-label">Price / day:</span><span class="detail-value">रु <?php echo number_format($booking['price_per_day'],2); ?></span></div>
                <div class="detail-row total-line"><span class="detail-label">Total:</span><span class="detail-value">रु <?php echo number_format($booking['total_price'],2); ?></span></div>
            </div>
        </div>



        <!-- Payment Methods -->
        <div class="payment-methods">
            <h3>Choose Payment Method</h3>
            
            <form id="paymentForm" method="post" action="user/process-payment.php" enctype="multipart/form-data">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                <input type="hidden" name="method" id="selectedMethod" value="">
                
                <div class="payment-options">
                    <!-- eSewa -->
                    <div class="payment-card" onclick="selectPayment('esewa')">
                        <div class="payment-logo">
                            <img src="https://esewa.com.np/common/images/esewa-logo.png" alt="eSewa" style="width: 100%; height: auto;">
                        </div>
                        <h4>eSewa</h4>
                        <p>Pay via QR Code</p>
                    </div>
                </div>

                <div class="payment-note">
                    <strong>Note:</strong> For eSewa payments, scan the QR code with your app, complete the payment, and upload the screenshot as proof.
                </div>

                <!-- Hidden file input (belongs to the form so uploaded files are submitted) -->
                <input type="file" id="esewaScreenshot" name="payment_screenshot" class="file-input" accept="image/*" onchange="handleFileUpload(this, 'esewa')" style="display:none;">

            </form>
        </div>
    </div>



    <!-- eSewa QR Modal -->
    <div id="esewaModal" class="qr-modal">
        <div class="qr-content">
            <button class="qr-close" onclick="closeModal('esewaModal')">&times;</button>
            <h3>Pay with eSewa</h3>
            
            <div class="qr-image">
                <!-- Prefer project QR image (absolute path) when available -->
                <?php $esewa_modal_qr = file_exists(__DIR__ . '/code/image/qrimage.jpg') ? '/project/code/image/qrimage.jpg' : '/project/qrimage.png'; ?>
                <div style="width:180px;padding:12px;border-radius:10px;border:1px dashed #e6e6e6;background:#fff;display:flex;align-items:center;justify-content:center;">
                    <img src="<?php echo $esewa_modal_qr; ?>" alt="eSewa QR Code" id="esewaQR" style="width:100%;height:auto;">
                </div>
                <p style="color: #999; font-size: 12px; margin-top: 10px;">Scan this QR in your eSewa app, then upload the confirmation screenshot below.</p>
            </div>

            <div class="qr-instructions">
                <ol>
                    <li>Open your eSewa app</li>
                    <li>Scan this QR code</li>
                    <li>Complete payment of रु <?php echo number_format($booking['total_price'], 2); ?></li>
                    <li>Take a screenshot of payment confirmation</li>
                    <li>Upload screenshot below</li>
                </ol>
            </div>

            <div class="upload-section">
                <h4>Upload Payment Screenshot</h4>
                <div class="upload-area" onclick="document.getElementById('esewaScreenshot').click()">
                    <p>📸 Click to upload screenshot</p>
                    <p style="font-size: 12px; color: #999;">JPG, PNG (Max 5MB)</p>
                </div>
                <div id="esewaFilePreview"></div>
            </div>

            <button type="submit" class="btn btn-success" onclick="submitPayment('esewa')">Confirm Payment</button>
            <button type="button" class="btn btn-secondary" onclick="closeModal('esewaModal')">Cancel</button>
        </div>
    </div>

    <script>
        let selectedPaymentMethod = '';

        function selectPayment(method) {
            // Remove selection from all cards
            document.querySelectorAll('.payment-card').forEach(card => card.classList.remove('selected'));

            // Add selection to clicked card
            event.currentTarget.classList.add('selected');
            selectedPaymentMethod = method;
            document.getElementById('selectedMethod').value = method;

            // Only eSewa is supported here; open its modal
            if (method === 'esewa') {
                document.getElementById('esewaModal').classList.add('active');
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function handleFileUpload(input, method) {
            const file = input.files[0];
            const previewDiv = document.getElementById(method + 'FilePreview');
            previewDiv.innerHTML = '';
            if (!file) return;

            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) { alert('File size should not exceed 5MB'); input.value = ''; return; }

            // If image — show thumbnail preview, otherwise show filename
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.style.maxWidth = '220px';
                img.style.border = '1px solid #eee';
                img.style.borderRadius = '8px';
                img.style.display = 'block';
                img.style.marginTop = '8px';

                const reader = new FileReader();
                reader.onload = function(e) { img.src = e.target.result; };
                reader.readAsDataURL(file);

                previewDiv.appendChild(img);
                const meta = document.createElement('div');
                meta.style.fontSize = '12px';
                meta.style.color = '#666';
                meta.style.marginTop = '6px';
                meta.textContent = file.name + ' • ' + Math.round(file.size/1024) + ' KB';
                previewDiv.appendChild(meta);

                const remove = document.createElement('div');
                remove.style.fontSize = '12px';
                remove.style.color = '#c00';
                remove.style.cursor = 'pointer';
                remove.style.marginTop = '6px';
                remove.textContent = 'Remove';
                remove.onclick = function(){ input.value = ''; previewDiv.innerHTML = ''; };
                previewDiv.appendChild(remove);
            } else {
                previewDiv.innerHTML = `<div class="uploaded-file"><span>✓ ${file.name}</span><span class="remove-file" onclick="removeFile('${method}')">Remove</span></div>`;
            }
        }

        function removeFile(method) {
            document.getElementById(method + 'Screenshot').value = '';
            document.getElementById(method + 'FilePreview').innerHTML = '';
        }

        function submitPayment(method) {
            const fileInput = document.getElementById(method + 'Screenshot');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                alert('Please upload payment screenshot before confirming');
                return;
            }
            // Ensure method is set and submit
            document.getElementById('selectedMethod').value = method;
            document.getElementById('paymentForm').submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('qr-modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>