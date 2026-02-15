<?php
require_once __DIR__ . '/../code/config.php';
$conn = getDBConnection();

$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $like = "%{$q}%";
    $stmt = $conn->prepare("SELECT * FROM bikes WHERE bike_name LIKE ? OR bike_model LIKE ? ORDER BY bike_id DESC");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $bikes = $stmt->get_result();
} else {
    $bikes = $conn->query("SELECT * FROM bikes ORDER BY bike_id DESC");
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Bikes / Packages</title>
  <link rel="stylesheet" href="../code/css/style.css">
  <link rel="stylesheet" href="../code/css/userhomepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .packages-wrap{max-width:1100px;margin:40px auto;padding:0 16px; font-family: sans-serif;}
    .packs-search{display:flex;gap:10px;margin-bottom:20px;}
    .packages-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;}
    .package-card{
        background:#fff;
        border-radius:10px;
        padding:14px;
        box-shadow:0 6px 18px rgba(0,0,0,0.06);
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        min-height:360px;
    }
    .package-card img{width:100%;height:150px;object-fit:cover;border-radius:8px;margin-bottom:10px;}
    .package-card a{display:block;text-align:center;text-decoration:none;padding:10px;border-radius:8px;background:#4a6cf7;color:#fff;font-weight:700;margin-top:12px}
    .badge {display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:0.85rem;}
    .avail {background:#e6ffef;color:#0b8a45;}
    .rented {background:#fff5f5;color:#b02a37;}
  </style>
</head>
<body>
  <header class="site-header">
    <nav class="nav">
      <div class="logo">Hamro <span>Ride</span></div>
      <ul class="nav-links">
       
        <?php if (!empty($_SESSION['user_id'])): ?><li><a href="userdashboard.php">Dashboard</a></li><?php endif; ?>
        <li><a href="packages.php">Packages</a></li>
        <li><a href="aboutus.html">About</a></li>
        <li><a href="contact.html">Contact</a></li>
      </ul>

      <?php if (!empty(
          $_SESSION['user_id']
      )): ?>
        <!-- User area for logged-in users -->
        <div class="user-area" id="userArea">
          <button id="userBtn" class="user-btn" aria-haspopup="true" aria-expanded="false">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
            <i class="fas fa-caret-down"></i>
          </button>

          <div id="userMenu" class="user-menu" role="menu" aria-hidden="true">
            <a href="userdashboard.php">Dashboard</a>
            <a href="mybooking.php">My Bookings</a>
            <a href="./userlogout.php">Sign out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="signup.php" class="btn btn-sm">Login/sign up</a>
      <?php endif; ?>
    </nav>
  </header>

  <div class="packages-wrap">
    <h1>Available Bikes</h1>
    <form class="packs-search" method="get" action="">
      <input type="search" name="q" placeholder="Search by name or model" value="<?php echo htmlspecialchars($q); ?>" style="flex:1;padding:10px;border-radius:8px;border:1px solid #ddd;">
      <button type="submit" style="padding:10px 16px;border-radius:8px;background:#25D366;color:#fff;border:none;font-weight:700;cursor:pointer;">Search</button>
    </form>

    <div class="packages-grid">
      <?php if ($bikes && $bikes->num_rows > 0): ?>
        <?php while ($bike = $bikes->fetch_assoc()): ?>
          <div class="package-card">
            <?php
// Decide image path (supports code/image and project-level image folders)
$filename = basename($bike['bike_image'] ?? '');
// prefer placeholder from existing locations (check code/image first)
$placeholder = file_exists(__DIR__ . '/../code/image/placeholder.svg') ? '../code/image/placeholder.svg' :
               (file_exists(__DIR__ . '/../image/placeholder.svg') ? '../image/placeholder.svg' :
               (file_exists(__DIR__ . '/../../image/placeholder.svg') ? '../../image/placeholder.svg' : ''));

if ($filename) {
    $codeDirPath = __DIR__ . '/../code/image/';       // c:/.../project/code/image
    $projectImagePath = __DIR__ . '/../image/';       // c:/.../project/image
    $projectImagePath2 = __DIR__ . '/../../image/';   // older locations

    if (file_exists($codeDirPath . $filename)) {
        $img = '../code/image/' . rawurlencode($filename);
    } elseif (file_exists($projectImagePath . $filename)) {
        $img = '../image/' . rawurlencode($filename);
    } elseif (file_exists($projectImagePath2 . $filename)) {
        $img = '../../image/' . rawurlencode($filename);
    } else {
        $img = $placeholder ?: 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#999" font-size="18">No image</text></svg>');
    }
} else {
    $img = $placeholder ?: 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#999" font-size="18">No image</text></svg>');
}
            ?>
            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($bike['bike_name']); ?>">
            <h3 style="margin:6px 0;"><?php echo htmlspecialchars($bike['bike_name']); ?></h3>
            <div style="color:#666;margin-bottom:10px;"><?php echo htmlspecialchars($bike['bike_model']); ?> • <?php echo htmlspecialchars($bike['bike_type'] ?? 'Other'); ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center; margin-bottom: 15px;">
              <div style="font-weight:700;">रु <?php echo number_format($bike['price_per_day'],2); ?>/day</div>
              <span class="badge <?php echo $bike['availability_status'] === 'available' ? 'avail' : 'rented'; ?>">
                <?php echo ucfirst($bike['availability_status']); ?>
              </span>
            </div>
            <a href="bike-details.php?bike_id=<?php echo $bike['bike_id']; ?>" style="display:block; text-align:center; text-decoration:none;padding:10px;border-radius:8px;background:#4a6cf7;color:#fff;font-weight:700;">View Details</a>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="grid-column:1/-1;padding:30px;text-align:center;color:#666;">No bikes found.</div>
      <?php endif; ?>
    </div>
  </div>

<script>
(function(){
  // user menu toggle
  const btn = document.getElementById('userBtn');
  const menu = document.getElementById('userMenu');
  if (btn && menu) {
    document.addEventListener('click', (e) => {
      if (btn.contains(e.target)) {
        const shown = menu.classList.toggle('show');
        btn.setAttribute('aria-expanded', shown ? 'true' : 'false');
        menu.setAttribute('aria-hidden', shown ? 'false' : 'true');
      } else if (!menu.contains(e.target)) {
        menu.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { menu.classList.remove('show'); btn.setAttribute('aria-expanded','false'); }
    });
  }
})();
</script>
</body>
</html>