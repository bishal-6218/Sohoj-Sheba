
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

    setBtnLoading(btn, text, loader, true);
    postJson('api/signup.php', { name, email, password, role })
        .then(data => {
            if (data.success) {
                window.location.href = 'login.html';
                return;
            }
            showFormError(data.message || 'Registration failed.');
            setBtnLoading(btn, text, loader, false);
        })
        .catch(() => {
            showFormError('Server error. Please try again.');
            setBtnLoading(btn, text, loader, false);
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

    setBtnLoading(btn, text, loader, true);
    postJson('api/login.php', { email, password, role })
        .then(data => {
            if (data.success) {
                window.location.href = (data.user && data.user.role === 'worker')
                    ? 'worker-dashboard.html'
                    : 'user-dashboard.html';
                return;
            }
            showFormError(data.message || 'Invalid email, password, or role.');
            setBtnLoading(btn, text, loader, false);
        })
        .catch(() => {
            showFormError('Server error. Please try again.');
            setBtnLoading(btn, text, loader, false);
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
function postJson(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body || {})
    }).then(r => r.json());
}

function setBtnLoading(btn, text, loader, isLoading) {
    btn.disabled = !!isLoading;
    text.style.display = isLoading ? 'none' : 'inline-flex';
    loader.style.display = isLoading ? 'inline-flex' : 'none';
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