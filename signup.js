
var currentStep = 1;
var TOTAL_STEPS  = 4;

/* ─── Boot ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    /* Set initial stepper state */
    updateStepper();

    /* Hide all back buttons on load (we're on step 1) */
    updateButtons();

    /* Role radio changes show/hide worker fields on step 4 */
    document.querySelectorAll('input[name="role"]').forEach(function (r) {
        r.addEventListener('change', syncWorkerFields);
    });
    syncWorkerFields();

    /* Input focus ring */
    document.querySelectorAll('.input-wrap input, .input-wrap select, .input-wrap textarea').forEach(function (el) {
        el.addEventListener('focus', function () { el.closest('.input-wrap').classList.add('focused'); });
        el.addEventListener('blur',  function () { el.closest('.input-wrap').classList.remove('focused'); });
    });

    /* Form submit */
    document.getElementById('signupForm').addEventListener('submit', function (e) {
        e.preventDefault();
        handleSubmit();
    });

    setupFileUploadFeedback();
});

/* ─── File upload: show selected filename + preview ───────────────── */
function setupFileUploadFeedback() {
    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    function bind(inputId, wrapId, statusId) {
        var input = document.getElementById(inputId);
        var wrap = document.getElementById(wrapId);
        var status = document.getElementById(statusId);
        if (!input || !wrap || !status) return;

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            status.innerHTML = '';
            if (!file) {
                wrap.classList.remove('has-file');
                status.hidden = true;
                return;
            }
            wrap.classList.add('has-file');
            status.hidden = false;

            var icon = document.createElement('div');
            icon.className = 'fu-icon';
            icon.innerHTML = '<i class="fa-solid fa-check"></i>';

            var textWrap = document.createElement('div');
            textWrap.className = 'fu-text';
            textWrap.innerHTML =
                '<div>Photo ready to upload</div>' +
                '<div class="fu-meta"><strong>' + escapeHtml(file.name) + '</strong> · ' + escapeHtml(formatSize(file.size)) + '</div>';

            status.appendChild(icon);
            status.appendChild(textWrap);

            if (file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var img = document.createElement('img');
                    img.className = 'fu-preview';
                    img.src = ev.target.result;
                    img.alt = 'Preview';
                    status.insertBefore(img, status.firstChild);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    bind('profilePhoto', 'profilePhotoWrap', 'profilePhotoStatus');
    bind('nidPhoto', 'nidPhotoWrap', 'nidPhotoStatus');
    bind('userProfilePhoto', 'userProfilePhotoWrap', 'userProfilePhotoStatus');
    bind('userNidPhoto', 'userNidPhotoWrap', 'userNidPhotoStatus');
}

/* ─── Navigation ────────────────────────────── */
function nextStep() {
    if (!validateStep(currentStep)) return;
    if (currentStep < TOTAL_STEPS) {
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function showStep(n) {
    /* Hide all steps */
    document.querySelectorAll('.form-step').forEach(function (s) {
        s.classList.remove('active');
    });

    /* Show target step */
    var target = document.querySelector('.form-step[data-step="' + n + '"]');
    if (target) target.classList.add('active');

    /* Update stepper visuals */
    updateStepper();
    updateButtons();

    /* Scroll to top of form */
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ─── Stepper visual update ─────────────────── */
/*
   Layout: 4 dots spaced evenly across 100%.
   Dot centres are at: 12.5%, 37.5%, 62.5%, 87.5%
   (each dot owns 25% of width; centre = midpoint of that slot)

   Fill line starts at first dot centre (12.5%)
   and grows rightward toward the active dot centre.

   Gap between adjacent dot centres = 25%.
   Fill width per completed gap:
     step 1 → 0   gaps → fill = 0%  (from 12.5% to 12.5%)
     step 2 → 1   gap  → fill = 25% (from 12.5% to 37.5%)
     step 3 → 2   gaps → fill = 50% (from 12.5% to 62.5%)
     step 4 → 3   gaps → fill = 75% (from 12.5% to 87.5%)

   But .stepper-fill starts at left:0, so we need to offset:
     actual fill element left  = 12.5%
     actual fill element width = (currentStep - 1) * 25%
*/
function updateStepper() {
    var fillEl = document.getElementById('stepperFill');

    /* Position the fill correctly */
    if (fillEl) {
        fillEl.style.left  = '12.5%';
        fillEl.style.width = ((currentStep - 1) * 25) + '%';
    }

    /* Update each dot */
    for (var i = 1; i <= TOTAL_STEPS; i++) {
        var dot = document.getElementById('sdot' + i);
        if (!dot) continue;

        /* Remove all states first */
        dot.classList.remove('is-active', 'is-done');

        if (i < currentStep) {
            dot.classList.add('is-done');
        } else if (i === currentStep) {
            dot.classList.add('is-active');
        }
        /* future steps: no class = grey default */
    }
}

/* ─── Show/hide back button ─────────────────── */
function updateButtons() {
    document.querySelectorAll('.btn-back').forEach(function (btn) {
        btn.style.display = (currentStep === 1) ? 'none' : 'inline-flex';
    });
}

/* ─── Worker/user field toggle ──────────────── */
function syncWorkerFields() {
    var role = document.querySelector('input[name="role"]:checked');
    var isWorker = role && role.value === 'worker';

    var wf  = document.querySelector('.worker-fields');
    var uf  = document.querySelector('.user-fields');

    if (wf) wf.style.display = isWorker ? 'block' : 'none';
    if (uf) uf.style.display = isWorker ? 'none'  : 'block';

    /* Update step 4 stepper label */
    var lbl = document.getElementById('step4Label');
    if (lbl) lbl.textContent = isWorker ? 'Professional Info' : 'Preferences';

    /* Update step 4 form heading & subtitle */
    var title = document.getElementById('step4Title');
    var sub   = document.getElementById('step4Subtitle');
    if (title) title.textContent = isWorker ? 'Professional Information'      : 'Your Preferences';
    if (sub)   sub.textContent   = isWorker ? 'Tell us about your professional background' : 'Personalise your Shohoj Sheba experience';
}

/* ─── Per-step validation ───────────────────── */
function validateStep(n) {
    if (n === 1) return true; /* Role selection always valid */

    if (n === 2) {
        var fn = document.querySelector('[name="firstName"]');
        var ln = document.querySelector('[name="lastName"]');
        var em = document.querySelector('[name="email"]');
        var pw = document.querySelector('[name="password"]');
        var cp = document.querySelector('[name="confirmPassword"]');
        var db = document.querySelector('[name="dateOfBirth"]');
        var gn = document.querySelector('[name="gender"]');

        if (!fn || !fn.value.trim()) { showToast('Please enter your first name', 'error'); fn && fn.focus(); return false; }
        if (!ln || !ln.value.trim()) { showToast('Please enter your last name', 'error');  ln && ln.focus(); return false; }
        if (!em || !isValidEmail(em.value)) { showToast('Please enter a valid email address', 'error'); em && em.focus(); return false; }
        if (!pw || pw.value.length < 6) { showToast('Password must be at least 6 characters', 'error'); pw && pw.focus(); return false; }
        if (!cp || pw.value !== cp.value) { showToast('Passwords do not match', 'error'); cp && cp.focus(); return false; }
        if (!db || !db.value) { showToast('Please select your date of birth', 'error'); db && db.focus(); return false; }
        if (!gn || !gn.value) { showToast('Please select your gender', 'error'); gn && gn.focus(); return false; }
        return true;
    }

    if (n === 3) {
        var ph = document.querySelector('[name="phone"]');
        var ct = document.querySelector('[name="country"]');
        var cy = document.querySelector('[name="city"]');
        var ar = document.querySelector('[name="area"]');
        var ad = document.querySelector('[name="address"]');

        if (!ph || !ph.value.trim()) { showToast('Please enter your phone number', 'error'); ph && ph.focus(); return false; }
        if (!ct || !ct.value)        { showToast('Please select your country', 'error');       ct && ct.focus(); return false; }
        if (!cy || !cy.value)        { showToast('Please select your city', 'error');           cy && cy.focus(); return false; }
        if (!ar || !ar.value.trim()) { showToast('Please enter your area/district', 'error');   ar && ar.focus(); return false; }
        if (!ad || !ad.value.trim()) { showToast('Please enter your street address', 'error');  ad && ad.focus(); return false; }
        return true;
    }

    if (n === 4) {
        var terms = document.getElementById('terms');
        if (!terms || !terms.checked) { showToast('You must agree to the Terms & Conditions', 'error'); return false; }
        return true;
    }

    return true;
}

/* ─── Submit ────────────────────────────────── */
function handleSubmit() {
    if (!validateStep(4)) return;

    var submitBtn = document.querySelector('.btn-submit');
    var btnText   = submitBtn ? submitBtn.querySelector('.btn-text')   : null;
    var btnLoader = submitBtn ? submitBtn.querySelector('.btn-loader') : null;

    if (submitBtn) submitBtn.disabled = true;
    if (btnText)   btnText.style.display   = 'none';
    if (btnLoader) btnLoader.style.display = 'inline-flex';

    var form = document.getElementById('signupForm');
    var fd = new FormData(form);

    // Also include a single "name" field for backend convenience
    var firstName = (form.querySelector('[name="firstName"]')?.value || '').trim();
    var lastName  = (form.querySelector('[name="lastName"]')?.value  || '').trim();
    var fullName = (firstName + ' ' + lastName).trim() || firstName || lastName || 'User';
    fd.set('name', fullName);

    fetch('api/signup.php', {
        method: 'POST',
        body: fd
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data && data.success) {
            showToast('Account created successfully! Redirecting to login...', 'success');
            setTimeout(function () { window.location.href = 'login.html'; }, 2000);
        } else {
            showToast((data && data.message) || 'Registration failed. Please try again.', 'error');
            if (submitBtn) submitBtn.disabled = false;
            if (btnText)   btnText.style.display   = 'inline-flex';
            if (btnLoader) btnLoader.style.display = 'none';
        }
    })
    .catch(function () {
        showToast('Server error while creating account. Please try again.', 'error');
        if (submitBtn) submitBtn.disabled = false;
        if (btnText)   btnText.style.display   = 'inline-flex';
        if (btnLoader) btnLoader.style.display = 'none';
    });
}

/* ─── Password toggle ───────────────────────── */
function togglePassword(fieldId, iconId) {
    var field = document.getElementById(fieldId);
    var icon  = document.getElementById(iconId);
    if (!field || !icon) return;
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/* ─── Email validation ──────────────────────── */
function isValidEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

/* ─── Toast notifications ───────────────────── */
var _toast = null;

function showToast(msg, type) {
    if (_toast) { _toast.remove(); _toast = null; }

    var icons = { error: 'fa-circle-exclamation', success: 'fa-circle-check', info: 'fa-info-circle' };

    _toast = document.createElement('div');
    _toast.className = 'signup-toast ' + (type || 'info');
    _toast.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + '"></i><span>' + msg + '</span>';
    document.body.appendChild(_toast);

    /* Force reflow then add .show to trigger transition */
    void _toast.offsetWidth;
    _toast.classList.add('show');

    setTimeout(function () {
        if (_toast) {
            _toast.classList.remove('show');
            setTimeout(function () { if (_toast) { _toast.remove(); _toast = null; } }, 350);
        }
    }, 4000);
}