<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../connection/connect.php";

// Retrieve data from GET parameters
$departmentName = $_GET['department_name'] ?? '';
$className = $_GET['class_name'] ?? '';
$studyMode = $_GET['study_mode'] ?? '';
$faculty = $_GET['faculty'] ?? '';
$searchInput = $_GET['search_student_id'] ?? '';

// Initialize semester and academic (remove GET dependency)
$semester = '';
$academic = '';

// Validate required parameters
if (empty($departmentName) || empty($className) || empty($studyMode) || empty($faculty)) {
    header("Location: selection_class.php");
    exit();
}

try {
    // Prepare SQL statement to fetch semester and academic
    $sql = "SELECT student_id, student_name, tell, password, department_name, class_name, study_mode, faculty_name, semester, academic, status 
            FROM students 
            WHERE department_name = :department_name 
              AND class_name = :class_name 
              AND study_mode = :study_mode 
              AND faculty_name = :faculty_name";

    // If search input is provided, add partial match conditions
    if (!empty($searchInput)) {
        $sql .= " AND (student_id LIKE :search_input OR student_name LIKE :search_input)";
    }

    // Add ORDER BY clause to sort the result by student_name
    $sql .= " ORDER BY student_name ASC";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':department_name', $departmentName);
    $stmt->bindParam(':class_name', $className);
    $stmt->bindParam(':study_mode', $studyMode);
    $stmt->bindParam(':faculty_name', $faculty);

    // Bind search input with wildcards for partial match
    if (!empty($searchInput)) {
        $searchWildcard = '%' . $searchInput . '%';
        $stmt->bindParam(':search_input', $searchWildcard);
    }

    // Execute the statement
    $stmt->execute();

    // Fetch results
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch semester and academic from the first row
    if (!empty($students)) {
        $semester = $students[0]['semester'];
        $academic = $students[0]['academic'];
    }

    // Close the connection (optional, since PDO closes automatically when script ends)
    $stmt = null;
    $conn = null;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Students In Class</title>
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
        <div id="studentExistsToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Student already exists!
            </div>
        </div>
        <div id="editSuccessToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Student updated successfully!
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
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>
        <div id="errorimporttoaster" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-success text-white">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Success</div>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body"></div>
</div>

        <div id="deleteSuccessToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Student deleted successfully!
            </div>
        </div>
    </div>
    <!-- Toast Notifications 
<div class="toast-container">
    <div id="deleteAllConfirmToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-white">
            <i class="bx bx-bell me-2"></i>
            <div class="me-auto fw-semibold">Confirm Delete</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Are you sure you want to delete all students?
            This action cannot be undone and will permanently delete all data of students.
            <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-danger me-3" id="confirmDeleteAll">Delete All</button>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        </div>
    </div>

    <div id="deleteAllSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="bx bx-bell me-2"></i>
            <div class="me-auto fw-semibold">Success</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            All students deleted successfully!
        </div>
    </div>

    <div id="deleteAllErrorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <i class="bx bx-bell me-2"></i>
            <div class="me-auto fw-semibold">Error</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            An error occurred while trying to delete all students. Please try again.
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
            An error occurred while trying to delete all students. Please try again.
        </div>
    </div>
