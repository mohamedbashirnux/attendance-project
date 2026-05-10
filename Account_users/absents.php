<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Include the database connection
include "../connection/connect.php";

// Get parameters from URL
$class_id = $_GET['class_id'] ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id_param = $_GET['faculty_id'] ?? $faculty_id;
$search_term = $_GET['search_student_id'] ?? '';

// Validate required parameters
if (empty($class_id) || empty($department_id)) {
    header("Location: selection_Absents.php");
    exit();
}

// Get class and department information
$class_info = [];
$department_info = [];

try {
    // Get class information
    $class_sql = "SELECT c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name 
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.id 
                  WHERE c.id = ? AND c.faculty_id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id, $faculty_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        header("Location: selection_Absents.php");
        exit();
    }

    // Get absences data for this class with proper percentage calculation
    $attendance_sql = "SELECT 
        s.student_id as student_varchar_id,
        COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) as student_name,
        COALESCE(subj.subject_name, 'Unknown Subject') as subject_name,
        COUNT(*) as absent_count,
        -- Calculate total sessions for this subject and class
        COALESCE((
            SELECT COUNT(*) 
            FROM attendance_sessions ats 
            WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
        ), 0) as total_sessions,
        -- Calculate attendance percentage: ((total_sessions - absent_count) / total_sessions) * 100
        CASE 
            WHEN COALESCE((
                SELECT COUNT(*) 
                FROM attendance_sessions ats 
                WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
            ), 0) > 0 THEN 
                ROUND(((COALESCE((
                    SELECT COUNT(*) 
                    FROM attendance_sessions ats 
                    WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
                ), 0) - COUNT(*)) * 100.0) / COALESCE((
                    SELECT COUNT(*) 
                    FROM attendance_sessions ats 
                    WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
                ), 1), 2)
            ELSE 
                100.00
        END as attendance_percentage
    FROM absences a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN subject_class sc ON a.subject_class_id = sc.id
    LEFT JOIN subjects subj ON sc.subject_id = subj.id
    WHERE a.class_id = ?";
    
    $params = [$class_id];
    
    if (!empty($search_term)) {
        $attendance_sql .= " AND (a.student_id LIKE ? OR s.full_name LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $attendance_sql .= " GROUP BY s.student_id, a.subject_class_id, subj.subject_name, sc.id
                        HAVING absent_count > 0
                        ORDER BY subj.subject_name ASC, COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) ASC";
    
    $attendance_stmt = $conn->prepare($attendance_sql);
    $attendance_stmt->execute($params);
    $results = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Let's see what we're getting
    // Uncomment the lines below to debug
    /*
    echo "<pre>Debug Info:\n";
    echo "Class ID: " . $class_id . "\n";
    echo "SQL Query: " . $attendance_sql . "\n";
    echo "Parameters: " . print_r($params, true) . "\n";
    echo "Results count: " . count($results) . "\n";
    echo "Results: " . print_r($results, true) . "\n";
    
    // Check if there's any absences data for this class
    $test_sql = "SELECT COUNT(*) as total_records FROM absences WHERE class_id = ?";
    $test_stmt = $conn->prepare($test_sql);
    $test_stmt->execute([$class_id]);
    $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total absences records for class: " . $test_result['total_records'] . "\n";
    
    // Check absent records specifically
    $absent_sql = "SELECT COUNT(*) as absent_records FROM absences WHERE class_id = ?";
    $absent_stmt = $conn->prepare($absent_sql);
    $absent_stmt->execute([$class_id]);
    $absent_result = $absent_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total absent records for class: " . $absent_result['absent_records'] . "\n";
    echo "</pre>";
    
    // Temporary simple query to see absent data without joins
    $simple_sql = "SELECT 
        student_id,
        subject_class_id,
        COUNT(*) as absent_count,
        absent_date
    FROM absences 
    WHERE class_id = ?
    GROUP BY student_id, subject_class_id
    ORDER BY student_id";
    
    $simple_stmt = $conn->prepare($simple_sql);
    $simple_stmt->execute([$class_id]);
    $simple_results = $simple_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>Simple Query Results:\n";
    print_r($simple_results);
    echo "</pre>";
    */

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
    <title>Class absents</title>
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

