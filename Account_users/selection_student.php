<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <!-- Meta tags, title, stylesheets, and scripts -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Student Management</title>
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
                        <h4 class="fw-bold py-3 mb-4">Select the Class Before Going to Students</h4>

                        <!-- Form Section -->
                        <div class="card">
                            <div class="card-body">
                                <form action="students_class.php" method="GET">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_name" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <!-- Options populated dynamically -->
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" required>
                                                <option value="" disabled selected>Choose a class</option>
                                                <!-- Options populated dynamically based on selected department -->
                                            </select>
                                            <input type="hidden" id="classNameHidden" name="class_name" />
                                            <input type="hidden" id="classIdHidden" name="class_id" />
                                            <input type="hidden" id="semesterHidden" name="semester" />
                                            <input type="hidden" id="academicHidden" name="academic" />
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="faculty" class="form-label">Faculty</label>
                                            <input type="text" class="form-control" id="faculty" name="faculty" readonly value="<?php echo $faculty; ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <input type="hidden" id="studyModeHidden" name="study_mode" />
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
            // Function to handle AJAX errors
            function handleAjaxError(jqXHR, textStatus, errorThrown, elementId, defaultMessage) {
                console.error('AJAX Error:', textStatus, errorThrown);
                $(`#${elementId}`).html(`<option value="" disabled selected>${defaultMessage}</option>`);
            }

            // Populate departments dropdown on page load
            $.ajax({
                url: '../Database_users/subject/fetch_departments.php',
                type: 'GET',
                success: function(data) {
                    $('#departmentSelect').html('<option value="" disabled selected>Choose department</option>' + data);
                    $('#modalDepartmentSelect').html('<option value="" disabled selected>Choose department</option>' + data);
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
                    $('#studyModeHidden').val('');
                    $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
                    return; // Exit early if no department selected
                }
                $.ajax({
                    url: '../Database_users/subject/fetch_classes.php',
                    type: 'GET',
                    data: { department_name: departmentId },
                    success: function(data) {
                        $('#classSelect').html('<option value="" disabled selected>Choose a class</option>' + data);
                        $('#studyModeHidden').val('');
                        $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        handleAjaxError(jqXHR, textStatus, errorThrown, 'classSelect', 'Error loading classes');
                    }
                });
            });

            // Handle change in class selection
            $('#classSelect').change(function() {
                var classId = $(this).val();
                var className = $("#classSelect option:selected").data('class-name');
                var departmentName = $('#departmentSelect option:selected').text();
                var semester = $("#classSelect option:selected").data('semester');
                var academic = $("#classSelect option:selected").data('academic'); // Get semester data from the option
        
             
                // Set the hidden input fields with the class name and class ID
                $('#classNameHidden').val(className);
                $('#classIdHidden').val(classId);
                $('#semesterHidden').val(semester);
                $('#academicHidden').val(academic);


                $.ajax({
                    url: '../Database_users/allocate_update_teaher/fetch_study_mode1.php',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(data) {
                        var studyMode = data.trim();
                        $('#studyModeHidden').val(studyMode);

                        // Fetch subjects for selected class
                        $.ajax({
                            url: '../Database_users/allocate_update_teaher/fetch_sub_class.php',
                            type: 'GET',
                            data: {
                                department_name: departmentName,
                                class_name: className,
                                study_mode: studyMode
                            },
                            success: function(data) {
                                $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>' + data);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                handleAjaxError(jqXHR, textStatus, errorThrown, 'subjectSelect', 'Error loading subjects');
                            }
                        });
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error fetching study mode:', textStatus, errorThrown);
                        $('#studyModeHidden').val('');
                    }
                });
            });
        });
    </script>
</body>
</html>
