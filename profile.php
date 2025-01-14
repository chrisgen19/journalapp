<?php

require_once 'config.php';
require_once 'auth.php';
require_once 'journal.php';
require_once 'tag_manager.php';

$auth = new Auth($conn);
$journal = new Journal($conn);
$tagManager = new TagManager($conn);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = $auth->getUserDetails($_SESSION['user_id']);

// Get user statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_entries FROM journals WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_entries = $stmt->get_result()->fetch_assoc()['total_entries'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_images FROM journal_images ji 
    JOIN journals j ON ji.journal_id = j.id 
    WHERE j.user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_images = $stmt->get_result()->fetch_assoc()['total_images'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_tags FROM tags WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_tags = $stmt->get_result()->fetch_assoc()['total_tags'];

// Get recent activity
$stmt = $conn->prepare("
    SELECT j.*, 
        (SELECT image_path FROM journal_images WHERE journal_id = j.id LIMIT 1) as thumbnail
    FROM journals j 
    WHERE j.user_id = ? 
    ORDER BY j.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_activity = $stmt->get_result();

// Get most used tags
$popular_tags = $tagManager->getPopularTags($_SESSION['user_id'], 5);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if ($auth->updateUser($_SESSION['user_id'], $username, $email)) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            $user = $auth->getUserDetails($_SESSION['user_id']); // Refresh user details
        } else {
            $_SESSION['message'] = "Failed to update profile.";
            $_SESSION['message_type'] = "danger";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = "New passwords do not match.";
            $_SESSION['message_type'] = "danger";
        } elseif ($auth->updatePassword($_SESSION['user_id'], $new_password)) {
            $_SESSION['message'] = "Password updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update password.";
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Add this after your existing user checks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['message'] = "Only JPG, PNG and GIF images are allowed.";
        $_SESSION['message_type'] = "danger";
    } elseif ($file['size'] > $max_size) {
        $_SESSION['message'] = "File size must be less than 5MB.";
        $_SESSION['message_type'] = "danger";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading file.";
        $_SESSION['message_type'] = "danger";
    } else {
        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        // Delete old photo if exists
        if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
            unlink($user['profile_photo']);
        }

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            if ($auth->updateProfilePhoto($_SESSION['user_id'], $filepath)) {
                $_SESSION['message'] = "Profile photo updated successfully!";
                $_SESSION['message_type'] = "success";
                $user = $auth->getUserDetails($_SESSION['user_id']); // Refresh user details
            } else {
                $_SESSION['message'] = "Failed to update profile photo.";
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Failed to save uploaded file.";
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Get writing streak
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT DATE(created_at)) as days_written,
           MAX(created_at) as last_entry_date
    FROM journals 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$streak_data = $stmt->get_result()->fetch_assoc();

// Calculate writing streak percentage
$writing_percentage = ($streak_data['days_written'] / 30) * 100;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #2c3e50;
        }
        .container-fluid {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .profile-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: #4a90e2;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            flex-shrink: 0;
        }
        .profile-info h1 {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #1a202c;
        }
        .profile-meta {
            color: #6c757d;
            font-size: 0.95rem;
        }
        .profile-meta i {
            margin-right: 0.5rem;
        }
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #4a90e2;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.5rem;
            font-family: 'Merriweather', serif;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.95rem;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        .section-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-family: 'Merriweather', serif;
            font-size: 1.25rem;
            color: #1a202c;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-title .btn {
            font-size: 0.9rem;
            padding: 0.4rem 1rem;
        }
        .activity-item {
            display: flex;
            align-items: start;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4a90e2;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .activity-content {
            flex: 1;
            min-width: 0;
        }
        .activity-title {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .activity-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .tag-item {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            color: #4a5568;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        .tag-item:hover {
            background: #4a90e2;
            color: white;
            transform: translateY(-2px);
        }
        .tag-count {
            background: rgba(0,0,0,0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 50px;
            font-size: 0.8rem;
        }
        .streak-card {
            text-align: center;
            padding: 2rem;
        }
        .streak-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 10px solid #f8f9fa;
            margin: 0 auto 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #4a90e2;
        }
        .streak-circle::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            border: 10px solid #4a90e2;
            border-top-color: transparent;
            transform: rotate(<?php echo -90 + ($writing_percentage * 3.6); ?>deg);
            transition: all 0.3s ease;
        }
        .streak-text {
            font-size: 1.1rem;
            color: #6c757d;
        }
        .streak-subtext {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .form-card h3 {
            font-family: 'Merriweather', serif;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #1a202c;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .form-floating .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            height: calc(3.5rem + 2px);
            padding: 1rem;
        }
        .form-floating .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.25rem rgba(74, 144, 226, 0.1);
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
        .btn-save {
            background: #4a90e2;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-save:hover {
            background: #357abd;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        /* Update this CSS class in profile.php */
        .btn-save {
            background: #4a90e2;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
            color: white; /* Add this line for white text */
        }

        .btn-save:hover {
            background: #357abd;
            transform: translateY(-1px);
            color: white; /* Also add this to maintain white text on hover */
        }

        .profile-photo-section {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: #4a90e2;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-upload-form {
            position: relative;
        }

        .file-input {
            position: absolute;
            width: 0.1px;
            height: 0.1px;
            opacity: 0;
            overflow: hidden;
            z-index: -1;
        }

        .upload-button label {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .upload-button label:hover {
            background: #4a90e2;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-photo-section">
                    <div class="profile-avatar">
                        <?php if ($user['profile_photo']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile photo" class="profile-image">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <form method="post" enctype="multipart/form-data" class="photo-upload-form">
                        <div class="upload-button">
                            <input type="file" name="profile_photo" id="profile_photo" class="file-input" accept="image/*" onchange="this.form.submit()">
                            <label for="profile_photo" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-camera me-2"></i>Change Photo
                            </label>
                        </div>
                    </form>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <div class="profile-meta">
                        <p class="mb-2">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-clock"></i>
                            Member since <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                        </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stat-cards">
                <!-- Total Entries -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_entries; ?></div>
                    <div class="stat-label">Total Journal Entries</div>
                </div>

                <!-- Total Images -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_images; ?></div>
                    <div class="stat-label">Images Uploaded</div>
                </div>

                <!-- Total Tags -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_tags; ?></div>
                    <div class="stat-label">Tags Created</div>
                </div>

                <!-- Writing Streak -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-number"><?php echo $streak_data['days_written']; ?></div>
                    <div class="stat-label">Days Written (Last 30 Days)</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left Column -->
                <div>
                    <!-- Recent Activity -->
                    <div class="section-card">
                        <div class="section-title">
                            <span>Recent Activity</span>
                            <a href="journals.php" class="btn btn-outline-primary">View All</a>
                        </div>
                        <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                            <a href="journal_view.php?id=<?php echo $activity['id']; ?>" class="activity-item text-decoration-none">
                                <div class="activity-icon">
                                    <?php if ($activity['thumbnail']): ?>
                                        <i class="fas fa-image"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-meta">
                                        <?php echo date('F j, Y - g:i A', strtotime($activity['created_at'])); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>

                    <!-- Account Settings -->
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

                    <!-- Profile Update Form -->
                    <div class="form-card">
                        <h3>Update Profile</h3>
                        <form method="post">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <label for="username">Username</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <label for="email">Email address</label>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-save text-white">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Password Update Form -->
                    <div class="form-card">
                        <h3>Change Password</h3>
                        <form method="post">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                                <label for="current_password">Current Password</label>
                                <span class="password-toggle" onclick="togglePassword('current_password')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required>
                                <label for="new_password">New Password</label>
                                <span class="password-toggle" onclick="togglePassword('new_password')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                                <label for="confirm_password">Confirm New Password</label>
                                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                            <button type="submit" name="update_password" class="btn btn-save text-white">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Writing Streak -->
                    <div class="section-card streak-card">
                        <div class="streak-circle">
                            <?php echo $streak_data['days_written']; ?>
                        </div>
                        <div class="streak-text">Days Written</div>
                        <div class="streak-subtext">Last 30 Days</div>
                    </div>

                    <!-- Popular Tags -->
                    <div class="section-card">
                        <div class="section-title">
                            <span>Most Used Tags</span>
                        </div>
                        <div class="tag-list">
                            <?php while ($tag = $popular_tags->fetch_assoc()): ?>
                                <a href="journals_by_tag.php?id=<?php echo $tag['id']; ?>" 
                                   class="tag-item text-decoration-none">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                    <span class="tag-count"><?php echo $tag['usage_count']; ?></span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
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
    </script>
</body>
</html>