/* =============================================
   SHOHOJ SHEBA — AUTH.JS
   Logic for Signup and Login
   ============================================= */

// 1. Handle Registration (Signup)
function handleSignup(e) {
    e.preventDefault();
    
    const name = document.getElementById('fullname').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.querySelector('input[name="role"]:checked').value;
    
    const btn = document.getElementById('signupBtn');
    const text = btn.querySelector('.btn-text');
    const loader = btn.querySelector('.btn-loader');

    // Validation
    if (password.length < 6) {
        alert("Password must be at least 6 characters");
        return;
    }

    // Show Loading
    btn.disabled = true;
    text.style.display = 'none';
    loader.style.display = 'inline-flex';

    setTimeout(() => {
        // Get existing users from localStorage or start empty array
        const users = JSON.parse(localStorage.getItem('shohoj_sheba_users') || '[]');
        
        // Check if email is already taken
        if (users.find(u => u.email === email)) {
            alert("This email is already registered!");
            btn.disabled = false;
            text.style.display = 'inline-flex';
            loader.style.display = 'none';
            return;
        }

        // Save new user
        users.push({ name, email, password, role });
        localStorage.setItem('shohoj_sheba_users', JSON.stringify(users));

        alert("Registration Successful! Redirecting to login...");
        window.location.href = 'login.html';
    }, 1500);
}

// 2. Handle Login
function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.querySelector('input[name="role"]:checked').value;

    // Find user in localStorage
    const users = JSON.parse(localStorage.getItem('shohoj_sheba_users') || '[]');
    const user = users.find(u => u.email === email && u.password === password && u.role === role);

    if (user) {
        // Save current session so main.js knows you're logged in
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

// 3. Password Toggle (Eye Icon)
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