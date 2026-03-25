<?php
// Sidebar for Admin Dashboard
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="dashboard-sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="logo">
            <img src="img/olfu-logo.png" alt="OLFU Logo">
        </div>
        <div class="sidebar-title">
            <h1><?php echo APP_NAME; ?></h1>
            <p>Admin Dashboard</p>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <p class="nav-label">Navigation</p>
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="staff.php" class="<?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Staff Management</span>
                </a>
            </li>
            <li>
                <a href="services.php" class="<?php echo $currentPage === 'services.php' ? 'active' : ''; ?>">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Service Management</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>

        <!-- User info / Logout -->
        <ul class="sidebar-bottom-nav">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php
                    $initials = '';
                    if (!empty($currentUser['full_name'])) {
                        $names = explode(' ', $currentUser['full_name']);
                        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                    }
                    echo htmlspecialchars($initials);
                    ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="username"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Guest'); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($currentUser['email'] ?? 'noemail@domain.com'); ?></span>
                </div>
            </div>

            <li>
                <a href="logout.php" class="nav-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

</aside>
