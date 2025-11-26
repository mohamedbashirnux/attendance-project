<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- menu.php -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo" style="display: flex; justify-content: center; align-items: center;">
        <a href="dashboard.php" class="app-brand-link d-flex flex-column align-items-center">
            <img src="capital.png" alt="University Logo" class="w-px-50 h-auto">
            <span class="app-brand-text demo menu-text fw-bolder ms-2 text-center"></span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <li class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="/attendanceproject1/Account_users/dashboard.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Sections</span>
        </li>
        <li class="menu-item <?php echo in_array($current_page, ['department.php', 'Classes.php', 'Teacerpage.php', 'selection_class.php', 'before_sub_class.php', 'selection_teacher.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                <div data-i18n="Authentications">Academic</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'department.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/department.php" class="menu-link">
                        <div data-i18n="Basic">Department</div>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page == 'Classes.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/Classes.php" class="menu-link">
                        <div data-i18n="Notifications">Classes</div>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page == 'Teacerpage.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/Teacerpage.php" class="menu-link">
                        <div data-i18n="Notifications">Teacher</div>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page == 'selection_class.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/selection_class.php" class="menu-link">
                        <div data-i18n="Notifications">Subjects</div>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page == 'before_sub_class.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/before_sub_class.php" class="menu-link">
                        <div data-i18n="Notifications">Subjects/class</div>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page == 'selection_teacher.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/selection_teacher.php" class="menu-link">
                        <div data-i18n="Notifications">Allocate teachers & students</div>
                    </a>
                </li>
            </ul>
        </li>
        <li class="menu-item <?php echo in_array($current_page, ['selection_student.php', 'userSpage.php', 'admin.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div data-i18n="Account Settings">Students</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'selection_student.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/selection_student.php" class="menu-link">
                        <div data-i18n="Basic">Manage Students</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- Absents -->
        <li class="menu-item <?php echo in_array($current_page, ['selection_Absents.php', 'userSpage.php', 'admin.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='bx bx-bell-minus'></i>
                <div data-i18n="Account Settings py-2">Absents</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'selection_Absents.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/selection_Absents.php" class="menu-link">
                        <div data-i18n="Basic">Manage Absents</div>
                    </a>
                </li>
            </ul>
            <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Security</span>
            
        </li>
        <li class="menu-item <?php echo in_array($current_page, ['department.php', 'Classes.php', 'Teacerpage.php', 'selection_class.php', 'before_sub_class.php', 'selection_teacher.php']) ? 'active' : ''; ?>">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                <div data-i18n="Authentications">Additional data</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item <?php echo $current_page == 'department.php' ? 'active' : ''; ?>">
                    <a href="/attendanceproject1/Account_users/time_table.php" class="menu-link">
                        <div data-i18n="Basic">add table class</div>
                    </a>
                </li>
        </li>
        
    </ul>
</aside>
