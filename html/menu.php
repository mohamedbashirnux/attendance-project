<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- menu.php -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo" style="display: flex; justify-content: center; align-items: center;">
        <a href="Admin_dashboard.php" class="app-brand-link d-flex flex-column align-items-center">
            <img src="capital.png" alt="University Logo" class="w-px-50 h-auto">
            <span class="app-brand-text demo menu-text fw-bolder ms-2 text-center"></span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>
        <!-- Sections Header -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Academic</span>
        </li>

        <!-- Academic Section -->
        <li class="menu-item <?php echo in_array($current_page, ['Facultypage.php', 'Teacerpage.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                <div data-i18n="Academic">Academic</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'Facultypage.php' ? 'active' : ''; ?>">
                    <a href="Facultypage.php" class="menu-link">
                        <div data-i18n="Add Faculty">Add Faculty</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Settings Section -->
        <li class="menu-item <?php echo in_array($current_page, ['selection_student.php', 'userspage.php', 'admin.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Settings">Settings</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'userspage.php' ? 'active' : ''; ?>">
                    <a href="userspage.php" class="menu-link">
                        <div data-i18n="Add user">Add user</div>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page == 'admin.php' ? 'active' : ''; ?>">
                    <a href="admin.php" class="menu-link">
                        <div data-i18n="Add Admin">Add Admin</div>
                    </a>
                </li>
            </ul>
        </li>
        
        <!-- Application Header -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Application</span>
        </li>

        <!-- Notifications Section -->
        <li class="menu-item <?php echo in_array($current_page, ['notification.php', 'another_notification_page.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='bx bxs-bell-plus'></i>
                <div data-i18n="Notifications">Report Absents</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'notification.php' ? 'active' : ''; ?>">
                    <a href="selection_Absents.php" class="menu-link">
                        <div data-i18n="Add Notification">Absents</div>
                    </a>
                </li>
                <!-- Add more sub-menu items if needed -->
            </ul>
        </li>
    </ul>
</aside>
