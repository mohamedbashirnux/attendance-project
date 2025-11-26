<?php
// Include the database connection
include "../connection/connect.php";

// Initialize variables
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';

try {
    // Fetch student information
    $stmt_student = $conn->prepare("
    SELECT student_id, student_name, department_name, class_name, c_id, study_mode, password, semester, academic, faculty_name, status 
    FROM students 
    WHERE student_id = :student_id
    ");
    $stmt_student->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $stmt_student->execute();
    $student_info = $stmt_student->fetch(PDO::FETCH_ASSOC);

    if (!$student_info) {
        echo "Student not found!";
        exit();
    }

    // Get the actual faculty_name from the student record
    $actual_faculty = $student_info['faculty_name'];

    // Prepare the SQL statement for absents
    $stmt = $conn->prepare("
    SELECT * FROM absents 
    WHERE student_id = :student_id
    ORDER BY subject_name ASC;
    ");

    // Bind the student_id parameter
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group the results by subject_name
    $grouped_results = [];
    foreach ($results as $student) {
        $grouped_results[$student['subject_name']][] = $student;
    }

    // Optional: Handle case where no results are found
    if (empty($results)) {
        // echo "No records found for the provided student ID and faculty.";
    }
} catch (PDOException $e) {
    // Handle any errors
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Student Details</title>
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
        .subject-group {
            background-color: #f7f7f9;
        }
        .subject-header {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .student-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        /* Status badge styling */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .status-approved {
            background-color: #198754;
            color: white;
            border: 1px solid #198754;
        }
        
        .status-default {
            background-color: #6c757d;
            color: white;
            border: 1px solid #6c757d;
        }
    </style>
</head>
<body>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <!-- Success Toast -->
    <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Delete data successfully!
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
            Are you sure you want to delete this department?
            <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        </div>
    </div>
        
    <!-- Error Toast -->
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
<!-- Edit Success Toast -->
<div id="editSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-success text-white">
        <strong class="me-auto">Success</strong>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Updated successfully!
    </div>
</div>


<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php include 'menu.php'; ?>
        <div class="layout-page">
            <?php include 'navbar.php'; ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="d-flex align-items-center mb-4">
                        <a href="dashboard.php" class="btn btn-secondary me-3">Back</a>
                        <h4 class="fw-bold m-0">Student Details</h4>
                    </div>

                    <!-- Student Information Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Student Information</h5>
                            <div class="student-info">
                            <?php if ($student_info): ?>
    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_info['student_id']); ?></p>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($student_info['student_name']); ?></p>

    <!-- Display Class Name and Study Mode on the same line -->
    

    <p><strong>Department:</strong> <?php echo htmlspecialchars($student_info['department_name']); ?></p>
    <p><strong>Class:</strong> <?php echo htmlspecialchars($student_info['class_name']) . ' (' . htmlspecialchars($student_info['study_mode']) . ')'; ?></p>
    <p><strong>Study Mode:</strong> <?php echo htmlspecialchars($student_info['study_mode']); ?></p>
     <p><strong>Semester:</strong> <?php echo htmlspecialchars($student_info['semester']); ?></p>
      <p><strong>Academic:</strong> <?php echo htmlspecialchars($student_info['academic']); ?></p>
<!-- Status display with styling -->
    <p><strong>Status:</strong> 
        <?php 
        $status = $student_info['status'] ?? 'pending';
        $statusClass = '';
        
        if ($status === 'approved') {
            $statusClass = 'status-approved';
        } elseif ($status === 'pending') {
            $statusClass = 'status-pending';
        } else {
            $statusClass = 'status-default';
        }
        ?>
        <span class="status-badge <?php echo $statusClass; ?>">
            <?php echo ucfirst($status); ?>
        </span>
    </p>
    <p><strong>Password:</strong> <?php echo htmlspecialchars($student_info['password']); ?></p>
<?php else: ?>
    <p>No student information found.</p>
<?php endif; ?>



                            </div>
                        </div>
                    </div>

                    <!-- Absent Details Card -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Absent Details</h5>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="btn-group">
                                    <a href="single_student_report.php?student_id=<?php echo urlencode($student_id); ?>" class="btn btn-primary">
                                        Download Report
                                    </a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Subject Name</th>
                                            <th>Class Name</th>
                                            <th>Absent Date</th>
                                            <th>Excuses</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentTableBody">
                                    <?php if (!empty($grouped_results)) {
    foreach ($grouped_results as $subject => $students) {
        echo '<tr class="subject-header text-uppercase"><td colspan="7"><span class="badge bg-primary">' . htmlspecialchars($subject) . '</span></td></tr>';
        
        foreach ($students as $index => $student) {
            $rowClass = ($index % 2 == 0) ? 'subject-group' : '';
            
            echo '<tr class="' . $rowClass . ' text-capitalize">';
            echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
            echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['class_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['absent_date']) . '</td>';
            echo '<td>' . htmlspecialchars($student['excuses']) . '</td>';
            // Action column intentionally left empty (Edit/Delete disabled)
            echo '<td class="text-end"></td>';
            echo '</tr>';
        }
    }
} else {
    echo '<tr><td colspan="7" class="text-center">No Absents Found.</td></tr>';
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
<!-- Edit Excuses Modal -->
<div class="modal fade" id="editExcusesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Excuses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editExcusesForm">
                    <!-- Hidden fields -->
                    <input type="hidden" id="editStudentId" name="student_id">

                    <!-- Display Student ID (read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="editStudentIdDisplay" readonly>
                    </div>
                    
                    <!-- Display fields (read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="editStudentName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="editSubjectName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="editClassName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Absent Date</label>
                        <input type="text" class="form-control" id="editAbsentDate" readonly>
                    </div>
                    
                    <!-- Editable excuses dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Excuses</label>
                        <select class="form-select" id="editExcuses" name="excuses" required>
                            <option value="Bilaa cudur daar">bilaa cudur daar</option>
                            <option value="Medical">Medical</option>
                            <option value="Family Emergency">Family Emergency</option>
                            <option value="Personal reasons">Personal reasons</option>
                            
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveExcusesButton">Save Changes</button>
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
   document.addEventListener('DOMContentLoaded', function() {
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    
    // Attach click event to delete buttons
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            // Get data from button attributes
            const studentId = this.getAttribute('data-id');
            const studentName = this.getAttribute('data-student-name');
            const subjectName = this.getAttribute('data-subject');
            const className = this.getAttribute('data-class');
            const absentDate = this.getAttribute('data-absent-date');

            // Update confirmation toast content
            document.querySelector('#deleteConfirmToast .toast-body').innerHTML = `
                <p>Are you sure you want to delete this absence record for <strong>${studentName}</strong>?</p>
                <p>Date: ${absentDate}</p>
                <p class="text-danger"><strong>This action cannot be undone</strong></p>
                <div class="mt-3">
                    <button type="button" class="btn btn-danger me-2" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            `;

            // Show the confirmation toast
            deleteConfirmToast.show();

            // Handle confirm delete button click
            document.getElementById('confirmDelete').addEventListener('click', function() {
                deleteConfirmToast.hide();
                
                // Check if loading toast exists before showing it
                const loadingToastElement = document.getElementById('loadingToast');
                let loadingToast = null;
                if (loadingToastElement) {
                    loadingToast = new bootstrap.Toast(loadingToastElement);
                    loadingToast.show();
                }
                
                // Log data being sent (for debugging)
                console.log('Sending delete request with data:', {
                    student_id: studentId,
                    subject_name: subjectName,
                    class_name: className,
                    absent_date: absentDate
                });
                
                // Send delete request
                $.ajax({
                    url: '../Database_users/absent/delete_student.php',
                    type: 'POST',
                    data: {
                        student_id: studentId,
                        subject_name: subjectName,
                        class_name: className,
                        absent_date: absentDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Server response:', response);
                        if (loadingToast) {
                            if (loadingToast) {
                            loadingToast.hide();
                        }
                        }
                        
                        if (response.status === 'success') {
                        // Show success toast
                        $('#addSuccessToast').toast('show');
                        // Refresh the page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                        } else {
                            // Show error with message from server
                            document.querySelector('#errorToast .toast-body').textContent = 
                                response.message || 'Failed to delete record';
                            $('#errorToast').toast('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        loadingToast.hide();
                        
                        // Show detailed error
                        document.querySelector('#errorToast .toast-body').textContent = 
                            'Error: ' + error + '. Status: ' + xhr.status + ' ' + xhr.statusText;
                        $('#errorToast').toast('show');
                    }
                });
            }, { once: true });
        });
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            // Get data from button attributes
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            const subjectName = this.getAttribute('data-subject-name');
            const className = this.getAttribute('data-class-name');
            const absentDate = this.getAttribute('data-absent-date');
            const excuses = this.getAttribute('data-excuses');

            // Populate modal fields
            document.getElementById('editStudentId').value = studentId;
            document.getElementById('editStudentIdDisplay').value = studentId;
            document.getElementById('editStudentName').value = studentName;
            document.getElementById('editSubjectName').value = subjectName;
            document.getElementById('editClassName').value = className;
            document.getElementById('editAbsentDate').value = absentDate;
            
            // Set the current excuse as selected in dropdown
            const excusesSelect = document.getElementById('editExcuses');
            excusesSelect.value = excuses;

            // Show the modal
            const editModal = new bootstrap.Modal(document.getElementById('editExcusesModal'));
            editModal.show();
        });
    });

    // Handle save button click
    document.getElementById('saveExcusesButton').addEventListener('click', function() {
        const studentId = document.getElementById('editStudentId').value;
        const subjectName = document.getElementById('editSubjectName').value;
        const className = document.getElementById('editClassName').value;
        const absentDate = document.getElementById('editAbsentDate').value;
        const newExcuses = document.getElementById('editExcuses').value;

        // Send AJAX request to update excuses
        $.ajax({
            url: '../Database_users/absent/update_excuses.php',
            type: 'POST',
            data: {
                student_id: studentId,
                subject_name: subjectName,
                class_name: className,
                absent_date: absentDate,
                excuses: newExcuses
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === 'success') {
                        // Show success toast
                        $('#editSuccessToast').toast('show');
                        // Refresh the page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        // Show error toast
                        $('#errorToast').toast('show');
                    }
                } catch (e) {
                    // Show error toast
                    $('#errorToast').toast('show');
                }
            },
            error: function() {
                // Show error toast
                $('#errorToast').toast('show');
            }
        });

        // Hide the modal
        const editModal = bootstrap.Modal.getInstance(document.getElementById('editExcusesModal'));
        editModal.hide();
    });
});
</script>
</body>
</html>
