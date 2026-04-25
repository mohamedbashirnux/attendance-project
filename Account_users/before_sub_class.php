<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Include database connection
include "../connection/connect.php";

// Handle AJAX request for getting classes
if (isset($_POST['action']) && $_POST['action'] === 'get_classes') {
    header('Content-Type: application/json');
    
    $department_id = $_POST['department_id'] ?? '';
    $faculty_id = $_POST['faculty_id'] ?? '';
    
    try {
        $classes_sql = "SELECT id, class_name, study_mode, semester, academic_year 
                       FROM classes 
                       WHERE department_id = ? AND faculty_id = ? 
                       ORDER BY class_name, study_mode";
        $classes_stmt = $conn->prepare($classes_sql);
        $classes_stmt->execute([$department_id, $faculty_id]);
        $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'classes' => $classes]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch departments for this faculty
$departments = [];
try {
    $dept_sql = "SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->execute([$faculty_id]);
    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching departments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <!-- Meta tags, title, stylesheets, and scripts -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Choose Subject Class</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
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
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .form-control, .form-select {
            cursor: pointer;
        }
        /* Remove dropdown arrows */
        select {
            -moz-appearance: none;
            -webkit-appearance: none;
            appearance: none;
            background: none;
        }
    </style>
</head>
<body>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <!-- Toasts dynamically generated here as per your application -->
    </div>

    <!-- Layout Wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Include Menu -->
            <?php include 'menu.php'; ?>

            <!-- Main Content -->
            <div class="layout-page">
                <!-- Include Navbar -->
                <?php include 'navbar.php'; ?>

                <!-- Content Wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">Select the Class Before Going to Subjects</h4>

                        <!-- Form Section -->
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Faculty:</strong> <?php echo htmlspecialchars($faculty); ?>
                                </div>
                                <form action="sub_class.php" method="GET">
                                    <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_id" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" name="class_id" required>
                                                <option value="" disabled selected>Choose class</option>
                                                <!-- Options populated dynamically -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary">Go to Class Subjects</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- End Form Section -->
                    </div>
                </div>
                <!-- End Content Wrapper -->
            </div>
            <!-- End Main Content -->
        </div>
    </div>
    <!-- End Layout Wrapper -->
    <!-- JavaScript imports -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>
    <!-- Page JS - Implement your dynamic form logic here -->
    <script>
    $(document).ready(function() {
        // Handle change in department selection
        $('#departmentSelect').change(function() {
            var departmentId = $(this).val();
            var facultyId = <?php echo $faculty_id; ?>;
            
            if (departmentId) {
                // Fetch classes based on selected department
                $.ajax({
                    url: 'before_sub_class.php',
                    type: 'POST',
                    data: { 
                        action: 'get_classes',
                        department_id: departmentId,
                        faculty_id: facultyId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="" disabled selected>Choose class</option>';
                            response.classes.forEach(function(cls) {
                                options += '<option value="' + cls.id + '">' + cls.class_name + ' - ' + cls.study_mode + ' (' + cls.semester + ', ' + cls.academic_year + ')</option>';
                            });
                            $('#classSelect').html(options);
                        } else {
                            $('#classSelect').html('<option value="" disabled selected>No classes found</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching classes:", error);
                        $('#classSelect').html('<option value="" disabled selected>Error loading classes</option>');
                    }
                });
            } else {
                $('#classSelect').html('<option value="" disabled selected>Choose class</option>');
            }
        });
    });
    </script>
</body>
</html>
