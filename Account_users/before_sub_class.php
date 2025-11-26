<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// If logged in, you can access the faculty variable
$faculty = $_SESSION['faculty']; 
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
                                <form action="sub_class.php" method="GET">
                                    <div class="row">
                                        <input type="hidden" id="id" name="id" />
                                        <input type="hidden" id="studyModeHidden" name="study_mode" />
                                        <input type="hidden" id="classNameHidden" name="class_name" />
                                        <input type="hidden" id="semesterHidden" name="semester" />
                                        <input type="hidden" id="academicHidden" name="academic" />

                                        <div class="col-md-6 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_name" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <!-- Options populated dynamically -->
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" required>
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
    // Populate departments dropdown on page load
    $.ajax({
        url: '../Database_users/subject/fetch_departments.php',
        type: 'GET',
        success: function(data) {
            $('#departmentSelect').html('<option value="" disabled selected>Choose department</option>' + data);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching departments:", error);
        }
    });

    // Handle change in department selection
    $('#departmentSelect').change(function() {
        var departmentName = $(this).val();
        // Fetch classes based on selected department
        $.ajax({
            url: '../Database_users/subject/fetch_classes.php',
            type: 'GET',
            data: { department_name: departmentName },
            success: function(data) {
                $('#classSelect').html('<option value="" disabled selected>Choose a class</option>' + data);
            },
            error: function(xhr, status, error) {
                console.error("Error fetching classes:", error);
            }
        });
    });

    // Handle change in class selection
    $('#classSelect').change(function() {
        var selectedOption = $(this).find(':selected'); // Get the selected option element
        var selectedClassId = selectedOption.val(); // Get the class ID
        var selectedClassName = selectedOption.data('class-name'); // Get the class name
        var selectedDepartment = selectedOption.data('department-name'); // Get the department name
        var selectedSemester = selectedOption.data('semester');
        var selectedacademic = selectedOption.data('academic'); // Get the semester

        $('#id').val(selectedClassId); // Set the hidden input value for class ID
        $('#classNameHidden').val(selectedClassName); // Set the hidden input value for class name
        $('#semesterHidden').val(selectedSemester); 
        $('#academicHidden').val(selectedacademic); // Set the hidden input value for semester

        if (selectedClassId) {
            // Fetch study mode based on selected class ID and name
            $.ajax({
                url: '../Database_users/subject/fetch_study_mode1.php',
                type: 'GET',
                data: {
                    class_name: selectedClassName, // Pass class name
                    id: selectedClassId, // Pass class ID
                    department_name: selectedDepartment
                },
                success: function(data) {
                    $('#studyModeHidden').val(data.study_mode);
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching study mode:", error);
                }
            });
        }
    });
});

    </script>
</body>
</html>
