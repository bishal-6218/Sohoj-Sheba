<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard – Shohoj Sheba</title>
    <link rel="stylesheet" href="css/dashboard-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-mark"><i class="fa-solid fa-house-chimney"></i></div>
            Shohoj Sheba
        </div>
        <button id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
    </div>

    <nav class="sidebar-nav">
        <a href="#" class="nav-item active" data-page="overview"><i class="fa-solid fa-home"></i> Overview</a>
        <a href="#" class="nav-item" data-page="bookings"><i class="fa-solid fa-calendar-check"></i> My Bookings</a>
        <a href="#" class="nav-item" data-page="new"><i class="fa-solid fa-plus-circle"></i> New Booking</a>
        <a href="#" class="nav-item" data-page="history"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        <a href="#" class="nav-item" data-page="profile"><i class="fa-solid fa-user"></i> Profile</a>
    </nav>
</aside>

<main class="dashboard-main">
    <header class="top-bar">
        <button id="mobileMenuBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 id="pageTitle">Overview</h1>
    </header>

    <div class="dashboard-content">

        <section id="overview-page" class="content-section active">
            <div class="welcome-banner">
                <h2>Welcome back, Rajib!</h2>
                <p>Manage your home service bookings here.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div>
                        <strong>0</strong>
                        <span>Upcoming</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
                    <div>
                        <strong>0</strong>
                        <span>Completed</span>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <h3>Quick Book</h3>
                <p>Choose a service to get started →</p>
                <!-- You can add quick service cards here later -->
            </div>
        </section>

        <section id="bookings-page" class="content-section">
            <div class="section-card">
                <h3>My Current Bookings</h3>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark fa-3x"></i>
                    <p>No active bookings right now</p>
                </div>
            </div>
        </section>

        <!-- Add other sections with similar empty-state pattern -->

    </div>
</main>

<script src="js/dashboard.js"></script>
</body>
</html>