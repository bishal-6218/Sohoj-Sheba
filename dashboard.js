/* =============================================
   SHOHOJ SHEBA — DASHBOARD.JS
   Universal Logic for User & Worker
   ============================================= */

const SohojShebaDashboard = {
    init() {
        this.checkAuth();
        this.loadUserData();
        this.setupNavigation();
        this.setupSidebar();
        this.setupLogout();

        // Specific loaders
        if (document.body.classList.contains('worker-dashboard')) {
            this.loadWorkerContent();
        } else {
            this.loadUserContent();
        }
    },

    checkAuth() {
        const user = JSON.parse(localStorage.getItem('shohoj_sheba_user'));
        if (!user) {
            window.location.href = 'login.html';
            return;
        }
        // Simple security check: Ensure worker is on worker page and vice-versa
        const isWorkerPage = document.body.classList.contains('worker-dashboard');
        if (isWorkerPage && user.role !== 'worker') window.location.href = 'user-dashboard.html';
        if (!isWorkerPage && user.role === 'worker') window.location.href = 'worker-dashboard.html';
    },

    loadUserData() {
        const user = JSON.parse(localStorage.getItem('shohoj_sheba_user'));
        const nameElements = ['sidebarUserName', 'profileName', 'profileNameLarge', 'profileNameDetail'];
        const emailElements = ['sidebarUserEmail', 'profileEmailLarge', 'profileEmail'];

        nameElements.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = user.name;
        });
        emailElements.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = user.email;
        });
    },

    setupNavigation() {
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = item.getAttribute('data-page');
                this.showPage(page);
                
                navItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });
    },

    showPage(pageId) {
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById(`${pageId}-page`);
        if (target) {
            target.classList.add('active');
            const title = pageId.charAt(0).toUpperCase() + pageId.slice(1);
            document.getElementById('pageTitle').textContent = title.replace('-', ' ');
        }
    },

    loadWorkerContent() {
        const container = document.getElementById('availableJobs');
        if (!container) return;
        
        const mockJobs = [
            { title: "Leaking Tap Repair", loc: "Mirpur 10", pay: "৳500" },
            { title: "Furniture Assembly", loc: "Uttara Sector 4", pay: "৳1200" }
        ];

        container.innerHTML = mockJobs.map(job => `
            <div class="job-card">
                <div>
                    <strong>${job.title}</strong>
                    <p style="font-size:12px; color:#64748b;"><i class="fa-solid fa-location-dot"></i> ${job.loc}</p>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:bold; color:#2e7d32; margin-bottom:5px;">${job.pay}</div>
                    <button class="btn-sm-primary">Accept</button>
                </div>
            </div>
        `).join('');
    },

    loadUserContent() {
        // Here you would populate the "Booked Services" for the customer
    },

    setupSidebar() {
        const toggle = document.getElementById('sidebarToggle');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');

        [toggle, mobileBtn].forEach(btn => {
            if (btn) btn.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));
        });
    },

    setupLogout() {
        document.getElementById('logoutBtn')?.addEventListener('click', () => {
            localStorage.removeItem('shohoj_sheba_user');
            window.location.href = 'index.html';
        });
    }
};

document.addEventListener('DOMContentLoaded', () => SohojShebaDashboard.init());