<div class="toast-container position-fixed top-0 end-0 p-3">
    <!-- Success Toast -->
    <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Delete data successfull!
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
<!--  -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                            <a href="selection_absents.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Absent Details  </h4>
                        </div>
                        <div class="d-flex card-body bg-white">
                            <div class="d-flex flex-column bg-white p-2 m-2">
                                <div>
                                    <strong>Class Name:</strong> <?php echo htmlspecialchars($class_info['class_name']) . ' (' . htmlspecialchars($class_info['study_mode']) . ')'; ?>
                                </div>
                                <div>
                                    <strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?>
                                </div>
                                <div>
                                    <strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?>
                                </div>
                                <div>
                                    <strong>Department Name:</strong> <?php echo htmlspecialchars($class_info['department_name']); ?>
                                </div>
                                <div>
                                    <strong>Faculty Name:</strong> <?php echo htmlspecialchars($faculty); ?>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-4">
                            
                       
                            <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                
                                  <!-- Add Student Button -->
 <!--<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAbsentModal">
    Add Student
</button> -->

                                    <div class="d-flex align-content-center align-items-lg-center">
                                        <!-- Search -->
                                        <input type="text" class="form-control me-2" id="searchStudentId" placeholder="Search for a student by ID..." style="width: 300px;">
                                        <button type="button" class="btn btn-primary" id="searchButton"><i class='bx bx-search-alt-2'></i></button>
                                    </div>
                                 <div class="class d-flex ">
                                 <button type="button" class="btn btn-danger mx-2 delete_whole">
                                    clear
                                 </button>
                                 <div class="btn-group">
                                        <button
                                            type="button"
                                            class="btn btn-primary dropdown-toggle"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                          <i class='bx bx-download'></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                           <a href="download_absents_pdf.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>" class="dropdown-item">Class Report</a>
                                           <li><a class="dropdown-item" href="re-exam_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>">Exam Report</a></li>
                                           <li><a class="dropdown-item" href="#" id="subjectReportLink">Subject Report</a></li>
                                           <li><a class="dropdown-item" href="#" id="neverAttendedLink">Never Attended Report</a></li>
                                        </ul>
                                        </div>
                                </div>
                                 </div>


                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Subject Name</th>
                                                <th>Absent Sessions</th>
                                                <th>Total Sessions</th>
                                                <th>Attendance %</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                        <?php if (!empty($results)) {
                                                foreach ($results as $student) {
                                                    $attendance_percentage = floatval($student['attendance_percentage']);
                                                    $absent_count = intval($student['absent_count']);
                                                    $total_sessions = intval($student['total_sessions']);
                                                    
                                                    // Badge colors for attendance percentage (higher is better)
                                                    $badge_color = 'danger'; // Default to red
                                                    if ($attendance_percentage >= 75) {
                                                        $badge_color = 'success'; // Green for good attendance
                                                    } elseif ($attendance_percentage >= 50) {
                                                        $badge_color = 'warning'; // Yellow for average attendance
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($student['student_varchar_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
                                                    echo '<td><span class="badge bg-danger">' . $absent_count . '</span></td>';
                                                    echo '<td><span class="badge bg-info">' . $total_sessions . '</span></td>';
                                                    echo '<td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($student['attendance_percentage']) . '%</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">No absence data found.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- Add Student Absent Modal -->
<div class="modal fade" id="addAbsentModal" tabindex="-1" aria-labelledby="addAbsentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAbsentModalLabel">Add Absent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAbsentForm">
                    <div class="mb-3">
                        <label for="studentIdInput" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="studentIdInput" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentName" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="studentName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="subjectSelect" class="form-label">Subject</label>
                        <select class="form-control" id="subjectSelect" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="absentDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="absentDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="statuses" class="form-label">Status</label>
                        <input type="text" class="form-control" id="statuses" required readonly value="absent">
                    </div>
                    <div class="mb-3">
                    <label for="cudurDaar" class="form-label">Cudur Daar</label>
                        <select class="form-control" id="cudurDaar" required>
                        <option value="" disabled selected>Dooro sababta</option>
                            <option value="Cudur daar la'aan">Cudur daar la'aan</option>
                            <option value="Medical">Medical</option>
                            <option value="Family Emergency">Family Emergency</option>
                            <option value="Personal reasons">Personal reasons</option>
                        </select>
                    </div>
                    <input type="hidden" name="faculty" id="faculty" value="<?php echo htmlspecialchars($faculty); ?>">
                    <input type="hidden" name="class_id" id="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                    <input type="hidden" name="faculty_id" id="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                    <button type="submit" class="btn btn-primary">Add Absent</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal subject to generate the report -->

<div class="modal fade" id="subjectReportModal" tabindex="-1" aria-labelledby="subjectReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subjectReportModalLabel">Subject Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select a subject to generate the report:</p>
                <select id="subjectSelectReport" class="form-control">
                    <option value="">Select Subject</option>
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmSubjectReport" class="btn btn-primary">Generate Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Never Attended Report -->
<div class="modal fade" id="neverAttendedModal" tabindex="-1" aria-labelledby="neverAttendedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="neverAttendedModalLabel">Never Attended Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select a subject to see students who never attended:</p>
                <select id="subjectSelectNeverAttended" class="form-control">
                    <option value="">Select Subject</option>
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmNeverAttended" class="btn btn-danger">Generate Report</a>
            </div>
        </div>
    </div>
</div>

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
    
    function goToDetails(studentId, faculty) {
    window.location.href = 'single_student.php?student_id=' + studentId + '&faculty=' + faculty;
}

document.getElementById('searchStudentId').addEventListener('input', function() {
    var searchStudentId = this.value;
    var params = new URLSearchParams(window.location.search);
    params.set('search_student_id', searchStudentId);
    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
    fetchStudentData(searchStudentId);
});

function fetchStudentData(searchStudentId) {
    var xhr = new XMLHttpRequest();
    var params = new URLSearchParams(window.location.search);
    params.set('search_student_id', searchStudentId);
    xhr.open('GET', `${window.location.pathname}?${params.toString()}`, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xhr.responseText, 'text/html');
            var newTableBody = doc.getElementById('studentTableBody').innerHTML;
            document.getElementById('studentTableBody').innerHTML = newTableBody;
            var newPagination = doc.querySelector('.pagination') ? doc.querySelector('.pagination').innerHTML : '';
            document.querySelector('.pagination').innerHTML = newPagination;
        }
    };
    xhr.send();
}

document.getElementById('studentIdInput').addEventListener('change', function () {
    var studentId = this.value;
    var classId = document.getElementById('class_id').value;
    var facultyId = document.getElementById('faculty_id').value;
   
    // Fetch Student Name using new structure
    fetch(`../Database_users/students/search_student.php?student_id=${studentId}&class_id=${classId}&faculty_id=${facultyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.student) {
                document.getElementById('studentName').value = data.student.full_name;
            } else {
                document.getElementById('studentName').value = 'Student not found';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('studentName').value = 'Error loading student';
        });

    // Fetch Subjects for this class
    fetch(`../Database_users/subject_class/get_available_subjects.php?class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            var subjectSelect = document.getElementById('subjectSelect');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            if (data.success && data.subjects) {
                data.subjects.forEach(function (subject) {
                    var option = document.createElement('option');
                    option.value = subject.subject_id;
                    option.textContent = subject.subject_name;
                    subjectSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading subjects:', error);
        });
});

document.getElementById('addAbsentForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent the default form submission

    // Get the form data
    var studentId = document.getElementById('studentIdInput').value;
    var studentName = document.getElementById('studentName').value;
    var subjectId = document.getElementById('subjectSelect').value;
    var absentDate = document.getElementById('absentDate').value;
    var status = document.getElementById('statuses').value;
    var excuse = document.getElementById('cudurDaar').value;
    var classId = document.getElementById('class_id').value;
    var facultyId = document.getElementById('faculty_id').value;

    // Check if studentName is "Student not found"
    if (studentName === "Student not found") {
        alert('Cannot submit data: Student not found.');
        return; // Stop further execution if student not found
    }

    // Prepare the data to be sent to absences table
    var formData = new FormData();
    formData.append('student_id', studentId);
    formData.append('class_id', classId);
    formData.append('subject_class_id', subjectId);
    formData.append('teacher_id', '1'); // You may need to get the actual teacher ID
    formData.append('absent_date', absentDate);
    formData.append('status', 'absent');
    formData.append('excuse', excuse);

    // Send the data using fetch to submit absent
    fetch('../Database_users/absent/submit_absent.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success toast
            var successToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
            successToast.show();
            setTimeout(function() {
                location.reload();
            }, 800);

            document.getElementById('addAbsentForm').reset(); // Clear the form
            // Close the modal if necessary
            var addAbsentModal = new bootstrap.Modal(document.getElementById('addAbsentModal'));
            addAbsentModal.hide();
        } else {
            // Show error toast
            var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
            document.getElementById('errorToast').querySelector('.toast-body').textContent = data.message || 'Error submitting attendance';
            errorToast.show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
        errorToast.show();
    });
});

document.querySelector('.btn-danger.mx-2').addEventListener('click', function() {
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    // Confirm the deletion
    $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete all absent</p>
            <p class="text-danger"><strong>This action cannot be back </strong></p>
            <div class="mt-3">
                <button type="button" class="btn btn-danger me-2" id="confirmDelete">Delete absent</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);

        // Show the confirmation toast
        deleteConfirmToast.show();
        $('#confirmDelete').one('click', function(){
            deleteAbsences();
        })
        // Proceed with the deletion
      
    
});
function deleteAbsences() {
    // Get the values for class_name, study_mode, department_name
    var class_name = "<?php echo urlencode($class_name); ?>";
    var study_mode = "<?php echo urlencode($study_mode); ?>";
    var department_name = "<?php echo urlencode($department_name); ?>";
    var faculty = "<?php echo urlencode($faculty); ?>";

    // Create an XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Configure it: POST-request to the deletion script
    xhr.open('POST', '/attendanceproject1/Database_users/absent/delete_absents.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    // Send the request over the network
    xhr.send(`class_name=${class_name}&study_mode=${study_mode}&department_name=${department_name}&faculty=${faculty}`);

    // Handle the response
    xhr.onload = function() {
        if (xhr.status == 200) {
            $('#addSuccessToast').toast('show');
    // Refresh the page after a short delay
    setTimeout(function() {
        location.reload();
    }, 800);
            // location.reload(); // Reload the page to reflect the changes
        } else {
            alert('An error occurred while trying to delete absences. Please try again.');
        }
    };
}

// single subject report
document.getElementById('subjectReportLink').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent the default action

    // Get current URL parameters
    var classId = <?php echo json_encode($class_id); ?>;
    var departmentId = <?php echo json_encode($department_id); ?>;
    var facultyId = <?php echo json_encode($faculty_id); ?>;

    console.log('Loading subjects for class:', classId, 'department:', departmentId, 'faculty:', facultyId);

    // Show modal immediately with loading state
    var subjectSelect = document.getElementById('subjectSelectReport');
    subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
    subjectSelect.disabled = true;
    
    var subjectReportModal = new bootstrap.Modal(document.getElementById('subjectReportModal'));
    subjectReportModal.show();

    // Load subjects in the background
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../Database_users/subject_class/get_class_subjects.php?class_id=' + classId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log('XHR Status:', xhr.status);
            console.log('XHR Response:', xhr.responseText);
            
            subjectSelect.disabled = false;
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    console.log('Parsed data:', data);
                    
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    
                    if (data.success && data.subjects && data.subjects.length > 0) {
                        data.subjects.forEach(function(subject) {
                            var option = document.createElement('option');
                            option.value = subject.subject_name;
                            option.textContent = subject.subject_name;
                            subjectSelect.appendChild(option);
                        });
                        console.log('Added', data.subjects.length, 'subjects to dropdown');
                    } else {
                        subjectSelect.innerHTML = '<option value="">No subjects found for this class</option>';
                        console.log('No subjects found or API returned error:', data.message || 'Unknown error');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Raw response:', xhr.responseText);
                    subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                }
            } else {
                console.error('HTTP Error:', xhr.status);
                subjectSelect.innerHTML = '<option value="">Error loading subjects (HTTP ' + xhr.status + ')</option>';
            }
        }
    };
    
    xhr.onerror = function() {
        console.error('Network error occurred');
        subjectSelect.disabled = false;
        subjectSelect.innerHTML = '<option value="">Network error - please try again</option>';
    };
    
    xhr.send();

    // Update the modal's confirm button to include the selected subject
    document.getElementById('confirmSubjectReport').onclick = function() {
        var selectedSubject = document.getElementById('subjectSelectReport').value;
        console.log('Selected subject:', selectedSubject);
        
        if (selectedSubject && 
            selectedSubject !== '' && 
            selectedSubject !== 'No subjects found for this class' && 
            selectedSubject !== 'Error loading subjects' &&
            selectedSubject !== 'Loading subjects...' &&
            !selectedSubject.includes('Error loading subjects')) {
            window.location.href = `singlesubject_report.php?class_id=${classId}&department_id=${departmentId}&faculty_id=${facultyId}&subject_name=${encodeURIComponent(selectedSubject)}`;
        } else {
            alert('Please select a valid subject before generating the report.');
        }
    };
});

