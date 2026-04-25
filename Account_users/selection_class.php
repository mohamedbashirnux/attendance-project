<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <!-- Meta tags, title, stylesheets, and scripts -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Choose departmetn in subject</title>
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
                        <h4 class="fw-bold py-3 mb-4">Select Department to Manage Subjects</h4>

                        <!-- Form Section -->
                        <div class="card">
                            <div class="card-body">
                                <form action="subjects.php" method="GET">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_id" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <!-- Options populated dynamically -->
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="faculty" class="form-label">Faculty</label>
                                            <input type="text" class="form-control" id="faculty" name="faculty" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                            <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary">Go to Subjects</button>
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
        console.log('Page loaded, fetching departments...');
        
        // Load departments for this faculty
        $.ajax({
            url: '../Database_users/subject/get_departments.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                if (response.status === 'success' && response.departments) {
                    var departments = response.departments;
                    console.log('Departments found:', departments);
                    
                    if (departments.length > 0) {
                        var departmentOptions = '<option value="" disabled selected>Choose department</option>';
                        departments.forEach(function(department) {
                            departmentOptions += '<option value="' + department.id + '">' + department.department_name + '</option>';
                        });
                        $('#departmentSelect').html(departmentOptions);
                        console.log('Departments loaded successfully');
                    } else {
                        $('#departmentSelect').html('<option value="" disabled selected>No departments found for your faculty</option>');
                        console.log('No departments found for this faculty');
                    }
                } else {
                    console.error('Error in response:', response);
                    $('#departmentSelect').html('<option value="" disabled selected>Error: ' + (response.message || 'Unknown error') + '</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, '-', error);
                console.error('Response text:', xhr.responseText);
                $('#departmentSelect').html('<option value="" disabled selected>Error loading departments</option>');
            }
        });
    });
    </script>

</body>
</html>
