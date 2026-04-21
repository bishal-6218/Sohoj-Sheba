const SohojShebaDashboard = {

    currentUser: null,
    _profileData: null,
    _userBookingsCache: [],
    _workerPendingCache: [],
    _workerMyCache: [],
    _workerCompletedCache: [],
    _userBookingFilter: 'all',
    _workerJobsFilter: 'all',

    init() {
        this.checkAuth();
        this.setupNavigation();
        this.setupLogout();
        this.injectEditModal();
        this.setupBooking();
    },

    // ─── Auth check ───────────────────────────────────
    checkAuth() {
        fetch('api/session.php')
            .then(r => r.json())
            .then(data => {
                if (!data.loggedIn) { window.location.href = 'login.html'; return; }
                this.currentUser = data.user;
                const isWorkerPage = document.body.classList.contains('worker-dashboard');
                if (isWorkerPage && data.user.role !== 'worker') { window.location.href = 'user-dashboard.html'; return; }
                if (!isWorkerPage && data.user.role === 'worker') { window.location.href = 'worker-dashboard.html'; return; }
                this.populateUserUI(data.user);
                if (isWorkerPage) this.loadWorkerContent();
                else this.loadUserContent();
                this.loadProfile();
                if (isWorkerPage) this.loadWorkerRequests();
                else this.loadUserBookings();
            })
            .catch(() => { window.location.href = 'login.html'; });
    },

    populateUserUI(user) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('sidebarUserName',  user.name);
        set('sidebarUserEmail', user.email);
        set('profileName',      user.name);
        this._setProfilePhoto(user.profile_photo_path);
    },

    // ─── Load full profile ────────────────────────────
    loadProfile() {
        fetch('api/profile.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                this._profileData = data.profile;
                if (data.profile.role === 'worker') this.populateWorkerProfile(data.profile);
                else this.populateUserProfile(data.profile);
            })
            .catch(() => {});
    },

    // ─── Helpers ──────────────────────────────────────
    _set(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = (val !== null && val !== undefined && String(val).trim() !== '') ? val : '—';
    },
    _fmtInt(n) {
        const v = Number(n || 0);
        if (!Number.isFinite(v)) return '0';
        return String(Math.round(v));
    },
    _fmtMoneyBDT(amount) {
        const n = Number(amount || 0);
        if (!Number.isFinite(n)) return '৳0';
        const rounded = Math.round(n);
        return `৳${rounded.toLocaleString('en-US')}`;
    },
    _statusOf(row) {
        return String(row?.display_status || row?.status || '').toLowerCase();
    },
    _sumPrice(rows) {
        if (!Array.isArray(rows)) return 0;
        return rows.reduce((sum, r) => {
            const v = Number(r?.price || 0);
            return sum + (Number.isFinite(v) ? v : 0);
        }, 0);
    },
    _updateUserOverview() {
        const list = Array.isArray(this._userBookingsCache) ? this._userBookingsCache : [];
        const total = list.length;

        // "Pending" on overview = anything still in progress
        const pendingLike = new Set(['pending', 'accepted', 'in_progress']);
        const pending = list.filter(b => pendingLike.has(this._statusOf(b))).length;

        const completed = list.filter(b => this._statusOf(b) === 'completed').length;
        const spent = this._sumPrice(list.filter(b => this._statusOf(b) === 'completed'));

        const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setTxt('statTotal', this._fmtInt(total));
        setTxt('statPending', this._fmtInt(pending));
        setTxt('statCompleted', this._fmtInt(completed));
        setTxt('statSpent', this._fmtMoneyBDT(spent));

        // Recent bookings (overview card)
        const recentEl = document.getElementById('recentBookings');
        if (recentEl) {
            const top = list.slice(0, 3);
            recentEl.innerHTML = top.length
                ? top.map(b => this._renderBookingCardForUser(b)).join('')
                : `<div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                        <h4>No bookings yet</h4>
                        <p>Your recent bookings will show here.</p>
                   </div>`;
        }
    },
    _updateWorkerOverview() {
        const pending = Array.isArray(this._workerPendingCache) ? this._workerPendingCache : [];
        const completed = Array.isArray(this._workerCompletedCache) ? this._workerCompletedCache : [];

        const availableJobs = pending.length;
        const completedJobs = completed.length;
        const earned = this._sumPrice(completed);

        const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        setTxt('statAvailable', this._fmtInt(availableJobs));
        setTxt('statCompleted', this._fmtInt(completedJobs));
        setTxt('statEarnings', this._fmtMoneyBDT(earned));

        // Earnings page quick numbers (if present)
        setTxt('earnTotal', this._fmtMoneyBDT(earned));
        setTxt('earnMonth', '—');
        setTxt('earnPending', '—');
        setTxt('earnAvailable', this._fmtMoneyBDT(earned));
    },
    _formatDate(str) {
        if (!str) return '—';
        try { return new Date(str).toLocaleDateString('en-GB', { year:'numeric', month:'long', day:'numeric' }); }
        catch { return str; }
    },
    _cap(str) {
        if (!str) return '—';
        return str.charAt(0).toUpperCase() + str.slice(1).replace(/-/g, ' ');
    },
    _formatExp(val) {
        const map = { 'less-than-1':'Less than 1 year','1-2':'1-2 years','3-5':'3-5 years','5-10':'5-10 years','more-than-10':'More than 10 years' };
        return map[val] || val || '—';
    },

    // ─── Populate USER profile display ────────────────
    populateUserProfile(p) {
        const s = this._set.bind(this);
        s('sidebarUserName', p.name); s('sidebarUserEmail', p.email); s('profileName', p.name);
        s('profileNameLarge', p.name); s('profileEmailSide', p.email);
        s('profileNameDetail', p.name); s('profileEmail', p.email);
        s('profileJoinDate', this._formatDate(p.member_since));
        s('profileDOB',      this._formatDate(p.date_of_birth));
        s('profileGender',   this._cap(p.gender));
        s('profilePhone',    p.phone); s('profileWhatsapp', p.whatsapp); s('profileAltPhone', p.alternative_phone);
        s('profileCountry',  this._cap(p.country)); s('profileCity', this._cap(p.city));
        s('profileArea', p.area); s('profilePostal', p.postal_code); s('profileAddress', p.address);
        s('profileLanguage', this._cap(p.preferred_language));
        s('profileReferral', this._cap(p.referral_source));
        s('profilePreferences', p.preferences_text);
        this._setProfilePhoto(p.profile_photo_path);
    },

    // ─── Populate WORKER profile display ──────────────
    populateWorkerProfile(p) {
        const s = this._set.bind(this);
        s('sidebarUserName', p.name); s('sidebarUserEmail', p.email); s('profileName', p.name);
        s('profileNameLarge', p.name); s('profileEmailSide', p.email);
        s('profileNameDetail', p.name); s('profileEmail', p.email);
        s('profileJoinDate', this._formatDate(p.member_since));
        s('profileDOB',      this._formatDate(p.date_of_birth));
        s('profileGender',   this._cap(p.gender));
        s('profilePhone',    p.phone); s('profileWhatsapp', p.whatsapp); s('profileAltPhone', p.alternative_phone);
        s('profileCountry',  this._cap(p.country)); s('profileCity', this._cap(p.city));
        s('profileArea', p.area); s('profilePostal', p.postal_code); s('profileAddress', p.address);
        s('profileExperience', this._formatExp(p.experience));
        s('profileSkills',     p.skills);
        s('profileNID',        p.nid_number);
        s('profileLicense',    p.trade_license);
        s('profileJobs',       p.jobs_completed ?? '0');
        s('profileSpecialty',  (p.services && p.services.length > 0) ? p.services.join(', ') : '—');
        if (p.rating_avg && p.rating_avg > 0) {
            const rEl = document.querySelector('.big-rating');
            if (rEl) rEl.textContent = p.rating_avg.toFixed(1);
        }
        this._setProfilePhoto(p.profile_photo_path);
    },

    _photoUrl(path) {
        if (!path || String(path).trim() === '') return '';
        let p = String(path).trim().replace(/\\/g, '/');
        if (/^https?:\/\//i.test(p)) return p;
        // If DB stored a full filesystem path, keep only from uploads/ onward
        const m = p.match(/(uploads\/[^?#]+)/i);
        if (m) p = m[1];
        else if (!/^uploads\//i.test(p) && p.includes('/uploads/')) {
            const i = p.toLowerCase().indexOf('/uploads/');
            p = p.slice(i + 1);
        }
        // Leading "/" makes URL() resolve to site root and breaks apps in subfolders
        if (p.startsWith('/')) p = p.replace(/^\/+/, '');
        try {
            return new URL(p, window.location.href).href;
        } catch {
            return p;
        }
    },

    _setProfilePhoto(photoPath) {
        const url = this._photoUrl(photoPath);
        if (!url) return;

        const applyImg = (sel, alt) => {
            const el = document.querySelector(sel);
            if (!el) return;
            el.innerHTML = '';
            el.style.padding = '0';
            el.style.overflow = 'hidden';
            el.style.background = 'transparent';
            const img = document.createElement('img');
            img.src = url;
            img.alt = alt || 'Profile';
            img.loading = 'lazy';
            img.decoding = 'async';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;';
            img.onerror = () => {
                const isWorker = document.body.classList.contains('worker-dashboard');
                el.innerHTML = isWorker
                    ? '<i class="fa-solid fa-hard-hat"></i>'
                    : '<i class="fa-solid fa-user"></i>';
                el.style.padding = '';
                el.style.background = '';
            };
            el.appendChild(img);
        };

        applyImg('.profile-ava',       'Profile photo');
        applyImg('.sidebar .user-ava', 'Profile photo');
        applyImg('.topbar-avatar',     'Profile photo');
    },

    // ══════════════════════════════════════════════════
    // EDIT PROFILE MODAL
    // ══════════════════════════════════════════════════

    injectEditModal() {
        const isWorker = document.body.classList.contains('worker-dashboard');

        const workerExtras = isWorker ? `
            <div class="ep-section-title">Professional Information</div>
            <div class="ep-row">
                <div class="ep-group">
                    <label>Work Experience</label>
                    <div class="ep-input-wrap">
                        <i class="fa-solid fa-briefcase ep-icon"></i>
                        <select name="experience" id="ep_experience">
                            <option value="">Select experience</option>
                            <option value="less-than-1">Less than 1 year</option>
                            <option value="1-2">1-2 years</option>
                            <option value="3-5">3-5 years</option>
                            <option value="5-10">5-10 years</option>
                            <option value="more-than-10">More than 10 years</option>
                        </select>
                    </div>
                </div>
                <div class="ep-group">
                    <label>NID / Passport No.</label>
                    <div class="ep-input-wrap">
                        <i class="fa-solid fa-id-card ep-icon"></i>
                        <input type="text" name="nidNumber" id="ep_nidNumber" placeholder="NID or Passport number">
                    </div>
                </div>
            </div>
            <div class="ep-row">
                <div class="ep-group">
                    <label>Trade License</label>
                    <div class="ep-input-wrap">
                        <i class="fa-solid fa-certificate ep-icon"></i>
                        <input type="text" name="tradeLicense" id="ep_tradeLicense" placeholder="License number (optional)">
                    </div>
                </div>
                <div class="ep-group">
                    <label>Upload New Profile Photo</label>
                    <div class="ep-input-wrap ep-file-wrap">
                        <i class="fa-solid fa-camera ep-icon"></i>
                        <input type="file" name="profilePhoto" id="ep_profilePhoto" accept="image/*"
                            style="flex:1;padding:10px 0;background:transparent;border:none;outline:none;font-size:13px;color:var(--muted);">
                    </div>
                </div>
            </div>
            <div class="ep-group ep-full" style="margin-bottom:16px;">
                <label>Skills &amp; Expertise</label>
                <div class="ep-input-wrap">
                    <i class="fa-solid fa-wrench ep-icon"></i>
                    <textarea name="skills" id="ep_skills" rows="3" placeholder="Describe your skills..."></textarea>
                </div>
            </div>
            <div class="ep-group ep-full" style="margin-bottom:16px;">
                <label>Service Categories</label>
                <div class="ep-checkbox-grid" id="ep_servicesGrid">
                    <label class="ep-check-item"><input type="checkbox" name="services" value="carpenter"><span><i class="fa-solid fa-hammer"></i> Carpenter</span></label>
                    <label class="ep-check-item"><input type="checkbox" name="services" value="plumber"><span><i class="fa-solid fa-faucet-drip"></i> Plumber</span></label>
                    <label class="ep-check-item"><input type="checkbox" name="services" value="electrician"><span><i class="fa-solid fa-bolt"></i> Electrician</span></label>
                    <label class="ep-check-item"><input type="checkbox" name="services" value="mason"><span><i class="fa-solid fa-trowel-bricks"></i> Mason</span></label>
                    <label class="ep-check-item"><input type="checkbox" name="services" value="gardener"><span><i class="fa-solid fa-seedling"></i> Gardener</span></label>
                    <label class="ep-check-item"><input type="checkbox" name="services" value="home-repair"><span><i class="fa-solid fa-house-circle-check"></i> Home Repair</span></label>
                </div>
            </div>            ` : `
            <div class="ep-section-title">Profile photo</div>
            <div class="ep-row">
                <div class="ep-group">
                    <label>Change profile photo</label>
                    <div class="ep-input-wrap ep-file-wrap">
                        <i class="fa-solid fa-camera ep-icon"></i>
                        <input type="file" name="profilePhoto" id="ep_user_profilePhoto" accept="image/*"
                            style="flex:1;padding:10px 0;background:transparent;border:none;outline:none;font-size:13px;color:var(--muted);">
                    </div>
                </div>
                <div class="ep-group">
                    <label>NID / ID photo (optional)</label>
                    <div class="ep-input-wrap ep-file-wrap">
                        <i class="fa-solid fa-id-card ep-icon"></i>
                        <input type="file" name="userNidPhoto" id="ep_user_nidPhoto" accept="image/*"
                            style="flex:1;padding:10px 0;background:transparent;border:none;outline:none;font-size:13px;color:var(--muted);">
                    </div>
                </div>
            </div>
            <div class="ep-section-title">Preferences</div>
            <div class="ep-row">
                <div class="ep-group">
                    <label>Preferred Language</label>
                    <div class="ep-input-wrap">
                        <i class="fa-solid fa-language ep-icon"></i>
                        <select name="language" id="ep_language">
                            <option value="">Select language</option>
                            <option value="bangla">&#2476;&#2494;&#2434;&#2482;&#2494; (Bangla)</option>
                            <option value="english">English</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                </div>
                <div class="ep-group">
                    <label>How You Found Us</label>
                    <div class="ep-input-wrap">
                        <i class="fa-solid fa-bullhorn ep-icon"></i>
                        <select name="referralSource" id="ep_referralSource">
                            <option value="">Select source</option>
                            <option value="search-engine">Search Engine</option>
                            <option value="social-media">Social Media</option>
                            <option value="friend">Friend or Family</option>
                            <option value="advertisement">Advertisement</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ep-group ep-full" style="margin-bottom:16px;">
                <label>Special Preferences / Requirements</label>
                <div class="ep-input-wrap">
                    <i class="fa-solid fa-note-sticky ep-icon"></i>
                    <textarea name="preferences" id="ep_preferences" rows="3" placeholder="Any accessibility needs or preferences..."></textarea>
                </div>
            </div>`;

        document.body.insertAdjacentHTML('beforeend', `
        <!-- EDIT PROFILE MODAL -->
        <div id="editProfileModal" class="ep-overlay" style="display:none;" aria-modal="true" role="dialog">
            <div class="ep-modal">
                <div class="ep-modal-head">
                    <h2><i class="fa-solid fa-pen-to-square"></i> Edit Profile</h2>
                    <button class="ep-close" id="epCloseBtn" aria-label="Close">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <form id="editProfileForm" enctype="multipart/form-data" autocomplete="off">
                        <div id="epAlert" class="ep-alert" style="display:none;"></div>

                        <div class="ep-section-title">Personal Information</div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Full Name <span class="ep-req">*</span></label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-user ep-icon"></i>
                                    <input type="text" name="name" id="ep_name" placeholder="Your full name" required>
                                </div>
                            </div>
                            <div class="ep-group">
                                <label>Date of Birth</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-calendar ep-icon"></i>
                                    <input type="date" name="dateOfBirth" id="ep_dateOfBirth">
                                </div>
                            </div>
                        </div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Gender</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-venus-mars ep-icon"></i>
                                    <select name="gender" id="ep_gender">
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                        <option value="prefer-not-to-say">Prefer not to say</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ep-group"></div>
                        </div>

                        <div class="ep-section-title">Contact Details</div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Phone Number</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-phone ep-icon"></i>
                                    <input type="tel" name="phone" id="ep_phone" placeholder="+880 1XXX-XXXXXX">
                                </div>
                            </div>
                            <div class="ep-group">
                                <label>WhatsApp</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-brands fa-whatsapp ep-icon"></i>
                                    <input type="tel" name="whatsapp" id="ep_whatsapp" placeholder="+880 1XXX-XXXXXX">
                                </div>
                            </div>
                        </div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Alternative Phone</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-phone ep-icon"></i>
                                    <input type="tel" name="alternativePhone" id="ep_altPhone" placeholder="+880 1XXX-XXXXXX">
                                </div>
                            </div>
                            <div class="ep-group">
                                <label>Postal Code</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-envelope ep-icon"></i>
                                    <input type="text" name="postalCode" id="ep_postalCode" placeholder="Enter postal code">
                                </div>
                            </div>
                        </div>

                        <div class="ep-section-title">Address</div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Country</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-globe ep-icon"></i>
                                    <select name="country" id="ep_country">
                                        <option value="">Select country</option>
                                        <option value="bangladesh">Bangladesh</option>
                                        <option value="india">India</option>
                                        <option value="pakistan">Pakistan</option>
                                        <option value="nepal">Nepal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="ep-group">
                                <label>City / Division</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-city ep-icon"></i>
                                    <select name="city" id="ep_city">
                                        <option value="">Select division</option>
                                        <option value="dhaka">Dhaka</option>
                                        <option value="chittagong">Chittagong</option>
                                        <option value="sylhet">Sylhet</option>
                                        <option value="rajshahi">Rajshahi</option>
                                        <option value="khulna">Khulna</option>
                                        <option value="barisal">Barisal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Area / District</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-map-pin ep-icon"></i>
                                    <input type="text" name="area" id="ep_area" placeholder="e.g., Mirpur, Gulshan">
                                </div>
                            </div>
                            <div class="ep-group"></div>
                        </div>
                        <div class="ep-group ep-full" style="margin-bottom:16px;">
                            <label>Street Address</label>
                            <div class="ep-input-wrap">
                                <i class="fa-solid fa-location-dot ep-icon"></i>
                                <textarea name="address" id="ep_address" rows="2" placeholder="House/Flat, Street, Landmark"></textarea>
                            </div>
                        </div>

                        ${workerExtras}

                        <div class="ep-modal-foot">
                            <button type="button" class="ep-btn-cancel" id="epCancelBtn">Cancel</button>
                            <button type="submit" class="ep-btn-save" id="epSaveBtn">
                                <span class="ep-btn-text"><i class="fa-solid fa-floppy-disk"></i> Save Changes</span>
                                <span class="ep-btn-loader" style="display:none;"><i class="fa-solid fa-circle-notch fa-spin"></i> Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CHANGE PASSWORD MODAL -->
        <div id="changePwModal" class="ep-overlay" style="display:none;" aria-modal="true" role="dialog">
            <div class="ep-modal ep-modal-sm">
                <div class="ep-modal-head">
                    <h2><i class="fa-solid fa-lock"></i> Change Password</h2>
                    <button class="ep-close" id="cpCloseBtn">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <form id="changePwForm" autocomplete="off">
                        <div id="cpAlert" class="ep-alert" style="display:none;"></div>
                        <div class="ep-group ep-full" style="margin-bottom:16px;">
                            <label>Current Password <span class="ep-req">*</span></label>
                            <div class="ep-input-wrap">
                                <i class="fa-solid fa-lock ep-icon"></i>
                                <input type="password" name="currentPassword" id="cp_current" placeholder="Enter current password" required>
                                <button type="button" class="ep-eye-btn" onclick="SohojShebaDashboard._togglePw('cp_current',this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="ep-group ep-full" style="margin-bottom:16px;">
                            <label>New Password <span class="ep-req">*</span></label>
                            <div class="ep-input-wrap">
                                <i class="fa-solid fa-lock ep-icon"></i>
                                <input type="password" name="newPassword" id="cp_new" placeholder="Min. 6 characters" required>
                                <button type="button" class="ep-eye-btn" onclick="SohojShebaDashboard._togglePw('cp_new',this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="ep-group ep-full" style="margin-bottom:24px;">
                            <label>Confirm New Password <span class="ep-req">*</span></label>
                            <div class="ep-input-wrap">
                                <i class="fa-solid fa-lock ep-icon"></i>
                                <input type="password" name="confirmPassword" id="cp_confirm" placeholder="Re-enter new password" required>
                                <button type="button" class="ep-eye-btn" onclick="SohojShebaDashboard._togglePw('cp_confirm',this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="ep-modal-foot">
                            <button type="button" class="ep-btn-cancel" id="cpCancelBtn">Cancel</button>
                            <button type="submit" class="ep-btn-save" id="cpSaveBtn">
                                <span class="ep-btn-text"><i class="fa-solid fa-floppy-disk"></i> Update Password</span>
                                <span class="ep-btn-loader" style="display:none;"><i class="fa-solid fa-circle-notch fa-spin"></i> Updating...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- BOOKING MODAL (User) -->
        <div id="bookingModal" class="ep-overlay" style="display:none;" aria-modal="true" role="dialog">
            <div class="ep-modal">
                <div class="ep-modal-head">
                    <h2><i class="fa-solid fa-calendar-plus"></i> Book <span id="bk_serviceTitle">Service</span></h2>
                    <button class="ep-close" id="bkCloseBtn">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <form id="bookingForm" autocomplete="off">
                        <div class="ep-section-title">Select a professional</div>
                        <div class="ep-checkbox-grid" id="bk_workers" style="grid-template-columns:1fr;"></div>

                        <div class="ep-section-title" style="margin-top:18px;">Job details</div>
                        <div class="ep-row">
                            <div class="ep-group">
                                <label>Preferred Date & Time (optional)</label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-clock ep-icon"></i>
                                    <input type="datetime-local" name="scheduled_at" id="bk_scheduledAt">
                                </div>
                            </div>
                            <div class="ep-group">
                                <label>Address <span class="ep-req">*</span></label>
                                <div class="ep-input-wrap">
                                    <i class="fa-solid fa-location-dot ep-icon"></i>
                                    <input type="text" name="address_text" id="bk_address" placeholder="Your address" required>
                                </div>
                            </div>
                        </div>
                        <div class="ep-group ep-full" style="margin-top:12px;">
                            <label>Notes (optional)</label>
                            <div class="ep-input-wrap">
                                <i class="fa-solid fa-note-sticky ep-icon"></i>
                                <textarea name="notes" id="bk_notes" placeholder="Describe your problem (e.g. fan installation, leakage repair)"></textarea>
                            </div>
                        </div>

                        <div class="ep-modal-foot">
                            <button type="button" class="ep-btn-cancel" id="bkCancelBtn">Cancel</button>
                            <button type="button" class="ep-btn-save" id="bkConfirmBtn" onclick="SohojShebaDashboard.submitBooking()">
                                <span class="ep-btn-text"><i class="fa-solid fa-paper-plane"></i> Confirm Booking</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SUCCESS TOAST -->
        <div id="epToast" class="ep-toast" style="display:none;"></div>
        `);

        this._injectModalStyles();
        this._bindModalEvents();
    },

    // ─── Open modals & prefill ────────────────────────
    openEditModal() {
        const p = this._profileData;
        if (!p) { this._showToast('Profile not loaded yet, please wait.'); return; }

        const val = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
        val('ep_name',         p.name);
        val('ep_dateOfBirth',  p.date_of_birth || '');
        val('ep_gender',       p.gender || '');
        val('ep_phone',        p.phone || '');
        val('ep_whatsapp',     p.whatsapp || '');
        val('ep_altPhone',     p.alternative_phone || '');
        val('ep_postalCode',   p.postal_code || '');
        val('ep_country',      p.country || '');
        val('ep_city',         p.city || '');
        val('ep_area',         p.area || '');
        val('ep_address',      p.address || '');
        val('ep_language',     p.preferred_language || '');
        val('ep_referralSource', p.referral_source || '');
        val('ep_preferences',  p.preferences_text || '');
        val('ep_experience',   p.experience || '');
        val('ep_skills',       p.skills || '');
        val('ep_nidNumber',    p.nid_number || '');
        val('ep_tradeLicense', p.trade_license || '');

        // Worker service checkboxes — use slugs returned directly by profile.php GET
        const currentSlugs = p.service_slugs || [];
        document.querySelectorAll('#ep_servicesGrid input[type="checkbox"]').forEach(cb => {
            cb.checked = currentSlugs.includes(cb.value);
        });

        this._clearAlert('epAlert');
        document.getElementById('editProfileModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('ep_name')?.focus(), 100);
    },

    closeEditModal() {
        document.getElementById('editProfileModal').style.display = 'none';
        document.body.style.overflow = '';
    },

    openChangePwModal() {
        document.getElementById('changePwForm').reset();
        this._clearAlert('cpAlert');
        document.getElementById('changePwModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('cp_current')?.focus(), 100);
    },

    closeChangePwModal() {
        document.getElementById('changePwModal').style.display = 'none';
        document.body.style.overflow = '';
    },

    // ─── Bind modal events ────────────────────────────
    _bindModalEvents() {
        // Button delegation — catches dynamically added buttons in both dashboards
        document.addEventListener('click', e => {
            const btn = e.target.closest('button.btn-secondary');
            if (!btn) return;
            if (btn.textContent.trim().includes('Edit Profile'))    { this.openEditModal(); return; }
            if (btn.textContent.trim().includes('Change Password')) { this.openChangePwModal(); }
        });

        document.getElementById('epCloseBtn').addEventListener('click',  () => this.closeEditModal());
        document.getElementById('epCancelBtn').addEventListener('click',  () => this.closeEditModal());
        document.getElementById('cpCloseBtn').addEventListener('click',   () => this.closeChangePwModal());
        document.getElementById('cpCancelBtn').addEventListener('click',  () => this.closeChangePwModal());
        document.getElementById('bkCloseBtn')?.addEventListener('click',  () => this.closeBookingModal());
        document.getElementById('bkCancelBtn')?.addEventListener('click', () => this.closeBookingModal());

        document.getElementById('editProfileModal').addEventListener('click', e => { if (e.target === e.currentTarget) this.closeEditModal(); });
        document.getElementById('changePwModal').addEventListener('click',    e => { if (e.target === e.currentTarget) this.closeChangePwModal(); });
        document.getElementById('bookingModal')?.addEventListener('click',    e => { if (e.target === e.currentTarget) this.closeBookingModal(); });

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            this.closeEditModal();
            this.closeChangePwModal();
            this.closeBookingModal();
        });

        document.getElementById('editProfileForm').addEventListener('submit', e => { e.preventDefault(); this._submitEditProfile(); });
        document.getElementById('changePwForm').addEventListener('submit',    e => { e.preventDefault(); this._submitChangePassword(); });
    },

    // ─── Submit edit profile ──────────────────────────
    _submitEditProfile() {
        const saveBtn = document.getElementById('epSaveBtn');
        const btnText = saveBtn.querySelector('.ep-btn-text');
        const btnLoad = saveBtn.querySelector('.ep-btn-loader');

        if (!document.getElementById('ep_name')?.value.trim()) {
            this._showAlert('epAlert', 'error', 'Name cannot be empty.'); return;
        }

        saveBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoad.style.display = 'inline-flex';
        this._clearAlert('epAlert');

        fetch('api/profile.php', {
            method: 'POST',
            body: new FormData(document.getElementById('editProfileForm'))
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.closeEditModal();
                    this._showToast('Profile updated successfully!');
                    if (data.profile_photo_path) {
                        if (this.currentUser) this.currentUser.profile_photo_path = data.profile_photo_path;
                        this._setProfilePhoto(data.profile_photo_path);
                    }
                    this.loadProfile();
                } else {
                    this._showAlert('epAlert', 'error', data.message || 'Failed to save changes.');
                }
            })
            .catch(() => this._showAlert('epAlert', 'error', 'Server error. Please try again.'))
            .finally(() => {
                saveBtn.disabled = false;
                btnText.style.display = 'inline-flex';
                btnLoad.style.display = 'none';
            });
    },

    // ─── Submit change password ────────────────────────
    _submitChangePassword() {
        const saveBtn = document.getElementById('cpSaveBtn');
        const btnText = saveBtn.querySelector('.ep-btn-text');
        const btnLoad = saveBtn.querySelector('.ep-btn-loader');

        const current = document.getElementById('cp_current').value;
        const newPw   = document.getElementById('cp_new').value;
        const confirm = document.getElementById('cp_confirm').value;

        if (!current)          { this._showAlert('cpAlert', 'error', 'Please enter your current password.'); return; }
        if (newPw.length < 6)  { this._showAlert('cpAlert', 'error', 'New password must be at least 6 characters.'); return; }
        if (newPw !== confirm)  { this._showAlert('cpAlert', 'error', 'New passwords do not match.'); return; }

        saveBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoad.style.display = 'inline-flex';
        this._clearAlert('cpAlert');

        fetch('api/profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_password', currentPassword: current, newPassword: newPw })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.closeChangePwModal();
                    this._showToast('Password changed successfully!');
                    document.getElementById('changePwForm').reset();
                } else {
                    this._showAlert('cpAlert', 'error', data.message || 'Failed to change password.');
                }
            })
            .catch(() => this._showAlert('cpAlert', 'error', 'Server error. Please try again.'))
            .finally(() => {
                saveBtn.disabled = false;
                btnText.style.display = 'inline-flex';
                btnLoad.style.display = 'none';
            });
    },

    // ─── UI helpers ───────────────────────────────────
    _showAlert(id, type, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.className = `ep-alert ep-alert-${type}`;
        el.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> ${msg}`;
        el.style.display = 'flex';
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },
    _clearAlert(id) {
        const el = document.getElementById(id);
        if (el) { el.style.display = 'none'; el.textContent = ''; }
    },
    _showToast(msg) {
        const el = document.getElementById('epToast');
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
        el.classList.add('ep-toast-show');
        setTimeout(() => {
            el.classList.remove('ep-toast-show');
            setTimeout(() => { el.style.display = 'none'; }, 400);
        }, 3200);
    },
    _togglePw(fieldId, btn) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        const isText = field.type === 'text';
        field.type = isText ? 'password' : 'text';
        btn.querySelector('i').className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
    },

    // ─── Inject modal CSS ─────────────────────────────
    _injectModalStyles() {
        const style = document.createElement('style');
        style.textContent = `
        .ep-overlay {
            position:fixed;inset:0;z-index:1000;
            background:rgba(13,27,42,0.55);
            backdrop-filter:blur(4px);
            display:flex;align-items:center;justify-content:center;
            padding:20px;
            animation:epFadeIn 0.2s ease;
        }
        @keyframes epFadeIn{from{opacity:0}to{opacity:1}}

        .ep-modal {
            background:var(--surface,#fff);
            border-radius:var(--radius,14px);
            width:100%;max-width:700px;max-height:90vh;
            display:flex;flex-direction:column;
            box-shadow:0 24px 60px rgba(0,0,0,0.18);
            animation:epSlideUp 0.25s ease;
            overflow:hidden;
        }
        .ep-modal-sm{max-width:460px;}
        @keyframes epSlideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}

        .ep-modal-head {
            padding:20px 24px;
            border-bottom:1px solid var(--border,#e8ecf1);
            display:flex;align-items:center;justify-content:space-between;
            flex-shrink:0;
        }
        .ep-modal-head h2 {
            font-family:'Syne',sans-serif;font-size:17px;font-weight:700;
            color:var(--text,#0d1b2a);display:flex;align-items:center;gap:9px;
        }
        .ep-modal-head h2 i{color:var(--accent,#2e7d32);font-size:15px;}
        .ep-close {
            width:32px;height:32px;border-radius:8px;
            background:var(--bg,#f4f6f9);border:none;
            font-size:20px;cursor:pointer;color:var(--muted,#6b7a8d);
            display:flex;align-items:center;justify-content:center;
            transition:all 0.2s;line-height:1;
        }
        .ep-close:hover{background:#fef2f2;color:#dc2626;}

        .ep-modal-body{overflow-y:auto;padding:24px;flex:1;}

        .ep-section-title {
            font-family:'Syne',sans-serif;font-size:11px;font-weight:700;
            text-transform:uppercase;letter-spacing:1.1px;
            color:var(--accent,#2e7d32);
            margin:22px 0 14px;padding-bottom:7px;
            border-bottom:2px solid var(--accent-pale,#e8f5e9);
        }
        .ep-section-title:first-child{margin-top:0;}

        .ep-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
        .ep-group{display:flex;flex-direction:column;gap:6px;}
        .ep-group.ep-full{grid-column:1/-1;}
        .ep-group label{font-size:12.5px;font-weight:600;color:var(--text,#0d1b2a);}
        .ep-req{color:#dc2626;}

        .ep-input-wrap {
            display:flex;align-items:center;
            border:2px solid var(--border,#e8ecf1);
            border-radius:10px;background:var(--bg,#f4f6f9);
            transition:all 0.2s;overflow:hidden;
        }
        .ep-input-wrap:focus-within {
            border-color:var(--accent,#2e7d32);background:#fff;
            box-shadow:0 0 0 3px var(--accent-pale,#e8f5e9);
        }
        .ep-icon{padding:0 13px;color:var(--muted,#6b7a8d);font-size:14px;flex-shrink:0;transition:color 0.2s;}
        .ep-input-wrap:focus-within .ep-icon{color:var(--accent,#2e7d32);}
        .ep-input-wrap input,.ep-input-wrap select,.ep-input-wrap textarea {
            flex:1;padding:11px 12px 11px 0;border:none;background:transparent;
            font-family:'DM Sans',sans-serif;font-size:13.5px;
            color:var(--text,#0d1b2a);outline:none;resize:vertical;
        }
        .ep-input-wrap input::placeholder,.ep-input-wrap textarea::placeholder{color:#b0bec5;}
        .ep-eye-btn{background:none;border:none;padding:0 13px;color:var(--muted,#6b7a8d);cursor:pointer;font-size:14px;transition:color 0.2s;}
        .ep-eye-btn:hover{color:var(--accent,#2e7d32);}

        .ep-checkbox-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:4px;}
        .ep-check-item {
            border:2px solid var(--border,#e8ecf1);border-radius:9px;
            padding:10px 12px;display:flex;align-items:center;gap:8px;
            cursor:pointer;transition:all 0.2s;font-size:13px;font-weight:500;
            color:var(--text,#0d1b2a);
        }
        .ep-check-item:hover{border-color:var(--accent,#2e7d32);background:var(--accent-pale,#e8f5e9);}
        .ep-check-item input{width:16px;height:16px;cursor:pointer;accent-color:var(--accent,#2e7d32);flex-shrink:0;}
        .ep-check-item span i{color:var(--accent,#2e7d32);margin-right:3px;}

        .ep-modal-foot {
            display:flex;justify-content:flex-end;gap:10px;
            padding-top:20px;margin-top:8px;
            border-top:1px solid var(--border,#e8ecf1);
        }
        .ep-btn-cancel {
            padding:10px 22px;border-radius:10px;
            background:var(--bg,#f4f6f9);border:1.5px solid var(--border,#e8ecf1);
            font-family:'DM Sans',sans-serif;font-size:13.5px;font-weight:600;
            color:var(--text,#0d1b2a);cursor:pointer;transition:all 0.2s;
        }
        .ep-btn-cancel:hover{border-color:var(--accent,#2e7d32);color:var(--accent,#2e7d32);}
        .ep-btn-save {
            padding:10px 24px;border-radius:10px;
            background:linear-gradient(135deg,var(--accent,#2e7d32),var(--accent-light,#43a047));
            border:none;color:white;
            font-family:'DM Sans',sans-serif;font-size:13.5px;font-weight:600;
            cursor:pointer;transition:all 0.2s;
            display:inline-flex;align-items:center;gap:7px;
            box-shadow:0 4px 12px var(--accent-glow,rgba(46,125,50,0.35));
        }
        .ep-btn-save:hover{transform:translateY(-1px);box-shadow:0 6px 18px var(--accent-glow,rgba(46,125,50,0.4));}
        .ep-btn-save:disabled{opacity:0.65;cursor:not-allowed;transform:none;}

        .ep-alert {
            display:flex;align-items:center;gap:9px;
            padding:11px 15px;border-radius:9px;
            font-size:13.5px;font-weight:500;margin-bottom:18px;
        }
        .ep-alert-error  {background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;}
        .ep-alert-success{background:#f0fdf4;border:1px solid #86efac;color:#166534;}

        .ep-toast {
            position:fixed;bottom:28px;right:28px;z-index:2000;
            background:linear-gradient(135deg,var(--accent,#2e7d32),var(--accent-light,#43a047));
            color:white;padding:13px 22px;border-radius:12px;
            font-size:14px;font-weight:600;
            box-shadow:0 8px 30px rgba(46,125,50,0.35);
            opacity:0;transform:translateY(10px);
            transition:opacity 0.3s ease,transform 0.3s ease;
            pointer-events:none;
        }
        .ep-toast-show{opacity:1!important;transform:translateY(0)!important;}

        @media(max-width:600px){
            .ep-row{grid-template-columns:1fr;}
            .ep-checkbox-grid{grid-template-columns:1fr 1fr;}
            .ep-modal-body{padding:16px;}
        }
        `;
        document.head.appendChild(style);
    },

    // ══════════════════════════════════════════════════
    // SPA NAV & LIFECYCLE
    // ══════════════════════════════════════════════════

    setupNavigation() {
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', () => this.showPage(item.getAttribute('data-page')));
        });
    },

    showPage(pageId) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        const target = document.getElementById(pageId + '-page');
        if (target) target.classList.add('active');
        const navItem = document.querySelector(`.nav-item[data-page="${pageId}"]`);
        if (navItem) navItem.classList.add('active');
        const titleEl = document.getElementById('pageTitle');
        if (titleEl) {
            const labels = {
                'overview':'Overview','bookings':'My Bookings','new-booking':'New Booking',
                'history':'History','favorites':'Favorites','profile':'Profile',
                'jobs':'Available Jobs','my-jobs':'My Jobs','completed':'Completed Jobs','earnings':'Earnings',
            };
            titleEl.textContent = labels[pageId] || pageId;
        }

        // Lazy-load booking/job data per page
        if (pageId === 'bookings') this.loadUserBookings();
        if (pageId === 'jobs' || pageId === 'my-jobs') this.loadWorkerRequests();
    },

    loadUserContent() {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statTotal','—'); set('statPending','—'); set('statCompleted','—'); set('statSpent','—');
    },
    loadWorkerContent() {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('statAvailable','—'); set('statCompleted','—'); set('statEarnings','—');
        set('earnTotal','—'); set('earnMonth','—'); set('earnPending','—'); set('earnAvailable','—');
        set('profileSpecialty','—'); set('profileJobs','0');
    },

    // ══════════════════════════════════════════════════
    // BOOKINGS / JOB REQUESTS
    // ══════════════════════════════════════════════════

    setupBooking() {
        // User: click "Book Now" on a service
        document.addEventListener('click', e => {
            const btn = e.target.closest('button.btn-book');
            if (!btn) return;
            const service = btn.getAttribute('data-service')
                || btn.closest('.service-card')?.getAttribute('data-service');
            if (!service) return;
            this.openBookingModal(service);
        });

        // Booking modal: toggle worker profile details
        document.getElementById('bk_workers')?.addEventListener('click', e => {
            const t = e.target.closest('[data-wk-toggle]');
            if (!t) return;
            e.preventDefault();
            e.stopPropagation();
            const id = t.getAttribute('data-wk-toggle');
            if (!id) return;
            const details = document.getElementById(id);
            if (!details) return;
            const next = details.style.display === 'none' || !details.style.display ? 'block' : 'none';
            details.style.display = next;
            t.textContent = next === 'block' ? 'Hide Profile' : 'View Profile';
        });

        this.setupFilters();
    },

    setupFilters() {
        // User bookings filters
        document.querySelectorAll('#bookings-page .filter-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                const label = (btn.textContent || '').trim().toLowerCase();
                this._userBookingFilter = label.includes('pending')
                    ? 'pending'
                    : label.includes('accepted')
                        ? 'accepted'
                        : label.includes('completed')
                            ? 'completed'
                            : 'all';
                document.querySelectorAll('#bookings-page .filter-tab').forEach(x => x.classList.remove('active'));
                btn.classList.add('active');
                this.renderUserBookings();
            });
        });

        // Worker jobs filters (All / Nearby / High Paying)
        document.querySelectorAll('#jobs-page .filter-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                const label = (btn.textContent || '').trim().toLowerCase();
                this._workerJobsFilter = label.includes('nearby')
                    ? 'nearby'
                    : label.includes('high')
                        ? 'high_paying'
                        : 'all';
                document.querySelectorAll('#jobs-page .filter-tab').forEach(x => x.classList.remove('active'));
                btn.classList.add('active');
                this.renderWorkerPendingJobs();
            });
        });
    },

    async _fetchJson(url, opts) {
        const r = await fetch(url, opts);
        const j = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(j.message || 'Request failed');
        return j;
    },

    openBookingModal(serviceSlug) {
        if (!this.currentUser || this.currentUser.role !== 'user') return;
        const modal = document.getElementById('bookingModal');
        if (!modal) return;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        modal.setAttribute('data-service', serviceSlug);

        const serviceTitle = document.getElementById('bk_serviceTitle');
        if (serviceTitle) serviceTitle.textContent = (serviceSlug || '').replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

        document.getElementById('bk_workers').innerHTML =
            `<div class="empty-state" style="padding:16px;">
                <div class="empty-icon"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
                <h4>Loading professionals...</h4>
                <p>Please wait</p>
             </div>`;

        // Prefill address from loaded profile (if any)
        const addr = this._profileData?.address || '';
        const addrEl = document.getElementById('bk_address');
        if (addrEl && !addrEl.value) addrEl.value = addr;

        this._loadWorkersForService(serviceSlug);
    },

    closeBookingModal() {
        const modal = document.getElementById('bookingModal');
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    },

    async _loadWorkersForService(serviceSlug) {
        try {
            const data = await this._fetchJson(`api/workers.php?service=${encodeURIComponent(serviceSlug)}`);
            const list = Array.isArray(data.workers) ? data.workers : [];
            const wrap = document.getElementById('bk_workers');
            if (!wrap) return;

            if (list.length === 0) {
                wrap.innerHTML =
                    `<div class="empty-state" style="padding:16px;">
                        <div class="empty-icon"><i class="fa-solid fa-user-slash"></i></div>
                        <h4>No professionals available</h4>
                        <p>Try another service or check back later.</p>
                     </div>`;
                return;
            }

            wrap.innerHTML = list.map(w => {
                const rating = (w.rating_avg && Number(w.rating_avg) > 0) ? Number(w.rating_avg).toFixed(1) : '—';
                const photo = this._photoUrl(w.profile_photo_path || '');
                const avatar = photo
                    ? `<img src="${photo}" alt="Worker" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">`
                    : `<div style="width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:#f3f4f6;color:#6b7280;"><i class="fa-solid fa-user"></i></div>`;
                const detailsId = `wk_${serviceSlug}_${w.id}`.replace(/[^a-z0-9_]/gi, '_');
                const exp = this._formatExp(w.experience);
                const city = this._cap(w.city);
                const area = this._escapeHtml(w.area || '—');
                const skills = this._escapeHtml(w.skills || '—');
                return `
                <label class="ep-check-item" style="display:flex;gap:10px;align-items:center;justify-content:flex-start;">
                    <input type="radio" name="worker_user_id" value="${w.id}" required>
                    ${avatar}
                    <span style="display:flex;flex-direction:column;gap:2px;">
                        <span style="font-weight:700;color:var(--text);">${this._escapeHtml(w.name || 'Worker')}</span>
                        <span style="font-size:12px;color:var(--muted);">
                            Jobs: ${w.jobs_completed ?? 0}
                        </span>
                        <span style="margin-top:6px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                            <button type="button"
                                data-wk-toggle="${detailsId}"
                                style="background:transparent;border:none;padding:0;color:var(--accent);font-weight:700;font-size:12px;cursor:pointer;">
                                View Profile
                            </button>
                        </span>
                        <div id="${detailsId}" style="display:none;margin-top:8px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:var(--bg);">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <div>
                                    <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.4px;text-transform:uppercase;">Rating</div>
                                    <div style="font-size:12.5px;color:var(--text);font-weight:600;margin-top:2px;">
                                        <i class="fa-solid fa-star" style="color:var(--accent);"></i> ${this._escapeHtml(rating)}
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.4px;text-transform:uppercase;">Experience</div>
                                    <div style="font-size:12.5px;color:var(--text);font-weight:600;margin-top:2px;">${this._escapeHtml(exp)}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.4px;text-transform:uppercase;">Location</div>
                                    <div style="font-size:12.5px;color:var(--text);font-weight:600;margin-top:2px;">${this._escapeHtml(city)} • ${area}</div>
                                </div>
                                <div style="grid-column:1/-1;">
                                    <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:.4px;text-transform:uppercase;">Skills</div>
                                    <div style="font-size:12.5px;color:var(--text);font-weight:600;margin-top:2px;line-height:1.5;">${skills}</div>
                                </div>
                            </div>
                        </div>
                    </span>
                </label>`;
            }).join('');
        } catch (e) {
            document.getElementById('bk_workers').innerHTML =
                `<div class="empty-state" style="padding:16px;">
                    <div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <h4>Failed to load professionals</h4>
                    <p>${this._escapeHtml(e.message || 'Please try again.')}</p>
                 </div>`;
        }
    },

    async submitBooking() {
        const modal = document.getElementById('bookingModal');
        const form = document.getElementById('bookingForm');
        if (!modal || !form) return;

        const service = modal.getAttribute('data-service') || '';
        const fd = new FormData(form);
        const workerUserId = fd.get('worker_user_id');
        const scheduledAt = fd.get('scheduled_at');
        const address = fd.get('address_text');
        const notes = fd.get('notes');

        if (!workerUserId) { this._showToast('Please select a professional.'); return; }

        const btn = document.getElementById('bkConfirmBtn');
        btn.disabled = true;
        try {
            const res = await this._fetchJson('api/bookings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create',
                    service: service,
                    worker_user_id: Number(workerUserId),
                    scheduled_at: scheduledAt || null,
                    address_text: address || null,
                    notes: notes || null
                })
            });
            this.closeBookingModal();
            this._showToast('Booking request sent to worker!');
            this.showPage('bookings');
            this.loadUserBookings();
        } catch (e) {
            this._showToast(e.message || 'Failed to create booking.');
        } finally {
            btn.disabled = false;
        }
    },

    async loadUserBookings() {
        if (!this.currentUser || this.currentUser.role !== 'user') return;
        const wrap = document.getElementById('userBookings');
        if (!wrap) return;

        wrap.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-circle-notch fa-spin"></i></div><h4>Loading...</h4><p>Please wait</p></div>`;
        try {
            const data = await this._fetchJson('api/bookings.php?scope=user');
            const list = Array.isArray(data.bookings) ? data.bookings : [];
            this._userBookingsCache = list;
            this.renderUserBookings();
            this._updateUserOverview();
        } catch (e) {
            wrap.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h4>Failed to load bookings</h4><p>${this._escapeHtml(e.message || '')}</p></div>`;
        }
    },

    renderUserBookings() {
        const wrap = document.getElementById('userBookings');
        if (!wrap) return;
        let list = Array.isArray(this._userBookingsCache) ? [...this._userBookingsCache] : [];
        if (this._userBookingFilter !== 'all') {
            list = list.filter(b => (String(b.display_status || b.status).toLowerCase() === this._userBookingFilter));
        }
        if (list.length === 0) {
            wrap.innerHTML = `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-calendar"></i></div><h4>No bookings found</h4><p>No ${this._escapeHtml(this._prettyStatus(this._userBookingFilter))} bookings right now.</p></div>`;
        } else {
            wrap.innerHTML = list.map(b => this._renderBookingCardForUser(b)).join('');
        }

        // Fill history with completed/cancelled/denied
        const historyEl = document.getElementById('bookingHistory');
        if (historyEl) {
            const hist = (this._userBookingsCache || []).filter(b => ['completed', 'cancelled', 'denied'].includes(String(b.display_status || b.status).toLowerCase()));
            historyEl.innerHTML = hist.length
                ? hist.map(b => this._renderBookingCardForUser(b)).join('')
                : `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div><h4>No history yet</h4><p>Your completed/cancelled bookings will appear here.</p></div>`;
        }
    },

    async loadWorkerRequests() {
        if (!this.currentUser || this.currentUser.role !== 'worker') return;
        const wrapA = document.getElementById('availableJobs');
        const wrapB = document.getElementById('workerJobs');
        const wrapMy = document.getElementById('myAcceptedJobs');

        const loading = `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-circle-notch fa-spin"></i></div><h4>Loading...</h4><p>Please wait</p></div>`;
        if (wrapA) wrapA.innerHTML = loading;
        if (wrapB) wrapB.innerHTML = loading;
        if (wrapMy) wrapMy.innerHTML = loading;

        try {
            const pending = await this._fetchJson('api/bookings.php?scope=worker_pending');
            const my = await this._fetchJson('api/bookings.php?scope=worker_my');
            const completed = await this._fetchJson('api/bookings.php?scope=worker_completed');
            this._workerPendingCache = Array.isArray(pending.bookings) ? pending.bookings : [];
            this._workerMyCache = Array.isArray(my.bookings) ? my.bookings : [];
            this._workerCompletedCache = Array.isArray(completed.bookings) ? completed.bookings : [];
            this.renderWorkerPendingJobs();
            this.renderWorkerMyJobs();
            this.renderWorkerCompletedJobs();
            this._updateWorkerOverview();
        } catch (e) {
            const err = `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h4>Failed to load jobs</h4><p>${this._escapeHtml(e.message || '')}</p></div>`;
            if (wrapA) wrapA.innerHTML = err;
            if (wrapB) wrapB.innerHTML = err;
            if (wrapMy) wrapMy.innerHTML = err;
        }
    },

    renderWorkerPendingJobs() {
        const wrapA = document.getElementById('availableJobs');
        const wrapB = document.getElementById('workerJobs');
        let list = Array.isArray(this._workerPendingCache) ? [...this._workerPendingCache] : [];

        if (this._workerJobsFilter === 'nearby') {
            list = list.filter(b => {
                const txt = `${b.address_text || ''}`.toLowerCase();
                const city = `${this._profileData?.city || ''}`.toLowerCase();
                const area = `${this._profileData?.area || ''}`.toLowerCase();
                if (!city && !area) return true;
                if (!txt) return false;
                return (city && txt.includes(city)) || (area && txt.includes(area));
            });
        } else if (this._workerJobsFilter === 'high_paying') {
            list = list.filter(b => Number(b.price || 0) >= 500);
        }

        const pHtml = list.length
            ? list.map(b => this._renderJobCardForWorker(b, true)).join('')
            : `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-briefcase"></i></div><h4>No jobs found</h4><p>No matching requests for this filter.</p></div>`;
        if (wrapA) wrapA.innerHTML = pHtml;
        if (wrapB) wrapB.innerHTML = pHtml;
    },

    renderWorkerMyJobs() {
        const wrapMy = document.getElementById('myAcceptedJobs');
        if (!wrapMy) return;
        const list = Array.isArray(this._workerMyCache) ? this._workerMyCache : [];
        wrapMy.innerHTML = list.length
            ? list.map(b => this._renderJobCardForWorker(b, false)).join('')
            : `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-clipboard-list"></i></div><h4>No accepted jobs</h4><p>Jobs you accept will appear here.</p></div>`;
    },

    renderWorkerCompletedJobs() {
        const wrap = document.getElementById('completedJobs');
        if (!wrap) return;
        const list = Array.isArray(this._workerCompletedCache) ? this._workerCompletedCache : [];
        wrap.innerHTML = list.length
            ? list.map(b => this._renderJobCardForWorker(b, false, true)).join('')
            : `<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-check-double"></i></div><h4>No completed jobs yet</h4><p>Completed jobs will appear here.</p></div>`;
    },

    async workerDecision(bookingId, decision) {
        try {
            await this._fetchJson('api/bookings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'worker_decide', booking_id: Number(bookingId), decision })
            });
            this._showToast(decision === 'accept' ? 'Job accepted!' : 'Job denied.');
            this.loadWorkerRequests();
        } catch (e) {
            this._showToast(e.message || 'Failed to update request.');
        }
    },

    async workerComplete(bookingId) {
        try {
            await this._fetchJson('api/bookings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'worker_complete', booking_id: Number(bookingId) })
            });
            this._showToast('Job marked as completed.');
            this.loadWorkerRequests();
        } catch (e) {
            this._showToast(e.message || 'Failed to complete job.');
        }
    },

    _renderBookingCardForUser(b) {
        const status = this._prettyStatus(b.display_status || b.status);
        const when = b.scheduled_at ? this._formatDate(b.scheduled_at) : 'Not scheduled';
        const worker = b.worker_name ? `Worker: ${this._escapeHtml(b.worker_name)}` : 'Worker: —';
        return `
        <div class="job-card" style="padding:14px 16px;">
            <div class="job-info">
                <h4>${this._escapeHtml(b.service_name || 'Service')} <span style="font-weight:600;color:var(--muted);">#${this._escapeHtml(b.booking_code || '')}</span></h4>
                <p><i class="fa-solid fa-clock"></i> ${this._escapeHtml(when)}</p>
                <p><i class="fa-solid fa-user"></i> ${worker}</p>
            </div>
            <div class="job-right">
                <div class="job-pay" style="font-size:12px;">${this._escapeHtml(status)}</div>
            </div>
        </div>`;
    },

    _renderJobCardForWorker(b, showActions, isCompleted = false) {
        const when = b.scheduled_at ? this._formatDate(b.scheduled_at) : 'Not scheduled';
        const addr = b.address_text ? this._escapeHtml(b.address_text) : '—';
        const notes = b.notes ? this._escapeHtml(b.notes) : '—';
        const user = `${this._escapeHtml(b.user_name || 'User')} (${this._escapeHtml(b.user_phone || '—')})`;
        const status = this._prettyStatus(b.display_status || b.status);
        const actions = showActions ? `
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
                <button class="btn-secondary" style="padding:8px 10px;border-radius:10px;" onclick="SohojShebaDashboard.workerDecision(${b.id},'deny')">
                    <i class="fa-solid fa-xmark"></i> Deny
                </button>
                <button class="btn-primary" style="padding:8px 10px;border-radius:10px;" onclick="SohojShebaDashboard.workerDecision(${b.id},'accept')">
                    <i class="fa-solid fa-check"></i> Accept
                </button>
            </div>` : '';
        const completeBtn = (!showActions && !isCompleted && String(b.status).toLowerCase() !== 'completed') ? `
            <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                <button class="btn-primary" style="padding:8px 10px;border-radius:10px;" onclick="SohojShebaDashboard.workerComplete(${b.id})">
                    <i class="fa-solid fa-check-double"></i> Mark Completed
                </button>
            </div>` : '';
        return `
        <div class="job-card" style="padding:14px 16px;">
            <div class="job-info">
                <h4>${this._escapeHtml(b.service_name || 'Service')} <span style="font-weight:600;color:var(--muted);">#${this._escapeHtml(b.booking_code || '')}</span></h4>
                <p><i class="fa-solid fa-user"></i> ${user}</p>
                <p><i class="fa-solid fa-location-dot"></i> ${addr}</p>
                <p><i class="fa-solid fa-note-sticky"></i> ${notes}</p>
                <p><i class="fa-solid fa-clock"></i> ${this._escapeHtml(when)}</p>
            </div>
            <div class="job-right">
                <div class="job-pay" style="font-size:12px;">${this._escapeHtml(status)}</div>
                ${actions}
                ${completeBtn}
            </div>
        </div>`;
    },

    _prettyStatus(s) {
        const v = (s || '').toString().toLowerCase();
        if (v === 'pending') return 'Pending';
        if (v === 'accepted') return 'Accepted';
        if (v === 'in_progress') return 'In Progress';
        if (v === 'completed') return 'Completed';
        if (v === 'denied') return 'Denied';
        if (v === 'cancelled') return 'Cancelled';
        return s || '—';
    },

    _escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    setupLogout() {
        const doLogout = () => {
            fetch('api/logout.php', { method:'POST' }).catch(()=>{}).finally(() => { window.location.href = 'index.html'; });
        };
        document.getElementById('logoutBtn')?.addEventListener('click', doLogout);
        document.getElementById('profileLogout')?.addEventListener('click', doLogout);
    },

    quickBookService() { this.showPage('new-booking'); }
};

document.addEventListener('DOMContentLoaded', () => SohojShebaDashboard.init());