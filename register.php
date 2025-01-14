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
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        if ($auth->register($username, $password, $email)) {
            $success = "Registration successful! Please login.";
        } else {
            $error = "Username or email already exists.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Daily Journal</title>
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

        /* Register Section */
        .register-section {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 100%;
            overflow-y: auto;
        }

        @media (min-width: 992px) {
            .register-section {
                max-width: 500px;
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .register-title {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }

        .register-subtitle {
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

        .password-strength {
            height: 4px;
            background: #e9ecef;
            margin-top: 0.5rem;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .form-check {
            margin: 1rem 0;
        }

        .form-check-input:checked {
            background-color: #4a90e2;
            border-color: #4a90e2;
        }

        .btn-register {
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

        .btn-register:hover {
            background: #357abd;
            transform: translateY(-1px);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
        }

        .login-link a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .terms-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 1rem;
        }

        .terms-text a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
        }

        .terms-text a:hover {
            text-decoration: underline;
        }

        /* Help text styles */
        .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .help-text.valid {
            color: #28a745;
        }

        .help-text.invalid {
            color: #dc3545;
        }

        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .requirement-item i {
            font-size: 0.8rem;
        }

        .requirement-item.valid {
            color: #28a745;
        }

        .requirement-item.valid i {
            color: #28a745;
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

                <h1>Start Your Digital Journaling Journey</h1>
                <p>Join thousands of users who have discovered the joy of digital journaling. Create, organize, and preserve your memories in a beautiful and secure space.</p>

                <ul class="feature-list">
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                        Write in markdown or rich text
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        Calendar view for easy navigation
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-cloud"></i>
                        </div>
                        Secure cloud backup
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        Customizable themes and layouts
                    </li>
                </ul>
            </div>
        </div>

        <!-- Register Section -->
        <div class="register-section">
            <div class="register-header">
                <h2 class="register-title">Create Account</h2>
                <p class="register-subtitle">Start your journaling journey today</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
                <!-- Username Field -->
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Choose a username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           pattern="[a-zA-Z0-9_]{3,20}">
                    <label for="username">Username</label>
                    <div class="help-text">3-20 characters, letters, numbers, and underscores only</div>
                </div>

                <!-- Email Field -->
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Enter your email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <label for="email">Email address</label>
                </div>

                <!-- Password Field -->
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Create a password" required
                           onkeyup="checkPasswordStrength(this.value)">
                    <label for="password">Password</label>
                    <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon')">
                        <i class="far fa-eye" id="toggleIcon"></i>
                    </span>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Confirm your password" required>
                    <label for="confirm_password">Confirm Password</label>
                    <span class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIconConfirm')">
                        <i class="far fa-eye" id="toggleIconConfirm"></i>
                    </span>
                </div>

                <!-- Password Requirements -->
                <div class="password-requirements">
                    <div class="requirement-item" id="req-length">
                        <i class="fas fa-circle"></i>
                        At least 6 characters long
                    </div>
                    <div class="requirement-item" id="req-uppercase">
                        <i class="fas fa-circle"></i>
                        Contains uppercase letter
                    </div>
                    <div class="requirement-item" id="req-lowercase">
                        <i class="fas fa-circle"></i>
                        Contains lowercase letter
                    </div>
                    <div class="requirement-item" id="req-number">
                        <i class="fas fa-circle"></i>
                        Contains number
                    </div>
                    <div class="requirement-item" id="req-special">
                        <i class="fas fa-circle"></i>
                        Contains special character
                    </div>
                </div>

                <!-- Terms Checkbox -->
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-register">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>

                <!-- Login Link -->
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Privacy</h6>
                    <p>We respect your privacy and protect your personal information.</p>
                    
                    <h6>2. Content</h6>
                    <p>You are responsible for the content you create in your journal.</p>
                    
                    <h6>3. Security</h6>
                    <p>We implement security measures to protect your data.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Check password strength
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrengthBar');
            const requirements = {
                length: password.length >= 6,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                if (requirements[req]) {
                    element.classList.add('valid');
                    element.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
                } else {
                    element.classList.remove('valid');
                    element.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
                }
            });

            // Calculate strength percentage
            const strength = Object.values(requirements).filter(Boolean).length * 20;
            
            // Update strength bar
            strengthBar.style.width = `${strength}%`;
            if (strength <= 20) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength <= 40) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else if (strength <= 60) {
                strengthBar.style.backgroundColor = '#17a2b8';
            } else if (strength <= 80) {
                strengthBar.style.backgroundColor = '#28a745';
            } else {
                strengthBar.style.backgroundColor = '#198754';
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

        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>