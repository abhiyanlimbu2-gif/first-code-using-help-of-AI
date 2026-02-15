<?php
require_once __DIR__ . '/../code/config.php';
requireLogin();

$access_error = $_SESSION['access_error'] ?? '';
unset($_SESSION['access_error']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>User Dashboard | MeroRide</title>
  <link rel="stylesheet" href="../code/css/userhomepage.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <header class="site-header">
    <nav class="nav">
      <div class="logo">Hamro <span>Ride</span></div>
      <ul class="nav-links">
        
        <li><a href="packages.php">Packages</a></li>
        <li><a href="aboutus.html">About</a></li>
        <li><a href="contact.html">Contact</a></li>
        
      </ul>

      <!-- User face icon + dropdown -->
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
    </nav>
  </header>

  <main>
    <?php if (!empty($access_error)): ?>
      <div style="max-width:1100px;margin:18px auto;padding:12px 16px;border-radius:8px;background:#fff4f4;color:#721c24;border-left:4px solid #dc3545;"><?php echo htmlspecialchars($access_error); ?></div>
    <?php endif; ?>
    <!-- HERO (copied from homepage) -->
    <section class="hero">
      <div class="hero-inner">
        <div class="hero-left">
          <h1 class="hero-title">Hamro Rental, <span>MeroRide</span></h1>
          <p class="hero-sub">Discover Kathmandu with ease! Our affordable bike and scooter rentals offer reliable and professional service.</p>
          <div class="hero-cta">
            <a href="packages.php" class="btn btn-primary">Browse Bikes</a>
            <a href="aboutus.html" class="btn btn-outline">Learn More</a>
          </div>
          <div class="trust">
            <div class="trust-item"><i class="fas fa-shield-alt"></i> Fully Insured</div>
            <div class="trust-item"><i class="fas fa-clock"></i> 24/7 Support</div>
          </div>
        </div>

        <div class="hero-right">
          <div class="art-card">
            
          </div>
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section class="stats-row">
      <div class="stat"><strong>50+</strong><span>Bikes Available</span></div>
      <div class="stat"><strong>500+</strong><span>Happy Riders</span></div>
      <div class="stat"><strong>10+</strong><span>Locations</span></div>
      <div class="stat"><strong>24/7</strong><span>Support</span></div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="how-it-works">
      <h2>How It Works</h2>
      <p class="subtle">Rent a bike in just 3 simple steps</p>
      <div class="how-grid">
        <div class="how-card">
          <div class="how-num">01</div>
          <h4>Choose Your Bike</h4>
          <p>Browse our collection and pick the perfect ride for your journey.</p>
        </div>
        <div class="how-card">
          <div class="how-num">02</div>
          <h4>Book & Reserve</h4>
          <p>Fill in your details, pick your dates, and reserve instantly.</p>
        </div>
        <div class="how-card">
          <div class="how-num">03</div>
          <h4>Ride & Explore</h4>
          <p>Pick up your bike and start exploring Kathmandu at your pace.</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">© <?php echo date('Y'); ?> MeroRide — All rights reserved</div>
  </footer>

  <script>
    (function(){
      const btn = document.getElementById('userBtn');
      const menu = document.getElementById('userMenu');

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
    })();
  </script>
</body>
</html>