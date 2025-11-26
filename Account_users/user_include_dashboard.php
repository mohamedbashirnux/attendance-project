<?php
// Start session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is not logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    // Redirect to login page
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Dashboard</title>
    <meta name="description" content="" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700&display=swap"
        rel="stylesheet" />
    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <!-- Page CSS -->
    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <style>
    .faculty-welcome {
        display: flex;
        align-items: center;
        font-size: 1.25rem;
        /* Adjust the size as needed */
        color: #007bff;
        /* Bootstrap primary color */
        font-weight: bold;
        padding-right: 1rem;
    }

    .faculty-welcome i {
        margin-right: 0.5rem;
        /* Space between icon and text */
    }
    </style>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            <aside id="layout-menu"
                class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo"
                    style="display: flex; justify-content: center; align-items: center;">
                    <a href="index.html"
                        class="app-brand-link d-flex flex-column align-items-center">
                        <img src="../assets/imgages/capital.png"
                            alt="University Logo" class="w-px-50 h-auto">
                        <span class="app-brand-text demo menu-text fw-bolder ms-2 text-center"></span>
                    </a>
                    <a href="javascript:void(0);"
                        class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                        <i class="bx bx-chevron-left bx-sm align-middle"></i>
                    </a>
                </div>
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <!-- Dashboard -->
                    <li class="menu-item active">
                        <a href="User_dashboard.php"
                            class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div data-i18n="Analytics">Dashboard</div>
                        </a>
                    </li>
                    <!-- Layouts -->
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Sections</span>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);"
                            class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-book"></i>
                            <div data-i18n="Account Settings">Academic</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="department.php"
                                    class="menu-link">
                                    <div data-i18n="Account">Department</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="Teacerpage.php"
                                    class="menu-link">
                                    <div data-i18n="Notifications">Classes</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="Facultypage.php"
                                    class="menu-link">
                                    <div data-i18n="Account">Subjects</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="Facultypage.php"
                                    class="menu-link">
                                    <div data-i18n="Account">Allocate teachers and subjects</div>
                                </a>
                            </li>
                        </ul>

                    </li>

                    <li class="menu-item">
                        <a href="javascript:void(0);"
                            class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                            <div data-i18n="Authentications">Settings</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="userspage.php"
                                    class="menu-link">
                                    <div data-i18n="Basic">Add User</div>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="admin.php"
                                    class="menu-link">
                                    <div data-i18n="Basic">Add Admin</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </aside>
            <!-- / Menu -->
            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4"
                            href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>
                    <div class="navbar-nav-right d-flex align-items-center"
                        id="navbar-collapse">
                        <ul class="navbar-nav flex-row align-items-center">
                            <!-- Display faculty name -->
                            <li class="nav-item lh-1 me-3">
                                <span class="faculty-welcome">
                                    <i class="bx bx-user"
                                        style="font-size: 1.5rem;"></i> Welcome, <?php echo $faculty; ?>
                                </span>
                            </li>

                        </ul>
                        <!-- User -->
                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow"
                                    href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="../assets/img/avatars/1.png"
                                            alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="../assets/img/avatars/1.png"
                                                            alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?php echo $faculty; ?></span>
                                                    <small class="text-muted">User</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">Settings</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <span
                                                class="d-flex align-items-center align-middle">
                                                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                                                <                                                <span class="flex-grow-1 align-middle">Billing</span>
                                                <span
                                                    class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <li>
                                            <a class="dropdown-item"
                                                href="../interval/interval_screen.php">
                                                <i class="bx bx-power-off me-2"></i>
                                                <span class="align-middle">Log out</span>
                                            </a>
                                        </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- /Navbar -->
                <!-- Content wrapper -->
                <div class="layout-content">
                    <!-- Add your dashboard content here -->

                    <div class="row">
                        <!-- Add your dashboard content here -->
                        <!-- Your content goes here -->

                    </div>
                    <!-- /Content wrapper -->
                </div>
                <!-- /Layout container -->
            </div>
            <!-- /Layout page -->
        </div>
        <!-- /Layout container -->
    </div>
    <!-- /Layout wrapper -->

    <!-- Core Vendors JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>
    <!-- Page JS -->
    <script src="../assets/js/dashboards-analytics.js"></script>
    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>
