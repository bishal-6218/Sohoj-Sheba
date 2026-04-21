

document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ Shohoj Sheba Landing Page Initialized');

    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);

    // ─────────────────────────────────────────────
    // 1. Navbar scroll shadow
    // ─────────────────────────────────────────────
    const navbar = document.getElementById('navbar');
    const backToTop = document.getElementById('backToTop');

    window.addEventListener('scroll', () => {
        if (!navbar) return;
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
            if (backToTop) backToTop.classList.add('visible');
        } else {
            navbar.classList.remove('scrolled');
            if (backToTop) backToTop.classList.remove('visible');
        }
    });

    // ─────────────────────────────────────────────
    // 2. Smooth scroll for all # anchors
    // ─────────────────────────────────────────────
    $$('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            // Allow normal navigation if it's not a fragment
            if (this.getAttribute('href') === '#') return;

            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = $(targetId);

            if (target) {
                const headerOffset = 80; // approx navbar height
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ─────────────────────────────────────────────
    // 3. Hamburger menu toggle
    // ─────────────────────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const nav = $('nav');

    if (hamburger && nav) {
        hamburger.addEventListener('click', () => {
            nav.classList.toggle('open');
            hamburger.classList.toggle('active');
        });
    }

    // ─────────────────────────────────────────────
    // 4. Back to top button (already has onclick inline)
    //    Just make sure class toggles correctly (done above)
    // ─────────────────────────────────────────────

    // ─────────────────────────────────────────────
    // 5. Scroll reveal / intersection observer
    // ─────────────────────────────────────────────
    const revealElements = $$('.service-card, .step, .feature-box, .testimonial-card');

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    // Optional: stop observing after reveal
                    // observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(el => {
            el.classList.add('reveal'); // make sure class is present
            observer.observe(el);
        });
    } else {
        // Fallback for very old browsers: show everything
        revealElements.forEach(el => el.classList.add('revealed'));
    }

    // ─────────────────────────────────────────────
    // 6. Quick services strip → service detail page
    // ─────────────────────────────────────────────
    $$('.quick-service-item').forEach(item => {
        item.style.cursor = 'pointer'; // visual hint

        item.addEventListener('click', () => {
            const service = item.getAttribute('data-service');
            if (service) {
                // You can normalize or map names if needed
                window.location.href = `service-detail.html?service=${service.toLowerCase()}`;
            }
        });
    });

    // ─────────────────────────────────────────────
    // Optional: Close mobile menu when clicking a link
    // ─────────────────────────────────────────────
    $$('nav a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) { // typical mobile breakpoint
                if (nav) nav.classList.remove('open');
                if (hamburger) hamburger.classList.remove('active');
            }
        });
    });
});