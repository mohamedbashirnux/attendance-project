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
    <title>Select Teacher</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Icons -->
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
    </style>
</head>
<body>
    <!-- Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Success</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Department added successfully!
            </div>
        </div>
        <div id="warningToast" class="toast bg-warning text-dark" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <strong class="me-auto">Warning</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Subject already allocated to the teacher for the selected criteria.
            </div>
        </div>
        <div id="errorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Error</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                An error occurred. Please try again.
            </div>
        </div>
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
                        <h4 class="fw-bold py-3 mb-4">Select the teacher to give a subject</h4>

                        <!-- Form Section -->
                        <div class="card">
                            <div class="card-body">
                                <form id="allocationForm" method="POST">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="teacherId" class="form-label">Teacher ID</label>
                                            <input type="number" class="form-control" id="teacherId" name="teacher_id" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="teacherName" class="form-label">Teacher Name</label>
                                            <input type="text" class="form-control" id="teacherName" name="teacher_name" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_name" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <!-- Options populated dynamically -->
                                            </select>
                                        </div>

                                  </div>     <!--  -->
                                    <div class="row">
                                       
                                        <div class="col-md-4 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" name="class_name" required>
                                                <option value="" disabled selected>Choose a class</option>
                                                <!-- Options populated dynamically based on selected department -->
                                            </select>
                                        </div>
                                        <input type="text" class="form-control" id="c_id" name="c_id" hidden>
                                        <div class="col-md-4 mb-3">
                                            <label for="subjectSelect" class="form-label">Subject</label>
                                            <select class="form-select" id="subjectSelect" name="subject_name" required>
                                                <option value="" disabled selected>Choose subject</option>
                                                <!-- Options populated dynamically based on selected class -->
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="faculty" class="form-label">Faculty</label>
                                            <input type="text" class="form-control" id="faculty" name="faculty_name" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                        </div>


                                    </div>
                                    <div class="row">
                                       
                                       
                                          <div class="col-md-6 mb-3">
                                             <label for="time" class="form-label">Start Time</label>
                                            <input type="time"  class="form-control" id="start_time" name="start_time"  required />
                                        </div>

                                          <div class="col-md-6 mb-3">
                                             <label for="time" class="form-label">End Time</label>
                                            <input type="time"  class="form-control" id="end_time" name="end_time"  required />
                                        </div>
                                        <input type="hidden" id="studyModeHidden" name="study_mode" />
                                    </div>
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-center align-items-center">
                                            <button type="submit" class="btn btn-primary me-3">Allocate Subject</button>
                                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#viewTeachersModal">See Allocated Classes</button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Modal to View Allocated Teachers and Subjects -->
                               
 
<div class="modal fade" id="viewTeachersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Allocated Teachers and Subjects</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="allocateForm" action="allocate.php" method="GET">
                <!-- Hidden input to store semester value -->
                <input type="hidden" id="semesterHidden" name="semester" />
                <input type="hidden" id="classHidden" name="classHidden" />

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalDepartmentSelect" class="form-label">Department</label>
                        <select class="form-select" id="modalDepartmentSelect" name="department_name" required>
                            <option value="" disabled selected>Choose department</option>
                            <!-- Options populated dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalClassSelect" class="form-label">Class</label>
                        <select class="form-select" id="modalClassSelect" name="class_name" required>
                            <option value="" disabled selected>Choose a class</option>
                            <!-- Options populated dynamically based on selected department -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalStudyMode" class="form-label">Study Mode</label>
                        <input type="text" class="form-control" id="modalStudyMode" name="study_mode" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="modalFaculty" class="form-label">Faculty</label>
                        <input type="text" class="form-control" id="modalFaculty" name="faculty_name" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="goButton">Go</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Container to display the response -->
