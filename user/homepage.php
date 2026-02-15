<?php require_once __DIR__ . '/../code/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Hamro Rental | MeroRide</title>
  <link rel="stylesheet" href="../code/css/style.css">
  <link rel="stylesheet" href="../code/css/homepage.css"> <!-- new -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <header class="site-header">
    <nav class="nav">
      <div class="logo">Hamro <span>Ride</span></div>
      <ul class="nav-links">
        <li><a href="homepage.php">Home</a></li>
        <li><a href="packages.php">Packages</a></li>
        <li><a href="aboutus.html">About</a></li>
        <li><a href="contact.html">Contact</a></li>
        
      </ul>
      <a href="signup.php" class="btn btn-sm">Login/sign up</a> 
    </nav>
  </header>

  <main>
    <!-- HERO -->
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
</body>
</html>