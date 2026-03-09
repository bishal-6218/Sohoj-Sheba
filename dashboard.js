/* =============================================
   SHOHOJ SHEBA — FULL DASHBOARD.JS
   Matches Merged CSS & Handle Page Switching
   ============================================= */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Select all necessary elements
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');
    const pageTitle = document.getElementById('pageTitle');
    const logoutBtn = document.getElementById('logoutBtn');

    // 2. Navigation Logic
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the target page name from the data-page attribute
            const targetPage = this.getAttribute('data-page');
            if (!targetPage) return;

            // Update Sidebar UI: Remove 'active' from all, add to clicked
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');

            // Switch Content: Hide all sections, show the target one
            sections.forEach(section => {
                section.classList.remove('active');
                // Matches IDs like "overview-page", "jobs-page", etc.
                if (section.id === `${targetPage}-page`) {
                    section.classList.add('active');
                }
            });

            // Update the Header Title dynamically
            if (pageTitle) {
                const formattedTitle = targetPage.charAt(0).toUpperCase() + targetPage.slice(1);
                pageTitle.innerText = formattedTitle.replace('-', ' ');
            }
        });
    });

    // 3. Logout Logic
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const confirmLogout = confirm("Are you sure you want to logout from Shohoj Sheba?");
            if (confirmLogout) {
                // Redirect back to your specific login page
                window.location.href = "login.html";
            }
        });
    }

    // 4. Quick Action Button Fix
    // If you have a "View Jobs" button in the Overview that should open the Jobs tab
    const quickJobBtn = document.querySelector('.btn-view-jobs');
    if (quickJobBtn) {
        quickJobBtn.addEventListener('click', () => {
            const jobsNav = document.querySelector('.nav-item[data-page="jobs"]');
            if (jobsNav) jobsNav.click();
        });
    }
});