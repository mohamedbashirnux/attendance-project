<?php
// Include the database connection and session management
include "../connection/connect.php";
include "session_faculty.php";

// Initialize variables
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';

try {
    // Fetch student information using the new normalized structure
    $stmt_student = $conn->prepare("
        SELECT s.student_id, s.full_name, s.status, d.department_name, c.class_name, c.id as class_id, c.study_mode, c.semester, c.academic_year
        FROM students s 
        JOIN classes c ON s.class_id = c.id
        JOIN departments d ON c.department_id = d.id
        WHERE s.student_id = :student_id
    ");
    $stmt_student->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $stmt_student->execute();
    $student_info = $stmt_student->fetch(PDO::FETCH_ASSOC);

    // Get attendance statistics from attendance_sessions table
    $attendance_stats = [];
    if ($student_info) {
        // First, get all subjects for this class
        $stats_stmt = $conn->prepare("
            SELECT 
                subj.subject_name,
                subj.id as subject_id,
                COUNT(ats.id) as total_sessions,
                -- Calculate attended sessions based on present_students from attendance_sessions
                SUM(CASE 
                    WHEN ats.present_students > 0 AND EXISTS (
                        SELECT 1 FROM students s WHERE s.student_id = :student_id AND s.class_id = ats.class_id
                    ) THEN 1 
                    ELSE 0 
                END) as attended_sessions_estimate,
                -- Calculate absent sessions from absences table
                COALESCE((
                    SELECT COUNT(*) 
                    FROM absences a 
                    WHERE a.student_id = (SELECT id FROM students WHERE student_id = :student_id) 
                    AND a.subject_class_id = sc.id
                ), 0) as absent_sessions
            FROM subject_class sc
            JOIN subjects subj ON sc.subject_id = subj.id
            LEFT JOIN attendance_sessions ats ON ats.subject_class_id = sc.id AND ats.class_id = :class_id
            WHERE sc.class_id = :class_id
            GROUP BY subj.id, subj.subject_name
            ORDER BY subj.subject_name ASC
        ");
        $stats_stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        $stats_stmt->bindParam(':class_id', $student_info['class_id'], PDO::PARAM_INT);
        $stats_stmt->execute();
        $temp_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process the results to calculate proper attendance
        foreach ($temp_stats as $stat) {
            $total_sessions = (int)$stat['total_sessions'];
            $absent_sessions = (int)$stat['absent_sessions'];
            $attended_sessions = max(0, $total_sessions - $absent_sessions);
            
            // Calculate attendance percentage
            if ($total_sessions > 0) {
                // If there are sessions, calculate based on attended vs total
                $attendance_percentage = round(($attended_sessions / $total_sessions) * 100, 2);
            } else {
                // If no sessions yet, show 100% (perfect attendance)
                $attendance_percentage = 100.00;
            }
            
            $attendance_stats[] = [
                'subject_name' => $stat['subject_name'],
                'total_sessions' => $total_sessions,
                'attended_sessions' => $attended_sessions,
                'absent_sessions' => $absent_sessions,
                'attendance_percentage' => $attendance_percentage
            ];
        }
    }

    // Prepare the SQL statement for absences using the new structure
    $stmt = $conn->prepare("
        SELECT a.*, s.full_name as student_name, s.student_id as student_varchar_id, 
               subj.subject_name, c.class_name, c.study_mode, d.department_name,
               t.full_name as teacher_name, a.absence_date, a.excuse
        FROM absences a 
        JOIN students s ON a.student_id = s.id
        JOIN classes c ON a.class_id = c.id
        JOIN departments d ON c.department_id = d.id
        JOIN subject_class sc ON a.subject_class_id = sc.id
        JOIN subjects subj ON sc.subject_id = subj.id
        JOIN teachers t ON a.teacher_id = t.id
        WHERE s.student_id = :student_id
        ORDER BY subj.subject_name ASC, a.absence_date DESC
    ");

    // Bind the student_id parameter
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no results found in new structure, try to use old absents table as fallback
    if (empty($results)) {
        try {
            $fallback_stmt = $conn->prepare("
                SELECT student_id as student_varchar_id, student_name, subject_name, class_name, 
                       department_name, study_mode, absent_date as absence_date, 
                       excuses as excuse, 'Unknown' as teacher_name,
                       CONCAT('old_', student_id, '_', absent_date) as id
                FROM absents 
                WHERE student_id = :student_id
                ORDER BY subject_name ASC, absent_date DESC
            ");
            $fallback_stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
            $fallback_stmt->execute();
            $results = $fallback_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Old table doesn't exist, that's fine
            $results = [];
        }
    }

    // Group the results by subject_name
    $grouped_results = [];
    foreach ($results as $student) {
        $grouped_results[$student['subject_name']][] = $student;
    }

    // Optional: Handle case where no results are found
    if (empty($results)) {
        // echo "No records found for the provided student ID.";
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
    <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student_info['full_name']); ?></p>
    <p><strong>Status:</strong> 
        <?php 
        $statusClass = $student_info['status'] === 'approved' ? 'bg-success' : 'bg-warning';
        $statusText = ucfirst($student_info['status']);
        ?>
        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
    </p>
    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($sessionInfo['faculty_name']); ?></p>
    <p><strong>Department:</strong> <?php echo htmlspecialchars($student_info['department_name']); ?></p>
    <p><strong>Semester:</strong> <?php echo htmlspecialchars($student_info['semester']); ?></p>
    <p><strong>Class Name:</strong> <?php echo htmlspecialchars($student_info['class_name']); ?></p>
    <p><strong>Study Mode:</strong> <?php echo htmlspecialchars($student_info['study_mode']); ?></p>
    <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($student_info['academic_year']); ?></p>
<?php else: ?>
    <p>No student information found.</p>
<?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Statistics Card -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Attendance Statistics by Subject</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Subject Name</th>
                                            <th>Total Sessions</th>
                                            <th>Attended</th>
                                            <th>Absent</th>
                                            <th>Attendance %</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attendance_stats)): ?>
                                            <?php foreach ($attendance_stats as $stat): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($stat['subject_name']); ?></td>
                                                    <td><span class="badge bg-info"><?php echo $stat['total_sessions']; ?></span></td>
                                                    <td><span class="badge bg-success"><?php echo $stat['attended_sessions']; ?></span></td>
                                                    <td><span class="badge bg-danger"><?php echo $stat['absent_sessions']; ?></span></td>
                                                    <td>
                                                        <span class="badge <?php echo $stat['attendance_percentage'] >= 75 ? 'bg-success' : ($stat['attendance_percentage'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>">
                                                            <?php echo $stat['attendance_percentage']; ?>%
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($stat['attendance_percentage'] >= 75): ?>
                                                            <span class="badge bg-success">Good</span>
                                                        <?php elseif ($stat['attendance_percentage'] >= 50): ?>
                                                            <span class="badge bg-warning">Average</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Poor</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No subjects found for this student's class.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
            echo '<td>' . htmlspecialchars($student['student_varchar_id']) . '</td>';
            echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['class_name']) . ' (' . htmlspecialchars($student['study_mode']) . ')</td>';
            echo '<td>' . htmlspecialchars($student['absence_date']) . '</td>';
            echo '<td>' . htmlspecialchars($student['excuse']) . '</td>';
            echo '<td class="text-end">
                <!-- Edit Button -->
                <button class="btn btn-sm btn-warning edit-btn" 
                    data-student-id="' . htmlspecialchars($student['student_varchar_id']) . '"
                    data-student-name="' . htmlspecialchars($student['student_name']) . '"
                    data-subject-name="' . htmlspecialchars($student['subject_name']) . '"
                    data-class-name="' . htmlspecialchars($student['class_name']) . '"
                    data-absent-date="' . htmlspecialchars($student['absence_date']) . '"
                    data-excuses="' . htmlspecialchars($student['excuse']) . '"
                    data-department-name="' . htmlspecialchars($student['department_name']) . '"
                    data-study-mode="' . htmlspecialchars($student['study_mode']) . '"
                    data-absence-id="' . htmlspecialchars($student['id']) . '">
                    Edit
                </button>
                
                <!-- Delete Button -->
                <button class="btn btn-sm btn-danger delete-btn" 
                    data-id="' . htmlspecialchars($student['id']) . '"
                    data-student-name="' . htmlspecialchars($student['student_name']) . '"
                    data-subject="' . htmlspecialchars($student['subject_name']) . '"
                    data-class="' . htmlspecialchars($student['class_name']) . '"
                    data-absent-date="' . htmlspecialchars($student['absence_date']) . '">
                    Delete
                </button>
            </td>';
            echo '</tr>';
        }
    }
} else {
    echo '<tr><td colspan="7" class="text-center">No Absences Found.</td></tr>';
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
                            <option value="Family Emergency">Family Emergency</option>
                            <option value="Medical Appointment">Medical Appointment</option>
                            <option value="Personal Reason">Personal Reason</option>
                            <option value="Official Duty">Official Duty</option>
                            <option value="Other">Other</option>
                            <option value="No Excuse">No Excuse</option>
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
                
                // Send delete request using the absence ID
                $.ajax({
                    url: '../Database_users/absent/delete_student.php',
                    type: 'POST',
                    data: {
                        id: studentId // This is now the absence record ID
                    },
                    success: function(response) {
                        // Show success toast
                        $('#addSuccessToast').toast('show');
                        // Refresh the page after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    },
                    error: function() {
                        // Show error toast
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
            const absenceId = this.getAttribute('data-absence-id'); // Get the absence record ID

            // Populate modal fields
            document.getElementById('editStudentId').value = absenceId; // Store absence ID instead
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
        const absenceId = document.getElementById('editStudentId').value; // This is now the absence ID
        const newExcuses = document.getElementById('editExcuses').value;

        // Send AJAX request to update excuses
        $.ajax({
            url: '../Database_users/absent/update_excuses.php',
            type: 'POST',
            data: {
                absence_id: absenceId, // Use absence_id instead of multiple fields
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