</div>
-->





    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                            <a href="selection_student.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Selected Class Details </h4>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-column">
                                    <div>
                                        <strong>Class Name:</strong> <?php echo $className .' ('.$studyMode. ')'; ?>
                                    </div>
                                    <div>
                                        <strong>Semester:</strong> <?php echo $semester; ?>
                                    </div>
                                    <div>
                                        <strong>Department Name:</strong> <?php echo $departmentName; ?>
                                    </div>
                                    <div>
                                        <strong>Academic Year:</strong> <?php echo $academic; ?>
                                    </div>
                                    <div>
                                        <strong>Faculty Name:</strong> <?php echo $faculty; ?>
                                    </div>
                                    <div>
                                        <strong>Total Number of Students:</strong> <?php echo count($students); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Student</button>
                                    <div class="d-flex align-content-center align-items-lg-center">
                                        <!-- Search -->
                                        <input type="text" class="form-control me-2" id="searchStudentId" placeholder="Search for by ID or Name..." style="width: 300px;">
                                        <button type="button" class="btn btn-primary" id="searchButton"><i class='bx bx-search-alt-2'></i></button>
                                    </div>
                                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importSubjectModal">Import</button> 
                                       <button id="approveAllBtn" class="btn btn-success">Approve All Students</button>
                                    <button id="pendingAllBtn" class="btn btn-warning">Make All Pending</button>
                                   <!-- <button id="deleteAllBtn" class="btn btn-danger">Delete All Students</button> -->

                                </div>

                                <div class="table-">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Student Number</th>
                                                <th>Password</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                            <!-- Student data will be dynamically inserted here -->
                                            <?php
                                            if (!empty($students)) {
                                                $counter = 1; // Initialize the counter
                                                foreach ($students as $student) {
                                                    echo '<tr>';
                                                    // Use the counter to display the row number
                                                    echo '<td>' . $counter . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['tell']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['password']) . '</td>'; // Password display
                                                    
                                                    // Status column as clickable button
                                                    $status = $student['status'] ?? 'pending';
                                                    $statusClass = $status === 'approved' ? 'btn-success' : 'btn-warning';
                                                    $nextStatus = $status === 'approved' ? 'pending' : 'approved';
                                                    echo '<td><button class="btn btn-sm ' . $statusClass . ' status-toggle-btn" data-id="' . htmlspecialchars($student['student_id']) . '" data-current-status="' . $status . '" data-new-status="' . $nextStatus . '">' . ucfirst($status) . '</button></td>';
                                                    
                                                    echo '<td class="text-end">
                                                    
                                                    <button class="btn btn-sm btn-warning edit-btn" 
                                                        data-id="' . htmlspecialchars($student['student_id']) . '"
                                                        data-name="' . htmlspecialchars($student['student_name']) . '"
                                                        data-department="' . htmlspecialchars($student['department_name']) . '"
                                                        data-class="' . htmlspecialchars($student['class_name']) . '"
                                                        data-faculty="' . htmlspecialchars($student['faculty_name']) . '"
                                                        data-tell="' . htmlspecialchars($student['tell']) . '"
                                                        data-password="' . htmlspecialchars($student['password']) . '">
                                                        Edit
                                                    </button>';
                                                    
                                                
                                                    echo '<button class="btn btn-sm btn-danger delete-btn ms-1" data-id="' . $student['student_id'] . '">Delete</button>
                                                </td>';
                                                    echo '</tr>';
                                                    
                                                    $counter++; // Increment the counter
                                                }
                                            } else {
                                                echo '<tr><td colspan="7" class="text-center">No data for Students available</td></tr>';
                                            }
                                            ?>
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

 
<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentForm" action="add_student.php" method="POST">
    <div class="modal-body">
        <!-- Student ID -->
        <div class="mb-3">
            <label for="studentId" class="form-label">Student ID</label>
            <input type="number" class="form-control" id="studentId" name="studentId" required>
        </div>

        <!-- Student Name -->
        <div class="mb-3">
            <label for="studentName" class="form-label">Student Name</label>
            <input type="text" class="form-control" id="studentName" name="studentName" required>
        </div>

        <!-- Student Number & Password -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="studentnumber" class="form-label">Student Number</label>
                <input type="number" class="form-control" id="studentnumber" name="studentnumber" required>
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="off">
            </div>
        </div>

        <!-- Readonly Fields -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Department Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($departmentName) ?>" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Class Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($className) ?>" readonly>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Study Mode</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($studyMode) ?>" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Semester</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($semester) ?>" readonly>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Academic Year</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($academic) ?>" readonly>
            </div>
        </div>

        <!-- Hidden Inputs (Ensure All Are Sent) -->
        <input type="hidden" name="departmentName" value="<?= htmlspecialchars($departmentName) ?>">
        <input type="hidden" name="className" value="<?= htmlspecialchars($className) ?>">
        <input type="hidden" name="studyMode" value="<?= htmlspecialchars($studyMode) ?>">
        <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
        <input type="hidden" name="academic" value="<?= htmlspecialchars($academic) ?>">
        <input type="hidden" name="faculty" value="<?= htmlspecialchars($faculty) ?>">
        <input type="hidden" name="c_id" value="<?= htmlspecialchars($c_id) ?>"> <!-- ✅ Added missing class_id -->

    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Student</button>
    </div>
