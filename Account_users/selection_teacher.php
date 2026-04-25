<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Include database connection
include "../connection/connect.php";

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

// Handle AJAX request for getting classes
if (isset($_POST['action']) && $_POST['action'] === 'get_classes') {
    header('Content-Type: application/json');
    
    $department_id = $_POST['department_id'] ?? '';
    
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

// Handle AJAX request for getting subjects
if (isset($_POST['action']) && $_POST['action'] === 'get_subjects') {
    header('Content-Type: application/json');
    
    $class_id = $_POST['class_id'] ?? '';
    
    try {
        // Get subjects assigned to this class
        $subjects_sql = "SELECT s.id, s.subject_name 
                        FROM subject_class sc 
                        JOIN subjects s ON sc.subject_id = s.id 
                        WHERE sc.class_id = ? AND sc.faculty_id = ? 
                        ORDER BY s.subject_name";
        $subjects_stmt = $conn->prepare($subjects_sql);
        $subjects_stmt->execute([$class_id, $faculty_id]);
        $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request for getting teacher name
if (isset($_POST['action']) && $_POST['action'] === 'get_teacher_name') {
    header('Content-Type: application/json');
    
    $teacher_id = $_POST['teacher_id'] ?? '';
    
    try {
        // Get teacher by teacher_id (the actual teacher identifier like TCH-2024-001)
        $teacher_sql = "SELECT id, full_name FROM teachers WHERE teacher_id = ?";
        $teacher_stmt = $conn->prepare($teacher_sql);
        $teacher_stmt->execute([$teacher_id]);
        $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            echo json_encode([
                'success' => true, 
                'teacher_name' => $teacher['full_name'],
                'auto_id' => $teacher['id']  // Return the auto-increment ID for allocations
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Teacher not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
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
                                <div class="mb-3">
                                    <strong>Faculty:</strong> <?php echo htmlspecialchars($faculty); ?>
                                </div>
                                <form id="allocationForm" method="POST">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="teacherId" class="form-label">Teacher ID</label>
                                            <input type="text" class="form-control" id="teacherId" name="teacher_id_display" required placeholder="Enter teacher ID (e.g., TCH-2024-001)">
                                            <input type="hidden" id="teacherAutoId" name="teacher_id" value="">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="teacherName" class="form-label">Teacher Name</label>
                                            <input type="text" class="form-control" id="teacherName" name="teacher_name" readonly placeholder="Teacher name will appear here">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_id" required>
                                                <option value="" disabled selected>Choose department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" name="class_id" required>
                                                <option value="" disabled selected>Choose a class</option>
                                                <!-- Options populated dynamically based on selected department -->
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="subjectSelect" class="form-label">Subject</label>
                                            <select class="form-select" id="subjectSelect" name="subject_id" required>
                                                <option value="" disabled selected>Choose subject</option>
                                                <!-- Options populated dynamically based on selected class -->
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="faculty" class="form-label">Faculty</label>
                                            <input type="text" class="form-control" id="faculty" name="faculty_name" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                            <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="start_time" class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="start_time" name="start_time" required />
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="end_time" class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="end_time" name="end_time" required />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-center align-items-center">
                                            <button type="submit" class="btn btn-primary me-3">Allocate Subject</button>
                                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#viewAllocationsModal">View Allocations</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                                <!-- Modal to View Allocations -->
                                <div class="modal fade" id="viewAllocationsModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">View Teacher Allocations</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="allocate.php" method="GET">
                                                    <div class="mb-3">
                                                        <label for="modalDepartmentSelect" class="form-label">Department</label>
                                                        <select class="form-select" id="modalDepartmentSelect" name="department_id" required>
                                                            <option value="" disabled selected>Choose department</option>
                                                            <?php foreach ($departments as $dept): ?>
                                                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="modalClassSelect" class="form-label">Class</label>
                                                        <select class="form-select" id="modalClassSelect" name="class_id" required>
                                                            <option value="" disabled selected>Choose a class</option>
                                                            <!-- Options populated dynamically based on selected department -->
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="modalFaculty" class="form-label">Faculty</label>
                                                        <input type="text" class="form-control" id="modalFaculty" name="faculty_name" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                                        <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">View Allocations</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>


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
        // Initialize toasts
        var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
        var warningToast = new bootstrap.Toast(document.getElementById('warningToast'));
        var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));

        // Handle teacher ID input - fetch teacher name
        $('#teacherId').on('blur', function() {
            var teacherId = $(this).val().trim();
            if (teacherId) {
                $.ajax({
                    url: 'selection_teacher.php',
                    type: 'POST',
                    data: { 
                        action: 'get_teacher_name',
                        teacher_id: teacherId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#teacherName').val(response.teacher_name);
                            $('#teacherAutoId').val(response.auto_id); // Store the auto-increment ID for allocation
                        } else {
                            $('#teacherName').val('Teacher not found');
                            $('#teacherAutoId').val('');
                        }
                    },
                    error: function() {
                        $('#teacherName').val('Error fetching teacher name');
                        $('#teacherAutoId').val('');
                    }
                });
            } else {
                $('#teacherName').val('');
                $('#teacherAutoId').val('');
            }
        });

        // Handle department selection - fetch classes
        $('#departmentSelect').on('change', function() {
            var departmentId = $(this).val();
            if (departmentId) {
                $.ajax({
                    url: 'selection_teacher.php',
                    type: 'POST',
                    data: { 
                        action: 'get_classes',
                        department_id: departmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="" disabled selected>Choose a class</option>';
                            response.classes.forEach(function(cls) {
                                options += '<option value="' + cls.id + '">' + cls.class_name + ' - ' + cls.study_mode + ' (' + cls.semester + ', ' + cls.academic_year + ')</option>';
                            });
                            $('#classSelect').html(options);
                        } else {
                            $('#classSelect').html('<option value="" disabled selected>Error loading classes</option>');
                        }
                        $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
                    },
                    error: function() {
                        $('#classSelect').html('<option value="" disabled selected>Error loading classes</option>');
                        $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
                    }
                });
            } else {
                $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');
                $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
            }
        });

        // Handle class selection - fetch subjects
        $('#classSelect').on('change', function() {
            var classId = $(this).val();
            if (classId) {
                $.ajax({
                    url: 'selection_teacher.php',
                    type: 'POST',
                    data: { 
                        action: 'get_subjects',
                        class_id: classId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="" disabled selected>Choose subject</option>';
                            response.subjects.forEach(function(subject) {
                                options += '<option value="' + subject.id + '">' + subject.subject_name + '</option>';
                            });
                            $('#subjectSelect').html(options);
                        } else {
                            $('#subjectSelect').html('<option value="" disabled selected>No subjects found</option>');
                        }
                    },
                    error: function() {
                        $('#subjectSelect').html('<option value="" disabled selected>Error loading subjects</option>');
                    }
                });
            } else {
                $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
            }
        });

        // Handle modal department selection
        $('#modalDepartmentSelect').on('change', function() {
            var departmentId = $(this).val();
            if (departmentId) {
                $.ajax({
                    url: 'selection_teacher.php',
                    type: 'POST',
                    data: { 
                        action: 'get_classes',
                        department_id: departmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="" disabled selected>Choose a class</option>';
                            response.classes.forEach(function(cls) {
                                options += '<option value="' + cls.id + '">' + cls.class_name + ' - ' + cls.study_mode + ' (' + cls.semester + ', ' + cls.academic_year + ')</option>';
                            });
                            $('#modalClassSelect').html(options);
                        } else {
                            $('#modalClassSelect').html('<option value="" disabled selected>Error loading classes</option>');
                        }
                    },
                    error: function() {
                        $('#modalClassSelect').html('<option value="" disabled selected>Error loading classes</option>');
                    }
                });
            } else {
                $('#modalClassSelect').html('<option value="" disabled selected>Choose a class</option>');
            }
        });

        // Handle form submission
        $('#allocationForm').on('submit', function(event) {
            event.preventDefault();
            
            // Validate that teacher is selected
            if (!$('#teacherAutoId').val()) {
                $('#errorToast .toast-body').text('Please enter a valid teacher ID first.');
                errorToast.show();
                return;
            }
            
            $.ajax({
                url: '../Database_users/allocate_teacher/add_allocation_new.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        $('#allocationForm')[0].reset();
                        $('#teacherName').val('');
                        $('#teacherAutoId').val('');
                        $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');
                        $('#subjectSelect').html('<option value="" disabled selected>Choose subject</option>');
                        
                        $('#addSuccessToast .toast-body').text(response.message);
                        addSuccessToast.show();
                    } else {
                        if (response.message.includes('already allocated') || response.message.includes('conflict')) {
                            $('#warningToast .toast-body').text(response.message);
                            warningToast.show();
                        } else {
                            $('#errorToast .toast-body').text(response.message);
                            errorToast.show();
                        }
                    }
                },
                error: function() {
                    $('button[type="submit"]').prop('disabled', false);
                    $('#errorToast .toast-body').text('An error occurred. Please try again.');
                    errorToast.show();
                }
            });
        });
    });
    </script>
</body>
</html>
