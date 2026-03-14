<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — Shohoj Sheba</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="navbar">
        <a href="index.html" class="logo">
            <div class="logo-mark"><i class="fa-solid fa-house-chimney"></i></div>
            <span>Shohoj<span class="logo-accent">Sheba</span></span>
        </a>
        <div class="nav-actions">
            <span class="nav-hint">Already have an account?</span>
            <a href="login.html" class="nav-btn-solid">Log In</a>
        </div>
    </div>
</header>

<main class="login-page">
    <div class="login-left" id="sidePanel">
        <div class="login-left-overlay"></div>
        <div class="login-left-content">
            <div class="left-badge"><i class="fa-solid fa-star"></i> Join Us</div>
            <h2>Start Your Journey<br>with <span>Shohoj Sheba</span></h2>
            <p>Access the best home services or grow your business as a professional provider.</p>
        </div>
    </div>

    <div class="login-right">
        <div class="login-form-wrap">
            <h1 class="form-title">Create Account</h1>
            
            <div class="role-toggle">
                <input type="radio" name="role" id="role-user" value="user" checked>
                <label for="role-user" class="role-label"><i class="fa-solid fa-user"></i><span>User</span></label>
                <input type="radio" name="role" id="role-worker" value="worker">
                <label for="role-worker" class="role-label"><i class="fa-solid fa-hard-hat"></i><span>Worker</span></label>
            </div>

            <form class="login-form" onsubmit="handleSignup(event)">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user-tag input-icon"></i>
                        <input type="text" id="fullname" placeholder="Enter your full name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" id="email" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword()">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn" id="signupBtn">
                    <span class="btn-text">Sign Up</span>
                    <span class="btn-loader" style="display:none;"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                </button>
            </form>
            <p class="signup-prompt">Already have an account? <a href="login.html" class="signup-link">Log In</a></p>
        </div>
    </div>
</main>

<script src="auth.js"></script>
<script>
    // Theme switching logic for Worker vs User visual modes
    document.querySelectorAll('input[name="role"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const sidePanel = document.getElementById('sidePanel');
            if (this.value === 'worker') sidePanel.classList.add('worker-mode');
            else sidePanel.classList.remove('worker-mode');
        });
    });
</script>
</body>
</html>