</form>

        </div>
    </div>
</div>



<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" role="dialog" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStudentForm" action="../Database_users/students/edit_student.php" method="POST">
                <div class="modal-body">
                    <!-- Student ID -->
                    <div class="mb-3">
                        <label for="editStudentId" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="editStudentId" name="editStudentId" required>
                        <input type="hidden" id="originalStudentId" name="originalStudentId">
                    </div>

                    <!-- Student Name -->
                    <div class="mb-3">
                        <label for="editStudentName" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="editStudentName" name="editStudentName" required>
                    </div>

                    <!-- Row for Student Number and Password -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudentnumber" class="form-label">Telephone</label>
                            <input type="text" class="form-control" id="editStudentnumber" name="editStudentnumber" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="editPassword" name="editPassword" placeholder="Leave blank to keep current password">
                        </div>
                    </div>

                    <!-- Department and Class Name -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editDepartmentSelect" class="form-label">Department Name</label>
                            <select id="editDepartmentSelect" class="form-select" name="editDepartmentName" required>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editClassSelect" class="form-label">Current Class Name: <strong><?php echo $className . ' (' . $studyMode . ')'; ?></strong></label>
                            <select id="editClassSelect" class="form-select" name="editClassName" required>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                    </div>

                    <!-- Study Mode -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editStudyMode" class="form-label">Study Mode</label>
                            <input type="text" class="form-control" id="editStudyMode" name="editStudyMode" readonly>
                        </div>
                    </div>

                  <!-- Hidden fields for Faculty Name -->
                    <input type="hidden" id="editFacultyName" value="<?php echo ($faculty); ?>" name="editFacultyName">
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="importSubjectModal" tabindex="-1" aria-labelledby="importSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class=" d-flex flex-column">
                <h5 class="modal-title" id="importSubjectModalLabel">Import Students </h5>
                <p class="modal-title" id="importSubjectModalLabel">*the excel file must to contain only student id  fisrtly then student name, then student phone </p>
                <p class="modal-title" id="importSubjectModalLabel">* firts row is included  </p>
                </div>
          
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Database_users/students/import_students.php" id="importSubjectForm" method="POST" enctype="multipart/form-data">
                    <div class="input-group mb-3">
                        
                        <input type="file" class="form-control" id="inputGroupFile04" name="file" aria-describedby="inputGroupFileAddon04" aria-label="Upload" required>
                        <input type="hidden" class="form-control" id="departmentName" name="departmentName" value="<?php echo ($departmentName); ?>">
                        <input type="hidden" class="form-control" id="className" name="className" value="<?php echo ($className); ?>">
                        <input type="hidden" class="form-control" id="password" name="password" value="<?php echo ($className); ?>">
                        <input type="hidden" class="form-control" id="studyMode" name="studyMode" value="<?php echo ($studyMode); ?>">
                        <input type="hidden" id="class_id" name="class_id" value="<?php echo ($classId); ?>">
                        <input type="hidden" name="faculty" value="<?php echo ($faculty); ?>">
                        <button class="btn btn-outline-primary" type="submit" id="inputGroupFileAddon04">Upload</button>
                    </div>
                   
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
$(document).ready(function() {
    // Initialize toasts
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
    var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
    var studentExistsToast = new bootstrap.Toast(document.getElementById('studentExistsToast'));
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
    var errorimporttoaster = new bootstrap.Toast(document.getElementById('errorimporttoaster'));

    // Handle form submission for adding students
$('#addStudentForm').on('submit', function(event) {
    event.preventDefault();
    
    // Log form data to verify inputs
    console.log($(this).serializeArray());

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
                fetchStudentList();
                addSuccessToast.show();

                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                console.error("Response message: " + response.message);
                alert(response.message);  // Show backend message in an alert
            }
        },
        error: function(xhr, status, error) {
            $('button[type="submit"]').prop('disabled', false);
            console.error("AJAX Error: " + status + ' - ' + error);
            errorToast.show();
        }
    });
});

