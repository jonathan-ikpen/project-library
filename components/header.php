<?php
// components/header.php
require_once __DIR__ . '/../config/init.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPLS - Departmental Project Library</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/index.css?v=2">
    <script>
        // Apply theme immediately to prevent FOUC
        const savedTheme = localStorage.getItem('dpls-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body>
    <header class="header">
        <nav class="main-nav">
            <div>
                <a href="<?= BASE_URL ?>index.php" style="display: flex; align-items: center; gap: 12px; font-family: 'Anton', sans-serif; font-size: 24px; text-transform: uppercase; margin-right: 32px;">
                    <img src="<?= BASE_URL ?>assets/logo/pti_logo_bg.png" alt="Logo" style="height: 32px; object-fit: contain;">
                    DPLS
                </a>
            </div>
            <div style="display: flex; align-items: center;">
                <div class="nav-links desktop-only">
                    <a href="<?= BASE_URL ?>archive.php">Archive</a>
                    <a href="<?= BASE_URL ?>contact.php">Contact</a>
                    <?php if (is_logged_in()): ?>
                        <?php if (has_role('admin')): ?>
                            <a href="<?= BASE_URL ?>admin/dashboard.php">Admin Panel</a>
                        <?php elseif (has_role('supervisor')): ?>
                            <a href="<?= BASE_URL ?>supervisor/dashboard.php">Supervisor Portal</a>
                        <?php elseif (has_role('student')): ?>
                            <a href="<?= BASE_URL ?>student/dashboard.php">Student Dashboard</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>auth/profile.php">Profile</a>
                        <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-outline" style="padding: 8px 16px;">Logout</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>auth/login.php">Login</a>
                        <a href="<?= BASE_URL ?>auth/register.php" class="btn" style="padding: 8px 16px;">Register</a>
                    <?php endif; ?>
                </div>
                <button id="theme-toggle" aria-label="Toggle Dark Mode" style="background: transparent; border: none; padding: 8px; cursor: pointer; color: var(--text-primary); display: flex; align-items: center; margin-left: 16px; opacity: 0.8; transition: opacity 0.2s;">
                    <svg id="moon-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    <svg id="sun-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                </button>
                <button id="mobile-menu-toggle" class="mobile-only" aria-label="Open Menu" style="background: transparent; border: none; padding: 8px; cursor: pointer; color: var(--text-primary); display: none; align-items: center; margin-left: 8px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line></svg>
                </button>
            </div>
        </nav>
    </header>
    
    <!-- Mobile Drawer Overlay -->
    <div id="mobile-overlay" class="mobile-overlay"></div>
    <!-- Mobile Drawer -->
    <div id="mobile-drawer" class="mobile-drawer">
        <div style="display: flex; justify-content: flex-end; padding: 24px;">
            <button id="mobile-menu-close" aria-label="Close Menu" style="background: transparent; border: none; cursor: pointer; color: var(--text-primary);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="drawer-content" style="padding: 0 24px 24px 24px; display: flex; flex-direction: column;">
            <?php if (is_logged_in()): ?>
                <div class="eyebrow mb-2" style="color: var(--accent);">WORKSPACE</div>
                <?php
                if (has_role('admin')) {
                    echo '<a href="' . BASE_URL . 'admin/dashboard.php" class="drawer-link">Dashboard</a>';
                    echo '<a href="' . BASE_URL . 'admin/projects.php" class="drawer-link">Projects</a>';
                    echo '<a href="' . BASE_URL . 'admin/categories.php" class="drawer-link">Categories</a>';
                    echo '<a href="' . BASE_URL . 'admin/students.php" class="drawer-link">Students</a>';
                    echo '<a href="' . BASE_URL . 'admin/supervisors.php" class="drawer-link">Supervisors</a>';
                    echo '<a href="' . BASE_URL . 'admin/messages.php" class="drawer-link">Messages</a>';
                } elseif (has_role('supervisor')) {
                    echo '<a href="' . BASE_URL . 'supervisor/dashboard.php" class="drawer-link">Dashboard</a>';
                    echo '<a href="' . BASE_URL . 'supervisor/projects.php" class="drawer-link">Assigned Projects</a>';
                    echo '<a href="' . BASE_URL . 'supervisor/students.php" class="drawer-link">My Students</a>';
                } elseif (has_role('student')) {
                    echo '<a href="' . BASE_URL . 'student/dashboard.php" class="drawer-link">Dashboard</a>';
                    echo '<a href="' . BASE_URL . 'student/projects.php" class="drawer-link">Submitted Projects</a>';
                    echo '<a href="' . BASE_URL . 'student/upload.php" class="drawer-link">Upload Project</a>';
                }
                echo '<a href="' . BASE_URL . 'auth/profile.php" class="drawer-link">Profile</a>';
                ?>
                <div style="height: 1px; background: var(--line); margin: 8px 0;"></div>
            <?php endif; ?>
            
            <a href="<?= BASE_URL ?>archive.php" class="drawer-link">Archive</a>
            <a href="<?= BASE_URL ?>contact.php" class="drawer-link">Contact</a>
            <?php if (!is_logged_in()): ?>
                <a href="<?= BASE_URL ?>auth/login.php" class="drawer-link">Login</a>
                <a href="<?= BASE_URL ?>auth/register.php" class="drawer-link">Register</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>auth/logout.php" class="drawer-link">Logout</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($use_dashboard_layout) && $use_dashboard_layout): ?>
        <div class="dashboard-container" style="display: flex; min-height: calc(100vh - 80px);">
            <aside class="sidebar" style="width: 250px; border-right: 1px solid var(--line); padding: 32px 0;">
                <div style="padding: 0 24px 24px 24px; margin-bottom: 24px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 12px;">
                    <?php
                    $name = is_logged_in() ? $_SESSION['name'] : '';
                    $initials = '';
                    if ($name) {
                        $words = explode(' ', $name);
                        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    }
                    ?>
                    <div style="border: 1px solid var(--line); border-radius: 6px; padding: 4px 8px; font-size: 14px; font-weight: 500; color: var(--text-secondary);">
                        <?= h($initials) ?>
                    </div>
                    <div style="font-weight: 600; font-size: 15px; color: var(--text-primary);"><?= h($name) ?></div>
                </div>
                
                <nav class="sidebar-nav">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    
                    if (!function_exists('nav_link')) {
                        function nav_link($url, $label, $current_page) {
                            $basename = basename($url);
                            $active = ($basename === $current_page) ? 'active' : '';
                            return '<a href="' . BASE_URL . $url . '" class="sidebar-link ' . $active . '">' . h($label) . '</a>';
                        }
                    }

                    if (has_role('admin')) {
                        echo nav_link('admin/dashboard.php', 'Dashboard', $current_page);
                        echo nav_link('admin/projects.php', 'Projects', $current_page);
                        echo nav_link('admin/categories.php', 'Categories', $current_page);
                        
                        // Users Submenu Toggle
                        $users_pages = ['students.php', 'supervisors.php', 'user_edit.php'];
                        $users_open = in_array($current_page, $users_pages);
                        $users_active = $users_open ? 'active' : '';
                        $display_style = $users_open ? 'block' : 'none';
                        $chevron_transform = $users_open ? 'rotate(180deg)' : 'rotate(0deg)';
                        
                        echo '<button onclick="toggleUsersMenu(this)" class="sidebar-link ' . $users_active . '" style="width: 100%; text-align: left; background: transparent; border: none; font: inherit; cursor: pointer; display: flex; justify-content: space-between; align-items: center; padding-right: 24px; box-sizing: border-box;">
                                Users
                                <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.2s; transform: ' . $chevron_transform . ';"><polyline points="6 9 12 15 18 9"></polyline></svg>
                              </button>';
                        
                        echo '<div id="users-submenu" style="display: ' . $display_style . ';">';
                        $student_active = ($current_page === 'students.php') ? 'active' : '';
                        echo '<a href="' . BASE_URL . 'admin/students.php" class="sidebar-link ' . $student_active . '" style="padding-left: 32px;">Students</a>';
                        
                        $supervisor_active = ($current_page === 'supervisors.php') ? 'active' : '';
                        echo '<a href="' . BASE_URL . 'admin/supervisors.php" class="sidebar-link ' . $supervisor_active . '" style="padding-left: 32px;">Supervisors</a>';
                        echo '</div>';
                        
                        echo '<script>
                                function toggleUsersMenu(btn) {
                                    const submenu = document.getElementById("users-submenu");
                                    const chevron = btn.querySelector(".chevron");
                                    if (submenu.style.display === "none" || submenu.style.display === "") {
                                        submenu.style.display = "block";
                                        chevron.style.transform = "rotate(180deg)";
                                    } else {
                                        submenu.style.display = "none";
                                        chevron.style.transform = "rotate(0deg)";
                                    }
                                }
                              </script>';
                        
                        echo nav_link('admin/messages.php', 'Messages', $current_page);
                    } elseif (has_role('supervisor')) {
                        echo nav_link('supervisor/dashboard.php', 'Dashboard', $current_page);
                        echo nav_link('supervisor/projects.php', 'Assigned Projects', $current_page);
                        echo nav_link('supervisor/students.php', 'My Students', $current_page);
                    } elseif (has_role('student')) {
                        echo nav_link('student/dashboard.php', 'Dashboard', $current_page);
                        echo nav_link('student/projects.php', 'Submitted Projects', $current_page);
                        echo nav_link('student/upload.php', 'Upload Project', $current_page);
                    }
                    
                    // Common links for all roles
                    echo nav_link('auth/profile.php', 'Profile', $current_page);
                    ?>
                </nav>
            </aside>
            <main class="dashboard-content" style="flex: 1; padding: 32px; max-width: calc(100% - 250px);">
    <?php else: ?>
        <main class="container" style="flex: 1;">
    <?php endif; ?>
