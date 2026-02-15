<?php
require_once '../config.php';
requireAdmin();
$conn = getDBConnection();

// Fetch bookings with related user and bike info
$sql = "SELECT b.*, u.full_name AS user_name, u.email AS user_email, bk.bike_name
        FROM bookings b
        LEFT JOIN users u ON u.user_id = b.user_id
        LEFT JOIN bikes bk ON bk.bike_id = b.bike_id
        ORDER BY b.created_at DESC";
$res = $conn->query($sql);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Bookings</title>

  <link rel="stylesheet" href="../css/admin-bookings.css">
</head>
<body>
  <div class="admin-page-wrap">
    <header class="admin-header">
      <h1>Bookings</h1>
      <p class="muted">List of booking records (most recent first)</p>
    </header>

    <main class="booking-list">
      <?php if ($res && $res->num_rows > 0): ?>
        <?php while ($b = $res->fetch_assoc()): ?>
          <div class="booking-card">
            <div class="card-header">
              <div class="meta">
                <div class="id">Booking #<?php echo (int)$b['booking_id']; ?></div>
                <div class="date"><?php echo htmlspecialchars($b['created_at'] ?? $b['start_date']); ?></div>
              </div>
              <div class="status">
                <span class="badge status-<?php echo htmlspecialchars($b['booking_status']); ?>"><?php echo htmlspecialchars(ucfirst($b['booking_status'])); ?></span>
                <span class="badge pay-<?php echo htmlspecialchars($b['payment_status']); ?>"><?php echo htmlspecialchars(ucfirst($b['payment_status'])); ?></span>
              </div>
            </div>

            <div class="card-body">
              <div class="left">
                <div class="field"><strong>Customer:</strong> <?php echo htmlspecialchars($b['customer_name'] ?: $b['user_name'] ?? '—'); ?></div>
                <div class="field"><strong>Email:</strong> <?php echo htmlspecialchars($b['customer_email'] ?: $b['user_email'] ?? '—'); ?></div>
                <div class="field"><strong>Phone:</strong> <?php echo htmlspecialchars($b['customer_phone'] ?? '—'); ?></div>
                <div class="field"><strong>License #:</strong> <?php echo htmlspecialchars($b['license_number'] ?? '—'); ?></div>

                <div class="field"><strong>Bike:</strong> <?php echo htmlspecialchars($b['bike_name'] ?: ('ID ' . (int)$b['bike_id'])); ?></div>
                <div class="field"><strong>Dates:</strong> <?php echo htmlspecialchars($b['start_date'] . ' → ' . $b['end_date']); ?> <small>(<?php echo (int)$b['total_days']; ?> days)</small></div>

                <div class="field"><strong>Pickup:</strong> <?php echo htmlspecialchars($b['pickup_location'] ?? '—'); ?></div>
                <div class="field"><strong>Drop:</strong> <?php echo htmlspecialchars($b['drop_location'] ?? '—'); ?></div>

                <div class="field"><strong>Total:</strong> रु <?php echo number_format((float)$b['total_price'], 2); ?></div>
                <div class="field"><strong>Payment:</strong> <?php echo htmlspecialchars($b['payment_method'] ?? '—'); ?> <?php if (!empty($b['payment_reference'])): ?>(Ref: <?php echo htmlspecialchars($b['payment_reference']); ?>)<?php endif; ?></div>
              </div>

              <div class="right">
                <?php if (!empty($b['payment_screenshot'])):
                  // Build URL relative to Admin folder (screenshots are stored under code/user/uploads/...)
                  $imgUrl = '../user/' . ltrim($b['payment_screenshot'], '/');
                ?>
                  <a href="<?php echo htmlspecialchars($imgUrl); ?>" target="_blank" class="screenshot-link">
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Payment screenshot" class="screenshot">
                  </a>
                <?php else: ?>
                  <div class="no-screenshot">No screenshot</div>
                <?php endif; ?>

                <div class="actions">
                  <a class="btn" href="booking_view.php?booking_id=<?php echo (int)$b['booking_id']; ?>">View</a>
                  <?php if ($b['payment_status'] === 'pending'): ?>
                    <form method="post" action="verify_payment.php" style="display:inline-block;margin:0 6px;">
                      <input type="hidden" name="booking_id" value="<?php echo (int)$b['booking_id']; ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="btn" onclick="return confirm('Approve payment for booking #<?php echo (int)$b['booking_id']; ?>?');">Approve</button>
                    </form>
                    <form method="post" action="verify_payment.php" style="display:inline-block;margin:0 6px;">
                      <input type="hidden" name="booking_id" value="<?php echo (int)$b['booking_id']; ?>">
                      <input type="hidden" name="action" value="reject">
                      <button type="submit" class="btn danger" onclick="return confirm('Reject payment for booking #<?php echo (int)$b['booking_id']; ?>?');">Reject</button>
                    </form>
                  <?php endif; ?>
                  <a class="btn danger" href="booking_delete.php?booking_id=<?php echo (int)$b['booking_id']; ?>" onclick="return confirm('Delete booking #<?php echo (int)$b['booking_id']; ?>?');">Delete</a>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty">No bookings found.</div>
      <?php endif; ?>

    </main>
  </div>
</body>
</html>
