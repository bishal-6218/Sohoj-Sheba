<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Service Detail — Shohoj Sheba</title>

  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="additional-styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    /* Page-specific minimal styles — you can move to style.css later */
    .service-hero {
      min-height: 420px;
      background: linear-gradient(135deg, #2e7d32, #43a047);
      color: white;
      position: relative;
      display: flex;
      align-items: center;
    }

    .service-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&q=80') center/cover;
      opacity: 0.18;
    }

    .service-hero-content {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 5%;
      display: flex;
      align-items: center;
      gap: 60px;
      z-index: 2;
    }

    .service-hero-icon {
      width: 180px;
      height: 180px;
      background: rgba(255,255,255,0.95);
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 80px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.25);
      flex-shrink: 0;
    }

    .service-hero-text h1 {
      font-size: 3.2rem;
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 0.5rem;
    }

    .service-price {
      font-size: 1.4rem;
      font-weight: 700;
      background: rgba(255,255,255,0.25);
      padding: 0.6rem 1.8rem;
      border-radius: 50px;
      display: inline-block;
      backdrop-filter: blur(8px);
    }

    .detail-section {
      max-width: 1100px;
      margin: 0 auto;
      padding: 5rem 5% 8rem;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: #64748b;
      font-weight: 500;
      margin-bottom: 2.5rem;
      text-decoration: none;
    }

    .back-link:hover {
      color: #2e7d32;
    }

    .offer-item {
      background: white;
      padding: 1.8rem;
      border-radius: 16px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.07);
      text-align: center;
    }

    .offer-item i {
      font-size: 2.2rem;
      margin-bottom: 1rem;
    }

    .take-service-btn {
      background: linear-gradient(135deg, #2e7d32, #43a047);
      color: white;
      border: none;
      padding: 1.1rem 3rem;
      font-size: 1.15rem;
      font-weight: 600;
      border-radius: 16px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.8rem;
      box-shadow: 0 10px 30px rgba(46,125,50,0.35);
      transition: all 0.3s;
    }

    .take-service-btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 40px rgba(46,125,50,0.45);
    }

    @media (max-width: 900px) {
      .service-hero-content {
        flex-direction: column;
        text-align: center;
        gap: 2.5rem;
      }
      .service-hero-icon {
        margin: 0 auto;
      }
    }
  </style>
</head>
<body>

<!-- Navbar (you can reuse the same one as index or make it simpler) -->
<header id="navbar">
  <div class="navbar">
    <div class="logo">
      <div class="logo-mark">
        <i class="fa-solid fa-house-chimney"></i>
      </div>
      <span>Sohoj<span class="logo-accent">Sheba</span></span>
    </div>
    <nav>
      <a href="index.html" class="nav-link">Home</a>
      <a href="index.html#services" class="nav-link">Services</a>
    </nav>
    <div class="nav-actions">
      <a href="login.html" class="nav-btn-solid">Log In / Book</a>
    </div>
    <button class="hamburger" id="hamburger">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Hero -->
<section class="service-hero">
  <div class="service-hero-content">
    <div id="heroIcon" class="service-hero-icon"></div>
    <div class="service-hero-text">
      <h1 id="serviceName">Loading...</h1>
      <p id="serviceShortDesc" style="font-size:1.25rem; opacity:0.92; max-width:520px; margin:1rem 0 1.5rem;">
        <!-- Filled by JS -->
      </p>
      <div id="servicePrice" class="service-price">Starts from ৳—</div>
    </div>
  </div>
</section>

<!-- Main content -->
<section class="detail-section">
  <a href="index.html#services" class="back-link">
    ← Back to Services
  </a>

  <h2 style="font-size:2.4rem; font-weight:800; margin-bottom:1.2rem;">About Our <span id="serviceNameRepeat">Service</span></h2>
  <p id="aboutText" style="font-size:1.1rem; line-height:1.8; color:#4b5563; max-width:780px;">
    <!-- Filled by JS -->
  </p>

  <!-- What we offer -->
  <div style="margin:5rem 0 3rem;">
    <h3 style="font-size:1.8rem; margin-bottom:2rem; text-align:center;">What We Offer</h3>
    <div id="includesGrid" class="service-info-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.8rem;">
      <!-- Filled by JS -->
    </div>
  </div>

  <!-- Call to action -->
  <div style="text-align:center; margin:6rem 0 4rem;">
    <button onclick="window.location.href='login.html'" class="take-service-btn">
      <i class="fa-solid fa-arrow-right"></i>
      Book This Service Now
    </button>
    <p style="margin-top:1rem; color:#6b7280; font-size:0.95rem;">
      Login required to place a booking • Takes less than 30 seconds
    </p>
  </div>
</section>

<!-- Footer (minimal version – copy your full footer from index.html if desired) -->
<footer style="background:#0f172a; color:white; padding:4rem 5% 2rem; text-align:center;">
  <p>© 2026 Shohoj Sheba. All rights reserved.</p>
</footer>

<!-- Scripts -->
<script src="services.js"></script>

</body>
</html>