// Example function to fetch the student list (you should implement this)
function fetchStudentList() {
    // Implement this function if needed
}

// Fetch updated student list

function loadDepartments(selectedDepartment, selectedClass) {
    $.ajax({
        url: '../Database_users/subject/fetch_departments.php',
        type: 'GET',
        success: function(data) {
            var departmentDropdown = $('#editDepartmentSelect');
            departmentDropdown.html('<option value="" disabled selected>Choose a department</option>' + data);
            
            if (selectedDepartment) {
                departmentDropdown.val(selectedDepartment).change(); // Set selected department
                loadClasses(selectedDepartment, selectedClass); // Load classes for selected department
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error loading departments:', textStatus, errorThrown); // Log error to console
        }
    });
}

function loadClasses(departmentName, selectedClass) {
    $.ajax({
        url: '../Database_users/allocate_update_teaher/fetch_classes.php',
        type: 'GET',
        data: { department_name: departmentName }, // Send selected department name
        success: function(data) {
            var classDropdown = $('#editClassSelect');
            classDropdown.html('<option value="" disabled selected>Choose a class</option>' + data);
            
            console.log('Class options:', classDropdown.html()); // Log available classes
            
            if (selectedClass) {
                if (classDropdown.find('option[value="' + selectedClass + '"]').length) {
                    classDropdown.val(selectedClass).change(); // Set selected class
                } else {
                    console.warn('Selected class not found:', selectedClass); // Log warning if class not found
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error loading classes:', textStatus, errorThrown); // Log error to console
        }
    });
}

function loadStudyMode(className) {
    $.ajax({
        url: '../Database_users/allocate_update_teaher/fetch_study_mode1.php',
        type: 'GET',
        data: { class_id: className }, // Send selected class ID
        success: function(data) {
            $('#editStudyMode').val(data.trim()); // Set study mode value
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error fetching study mode:', textStatus, errorThrown); // Log error to console
        }
    });
}


$('#editDepartmentSelect').change(function() {
    var departmentName = $(this).val();
    loadClasses(departmentName);
});

$('#editClassSelect').change(function() {
    var className = $(this).val();
    loadStudyMode(className);
});

$(document).on('click', '.edit-btn', function() {
    var departmentName = $(this).data('department');
    var className = $(this).data('class');
    var facultyName = $(this).data('faculty');
    var tell = $(this).data('tell');
    var password = $(this).data('password');
    
    $('#editStudentId').val($(this).data('id'));
    $('#editStudentName').val($(this).data('name'));
    $('#originalStudentId').val($(this).data('id'));
    $('#editFacultyName').val($(this).data('faculty'));
    $('#editStudentnumber').val($(this).data('tell'));
    $('#editPassword').val(password); // Add this line to populate the password field

    loadDepartments(departmentName, className);
    
    if (className) {
        loadStudyMode(className);
    }

    $('#editStudentModal').modal('show');
});

$('#editStudentForm').submit(function(event) {
    event.preventDefault();
    var className = $("#editClassSelect option:selected").data('class-name'); // Fetch class name from selected option
        var formData = $(this).serialize() + '&editClassName=' + encodeURIComponent(className);
    // var formData = $(this).serialize();
    
    $.ajax({
        url: $(this).attr('action'),
        type: $(this).attr('method'),
        data: formData,
        success: function(response) {
            // Handle the response from the server
            console.log('Response:', response);
            // Close the modal
            $('#editStudentModal').modal('hide');
            // Optionally, refresh the data or update the UI
            editSuccessToast.show();
            setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                    if (response.message === "Student ID already exists.") {
                        studentExistsToast.show(); // Show student already exists toast
                    } else {
                        errorToast.show(); // Show general error toast
                    }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error submitting form:', textStatus, errorThrown);
        }
    });
    
});

$(document).on('click', '.delete-btn', function() {
    var studentId = $(this).data('id');

    $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete the Student Of ID <strong>"${studentId}"</strong>?</p>
            <p class="text-danger"><strong>This action cannot be undone and will permanently delete all data of  students.</strong></p>
            <div class="mt-3">
                <button type="button" class="btn btn-danger me-2" id="confirmDelete">Delete class</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);

        // Show the confirmation toast
        deleteConfirmToast.show();
$('#confirmDelete').one('click', function() {
    deleteConfirmToast.hide();
    $.ajax({
        url: '../Database_users/students/delete_student.php',
        type: 'POST',
        data: { student_id: studentId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#deleteSuccessToast').show(); // Show success toast
                // Refresh the page or update the UI
                setTimeout(function() {
                    window.location.reload();
                }, 700);
            } else {
                // Handle error response
                $('#errorToast').show(); // Show error toast
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + ' - ' + error);
            $('#errorToast').show(); // Show error toast
        }
    });
}); // Store student ID in confirm button
});

// $('#confirmDelete').click(function() {
   
// });


$('#importSubjectForm').on('submit', function(event) {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: '../Database_users/students/import_students.php', // Ensure this path is correct
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('button[type="submit"]').prop('disabled', true);
        },
        success: function(response) {
            $('button[type="submit"]').prop('disabled', false);
            
            // Parse JSON response
            try {
                var res = JSON.parse(response);
            } catch (e) {
                console.error("Invalid JSON response", response);
                alert("An error occurred. Please try again.");
                return;
            }

            if (res.status === 'success') {
                $('#importSubjectForm')[0].reset();
                $('#importSubjectModal').modal('hide');
                $('#addSuccessToast .toast-body').text(res.message);
                $('#addSuccessToast').toast('show'); // Show success toast
                setTimeout(function() {
                    location.reload(); // Refresh the page after a delay
                }, 700); // Adjust delay as needed
            } else {
                // Show the error message from the server in the toast
                $('#errorimporttoaster .toast-body').text(res.message);
                $('#errorimporttoaster').toast('show');

                // Check if the error message contains "This ID already exists"
                if (res.message.includes("This ID")) {
                    // Refresh the page directly after showing the error
                    setTimeout(function() {
                        location.reload(); // Refresh the page immediately
                    }, 3000); // Adjust delay if needed (3 seconds here)
                }
            }
        },
        error: function(xhr, status, error) {
            $('button[type="submit"]').prop('disabled', false);
            console.error("AJAX Error: " + status + ' - ' + error);
            alert("An error occurred while importing the students. Please try again.");
        }
    });
});







   
});
document.getElementById('searchStudentId').addEventListener('input', function() {
    var searchStudentId = this.value;
    var params = new URLSearchParams(window.location.search);
    params.set('search_student_id', searchStudentId);
    
    // Construct a clean path without extra slashes
    var path = window.location.pathname;
    if (path.startsWith('/')) {
        path = path.substring(1); // Remove leading slash if it exists
    }
    var fullUrl = `${window.location.origin}/${path}?${params.toString()}`;

    // Log the URL to verify it's correct
    console.log(fullUrl);

    // Use replaceState with the cleaned URL
    window.history.replaceState({}, '', fullUrl);
    
    fetchStudentData(searchStudentId);
});