<div id="responseContainer"></div>


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
            url: '../Database_users/allocate_update_teaher/fetch_classes.php',
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

    // Handle teacher name fetching
    $('#teacherId').blur(function() {
        var teacherId = $(this).val();
        $.ajax({
            url: '../Database_users/subject/fetch_teacher_name.php',
            type: 'GET',
            data: { teacher_id: teacherId },
            success: function(data) {
                var trimmedData = data.trim();
                $('#teacherName').val(trimmedData === 'Unknown teacher' ? 'Unknown teacher' : trimmedData);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching teacher name:', textStatus, errorThrown);
                $('#teacherName').val('Error fetching teacher name');
            }
        });
    });

    // Handle main form submission
    $('#allocationForm').submit(function(event) {
        event.preventDefault();

        var className = $("#classSelect option:selected").data('class-name');
        var classId = $('#classSelect').val();
        var formData = $(this).serialize() + '&class_name=' + encodeURIComponent(className) + '&c_id=' + encodeURIComponent(classId);
        console.log(formData)

        $.ajax({
            url: '../Database_users/teacher/allocate_teacher.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                var toastElement, toastClass, message;
                if (response.status === 'success') {
                    toastElement = 'addSuccessToast';
                    toastClass = 'bg-success';
                    message = response.message;
                    $('#allocationForm')[0].reset();
                    $('#departmentSelect, #classSelect, #subjectSelect').val('');
                    $('#teacherName, #studyModeHidden').val('');
                } else if (response.status === 'warning' || response.message.includes('Subject already allocated')) {
                    toastElement = 'warningToast';
                    toastClass = 'bg-warning';
                    message = response.message || 'Subject already allocated to the teacher for the selected criteria.';
                } else {
                    toastElement = 'errorToast';
                    toastClass = 'bg-danger';
                    message = response.message;
                }
                    var toast = new bootstrap.Toast(document.getElementById(toastElement));
                    $(`#${toastElement}`).removeClass('bg-success bg-warning bg-danger').addClass(`${toastClass} text-white`);
                    $(`#${toastElement} .toast-body`).text(message);
                    toast.show();
                },
                error: function() {
                    var toast = new bootstrap.Toast(document.getElementById('errorToast'));
                    $('#errorToast').removeClass('bg-success bg-warning').addClass('bg-danger text-white');
                    $('#errorToast .toast-body').text('An error occurred. Please try again.');
                    toast.show();
                }
            });
        });

     



        $('#modalDepartmentSelect').change(function() {
            var departmentId = $(this).val();
            if (!departmentId) {
                $('#modalClassSelect').html('<option value="" disabled selected>Choose a class</option>');
                $('#modalStudyMode').val('');
                return;
            }
            $.ajax({
                url: '../Database_users/subject/fetch_classes.php',
                type: 'GET',
                data: { department_name: departmentId },
                success: function(data) {
                    // console.log(data)
                    var className = $("#modalClassSelect").val()
                    console.log(className)
                    $('#modalClassSelect').html('<option value="" disabled selected>Choose a class</option>' + data);
                    $('#modalStudyMode').val('');
                    
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError(jqXHR, textStatus, errorThrown, 'modalClassSelect', 'Error loading classes');
                }
            });
        });

        $('#modalClassSelect').change(function() {
            var classId = $(this).val();
            var semester = $("#modalClassSelect option:selected").data('semester'); // Get semester data from the option
            $('#semesterHidden').val(semester);
            var classHidden = $("#modalClassSelect option:selected").data('class-name'); // Get semester data from the option
            $('#classHidden').val(classHidden);
            console.log(classHidden)

            if (!classId) {
                $('#modalStudyMode').val('');
                return;
            }
            $.ajax({
                url: '../Database_users/allocate_update_teaher/fetch_study_mode1.php',
                type: 'GET',
                data: { class_id: classId },
                success: function(data) {
                    $('#modalStudyMode').val(data.trim());
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error fetching study mode:', textStatus, errorThrown);
                    $('#modalStudyMode').val('');
                }
            });
        });

       


    });
    </script>
</body>
</html>
