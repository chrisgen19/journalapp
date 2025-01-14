<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth($conn);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header("Location: journals.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        if ($auth->login($username, $password)) {
            header("Location: journals.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Journal - Your Personal Digital Journal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .split-container {
            display: flex;
            min-height: 100vh;
            background: white;
            box-shadow: 0 0 50px rgba(0,0,0,0.1);
        }

        /* Showcase Section */
        .showcase {
            flex: 1;
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            padding: 3rem;
            display: none;
            color: white;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .showcase {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
        }

        .showcase-content {
            position: relative;
            z-index: 2;
        }

        .showcase-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .brand-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a90e2;
            font-size: 1.5rem;
        }

        .brand-name {
            font-family: 'Merriweather', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .showcase h1 {
            font-family: 'Merriweather', serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .showcase p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .showcase::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="40" stroke="rgba(255,255,255,0.1)" stroke-width="2" fill="none"/></svg>') repeat;
            opacity: 0.4;
        }

        /* Login Section */
        .login-section {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 100%;
        }

        @media (min-width: 992px) {
            .login-section {
                max-width: 500px;
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-title {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #6c757d;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            height: calc(3.5rem + 2px);
            padding: 1rem 1rem;
        }

        .form-floating .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.25rem rgba(74, 144, 226, 0.1);
        }

        .form-floating label {
            padding: 1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
        }

        .form-check {
            margin: 1rem 0;
        }

        .form-check-input:checked {
            background-color: #4a90e2;
            border-color: #4a90e2;
        }

        .btn-login {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            margin-top: 1rem;
            background: #4a90e2;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #357abd;
            transform: translateY(-1px);
        }

        .social-login {
            margin-top: 2rem;
            text-align: center;
        }

        .social-login-text {
            color: #6c757d;
            margin-bottom: 1rem;
            position: relative;
        }

        .social-login-text::before,
        .social-login-text::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #e9ecef;
        }

        .social-login-text::before {
            left: 0;
        }

        .social-login-text::after {
            right: 0;
        }

        .social-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-button {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .social-button:hover {
            background: #f8f9fa;
            color: #4a90e2;
            transform: translateY(-2px);
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
        }

        .register-link a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .testimonials {
            margin-top: auto;
        }

        .testimonial {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .author-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .author-info p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="split-container">
        <!-- Showcase Section -->
        <div class="showcase">
            <div class="showcase-content">
                <div class="showcase-brand">
                    <div class="brand-logo">
                        <i class="fas fa-book"></i>
                    </div>
                    <h2 class="brand-name">Daily Journal</h2>
                </div>

                <h1>Capture Your Thoughts, Preserve Your Memories</h1>
                <p>Your personal digital sanctuary for documenting life's journey, organizing thoughts, and reflecting on your experiences.</p>

                <ul class="feature-list">
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        Private and secure journaling platform
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        Rich media support with image galleries
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        Organized with tags and categories
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        Access your journals anywhere, anytime
                    </li>
                </ul>
            </div>

            <div class="testimonials">
                <div class="testimonial">
                    <p class="testimonial-text">
                        "Daily Journal has transformed how I document my life. It's become an essential part of my daily routine."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <h4>Sarah Mitchell</h4>
                            <p>Writer & Blogger</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Section -->
        <div class="login-section">
            <div class="login-header">
                <h2 class="login-title">Welcome Back</h2>
                <p class="login-subtitle">Sign in to continue your journey</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <label for="username">Username</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="far fa-eye" id="toggleIcon"></i>
                    </span>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>

            <div class="social-login">
                <p class="social-login-text">Or continue with</p>
                <div class="social-buttons">
                    <a href="#" class="social-button">
                        <i class="fab fa-google"></i>
                    </a>
                    <a href="#" class="social-button">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-button">
                        <i class="fab fa-apple"></i>
                    </a>
                </div>
            </div>

            <div class="register-link">
            Don't have an account? <a href="register.php">Register now</a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>