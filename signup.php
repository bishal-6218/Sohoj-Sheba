<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up – Shohoj Sheba</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="navbar">
        <a href="index.php" class="logo">
            <div class="logo-mark"><i class="fa-solid fa-house-chimney"></i></div>
            Shohoj<span class="logo-accent">Sheba</span>
        </a>
        <a href="login.php" class="nav-btn-solid">Log In</a>
    </div>
</header>

<main class="login-page">
    <div class="login-left"></div>

    <div class="login-right">
        <div class="login-form-wrap">
            <h1>Create Account</h1>

            <form method="post" action="backend/signup.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="Your name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group password-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="pw" required minlength="6">
                        <button type="button" class="toggle-pw" onclick="togglePassword()">
                            <i class="fa-solid fa-eye" id="eye"></i>
                        </button>
                    </div>
                </div>

                <div class="role-toggle">
                    <label><input type="radio" name="role" value="user" checked> User</label>
                    <label><input type="radio" name="role" value="worker"> Worker</label>
                </div>

                <button type="submit" class="login-btn">Sign Up</button>
            </form>

            <p class="bottom-link">Already have an account? <a href="login.php">Log In</a></p>
        </div>
    </div>
</main>

<script>
// same togglePassword function as in login.php
function togglePassword() {
    const input = document.getElementById('pw');
    const eye = document.getElementById('eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

</body>
</html>