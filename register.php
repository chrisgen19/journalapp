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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-logo {
            width: 80px;
            height: 80px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .register-logo i {
            font-size: 2rem;
            color: #007bff;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-card .card-body {
            padding: 2rem;
        }
        .form-control {
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.15);
        }
        .input-group-text {
            border: 2px solid #e9ecef;
            background: #f8f9fa;
            border-right: none;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        .input-group .form-control {
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .btn-register {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 8px;
            width: 100%;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1rem;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .error-shake {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .password-toggle {
            cursor: pointer;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .password-strength {
            height: 5px;
            margin-top: 0.5rem;
            border-radius: 2.5px;
            transition: all 0.3s ease;
        }
        .terms {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Register Header -->
        <div class="register-header">
            <div class="register-logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="h3 mb-3">Create Account</h1>
            <p class="text-muted">Join us to start your journaling journey</p>
        </div>

        <!-- Register Form -->
        <div class="register-card">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show error-shake" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <!-- Username Field -->
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   placeholder="Choose a username" required>
                        </div>
                        <small class="text-muted">Username must be unique</small>
                    </div>

                    <!-- Email Field -->
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   placeholder="Enter your email" required>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="password" class="form-control" 
                                   id="password" placeholder="Create a password" required
                                   onkeyup="checkPasswordStrength(this.value)">
                            <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon')">
                                <i class="far fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" name="confirm_password" class="form-control" 
                                   id="confirmPassword" placeholder="Confirm your password" required>
                            <span class="password-toggle" onclick="togglePassword('confirmPassword', 'toggleIconConfirm')">
                                <i class="far fa-eye" id="toggleIconConfirm"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="terms">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>
            </div>
        </div>

        <!-- Login Link -->
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
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
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            if (password.match(/[^A-Za-z0-9]/)) strength += 25;

            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength <= 50) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else if (strength <= 75) {
                strengthBar.style.backgroundColor = '#17a2b8';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
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