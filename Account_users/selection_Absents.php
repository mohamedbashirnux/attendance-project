<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

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
    <title>Attendance Management</title>
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
                        <h4 class="fw-bold py-3 mb-4"> <i class='bx bx-bell-minus'></i> Select Class for Attendance</h4>

                        <!-- Form Section -->
                        <div class="card">
                            <div class="card-body">
                                <form action="absents.php" method="GET">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_id" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <!-- Options populated dynamically -->
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" name="class_id" required>
                                                <option value="" disabled selected>Choose a class</option>
                                                <!-- Options populated dynamically based on selected department -->
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="faculty" class="form-label">Faculty</label>
                                            <input type="text" class="form-control" id="faculty" name="faculty" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                            <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary">Go to Attendance Management</button>
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
            // Function to handle AJAX errors
            function handleAjaxError(jqXHR, textStatus, errorThrown, elementId, defaultMessage) {
                console.error('AJAX Error:', textStatus, errorThrown);
                $(`#${elementId}`).html(`<option value="" disabled selected>${defaultMessage}</option>`);
            }

            // Populate departments dropdown on page load
            $.ajax({
                url: '../Database_users/Department/show_departments.php?dropdown=true',
                type: 'GET',
                success: function(response) {
                    try {
                        // Handle both JSON and HTML responses
                        let departments = [];
                        if (typeof response === 'string') {
                            // Try to parse as JSON first
                            try {
                                const jsonResponse = JSON.parse(response);
                                departments = jsonResponse.departments || [];
                            } catch (e) {
                                // If not JSON, treat as HTML options
                                $('#departmentSelect').html('<option value="" disabled selected>Choose department</option>' + response);
                                return;
                            }
                        } else {
                            departments = response.departments || [];
                        }

                        // Build options from JSON data
                        let options = '<option value="" disabled selected>Choose department</option>';
                        departments.forEach(function(dept) {
                            options += `<option value="${dept.id}">${dept.department_name}</option>`;
                        });
                        $('#departmentSelect').html(options);
                    } catch (error) {
                        console.error('Error processing departments:', error);
                        handleAjaxError(null, 'parse', error, 'departmentSelect', 'Error loading departments');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError(jqXHR, textStatus, errorThrown, 'departmentSelect', 'Error loading departments');
                }
            });

            // Handle change in department selection
            $('#departmentSelect').change(function() {
                var departmentId = $(this).val();
                if (!departmentId) {
                    $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');
                    return;
                }

                $.ajax({
                    url: '../Database_users/Classes/show_classes.php?dropdown=true&department_id=' + departmentId,
                    type: 'GET',
                    success: function(response) {
                        try {
                            // Handle both JSON and HTML responses
                            let classes = [];
                            if (typeof response === 'string') {
                                // Try to parse as JSON first
                                try {
                                    const jsonResponse = JSON.parse(response);
                                    classes = jsonResponse.classes || [];
                                } catch (e) {
                                    // If not JSON, treat as HTML options
                                    $('#classSelect').html('<option value="" disabled selected>Choose a class</option>' + response);
                                    return;
                                }
                            } else {
                                classes = response.classes || [];
                            }

                            // Build options from JSON data
                            let options = '<option value="" disabled selected>Choose a class</option>';
                            classes.forEach(function(cls) {
                                options += `<option value="${cls.id}">${cls.class_name} (${cls.study_mode}) - ${cls.semester}</option>`;
                            });
                            $('#classSelect').html(options);
                        } catch (error) {
                            console.error('Error processing classes:', error);
                            handleAjaxError(null, 'parse', error, 'classSelect', 'Error loading classes');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        handleAjaxError(jqXHR, textStatus, errorThrown, 'classSelect', 'Error loading classes');
                    }
                });
            });
        });
    </script>
</body>
</html>
