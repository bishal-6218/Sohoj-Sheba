const SohojShebaDashboard = {

    currentUser: null,

    init() {
        this.checkAuth();
        this.setupNavigation();
        this.setupLogout();
    },

    // ─── Auth check ───────────────────────────────────
    checkAuth() {
        // TODO: Replace with → fetch('api/session.php')
        /*
        fetch('api/session.php')
            .then(r => r.json())
            .then(data => {
                if (!data.loggedIn) { window.location.href = 'login.html'; return; }
                this.currentUser = data.user;
                const isWorkerPage = document.body.classList.contains('worker-dashboard');
                if (isWorkerPage && data.user.role !== 'worker') window.location.href = 'user-dashboard.html';
                if (!isWorkerPage && data.user.role === 'worker') window.location.href = 'worker-dashboard.html';
                this.populateUserUI(data.user);
                if (isWorkerPage) this.loadWorkerContent();
                else this.loadUserContent();
            })
            .catch(() => { window.location.href = 'login.html'; });
        */

        // ── DEV BYPASS — remove this block and uncomment fetch() above when PHP is ready ──
        const isWorkerPage = document.body.classList.contains('worker-dashboard');
        this.currentUser = {
            name:  isWorkerPage ? 'Dev Worker'      : 'Dev User',
            email: isWorkerPage ? 'worker@dev.test' : 'user@dev.test',
            role:  isWorkerPage ? 'worker'           : 'user'
        };
        this.populateUserUI(this.currentUser);
        if (isWorkerPage) this.loadWorkerContent();
        else this.loadUserContent();
        // ── END DEV BYPASS ──
    },

    // ─── Populate name / email fields ─────────────────
    populateUserUI(user) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

        set('sidebarUserName',  user.name);
        set('sidebarUserEmail', user.email);
        set('profileName',      user.name);
        set('profileNameLarge', user.name);
        set('profileNameDetail',user.name);
        set('profileEmail',     user.email);
        set('profileEmailSide', user.email);

        // Member since — real value comes from DB; show placeholder until then
        set('profileJoinDate', '—');
    },

    // ─── SPA navigation ───────────────────────────────
    setupNavigation() {
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', () => {
                const page = item.getAttribute('data-page');
                this.showPage(page);
            });
        });
    },

    showPage(pageId) {
        // Hide all pages
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        // Deactivate all nav items
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

        // Show target page
        const target = document.getElementById(pageId + '-page');
        if (target) target.classList.add('active');

        // Activate matching nav item
        const navItem = document.querySelector(`.nav-item[data-page="${pageId}"]`);
        if (navItem) navItem.classList.add('active');

        // Update topbar title
        const titleEl = document.getElementById('pageTitle');
        if (titleEl) {
            const labels = {
                'overview':    'Overview',
                'bookings':    'My Bookings',
                'new-booking': 'New Booking',
                'history':     'History',
                'favorites':   'Favorites',
                'profile':     'Profile',
                'jobs':        'Available Jobs',
                'my-jobs':     'My Jobs',
                'completed':   'Completed Jobs',
                'earnings':    'Earnings',
            };
            titleEl.textContent = labels[pageId] || pageId;
        }
    },

    // ─── User dashboard data ──────────────────────────
    loadUserContent() {
        // TODO: fetch('api/user-stats.php') and fetch('api/bookings.php')
        // Set stats to dashes until DB is connected
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('statTotal',     '—');
        set('statPending',   '—');
        set('statCompleted', '—');
        set('statSpent',     '—');
    },

    // ─── Worker dashboard data ────────────────────────
    loadWorkerContent() {
        // TODO: fetch('api/worker-stats.php') and fetch('api/jobs.php?available=1')
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('statAvailable', '—');
        set('statCompleted', '—');
        set('statEarnings',  '—');

        // Earnings page
        set('earnTotal',     '—');
        set('earnMonth',     '—');
        set('earnPending',   '—');
        set('earnAvailable', '—');

        // Profile extras
        set('profileSpecialty', '—');
        set('profileJobs',      '—');
    },

    // ─── Logout ───────────────────────────────────────
    setupLogout() {
        const doLogout = () => {
            // TODO: fetch('api/logout.php', { method: 'POST' }).then(() => { window.location.href = 'index.html'; });
            window.location.href = 'index.html';
        };

        document.getElementById('logoutBtn')?.addEventListener('click', doLogout);
        document.getElementById('profileLogout')?.addEventListener('click', doLogout);
    },

    // ─── Quick book (navigates to new-booking page) ───
    quickBookService(serviceName) {
        // TODO: open booking modal or pre-select service
        this.showPage('new-booking');
    }
};

document.addEventListener('DOMContentLoaded', () => SohojShebaDashboard.init());