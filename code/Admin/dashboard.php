<?php
require_once '../config.php';
requireLogin();
requireAdmin();
$conn = getDBConnection();

$totalBikes = $conn->query("SELECT COUNT(*) AS cnt FROM bikes")->fetch_assoc()['cnt'] ?? 0;
$availableBikes = $conn->query("SELECT COUNT(*) AS cnt FROM bikes WHERE availability_status='available'")->fetch_assoc()['cnt'] ?? 0;
$totalBookings = $conn->query("SELECT COUNT(*) AS cnt FROM bookings")->fetch_assoc()['cnt'] ?? 0;
$totalRevenue = $conn->query("SELECT IFNULL(SUM(total_price),0) AS sum FROM bookings WHERE payment_status='paid'")->fetch_assoc()['sum'] ?? 0;

$distRes = $conn->query("SELECT COALESCE(NULLIF(bike_type,''),'Other') AS type, COUNT(*) AS cnt FROM bikes GROUP BY type ORDER BY cnt DESC");
$types = []; $maxCount = 1;
while ($r = $distRes->fetch_assoc()) { $types[] = $r; if ($r['cnt'] > $maxCount) $maxCount = $r['cnt']; }

$recent = $conn->query("
    SELECT bk.*, u.full_name, b.bike_name
    FROM bookings bk
    LEFT JOIN users u ON bk.user_id = u.user_id
    LEFT JOIN bikes b ON bk.bike_id = b.bike_id
    ORDER BY   bk.bike_id
    
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/admin-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
  <aside class="sidebar">
    <div class="sidebar-header"><h2>Mero<span>Ride</span></h2><p>Admin Panel</p></div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
      <a href="bikes.php" class="nav-item"><i class="fas fa-motorcycle"></i><span>Manage Bikes</span></a>
      <a href="adminbooking.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Bookings</span></a>
      <a href="users.php" class="nav-item"><i class="fas fa-users"></i><span>Users</span></a>
      <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="content-header">
      <h1>Admin Dashboard</h1>
      <div class="user-info">Overview of your bike rental business</div>
    </header>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon bikes"><i class="fas fa-motorcycle"></i></div><div class="stat-details"><p>Total Bikes</p><h3><?php echo (int)$totalBikes; ?></h3></div></div>
      <div class="stat-card"><div class="stat-icon users"><i class="fas fa-check-circle"></i></div><div class="stat-details"><p>Available Bikes</p><h3 style="color:#27ae60;"><?php echo (int)$availableBikes; ?></h3></div></div>
      <div class="stat-card"><div class="stat-icon bookings"><i class="fas fa-calendar"></i></div><div class="stat-details"><p>Total Bookings</p><h3><?php echo (int)$totalBookings; ?></h3></div></div>
      <div class="stat-card"><div class="stat-icon pending" style="background:#f7dfb8;color:#b17700;"><span class="nepal-rupee">रु</span></div><div class="stat-details"><p>Total Revenue</p><h3>रु <?php echo number_format((float)$totalRevenue, 2); ?></h3></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
      <section class="recent-section"><h2>Bike Types Distribution</h2>
        <?php if (count($types) === 0): ?><p>No bikes yet.</p><?php else: ?>
          <?php foreach ($types as $t): $pct = round(($t['cnt'] / max(1,$maxCount)) * 100); ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
              <div style="flex:1;"><div style="font-weight:600;margin-bottom:6px;"><?php echo htmlspecialchars($t['type']); ?></div><div class="dist-bar"><div class="dist-fill" style="width:<?php echo $pct; ?>%"></div></div></div>
              <div style="width:70px;text-align:right;font-weight:600;"><?php echo $t['cnt']; ?> bikes</div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <section class="recent-section"><h2>Recent Bookings</h2>
        <?php if ($recent->num_rows == 0): ?>
          <p style="color:#777;text-align:center;margin-top:40px;">No bookings yet</p>
        <?php else: while ($row = $recent->fetch_assoc()): ?>
          <div style="padding:12px 0;border-bottom:1px solid #f1f1f1;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <div><strong><?php echo htmlspecialchars($row['full_name'] ?: 'Guest'); ?></strong><div style="color:#888;font-size:0.95rem;"><?php echo htmlspecialchars($row['bike_name'] ?: 'Unknown bike'); ?></div></div>
              <div style="text-align:right;color:#666;"><div><?php echo htmlspecialchars($row['start_date']); ?> → <?php echo htmlspecialchars($row['end_date']); ?></div><div style="font-weight:700;margin-top:6px;">रु <?php echo number_format($row['total_price'],2); ?></div></div>
            </div>
          </div>
        <?php endwhile; endif; ?>
      </section>
    </div>
  </main>
</div>
</body>
</html>
<?php $conn->close(); ?>
