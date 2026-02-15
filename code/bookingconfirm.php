<?php
session_start();
require_once '../config.php'; // correct relative path to config

// get booking id ...


// Fetch booking details
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT b.*, bk.bike_name, bk.bike_model, bk.bike_image, bk.price_per_day
    FROM bookings b
    JOIN bikes bk ON b.bike_id = bk.bike_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$booking) {
    header("Location: user/packages.php");
    exit();
}

$success_message = $_SESSION['booking_success'] ?? '';
unset($_SESSION['booking_success']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Booking Confirmation | MeroRide</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon svg {
            width: 80px;
            height: 80px;
            fill: #28a745;
            background: white;
            border-radius: 50%;
            padding: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        
        h1 {
            margin: 0 0 10px;
            font-size: 2rem;
            color: #1a1a1a;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1.05rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }
        
        .booking-id {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .booking-id label {
            display: block;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .booking-id .id-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #4a6cf7;
        }
        
        .bike-summary {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .bike-summary img {
            width: 120px;
            height: 85px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .bike-summary-info h3 {
            margin: 0 0 5px;
            font-size: 1.3rem;
            color: #1a1a1a;
        }
        
        .bike-summary-info p {
            margin: 0;
            color: #666;
        }
        
        .details-section {
            margin-bottom: 30px;
        }
        
        .details-section h3 {
            margin: 0 0 15px;
            font-size: 1.1rem;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #e8e8e8;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1a1a1a;
            font-weight: 600;
            text-align: right;
        }
        
        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.3rem;
        }
        
        .total-label {
            font-weight: 700;
            color: #333;
        }
        
        .total-amount {
            font-weight: 800;
            color: #25D366;
            font-size: 1.6rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: #4a6cf7;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3d5fd6;
        }
        
        .btn-secondary {
            background: white;
            color: #4a6cf7;
            border: 2px solid #4a6cf7;
        }
        
        .btn-secondary:hover {
            background: #f8f9fa;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #4a6cf7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .info-box p {
            margin: 0;
            color: #1a1a1a;
            line-height: 1.6;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .actions, .btn {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
    </div>
    
    <div class="card">
        <h1>🎉 Booking Confirmed!</h1>
        <p class="subtitle">Thank you for choosing MeroRide</p>
        
        <?php if ($success_message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="booking-id">
            <label>Booking ID</label>
            <div class="id-number">#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>
        
        <div class="bike-summary">
            <?php
            $imgfile = !empty($booking['bike_image']) ? 'image/' . rawurlencode($booking['bike_image']) : 'image/placeholder.svg';
            ?>
            <img src="<?php echo $imgfile; ?>" alt="<?php echo htmlspecialchars($booking['bike_name']); ?>">
            <div class="bike-summary-info">
                <h3><?php echo htmlspecialchars($booking['bike_name']); ?></h3>
                <p><?php echo htmlspecialchars($booking['bike_model']); ?></p>
            </div>
        </div>
        
        <div class="details-section">
            <h3>Customer Information</h3>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">License Number</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['license_number']); ?></span>
            </div>
        </div>
        
        <div class="details-section">
            <h3>Rental Details</h3>
            <div class="detail-row">
                <span class="detail-label">Pickup Date</span>
                <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['start_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Drop-off Date</span>
                <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['end_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Rental Duration</span>
                <span class="detail-value"><?php echo $booking['total_days']; ?> day<?php echo $booking['total_days'] > 1 ? 's' : ''; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pickup Location</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['pickup_location']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Drop-off Location</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['drop_location']); ?></span>
            </div>
        </div>
        
        <div class="details-section">
            <h3>Booking Status</h3>
            <div class="detail-row">
                <span class="detail-label">Booking Status</span>
                <span class="detail-value">
                    <span class="status-badge status-pending"><?php echo ucfirst($booking['booking_status']); ?></span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status</span>
                <span class="detail-value">
                    <span class="status-badge status-unpaid"><?php echo ucfirst($booking['payment_status']); ?></span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Booking Date</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($booking['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="total-section">
            <div class="detail-row" style="border: none; margin-bottom: 10px;">
                <span class="detail-label">Daily Rate</span>
                <span class="detail-value">रु <?php echo number_format($booking['price_per_day'], 2); ?></span>
            </div>
            <div class="detail-row" style="border: none; margin-bottom: 15px;">
                <span class="detail-label">Number of Days</span>
                <span class="detail-value"><?php echo $booking['total_days']; ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Total Amount</span>
                <span class="total-amount">रु <?php echo number_format($booking['total_price'], 2); ?></span>
            </div>
        </div>
        
        <div class="info-box">
            <p><strong>📧 What's Next?</strong></p>
            <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($booking['customer_email']); ?></strong>. Our team will contact you within 24 hours to confirm your booking and discuss payment options.</p>
        </div>
        
        <div class="actions">
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Confirmation</button>
            <a href="user/packages.php" class="btn btn-primary">Browse More Bikes</a>
        </div>
    </div>
</div>

</body>
</html>