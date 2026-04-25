<?php
session_start();
include "../connection/connect.php";

// Initialize the error message variable
$error_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all required fields are filled
    if (empty($_POST['faculty']) || empty($_POST['username']) || empty($_POST['password'])) {
        $error_message = "Please fill in all fields and choose a faculty.";
    } else {
        // Use PDO prepared statements to prevent SQL injection
        $selectedFaculty = $_POST['faculty'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        try {
            // Query to check if the faculty user exists in faculty_users table for the selected faculty
            $stmt = $conn->prepare("SELECT fu.*, f.faculty_name FROM faculty_users fu 
                                   JOIN faculty f ON fu.faculty_id = f.id 
                                   WHERE fu.username = :username AND f.faculty_name = :faculty_name");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':faculty_name', $selectedFaculty);
            $stmt->execute();

            // Check if any user is found
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify the hashed password
                if (password_verify($password, $user['password'])) {
                    // User exists and password is correct, start session
                    $_SESSION['faculty_user_logged_in'] = true;
                    $_SESSION['faculty_user_id'] = $user['id'];
                    $_SESSION['faculty_id'] = $user['faculty_id'];
                    $_SESSION['faculty_name'] = $user['faculty_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();

                    // Redirect to the faculty dashboard
                    header("Location: ../Account_users/dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid username or password for the selected faculty.";
                }
            } else {
                // Invalid credentials
                $error_message = "Invalid username or password for the selected faculty.";
            }
        } catch (PDOException $e) {
            // Handle query error
            $error_message = "Error in query: " . $e->getMessage();
        }
    }
}

// Fetch faculties from the database
try {
    $stmt = $conn->prepare("SELECT * FROM faculty ORDER BY faculty_name");
    $stmt->execute();
    $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle query error
    die("Error in query: " . $e->getMessage());
}

$conn = null; // Close the connection
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
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
        #faculty {
            cursor: pointer;
        }
        #faculty option {
            cursor: pointer; /* Change cursor for the options */
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
                            <a href="" class="app-brand-link">
                                <img src="capital.png" alt="University Logo" style="width: 90px; height: auto; display: block; margin: 0 auto;" />
                            </a>
                            <h3 class="app-brand-text demo text-body fw-bolder" style="margin-top: 10px; color: #007BFF;">Attendance System</h3>
                        </div>
                        <h4 class="mb-2">Welcome Back! 👋</h4>
                        <p class="mb-4">Please sign in to your account</p>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        <form id="formAuthentication" class="mb-3" action="auth_faculty.php" method="POST">
                            <div class="mb-3" style="cursor: pointer;">
                                <label for="faculty" class="form-label">Select Faculty</label>
                                <select class="form-control" id="faculty" name="faculty" required>
                                    <option value="" disabled selected>Choose a Faculty</option>
                                    <?php foreach ($faculties as $faculty): ?>
                                        <option value="<?php echo htmlspecialchars($faculty['faculty_name']); ?>">
                                            <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required />
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Password</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password" placeholder="••••••••••••" aria-describedby="password" required />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                            </div>
                        </form>

                        <p class="text-center">
                            <span>Log-in as an admin?</span>
                            <a href="Auth_super_admin.php">
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
</body>
</html>
