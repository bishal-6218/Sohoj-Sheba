// ─── Signup ───────────────────────────────────
function handleSignup(e) {
    e.preventDefault();

    const name     = document.getElementById('fullname').value.trim();
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const role     = document.querySelector('input[name="role"]:checked').value;

    const btn    = document.getElementById('signupBtn');
    const text   = btn.querySelector('.btn-text');
    const loader = btn.querySelector('.btn-loader');

    if (password.length < 6) {
        showFormError('Password must be at least 6 characters.');
        return;
    }

    btn.disabled         = true;
    text.style.display   = 'none';
    loader.style.display = 'inline-flex';

    fetch('api/signup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password, role })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'login.html';
        } else {
            showFormError(data.message || 'Registration failed.');
            resetBtn(btn, text, loader);
        }
    })
    .catch(() => {
        showFormError('Server error. Please try again.');
        resetBtn(btn, text, loader);
    });
}

// ─── Login ────────────────────────────────────
function handleLogin(e) {
    e.preventDefault();

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const role     = document.querySelector('input[name="role"]:checked').value;

    const btn    = document.getElementById('loginBtn');
    const text   = btn.querySelector('.btn-text');
    const loader = btn.querySelector('.btn-loader');

    btn.disabled         = true;
    text.style.display   = 'none';
    loader.style.display = 'inline-flex';

    fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, role })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.user.role === 'worker') {
                window.location.href = 'worker-dashboard.html';
            } else {
                window.location.href = 'user-dashboard.html';
            }
        } else {
            showFormError(data.message || 'Invalid email, password, or role.');
            resetBtn(btn, text, loader);
        }
    })
    .catch(() => {
        showFormError('Server error. Please try again.');
        resetBtn(btn, text, loader);
    });
}

// ─── Password toggle ──────────────────────────
function togglePassword() {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ─── Helpers ──────────────────────────────────
function resetBtn(btn, text, loader) {
    btn.disabled         = false;
    text.style.display   = 'inline-flex';
    loader.style.display = 'none';
}

function showFormError(msg) {
    var existing = document.getElementById('form-error-banner');
    if (existing) existing.remove();

    var banner = document.createElement('div');
    banner.id = 'form-error-banner';
    banner.style.cssText = 'background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:14px;display:flex;align-items:center;gap:10px;margin-bottom:4px;';
    banner.innerHTML = '<i class="fa-solid fa-circle-exclamation" style="flex-shrink:0;"></i><span>' + msg + '</span>';

    var form      = document.querySelector('.login-form');
    var submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (form && submitBtn) {
        form.insertBefore(banner, submitBtn);
    } else if (form) {
        form.appendChild(banner);
    }

    setTimeout(function() { if (banner.parentNode) banner.remove(); }, 5000);
}