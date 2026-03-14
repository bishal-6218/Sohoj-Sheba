document.addEventListener('DOMContentLoaded', () => {

    // Tab / section navigation
    document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            const page = item.dataset.page;
            if (!page) return;

            document.querySelectorAll('.content-section').forEach(el => el.classList.remove('active'));
            const target = document.getElementById(page + '-page');
            if (target) target.classList.add('active');

            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            document.querySelector('#pageTitle').textContent = 
                page.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        });
    });

    // Mobile sidebar toggle
    const toggles = ['#sidebarToggle', '#mobileMenuBtn'];
    toggles.forEach(sel => {
        document.querySelector(sel)?.addEventListener('click', () => {
            document.querySelector('.sidebar')?.classList.toggle('mobile-open');
        });
    });

});