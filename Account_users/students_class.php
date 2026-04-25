<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Include database connection
include "../connection/connect.php";

// Get parameters from URL
$department_id = $_GET['department_id'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Validate parameters
if (empty($department_id) || empty($class_id)) {
    header('Location: selection_student.php');
    exit();
}

// Get department and class information
$department_name = '';
$class_info = [];

try {
    // Get department name
    $dept_sql = "SELECT department_name FROM departments WHERE id = ? AND faculty_id = ?";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->execute([$department_id, $faculty_id]);
    $dept_result = $dept_stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept_result) {
        $department_name = $dept_result['department_name'];
    }

    // Get class information
    $class_sql = "SELECT class_name, study_mode, semester, academic_year FROM classes WHERE id = ? AND faculty_id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id, $faculty_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        header('Location: selection_student.php');
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Students Management</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Student added successfully!
            </div>
        </div>

        <div id="editSuccessToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Updated</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Student updated successfully!
            </div>
        </div>

        <div id="deleteSuccessToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Deleted</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Student deleted successfully!
            </div>
        </div>

        <div id="errorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Error</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                An error occurred!
            </div>
        </div>

        <div id="deleteConfirmToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Confirm Delete</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Are you sure you want to delete this student?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <a href="selection_student.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                        <h4 class="fw-bold py-3 mb-4">Students Management</h4>
                        
                        <div class="d-flex flex-column bg-white p-2 m-2">
                            <div><strong>Class Name:</strong> <?php echo htmlspecialchars($class_info['class_name']) . ' (' . htmlspecialchars($class_info['study_mode']) . ')'; ?></div>
                            <div><strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?></div>
                            <div><strong>Department Name:</strong> <?php echo htmlspecialchars($department_name); ?></div>
                            <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?></div>
                            <div><strong>Faculty Name:</strong> <?php echo htmlspecialchars($faculty); ?></div>
                            <div><strong>Total Number of Students:</strong> <span id="totalStudents">0</span></div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Students List</h5>
                                    <div>
                                        <button type="button" class="btn btn-success me-2" id="approveAllBtn">
                                            Approve All
                                        </button>
                                        <button type="button" class="btn btn-warning me-2" id="pendingAllBtn">
                                            Pending All
                                        </button>
                                        <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#importStudentsModal">
                                            Import Students
                                        </button>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                            Add Student
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Search Bar -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="searchStudent" placeholder="Search by Student ID, Name, or Phone...">
                                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Student Number</th>
                                                <th>Password</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentsTableBody">
                                            <!-- Students data will be dynamically inserted here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Students Modal -->
    <div class="modal fade" id="importStudentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>⚠️ IMPORTANT - Save as CSV!</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Step 1:</strong> Download the Excel template</li>
                            <li><strong>Step 2:</strong> Fill in: Student ID, Name, Phone (3 columns)</li>
                            <li><strong>Step 3:</strong> File → Save As → CSV UTF-8 (Comma delimited)</li>
                            <li><strong>Step 4:</strong> Upload the CSV file here (NOT .xlsx!)</li>
                            <li>Password is set automatically to class name</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-info" id="downloadTemplateBtn">
                            Download Excel Template
                        </button>
                    </div>
                    
                    <form id="importStudentsForm" enctype="multipart/form-data">
                        <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".csv" required>
                            <div class="form-text text-danger"><strong>⚠️ ONLY CSV files (.csv) - Save your Excel as CSV first!</strong></div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Import Students
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" placeholder="e.g., ST2024001" required>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Student Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Student Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g., +252612345678" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Student Modal -->
    <div class="modal fade" id="transferStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>ℹ️ Transfer Student</strong>
                        <p class="mb-0">This will move the student to a different class. All attendance records will be kept.</p>
                    </div>
                    <form id="transferStudentForm">
                        <input type="hidden" id="transfer_student_id" name="student_id">
                        <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                        <div class="mb-3">
                            <label class="form-label"><strong>Current Student:</strong></label>
                            <p id="transfer_student_info" class="text-muted"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Faculty:</strong></label>
                            <p class="text-muted"><?php echo htmlspecialchars($faculty); ?></p>
                        </div>
                        <div class="mb-3">
                            <label for="transfer_department" class="form-label">Select Department</label>
                            <select class="form-select" id="transfer_department" name="department_id" required>
                                <option value="">-- Select Department --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="transfer_class" class="form-label">Select New Class</label>
                            <select class="form-select" id="transfer_class" name="new_class_id" required disabled>
                                <option value="">-- Select Class --</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Transfer Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_student_id" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Student Name</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Student Number</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
    $(document).ready(function() {
        var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
        var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
        var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
        var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
        var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
        var deleteStudentId = null;
        
        // Load students on page load
        fetchStudentsList();
        fetchStudentCount();
        
        // Load departments for transfer modal (only for current faculty)
        loadDepartments();

        // Function to fetch student count
        function fetchStudentCount() {
            var classId = '<?php echo $class_id; ?>';
            
            $.ajax({
                url: '../Database_users/students/show_students.php',
                type: 'GET',
                data: { 
                    class_id: classId,
                    action: 'count'
                },
                dataType: 'json',
                success: function(response) {
                    $('#totalStudents').text(response.total || 0);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching student count:', error);
                    $('#totalStudents').text('Error');
                }
            });
        }

        // Function to fetch students list
        function fetchStudentsList(searchValue = '') {
            var classId = '<?php echo $class_id; ?>';
            
            $.ajax({
                url: '../Database_users/students/show_students.php',
                type: 'GET',
                data: { 
                    class_id: classId,
                    search: searchValue
                },
                success: function(response) {
                    $('#studentsTableBody').html(response);
                    // Update count after fetching list
                    fetchStudentCount();
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching students list:', error);
                    $('#studentsTableBody').html('<tr><td colspan="7">Error loading students</td></tr>');
                }
            });
        }

        // Handle search input
        $('#searchStudent').on('input', function() {
            var searchValue = $(this).val().trim();
            fetchStudentsList(searchValue);
        });

        // Handle clear search
        $('#clearSearch').on('click', function() {
            $('#searchStudent').val('');
            fetchStudentsList();
        });

        // Handle download template (inside modal)
        $(document).on('click', '#downloadTemplateBtn', function() {
            window.location.href = 'student_excel_template.php';
        });

        // Handle import students form submission
        $('#importStudentsForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: '../Database_users/students/import_students.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true).html('Importing...');
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false).html('Import Students');
                    
                    if (response.success) {
                        $('#importStudentsForm')[0].reset();
                        $('#importStudentsModal').modal('hide');
                        $('#addSuccessToast .toast-body').text(response.message);
                        addSuccessToast.show();
                        fetchStudentsList();
                    } else {
                        $('#errorToast .toast-body').text(response.message);
                        errorToast.show();
                    }
                },
                error: function() {
                    $('button[type="submit"]').prop('disabled', false).html('Import Students');
                    $('#errorToast .toast-body').text('Error importing students');
                    errorToast.show();
                }
            });
        });

        // Handle add student form submission
        $('#addStudentForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '../Database_users/students/add_student.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        $('#addStudentForm')[0].reset();
                        $('#addStudentModal').modal('hide');
                        $('#addSuccessToast .toast-body').text('Student added successfully!');
                        addSuccessToast.show();
                        fetchStudentsList();
                    } else {
                        $('#errorToast .toast-body').text(response.message);
                        errorToast.show();
                    }
                },
                error: function() {
                    $('button[type="submit"]').prop('disabled', false);
                    $('#errorToast .toast-body').text('Error adding student');
                    errorToast.show();
                }
            });
        });

        // Handle status toggle (click on status badge)
        $(document).on('click', '.status-btn', function() {
            var studentId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var newStatus = currentStatus === 'approved' ? 'pending' : 'approved';
            var studentName = $(this).data('student-name');
            
            $.ajax({
                url: '../Database_users/students/update_status.php',
                type: 'POST',
                data: { 
                    id: studentId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#editSuccessToast .toast-body').text('Status updated successfully!');
                        editSuccessToast.show();
                        fetchStudentsList();
                    } else {
                        $('#errorToast .toast-body').text(response.message);
                        errorToast.show();
                    }
                },
                error: function() {
                    $('#errorToast .toast-body').text('Error updating status');
                    errorToast.show();
                }
            });
        });

        // Handle approve all students
        $('#approveAllBtn').on('click', function() {
            var classId = '<?php echo $class_id; ?>';
            
            if (confirm('Are you sure you want to approve all students in this class?')) {
                $.ajax({
                    url: '../Database_users/students/bulk_update_status.php',
                    type: 'POST',
                    data: { 
                        class_id: classId,
                        status: 'approved'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#addSuccessToast .toast-body').text('All students approved successfully!');
                            addSuccessToast.show();
                            fetchStudentsList();
                        } else {
                            $('#errorToast .toast-body').text(response.message);
                            errorToast.show();
                        }
                    },
                    error: function() {
                        $('#errorToast .toast-body').text('Error approving all students');
                        errorToast.show();
                    }
                });
            }
        });

        // Handle pending all students
        $('#pendingAllBtn').on('click', function() {
            var classId = '<?php echo $class_id; ?>';
            
            if (confirm('Are you sure you want to set all students to pending in this class?')) {
                $.ajax({
                    url: '../Database_users/students/bulk_update_status.php',
                    type: 'POST',
                    data: { 
                        class_id: classId,
                        status: 'pending'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#editSuccessToast .toast-body').text('All students set to pending successfully!');
                            editSuccessToast.show();
                            fetchStudentsList();
                        } else {
                            $('#errorToast .toast-body').text(response.message);
                            errorToast.show();
                        }
                    },
                    error: function() {
                        $('#errorToast .toast-body').text('Error setting all students to pending');
                        errorToast.show();
                    }
                });
            }
        });

        // Handle edit student button
        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            var studentId = $(this).data('id');
            var studentIdValue = $(this).data('student-id');
            var fullName = $(this).data('full-name');
            var phone = $(this).data('phone');
            var status = $(this).data('status');
            
            $('#edit_id').val(studentId);
            $('#edit_student_id').val(studentIdValue);
            $('#edit_full_name').val(fullName);
            $('#edit_phone').val(phone);
            $('#edit_status').val(status);
            $('#edit_password').val(''); // Clear password field
            
            $('#editStudentModal').modal('show');
        });

        // Handle edit student form submission
        $('#editStudentForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '../Database_users/students/edit_student.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        $('#editStudentModal').modal('hide');
                        $('#editSuccessToast .toast-body').text('Student updated successfully!');
                        editSuccessToast.show();
                        fetchStudentsList();
                    } else {
                        $('#errorToast .toast-body').text(response.message);
                        errorToast.show();
                    }
                },
                error: function() {
                    $('button[type="submit"]').prop('disabled', false);
                    $('#errorToast .toast-body').text('Error updating student');
                    errorToast.show();
                }
            });
        });

        // Handle delete student
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            deleteStudentId = $(this).data('id');
            var studentName = $(this).data('full-name');
            var studentIdValue = $(this).data('student-id');
            
            $('#deleteConfirmToast .toast-body').html(
                'Are you sure you want to delete student <strong>' + studentName + ' (' + studentIdValue + ')</strong>?' +
                '<div class="mt-3 pt-3 border-top d-flex justify-content-end">' +
                '<button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>' +
                '<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>' +
                '</div>'
            );
            deleteConfirmToast.show();
        });

        // Load departments for current faculty
        function loadDepartments() {
            var facultyId = '<?php echo $faculty_id; ?>';
            
            $.ajax({
                url: '../Database_users/students/get_departments.php',
                type: 'GET',
                data: { faculty_id: facultyId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">-- Select Department --</option>';
                        response.departments.forEach(function(dept) {
                            options += '<option value="' + dept.id + '">' + dept.department_name + '</option>';
                        });
                        $('#transfer_department').html(options);
                    }
                },
                error: function() {
                    console.error('Error loading departments');
                }
            });
        }

        // Handle department change in transfer modal
        $('#transfer_department').on('change', function() {
            var departmentId = $(this).val();
            var facultyId = '<?php echo $faculty_id; ?>';
            $('#transfer_class').prop('disabled', true).html('<option value="">-- Select Class --</option>');
            
            if (departmentId) {
                $.ajax({
                    url: '../Database_users/students/get_classes.php',
                    type: 'GET',
                    data: { 
                        faculty_id: facultyId,
                        department_id: departmentId 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">-- Select Class --</option>';
                            response.classes.forEach(function(cls) {
                                options += '<option value="' + cls.id + '">' + cls.class_name + ' (' + cls.study_mode + ') - ' + cls.semester + '</option>';
                            });
                            $('#transfer_class').html(options).prop('disabled', false);
                        }
                    },
                    error: function() {
                        console.error('Error loading classes');
                    }
                });
            }
        });

        // Handle transfer student button
        $(document).on('click', '.transfer-btn', function(e) {
            e.preventDefault();
            var studentId = $(this).data('id');
            var studentIdValue = $(this).data('student-id');
            var fullName = $(this).data('full-name');
            
            $('#transfer_student_id').val(studentId);
            $('#transfer_student_info').html('<strong>' + fullName + '</strong> (ID: ' + studentIdValue + ')');
            
            // Reset dropdowns
            $('#transfer_department').val('');
            $('#transfer_class').prop('disabled', true).html('<option value="">-- Select Class --</option>');
            
            $('#transferStudentModal').modal('show');
        });

        // Handle transfer student form submission
        $('#transferStudentForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '../Database_users/students/transfer_student.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true).html('Transferring...');
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false).html('Transfer Student');
                    
                    if (response.success) {
                        $('#transferStudentModal').modal('hide');
                        $('#addSuccessToast .toast-body').text('Student transferred successfully!');
                        addSuccessToast.show();
                        fetchStudentsList();
                    } else {
                        $('#errorToast .toast-body').text(response.message);
                        errorToast.show();
                    }
                },
                error: function() {
                    $('button[type="submit"]').prop('disabled', false).html('Transfer Student');
                    $('#errorToast .toast-body').text('Error transferring student');
                    errorToast.show();
                }
            });
        });

        // Handle confirm delete
        $(document).on('click', '#confirmDelete', function() {
            if (deleteStudentId) {
                $.ajax({
                    url: '../Database_users/students/delete_student.php',
                    type: 'POST',
                    data: { id: deleteStudentId },
                    dataType: 'json',
                    success: function(response) {
                        deleteConfirmToast.hide();
                        if (response.success) {
                            $('#deleteSuccessToast .toast-body').text('Student deleted successfully!');
                            deleteSuccessToast.show();
                            fetchStudentsList();
                        } else {
                            $('#errorToast .toast-body').text(response.message);
                            errorToast.show();
                        }
                        deleteStudentId = null;
                    },
                    error: function() {
                        deleteConfirmToast.hide();
                        $('#errorToast .toast-body').text('Error deleting student');
                        errorToast.show();
                        deleteStudentId = null;
                    }
                });
            }
        });
    });
    </script>
</body>
</html>