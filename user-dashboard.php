<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard — Shohoj Sheba</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link rel="stylesheet" href="additional-styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-mark">
                <i class="fa-solid fa-house-chimney"></i>
            </div>
            <span>Shohoj<span class="logo-accent">Sheba</span></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <a href="#" class="nav-item active" data-page="overview">
            <i class="fa-solid fa-home"></i>
            <span>Overview</span>
        </a>
        <a href="#" class="nav-item" data-page="bookings">
            <i class="fa-solid fa-calendar-check"></i>
            <span>My Bookings</span>
        </a>
        <a href="#" class="nav-item" data-page="new-booking">
            <i class="fa-solid fa-plus-circle"></i>
            <span>New Booking</span>
        </a>
        <a href="#" class="nav-item" data-page="history">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>History</span>
        </a>
        <a href="#" class="nav-item" data-page="favorites">
            <i class="fa-solid fa-star"></i>
            <span>Favorites</span>
        </a>
        <a href="#" class="nav-item" data-page="profile">
            <i class="fa-solid fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-info">
                <strong id="sidebarUserName">User Name</strong>
                <span id="sidebarUserEmail">user@email.com</span>
            </div>
        </div>
        <button class="logout-btn" id="logoutBtn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>

<!-- Main Content -->
<main class="dashboard-main">
    <!-- Top Bar -->
    <header class="top-bar">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="page-title" id="pageTitle">Dashboard Overview</h1>
        <div class="top-bar-actions">
            <button class="notification-btn">
                <i class="fa-solid fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <div class="user-menu">
                <div class="user-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="dashboard-content">

        <!-- OVERVIEW PAGE -->
        <section id="overview-page" class="content-section active">
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h2>Welcome back, <span id="profileName">User</span>! 👋</h2>
                    <p>Here's what's happening with your services today.</p>
                </div>
                <button class="btn-primary" onclick="SohojShebaDashboard.showPage('new-booking')">
                    <i class="fa-solid fa-plus"></i> Book New Service
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid" id="userStats">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>৳0</h3>
                        <p>Total Spent</p>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fa-solid fa-clock"></i> Recent Bookings</h3>
                    <a href="#" onclick="SohojShebaDashboard.showPage('bookings')" class="link-btn">View All</a>
                </div>
                <div id="recentBookings" class="bookings-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-grid">
                <div class="quick-action-card" onclick="SohojShebaDashboard.quickBookService('Carpenter')">
                    <div class="qa-icon qa-green">
                        <i class="fa-solid fa-hammer"></i>
                    </div>
                    <h4>Carpenter</h4>
                    <p>Book Now</p>
                </div>
                <div class="quick-action-card" onclick="SohojShebaDashboard.quickBookService('Plumber')">
                    <div class="qa-icon qa-blue">
                        <i class="fa-solid fa-faucet-drip"></i>
                    </div>
                    <h4>Plumber</h4>
                    <p>Book Now</p>
                </div>
                <div class="quick-action-card" onclick="SohojShebaDashboard.quickBookService('Electrician')">
                    <div class="qa-icon qa-yellow">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h4>Electrician</h4>
                    <p>Book Now</p>
                </div>
                <div class="quick-action-card" onclick="SohojShebaDashboard.quickBookService('Mason')">
                    <div class="qa-icon qa-brown">
                        <i class="fa-solid fa-trowel-bricks"></i>
                    </div>
                    <h4>Mason</h4>
                    <p>Book Now</p>
                </div>
            </div>
        </section>

        <!-- BOOKINGS PAGE -->
        <section id="bookings-page" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fa-solid fa-calendar-check"></i> All Bookings</h3>
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">All</button>
                        <button class="filter-tab" data-filter="pending">Pending</button>
                        <button class="filter-tab" data-filter="accepted">Accepted</button>
                        <button class="filter-tab" data-filter="completed">Completed</button>
                    </div>
                </div>
                <div id="userBookings" class="bookings-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </section>

        <!-- NEW BOOKING PAGE -->
        <section id="new-booking-page" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fa-solid fa-plus-circle"></i> Book New Service</h3>
                </div>
                <div class="services-grid">
                    <div class="service-option" data-service="Carpenter">
                        <div class="service-icon so-green">
                            <i class="fa-solid fa-hammer"></i>
                        </div>
                        <h4>Carpenter</h4>
                        <p class="service-price">From ৳500</p>
                        <button class="btn-book" onclick="SohojShebaDashboard.quickBookService('Carpenter')">
                            Book Now
                        </button>
                    </div>
                    <div class="service-option" data-service="Plumber">
                        <div class="service-icon so-blue">
                            <i class="fa-solid fa-faucet-drip"></i>
                        </div>
                        <h4>Plumber</h4>
                        <p class="service-price">From ৳400</p>
                        <button class="btn-book" onclick="SohojShebaDashboard.quickBookService('Plumber')">
                            Book Now
                        </button>
                    </div>
                    <div class="service-option" data-service="Electrician">
                        <div class="service-icon so-yellow">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <h4>Electrician</h4>
                        <p class="service-price">From ৳450</p>
                        <button class="btn-book" onclick="SohojShebaDashboard.quickBookService('Electrician')">
                            Book Now
                        </button>
                    </div>
                    <div class="service-option" data-service="Mason">
                        <div class="service-icon so-brown">
                            <i class="fa-solid fa-trowel-bricks"></i>
                        </div>
                        <h4>Mason</h4>
                        <p class="service-price">From ৳600</p>
                        <button class="btn-book" onclick="SohojShebaDashboard.quickBookService('Mason')">
                            Book Now
                        </button>
                    </div>
                    <div class="service-option" data-service="Gardener">
                        <div class="service-icon so-teal">
                            <i class="fa-solid fa-seedling"></i>
                        </div>
                        <h4>Gardener</h4>
                        <p class="service-price">From ৳350</p>
                        <button class="btn-book" onclick="SohojShebaDashboard.quickBookService('Gardener')">
                            Book Now
                        </button>
                    </div>
                    <div class="service-option" data-service="Home Repair">
                        <div class="service-icon so-red">
                            <i class="fa-solid fa-house-circle-check"></i>
                        </div>
                        <h4>Home Repair</h4>
                        <p class="service-price">From ৳450</p>
                        <button class="btn-book" onclick="SohojShebaDashboard.quickBookService('Home Repair')">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- HISTORY PAGE -->
        <section id="history-page" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Booking History</h3>
                </div>
                <div id="bookingHistory" class="bookings-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </section>

        <!-- FAVORITES PAGE -->
        <section id="favorites-page" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fa-solid fa-star"></i> Favorite Workers</h3>
                </div>
                <div class="empty-state">
                    <i class="fa-solid fa-star"></i>
                    <h3>No Favorites Yet</h3>
                    <p>Workers you mark as favorites will appear here</p>
                </div>
            </div>
        </section>

        <!-- PROFILE PAGE -->
        <section id="profile-page" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fa-solid fa-user"></i> My Profile</h3>
                </div>
                <div class="profile-content">
                    <div class="profile-header">
                        <div class="profile-avatar-large">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="profile-info-main">
                            <h2 id="profileNameLarge">User Name</h2>
                            <p id="profileEmailLarge">user@email.com</p>
                            <span class="profile-badge">User Account</span>
                        </div>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-group">
                            <label><i class="fa-solid fa-envelope"></i> Email</label>
                            <p id="profileEmail">user@email.com</p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fa-solid fa-user"></i> Name</label>
                            <p id="profileNameDetail">User Name</p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fa-solid fa-shield-halved"></i> Account Type</label>
                            <p id="profileRole">User</p>
                        </div>
                        <div class="detail-group">
                            <label><i class="fa-solid fa-calendar"></i> Member Since</label>
                            <p id="profileJoinDate">January 2026</p>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button class="btn-secondary">
                            <i class="fa-solid fa-pen"></i> Edit Profile
                        </button>
                        <button class="btn-secondary">
                            <i class="fa-solid fa-key"></i> Change Password
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </div>
</main>

<!-- JavaScript Files -->
<script src="dashboard.js"></script>

</body>
</html>
