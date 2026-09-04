<?php
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="dashboard-sidebar">

    <div class="sidebar-brand">
        <button class="sidebar-toggle" type="button" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>
        <div class="logo">
            <img src="img/olfu-logo.png" alt="OLFU Logo">
        </div>
        <div class="sidebar-title">
            <h1><?php echo APP_NAME; ?></h1>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <nav class="sidebar-nav">
        <p class="nav-label">Navigation</p>
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if (
                ($_SESSION['role'] ?? '') !== 'admin' &&
                (hasPermission('view_queue') || hasPermission('manage_queue'))
            ): ?>
                <li>
                    <a href="queue.php" class="<?php echo $currentPage === 'queue.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list-ol"></i>
                        <span>Queue / Transactions</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('view_services') || hasPermission('manage_services')): ?>
                <li>
                    <a href="services.php" class="<?php echo $currentPage === 'services.php' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i>
                        <span><?php echo hasPermission('manage_services') ? 'Service Management' : 'My Services'; ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('view_reports') || hasPermission('view_reports_office') || hasPermission('view_own_metrics')): ?>
                <li>
                    <a href="reports.php" class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>
                            <?php
                            if (hasPermission('view_reports')) {
                                echo 'Reports & Analytics';
                            } elseif (hasPermission('view_reports_office')) {
                                echo 'Office Reports';
                            } else {
                                echo 'My Metrics';
                            }
                            ?>
                        </span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('manage_staff')): ?>
                <li>
                    <a href="staff.php" class="<?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Staff Management</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('view_staff') && !hasPermission('manage_staff')): ?>
                <li>
                    <a href="staff.php" class="<?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Staff List</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (($_SESSION['role_id'] ?? null) === 1 || ($_SESSION['role'] ?? '') === 'admin'): ?>
                <li>
                    <a href="roles.php" class="<?php echo $currentPage === 'roles.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i>
                        <span>Roles & Permissions</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (hasPermission('manage_settings')): ?>
                <li>
                    <a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <li>
                    <a href="archive.php" class="<?php echo $currentPage === 'archive.php' ? 'active' : ''; ?>">
                        <i class="fas fa-archive"></i>
                        <span>Archive</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>
            </li>
        </ul>

        <ul class="sidebar-bottom-nav">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php
                    $initials = '';
                    if (!empty($currentUser['full_name'])) {
                        $names = explode(' ', trim($currentUser['full_name']));
                        $firstInitial = strtoupper(substr($names[0], 0, 1));
                        $lastInitial = '';
                        if (count($names) > 1) {
                            $lastName = $names[count($names) - 1];
                            $lastInitial = strtoupper(substr($lastName, 0, 1));
                        }
                        $initials = $firstInitial . $lastInitial;
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

<script src="scripts/sidebar.js"></script>
