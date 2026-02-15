<?php
require_once __DIR__ . '/../code/config.php';
requireLogin();
// Legacy route: redirect to main mybookings page
header('Location: mybooking.php');
exit;
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Bookings | MeroRide</title>
  <link rel="stylesheet" href="../code/css/style.css">
  <link rel="stylesheet" href="../code/css/userhomepage.css">
  <style>
    .bookings-wrap{max-width:1100px;margin:40px auto;padding:0 16px;font-family:Inter,Arial,Helvetica,sans-serif}
    .booking-row{background:#fff;border:1px solid #eee;padding:14px;border-radius:8px;display:flex;justify-content:space-between;gap:12px;align-items:center}
    .booking-left{flex:1}
    .booking-right{display:flex;gap:8px;align-items:center}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:0.85rem}
    .status-pending{background:#fff4cf;color:#856404}
    .status-confirmed{background:#e6ffef;color:#0b8a45}
    .status-cancelled{background:#fff0f6;color:#b02a37}
    .pay-paid{background:#e6ffef;color:#0b8a45}
    .small{font-size:0.9rem;color:#666}
    .no-bookings{padding:40px;text-align:center;color:#666}
    .btn{display:inline-block;padding:8px 12px;border-radius:6px;background:#4a6cf7;color:#fff;text-decoration:none;font-weight:700}
  </style>
</head>
<body>
  <header class="site-header">
    <nav class="nav">
      <div class="logo">Hamro <span>Ride</span></div>
      <ul class="nav-links">
        <li><a href="homepage.php">Home</a></li>
        <li><a href="packages.php">Packages</a></li>
        <li><a href="userdashboard.php">Dashboard</a></li>
      </ul>
      <div class="user-area">
        <a href="userdashboard.php" class="small"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></a>
      </div>
    </nav>
  </header>

  <main class="bookings-wrap">
    <h1>My Bookings</h1>

    <?php if ($bookings && $bookings->num_rows > 0): ?>
      <?php while ($b = $bookings->fetch_assoc()): ?>
        <div class="booking-row" style="margin-bottom:12px;">
          <div class="booking-left">
            <div style="font-weight:700;">#<?php echo (int)$b['booking_id']; ?> — <?php echo htmlspecialchars($b['bike_name'] ?? 'Bike'); ?> <?php echo !empty($b['bike_model']) ? '('.htmlspecialchars($b['bike_model']).')' : ''; ?></div>
            <div class="small"><?php echo htmlspecialchars($b['start_date']); ?> → <?php echo htmlspecialchars($b['end_date']); ?> • <?php echo (int)$b['total_days']; ?> day(s)</div>
            <div class="small">Total: रु <?php echo number_format((float)$b['total_price'],2); ?></div>
          </div>
          <div class="booking-right">
            <div class="badge status-<?php echo htmlspecialchars($b['booking_status']); ?>"><?php echo htmlspecialchars(ucfirst($b['booking_status'])); ?></div>
            <div class="badge pay-<?php echo htmlspecialchars($b['payment_status']); ?>"><?php echo htmlspecialchars(ucfirst($b['payment_status'])); ?></div>
            <a class="btn" href="booking_view.php?booking_id=<?php echo (int)$b['booking_id']; ?>">View</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-bookings">You have no bookings yet. Browse <a href="packages.php">bikes</a> to make a reservation.</div>
    <?php endif; ?>

  </main>
</body>
</html>