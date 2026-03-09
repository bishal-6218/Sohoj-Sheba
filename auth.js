/* =============================================
   SHOHOJ SHEBA — AUTH.JS
   Logic for Signup and Login
   ============================================= */

// 1. Password Toggle (Eye Icon)
// This allows users to see their password while typing
function togglePassword() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// 2. Handle Registration (Signup)
function handleSignup(e) {
    e.preventDefault();
    
    // Get input values
    const name = document.getElementById('fullname').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const roleElement = document.querySelector('input[name="role"]:checked');
    
    // Check if role is selected
    if (!roleElement) {
        alert("Please select a role (User or Worker)");
        return;
    }
    const role = roleElement.value;

    // Select the button and loader for visual feedback
    const btn = document.getElementById('signupBtn');
    const text = btn.querySelector('.btn-text');
    const loader = btn.querySelector('.btn-loader');

    // Validation
    if (password.length < 6) {
        alert("Password must be at least 6 characters");
        return;
    }

    // Visual feedback: Start loading
    btn.disabled = true;
    if (text) text.style.display = 'none';
    if (loader) loader.style.display = 'inline-flex';

    setTimeout(() => {
        // Get existing users from localStorage or start empty array
        const users = JSON.parse(localStorage.getItem('shohoj_sheba_users') || '[]');
        
        // Check if email is already taken
        if (users.find(u => u.email === email)) {
            alert("This email is already registered!");
            btn.disabled = false;
            if (text) text.style.display = 'inline-flex';
            if (loader) loader.style.display = 'none';
            return;
        }

        // Save new user
        users.push({ name, email, password, role });
        localStorage.setItem('shohoj_sheba_users', JSON.stringify(users));

        alert("Registration Successful! Redirecting to login...");
        window.location.href = 'login.html';
    }, 1500);
}

// 3. Handle Login
function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const roleElement = document.querySelector('input[name="role"]:checked');

    if (!roleElement) {
        alert("Please select your role");
        return;
    }
    const role = roleElement.value;

    // Find user in localStorage
    const users = JSON.parse(localStorage.getItem('shohoj_sheba_users') || '[]');
    const user = users.find(u => u.email === email && u.password === password && u.role === role);

    if (user) {
        // Save current session so the site knows you're logged in
        localStorage.setItem('shohoj_sheba_user', JSON.stringify(user));
        
        // Redirect based on role
        if (role === 'worker') {
            window.location.href = 'worker-dashboard.html';
        } else {
            window.location.href = 'user-dashboard.html';
        }
    } else {
        alert("Invalid email, password, or role. Please try again.");
    }
}