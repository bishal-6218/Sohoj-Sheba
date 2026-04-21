const serviceDatabase = {
    carpenter: {
        name: "Carpenter",
        price: "500",
        icon: "fa-hammer",
        color: "#2e7d32",
        shortDesc: "Expert furniture repair, assembly & custom woodwork at your doorstep.",
        about: "Our certified carpenters bring 5+ years of experience to every job. From fixing wobbly tables to building custom shelves, we use premium materials and finish every project with perfection.",
        includes: [
            "Furniture assembly & repair",
            "Door & window installation",
            "Custom shelves & cabinets",
            "Wood polishing & restoration",
            "Roof & floor woodwork"
        ]
    },
    plumber: {
        name: "Plumber",
        price: "400",
        icon: "fa-faucet-drip",
        color: "#1565c0",
        shortDesc: "Leak fixing, pipe installation & complete bathroom solutions.",
        about: "Professional plumbers who solve every water-related problem quickly and cleanly. From small leaks to full bathroom renovation — we do it all.",
        includes: [
            "Leak detection & repair",
            "Pipe installation & replacement",
            "Bathroom & kitchen fittings",
            "Water heater repair",
            "Drainage cleaning"
        ]
    },
    electrician: {
        name: "Electrician",
        price: "450",
        icon: "fa-bolt",
        color: "#f59e0b",
        shortDesc: "Wiring, fan/light installation & full electrical safety checks.",
        about: "Certified electricians with safety-first approach. We handle everything from simple bulb changes to complete house wiring.",
        includes: [
            "Wiring & rewiring",
            "Fan & light installation",
            "Appliance repair",
            "Circuit breaker fixes",
            "Full electrical safety audit"
        ]
    },
    mason: {
        name: "Mason",
        price: "600",
        icon: "fa-trowel-bricks",
        color: "#8d5524",
        shortDesc: "Brickwork, tiling, plastering & structural repairs.",
        about: "Skilled masons who build and repair with precision. Whether it's a small crack or full wall renovation, our team delivers perfect results.",
        includes: [
            "Brick & block work",
            "Tile & marble installation",
            "Wall plastering",
            "Concrete repair",
            "Staircase & pillar construction"
        ]
    },
    gardener: {
        name: "Gardener",
        price: "350",
        icon: "fa-seedling",
        color: "#059669",
        shortDesc: "Lawn care, plant maintenance & beautiful garden landscaping.",
        about: "Passionate gardeners who turn your outdoor space into a green paradise. We take care of plants, lawns and seasonal flowers.",
        includes: [
            "Lawn mowing & maintenance",
            "Planting & pruning",
            "Garden landscaping",
            "Pest control",
            "Seasonal flower beds"
        ]
    },
    "home-repair": {
        name: "Home Repair",
        price: "450",
        icon: "fa-house-circle-check",
        color: "#7c3aed",
        shortDesc: "All-in-one home maintenance & quick repair solutions.",
        about: "Your one-stop handyman service. From painting to minor electrical or plumbing fixes — we handle everything under one roof.",
        includes: [
            "Minor home repairs",
            "Painting & wall work",
            "Cleaning & organizing",
            "Furniture fixing",
            "General handyman services"
        ]
    }
};

function getServiceKey() {
    const urlParams = new URLSearchParams(window.location.search);
    const raw = urlParams.get('service');
    const key = (raw ? String(raw) : '').toLowerCase().trim();
    return key || 'carpenter';
}

function getServiceData(serviceKey) {
    if (serviceDatabase[serviceKey]) return serviceDatabase[serviceKey];
    return serviceDatabase.carpenter;
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function setHtml(id, html) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html;
}

function loadServiceDetail() {
    let serviceKey = getServiceKey();

    // Fallback to carpenter if invalid service
    if (!serviceDatabase[serviceKey]) {
        console.warn(`Service "${serviceKey}" not found → fallback to carpenter`);
        serviceKey = 'carpenter';
    }

    const service = getServiceData(serviceKey);

    // ─── Update page title ───────────────────────────────────────
    document.title = `${service.name} Service — Shohoj Sheba`;

    // ─── Hero section ─────────────────────────────────────────────
    setText('serviceName', service.name);
    setText('serviceShortDesc', service.shortDesc);
    setHtml('servicePrice', `Starts from <strong>৳${service.price}</strong>`);

    const iconEl = document.getElementById('heroIcon');
    if (iconEl) {
        iconEl.innerHTML = `<i class="fa-solid ${service.icon}"></i>`;
        iconEl.style.background = `linear-gradient(135deg, white, #f8fafc)`;
        iconEl.style.color = service.color;
    }

    // ─── About section ────────────────────────────────────────────
    setText('aboutText', service.about);
    setText('serviceNameRepeat', service.name);

    // ─── What we offer / includes grid ────────────────────────────
    const gridEl = document.getElementById('includesGrid');
    if (gridEl) {
        gridEl.innerHTML = service.includes
            .map(item => `
                <div class="offer-item">
                    <i class="fa-solid fa-check-circle" style="color:${service.color};"></i>
                    <span>${item}</span>
                </div>
            `)
            .join('');
    }

    console.log(`Loaded service: ${service.name}`);
}

// ────────────────────────────────────────────────────────────────
// Initialize when page is ready
// ────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadServiceDetail();

    // Optional: hamburger menu support (if you have mobile nav on this page)
    const hamburger = document.getElementById('hamburger');
    const navMenu   = document.querySelector('nav');
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            hamburger.classList.toggle('active');
        });
    }
});