<?php
// navbar.php

?>
<!-- Add this style block to your main page's head section -->
<style>
    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 0.75rem 0;
    }

    .navbar-brand {
        font-family: 'Merriweather', serif;
        font-weight: 700;
        color: #1a202c;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .navbar-brand:hover {
        color: #4a90e2;
    }

    .brand-logo {
        width: 35px;
        height: 35px;
        background: #4a90e2;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }

    .nav-link {
        color: #4a5568;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        position: relative;
        margin: 0 0.25rem;
    }

    .nav-link:hover, .nav-link.active {
        color: #4a90e2;
        background: #f8f9fa;
    }

    .nav-link i {
        margin-right: 0.5rem;
    }

    .user-menu .nav-link {
        display: flex;
        align-items: center;
    }

    .user-avatar {
        width: 35px;
        height: 35px;
        background: #4a90e2;
        border-radius: 60px;
        display: flex
    ;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        flex-shrink: 0;
        overflow: hidden;
        margin-right: 0.75rem;
    }

    .user-avatar img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-name {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .navbar-toggler {
        border: none;
        padding: 0.5rem;
        border-radius: 8px;
    }

    .navbar-toggler:focus {
        box-shadow: none;
        background: #f8f9fa;
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 0.5rem;
        min-width: 200px;
    }

    .dropdown-item {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #4a5568;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        color: #4a90e2;
    }

    .dropdown-item.danger {
        color: #dc3545;
    }

    .dropdown-item.danger:hover {
        background: #fff5f5;
        color: #dc3545;
    }

    .dropdown-divider {
        margin: 0.5rem 0;
        border-color: #f0f0f0;
    }

    .create-btn {
        background: #4a90e2;
        color: white;
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        border: 2px solid transparent;
    }

    .create-btn:hover {
        background: #357abd;
        color: white;
        transform: translateY(-1px);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .create-btn {
            margin-top: 0.5rem;
            width: 100%;
            justify-content: center;
        }

        .nav-link {
            padding: 0.75rem 1rem;
        }

        .user-menu {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #f0f0f0;
        }
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .navbar {
            background: rgba(26, 32, 44, 0.95);
        }

        .navbar-brand {
            color: white;
        }

        .nav-link {
            color: #a0aec0;
        }

        .nav-link:hover, .nav-link.active {
            color: #4a90e2;
            background: rgba(74, 144, 226, 0.1);
        }

        .dropdown-menu {
            background: #2d3748;
            border-color: #4a5568;
        }

        .dropdown-item {
            color: #a0aec0;
        }

        .dropdown-item:hover {
            background: rgba(74, 144, 226, 0.1);
            color: #4a90e2;
        }

        .dropdown-divider {
            border-color: #4a5568;
        }

        .navbar-toggler-icon {
            filter: invert(1);
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: #2d3748;
            }
        }
    }

    body {
        padding-top: 73px;
    }

    /* Update this style block in navbar.php */
    .nav-link {
        color: #4a5568;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        position: relative;
        margin: 0 0.25rem;
    }

    /* Make this more specific to override Bootstrap */
    .navbar .nav-link.active,
    .navbar .nav-link:hover {
        color: #4a90e2;
        background: #f8f9fa;
    }

    /* Also update dark mode styles to be more specific */
    @media (prefers-color-scheme: dark) {
        .navbar .nav-link {
            color: #a0aec0;
        }

        .navbar .nav-link.active,
        .navbar .nav-link:hover {
            color: #4a90e2;
            background: rgba(74, 144, 226, 0.1);
        }
    }
</style>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="index.php">
            <span class="brand-logo">
                <i class="fas fa-book"></i>
            </span>
            Daily Journal
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'journals.php' ? 'active' : ''; ?>" 
                           href="journals.php">
                            <i class="fas fa-book-open"></i>
                            My Journals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : ''; ?>" 
                           href="calendar.php">
                            <i class="fas fa-calendar-alt"></i>
                            Calendar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Create Button -->
                <a href="journal_create.php" class="create-btn me-3">
                    <i class="fas fa-plus"></i>
                    New Entry
                </a>

                <!-- User Menu -->
                <div class="nav-item dropdown user-menu">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <span class="user-avatar">
                            <?php if ($user['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile photo" class="profile-image">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-circle"></i>
                                Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="nav-item">
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                </div>
                <div class="nav-item">
                    <a href="register.php" class="create-btn">
                        Register
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>