function fetchStudentData(searchStudentId) {
    var xhr = new XMLHttpRequest();
    var params = new URLSearchParams(window.location.search);
    params.set('search_student_id', searchStudentId);

    // Open the GET request with the clean URL
    xhr.open('GET', `${window.location.origin}/${window.location.pathname}?${params.toString()}`, true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xhr.responseText, 'text/html');
            
            // Update table content with the new data from the server
            var newTableBody = doc.getElementById('studentTableBody').innerHTML;
            document.getElementById('studentTableBody').innerHTML = newTableBody;
            
            // Update pagination if it exists in the response
            var newPagination = doc.querySelector('.pagination').innerHTML;
            document.querySelector('.pagination').innerHTML = newPagination;
        }
    };
    xhr.send();
}


</script>

<script>
/*
$(document).ready(function() {
    // Initialize the toasts
    const deleteAllConfirmToast = new bootstrap.Toast(document.getElementById('deleteAllConfirmToast'));
    const deleteAllSuccessToast = new bootstrap.Toast(document.getElementById('deleteAllSuccessToast'));
    const deleteAllErrorToast = new bootstrap.Toast(document.getElementById('deleteAllErrorToast'));

    // Handle the click event for delete all button
    $('#deleteAllBtn').on('click', function() {
        // Show the confirmation toast
        deleteAllConfirmToast.show();
    });

    // Handle the confirmation of deleting all students
    $('#confirmDeleteAll').on('click', function() {
        // Hide the confirmation toast
        deleteAllConfirmToast.hide();

        // Make AJAX call to delete all students
        $.ajax({
            url: './Database_users/students/delete_all_students.php', // Adjust the URL to your delete all script
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success toast
                    deleteAllSuccessToast.show();
                    // Refresh the page or update the UI
                    setTimeout(function() {
                        window.location.reload(); // Refresh the page after a short delay
                    }, 1000);
                } else {
                    // Show error toast if there's a problem
                    deleteAllErrorToast.show();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error);
                deleteAllErrorToast.show(); // Show error toast
            }
        });
    });
});
*/
</script>
<script>
     // Function to update all students status
    function updateAllStudentsStatus(status) {
        $.ajax({
            url: '../Database_users/students/update_all_students_status.php',
            type: 'POST',
            data: {
                department_name: '<?php echo $departmentName; ?>',
                class_name: '<?php echo $className; ?>',
                study_mode: '<?php echo $studyMode; ?>',
                faculty_name: '<?php echo $faculty; ?>',
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update all status buttons on the page
                    $('.status-toggle-btn').each(function() {
                        var button = $(this);
                        if (status === 'approved') {
                            button.removeClass('btn-warning').addClass('btn-success')
                                  .text('Approved')
                                  .data('current-status', 'approved')
                                  .data('new-status', 'pending');
                        } else {
                            button.removeClass('btn-success').addClass('btn-warning')
                                  .text('Pending')
                                  .data('current-status', 'pending')
                                  .data('new-status', 'approved');
                        }
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error);
                alert('An error occurred while updating students status. Please try again.');
            }
        });
    }
</script>
<script>
$(document).ready(function() {
    // Status toggle button handler (clickable status column)
    $(document).on('click', '.status-toggle-btn', function() {
        var studentId = $(this).data('id');
        var newStatus = $(this).data('new-status');
        var button = $(this);
        
        $.ajax({
            url: '../Database_users/students/approved_pending.php',
            type: 'POST',
            data: {
                student_id: studentId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (newStatus === 'approved') {
                        // Student is now approved - Green button
                        button.removeClass('btn-warning').addClass('btn-success')
                              .text('Approved')
                              .data('current-status', 'approved')
                              .data('new-status', 'pending');
                    } else {
                        // Student is now pending - Yellow button
                        button.removeClass('btn-success').addClass('btn-warning')
                              .text('Pending')
                              .data('current-status', 'pending')
                              .data('new-status', 'approved');
                    }
                    
                    // Status updated successfully - no alert needed
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error);
                alert('An error occurred while updating student status. Please try again.');
            }
        });
    });

    // Approve all students button handler
    $('#approveAllBtn').on('click', function() {
        if (confirm('Are you sure you want to approve ALL students in this class?')) {
            updateAllStudentsStatus('approved');
        }
    });

    // Make all pending button handler
    $('#pendingAllBtn').on('click', function() {
        if (confirm('Are you sure you want to make ALL students pending in this class?')) {
            updateAllStudentsStatus('pending');
        }
    });
});
</script>




</body>
</html>
