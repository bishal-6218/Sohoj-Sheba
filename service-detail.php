<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service – Shohoj Sheba</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header id="navbar">
    <div class="navbar">
        <div class="logo">Shohoj<span class="logo-accent">Sheba</span></div>
        <a href="index.php" class="back-link">← Back to Home</a>
    </div>
</header>

<section class="service-hero">
    <div class="service-hero-content">
        <div id="heroIcon" class="service-hero-icon"></div>
        <div>
            <h1 id="serviceName"></h1>
            <p id="serviceShortDesc"></p>
            <div id="servicePrice" class="service-price"></div>
        </div>
    </div>
</section>

<section class="detail-section">
    <h2>About this Service</h2>
    <p id="aboutText"></p>

    <h3>What’s Included</h3>
    <div id="includesGrid" class="includes-grid"></div>

    <div class="cta-center">
        <a href="login.php" class="btn-solid">Book This Service</a>
    </div>
</section>

<script src="js/services.js"></script>
</body>
</html>