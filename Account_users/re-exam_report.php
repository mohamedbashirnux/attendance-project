<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

include "../connection/connect.php";

// Get the required parameters from the URL
$class_id = $_GET['class_id'] ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id_param = $_GET['faculty_id'] ?? $faculty_id;

// Validate required parameters
if (empty($class_id)) {
    header("Location: selection_Absents.php");
    exit();
}

try {
    // Get class information
    $class_sql = "SELECT c.class_name, c.study_mode, c.semester, c.academic_year, d.department_name
                  FROM classes c 
                  JOIN departments d ON c.department_id = d.id 
                  WHERE c.id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        header("Location: selection_Absents.php");
        exit();
    }

    // Extract class information
    $class_name = $class_info['class_name'];
    $department_name = $class_info['department_name'];
    $study_mode = $class_info['study_mode'];
    $semester = $class_info['semester'];
    $academic_year = $class_info['academic_year'];
    $faculty_name = $faculty; // Use session faculty name

    // Prepare and execute the SQL query for the exam report
    // Students who missed 3 or more times in one subject
    $stmt = $conn->prepare("
    SELECT 
        s.student_id,
        s.full_name as student_name,
        subj.subject_name,
        COUNT(a.id) AS absence_count
    FROM 
        absences a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN subject_class sc ON a.subject_class_id = sc.id
    INNER JOIN subjects subj ON sc.subject_id = subj.id
    WHERE
        a.class_id = ?
    GROUP BY 
        s.id, s.student_id, s.full_name, subj.subject_name
    HAVING 
        absence_count >= 3
    ORDER BY 
        s.full_name ASC, subj.subject_name ASC
    ");

    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Count unique students
$unique_students = [];
foreach ($results as $row) {
    $unique_students[$row['student_id']] = $row['student_name'];
}
$total_students = count($unique_students);
$total_records = count($results); // Total student-subject combinations

?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Re-exam report</title>
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

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php include 'menu.php'; ?>
        <div class="layout-page">
            <?php include 'navbar.php'; ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="d-flex align-items-center mb-4">
                        <a href="absents.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                        <h4 class="fw-bold m-0">Absent RE-EXAM REPORT</h4>
                    </div>
                    <div class="d-flex card-body bg-white">
                        <div class="d-flex flex-column">
                            <div>
                                <strong>Class Name:</strong> <?php echo htmlspecialchars($class_name) . ' (' . htmlspecialchars($study_mode) . ')'; ?>
                            </div>
                            <div>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?>
                            </div>
                            <div>
                                <strong>Department Name:</strong> <?php echo htmlspecialchars($department_name); ?>
                            </div>
                            <div>
                                <strong>Academic Year:</strong> <?php echo htmlspecialchars($academic_year); ?>
                            </div>
                            <div>
                                <strong>Faculty Name:</strong> <?php echo htmlspecialchars($faculty_name); ?>
                            </div>
                            <div><strong>Total Students Requiring Re-exam:</strong> <?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="btn-group">
                                    <a class="dropdown-item btn btn-primary" href="download_exam_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>"> Exam Report </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Student id</th>
                                            <th>Student Name</th>
                                            <th>Subject Name</th>
                                            <th>Absences</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentTableBody">
                                    <?php 
                                    if (!empty($results)) {
                                        $previous_student = '';
                                        $student_count = 0;

                                        foreach ($results as $student) {
                                            $current_student = $student['student_name'];

                                            echo '<tr>';
                                            
                                            // Show number only for the first occurrence of the student
                                            if ($current_student !== $previous_student) {
                                                $student_count++;
                                                echo '<td>' . $student_count . '</td>';
                                            } else {
                                                echo '<td></td>';
                                            }

                                            // Display student data
                                            echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                                            echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                            echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';

                                            // Determine badge color based on absence count
                                            $absence_count = intval($student['absence_count']);
                                            $badge_color = 'danger'; // Default to red

                                            if ($absence_count >= 5) {
                                                $badge_color = 'danger'; // Red for 5+ absences
                                            } elseif ($absence_count >= 3) {
                                                $badge_color = 'warning'; // Orange for 3-4 absences
                                            }

                                            echo '<td><span class="badge bg-' . $badge_color . '">' . $absence_count . ' times</span></td>';
                                            echo '</tr>';

                                            $previous_student = $current_student;
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No data found.</td></tr>';
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

<script src="../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../assets/vendor/libs/popper/popper.js"></script>
<script src="../assets/vendor/js/bootstrap.js"></script>
<script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../assets/vendor/js/menu.js"></script>
<script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
<script src="../assets/js/main.js"></script>

</body>
</html>