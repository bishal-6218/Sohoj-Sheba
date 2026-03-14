<!-- header + sidebar same as before -->

<main class="dashboard-main">
    <header class="top-bar">
        <button id="mobileMenuBtn"><i class="fa-solid fa-bars"></i></button>
        <h1 id="pageTitle">Overview</h1>
        <div class="top-bar-actions">
            <button class="notification-btn"><i class="fa-solid fa-bell"></i></button>
        </div>
    </header>

    <div class="dashboard-content">

        <!-- Overview -->
        <section id="overview-page" class="content-section active">
            <div class="welcome-banner worker-banner">
                <h2>Welcome, <span id="workerName">Worker</span></h2>
                <p>Check your latest jobs and earnings.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f5e9;color:#2e7d32"><i class="fa-solid fa-briefcase"></i></div>
                    <div>
                        <strong>0</strong>
                        <div>Active Jobs</div>
                    </div>
                </div>
                <!-- more stat cards... -->
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="empty-state">
                    <i class="fa-solid fa-clock-rotate-left fa-2x"></i>
                    <p>No recent activity yet</p>
                </div>
            </div>
        </section>

        <!-- Other sections: jobs, my-jobs, earnings, profile ... -->
        <!-- Use similar .empty-state pattern -->

    </div>
</main>

<script src="js/dashboard.js"></script>