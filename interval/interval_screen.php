<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - Attendance Management System</title>
    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/css/pages/page-auth.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <style>
        /* Custom CSS for login page */
        .login-options {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .login-option {
            margin: 0 10px;
            text-align: center;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: opacity 0.3s ease, background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            color: #007BFF;
            border: 1px solid #007BFF;
        }

        .login-option:hover {
            opacity: 0.8;
            background-color: #007BFF; /* Light background color on hover */
            color: #fff; /* Text color on hover */
            border-color: #3395FF; /* Border color on hover */
        }

        .login-icon {
            font-size: 35px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <a href="interval_screen.php" class="app-brand-link">
                                <img src="capital.png" alt="University Logo" style="width: 90px; height: auto; display: block; margin: 0 auto;" />
                            </a>
                            <h3 class="app-brand-text demo text-body fw-bolder" style="margin-top: 10px; color: #007BFF;  text-transform: none;">Attendance Management System</h3>
                        </div>
                        <p class="mb-4 text-center" style="color: #007BFF;">Please select your login option</p>
                        <div class="login-options d-flex justify-content-center">
                            <a href="auth_faculty.php" class="login-option btn mx-3">
                                <i class="bx bx-user login-icon"></i>
                                <div>Login as User</div>
                            </a>
                            <a href="Auth_super_admin.php" class="login-option btn mx-3">
                                <i class="bx bx-shield login-icon"></i>
                                <div>Login as Admin</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>
</html>
