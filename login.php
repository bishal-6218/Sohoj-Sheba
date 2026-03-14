<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Shohoj Sheba</title>
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
        <a href="signup.php" class="nav-btn-solid">Sign Up</a>
    </div>
</header>

<main class="login-page">
    <div class="login-left"></div>

    <div class="login-right">
        <div class="login-form-wrap">
            <h1>Log In</h1>
            <p class="subtitle">Choose your account type</p>

            <form method="post" action="backend/login.php">
                <div class="role-toggle">
                    <label><input type="radio" name="role" value="user" checked> User</label>
                    <label><input type="radio" name="role" value="worker"> Worker</label>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>

                <div class="form-group password-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="pw" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword()">
                            <i class="fa-solid fa-eye" id="eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">Log In</button>
            </form>

            <p class="bottom-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>
</main>

<script>
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