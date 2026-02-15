<?php
require_once __DIR__ . '/../code/config.php';
requireLogin();
$conn = getDBConnection();

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) { header('Location: mybooking.php'); exit; }

// Get user info
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$user_email = $user['email'] ?? '';

// Fetch booking
$stmt = $conn->prepare("SELECT b.*, bk.bike_name, bk.bike_model FROM bookings b LEFT JOIN bikes bk ON b.bike_id=bk.bike_id WHERE b.booking_id = ? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) { header('Location: mybooking.php'); exit; }

// Ownership check: allow if booking customer_email matches logged in user's email
if ($booking['customer_email'] !== $user_email) {
    // Not allowed
    header('Location: mybooking.php'); exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Booking #<?php echo (int)$booking['booking_id']; ?> | MeroRide</title>
  <link rel="stylesheet" href="../code/css/style.css">
  <style>
    .container{max-width:900px;margin:30px auto;padding:16px}
    .card{background:#fff;border:1px solid #eee;padding:18px;border-radius:8px}
    .field{margin-bottom:8px}
    .label{font-weight:700}
  </style>
</head>
<body>
  <div class="container">
    <a href="mybooking.php">← Back to My Bookings</a>
    <h1>Booking #<?php echo (int)$booking['booking_id']; ?></h1>
    <div class="card">
      <div class="field"><span class="label">Bike:</span> <?php echo htmlspecialchars($booking['bike_name'] . ' ' . ($booking['bike_model'] ?? '')); ?></div>
      <div class="field"><span class="label">Customer:</span> <?php echo htmlspecialchars($booking['customer_name']); ?></div>
      <div class="field"><span class="label">Email:</span> <?php echo htmlspecialchars($booking['customer_email']); ?></div>
      <div class="field"><span class="label">Phone:</span> <?php echo htmlspecialchars($booking['customer_phone']); ?></div>
      <div class="field"><span class="label">License:</span> <?php echo htmlspecialchars($booking['license_number']); ?></div>
      <div class="field"><span class="label">Pickup:</span> <?php echo htmlspecialchars($booking['start_date']); ?></div>
      <div class="field"><span class="label">Drop-off:</span> <?php echo htmlspecialchars($booking['end_date']); ?></div>
      <div class="field"><span class="label">Days:</span> <?php echo (int)$booking['total_days']; ?></div>
      <div class="field"><span class="label">Total:</span> रु <?php echo number_format((float)$booking['total_price'],2); ?></div>
      <div class="field"><span class="label">Booking status:</span> <?php echo htmlspecialchars($booking['booking_status']); ?></div>
      <div class="field"><span class="label">Payment status:</span> <?php echo htmlspecialchars($booking['payment_status']); ?></div>
      <?php if (!empty($booking['payment_screenshot'])): ?>
        <div class="field"><span class="label">Payment proof:</span><br><img src="<?php echo htmlspecialchars('../' . ltrim($booking['payment_screenshot'], '/')); ?>" alt="Payment" style="max-width:320px;border:1px solid #eee;border-radius:8px;margin-top:8px"></div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>