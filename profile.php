<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth($conn);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = $auth->getUserDetails($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message_type = "danger";
    
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if ($auth->updateUser($_SESSION['user_id'], $username, $email)) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            $user = $auth->getUserDetails($_SESSION['user_id']);
        } else {
            $_SESSION['message'] = "Failed to update profile.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = "New passwords do not match.";
        } elseif ($auth->updatePassword($_SESSION['user_id'], $new_password)) {
            $_SESSION['message'] = "Password updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update password.";
        }
    }
    
    // Redirect to refresh the page and show message
    header("Location: profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-header {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .nav-pills .nav-link {
            color: #495057;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-right: 0.5rem;
        }
        .nav-pills .nav-link.active {
            background-color: #007bff;
            color: white;
        }
        .nav-pills .nav-link i {
            margin-right: 0.5rem;
        }
        .tab-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="container py-5">
            <!-- Profile Header -->
            <div class="profile-header d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                    <div class="profile-avatar me-4">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h1 class="h3 mb-2"><?php echo htmlspecialchars($user['username']); ?></h1>
                        <p class="text-muted mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <a href="journals.php" class="btn btn-primary">
                    <i class="fas fa-book me-2"></i>My Journals
                </a>
            </div>

            <!-- Status Message -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                    <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Navigation Pills -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="pill" href="#overview">
                        <i class="fas fa-home"></i>Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#edit-profile">
                        <i class="fas fa-user-edit"></i>Edit Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#security">
                        <i class="fas fa-shield-alt"></i>Security
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview">
                    <div class="row g-4">
                        <!-- Statistics -->
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM journals WHERE user_id = ?");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result()->fetch_assoc();
                                    echo $result['count'];
                                    ?>
                                </div>
                                <div class="stat-label">Total Journal Entries</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM journal_images WHERE journal_id IN (SELECT id FROM journals WHERE user_id = ?)");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result()->fetch_assoc();
                                    echo $result['count'];
                                    ?>
                                </div>
                                <div class="stat-label">Images Uploaded</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM journals WHERE user_id = ? AND entry_date = CURDATE()");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result()->fetch_assoc();
                                    echo $result['count'];
                                    ?>
                                </div>
                                <div class="stat-label">Entries Today</div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="col-12">
                            <h3 class="h5 mb-4">Recent Activity</h3>
                            <div class="card">
                                <?php
                                $stmt = $conn->prepare("
                                    SELECT title, entry_date 
                                    FROM journals 
                                    WHERE user_id = ? 
                                    ORDER BY created_at DESC 
                                    LIMIT 5
                                ");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $activities = $stmt->get_result();
                                
                                if ($activities->num_rows > 0):
                                    while ($activity = $activities->fetch_assoc()):
                                ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-pen me-2 text-primary"></i>
                                                Created journal entry: <?php echo htmlspecialchars($activity['title']); ?>
                                            </div>
                                            <span class="activity-date">
                                                <?php echo date('M j, Y', strtotime($activity['entry_date'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="p-4 text-center text-muted">
                                        No recent activity
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Tab -->
                <div class="tab-pane fade" id="edit-profile">
                    <form method="post" class="card">
                        <div class="card-body">
                            <h3 class="h5 mb-4">Edit Profile Information</h3>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security">
                    <form method="post" class="card">
                        <div class="card-body">
                            <h3 class="h5 mb-4">Change Password</h3>
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>