// Never Attended Report
document.getElementById('neverAttendedLink').addEventListener('click', function(event) {
    event.preventDefault();

    var classId = <?php echo json_encode($class_id); ?>;
    var departmentId = <?php echo json_encode($department_id); ?>;
    var facultyId = <?php echo json_encode($faculty_id); ?>;

    console.log('Loading subjects for never attended report');

    // Show modal immediately with loading state
    var subjectSelect = document.getElementById('subjectSelectNeverAttended');
    subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
    subjectSelect.disabled = true;
    
    var neverAttendedModal = new bootstrap.Modal(document.getElementById('neverAttendedModal'));
    neverAttendedModal.show();

    // Load subjects in the background
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../Database_users/subject_class/get_class_subjects.php?class_id=' + classId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            subjectSelect.disabled = false;
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    
                    if (data.success && data.subjects && data.subjects.length > 0) {
                        data.subjects.forEach(function(subject) {
                            var option = document.createElement('option');
                            option.value = subject.subject_name;
                            option.textContent = subject.subject_name;
                            subjectSelect.appendChild(option);
                        });
                    } else {
                        subjectSelect.innerHTML = '<option value="">No subjects found for this class</option>';
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                }
            } else {
                subjectSelect.innerHTML = '<option value="">Error loading subjects (HTTP ' + xhr.status + ')</option>';
            }
        }
    };
    
    xhr.onerror = function() {
        subjectSelect.disabled = false;
        subjectSelect.innerHTML = '<option value="">Network error - please try again</option>';
    };
    
    xhr.send();

    // Update the modal's confirm button
    document.getElementById('confirmNeverAttended').onclick = function() {
        var selectedSubject = document.getElementById('subjectSelectNeverAttended').value;
        
        if (selectedSubject && 
            selectedSubject !== '' && 
            selectedSubject !== 'No subjects found for this class' && 
            selectedSubject !== 'Error loading subjects' &&
            selectedSubject !== 'Loading subjects...' &&
            !selectedSubject.includes('Error loading subjects')) {
            window.location.href = `never_attended_report.php?class_id=${classId}&department_id=${departmentId}&faculty_id=${facultyId}&subject_name=${encodeURIComponent(selectedSubject)}`;
        } else {
            alert('Please select a valid subject before generating the report.');
        }
    };
});
// single subject report
</script>
</body>
</html>
