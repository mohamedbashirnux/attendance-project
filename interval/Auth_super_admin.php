<?php
session_start();

// Include the database connection file
include "../connection/connect.php"; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the submitted username and password
    $username = $_POST['email-username'];
    $password = $_POST['password'];

    try {
        // Prepare the SQL statement using PDO for super_admin table
        $stmt = $conn->prepare('SELECT * FROM super_admin WHERE username = :username');
        $stmt->bindParam(':username', $username);

        // Execute the prepared statement
        $stmt->execute();

        // Fetch the result
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists and verify the hashed password
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables for the logged-in super admin
            $_SESSION['super_admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_fullname'] = $user['full_name'];
            $_SESSION['last_activity'] = time(); // Initialize last activity time

            // Redirect to the super admin dashboard
            header('Location: ../super_admin/dashboard.php');
            exit();
        } else {
            // If login fails, set an error message
            $error = "Invalid username or password";
        }

    } catch (PDOException $e) {
        // Handle any errors
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - Admin Dashboard</title>
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
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <a href="" class="app-brand-link">
                                <img src="../assets/imgages/capital.png" alt="University Logo" style="width: 90px; height: auto; display: block; margin: 0 auto;" />
                            </a>
                            <h3 class="app-brand-text demo text-body fw-bolder" style="margin-top: 10px;">Admin Dashboard</h3>
                        </div>
                        <h4 class="mb-2">Welcome Back! 👋</h4>
                        <p class="mb-4">Please sign in to your account</p>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form id="formAuthentication" class="mb-3" action="Auth_super_admin.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Username</label>
                                <input type="text" class="form-control" id="email" name="email-username" placeholder="Enter your email or username" autofocus />
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Password</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password" placeholder="••••••••••••" aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                            </div>
                        </form>
                        <p class="text-center">
                            <span>Log-in as a user?</span>
                            <a href="auth_faculty.php">
                                <span>Sign in here</span>
                            </a>
                        </p>
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