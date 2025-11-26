<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

include "../connection/connect.php";
// Get the required parameters from the URL
$class_name = $_GET['class_name'];
$department_name = $_GET['department_name'];
$study_mode = $_GET['study_mode'];
$semester = $_GET['semester'];
$academic = $_GET['academic'];
$faculty = $_GET['faculty'];

// Prepare and execute the SQL query for the exam report
$stmt = $conn->prepare("
SELECT 
    absents.*,
    CONCAT('Absent- ', FORMAT((COUNT(*) / total_days_table.total_days * 10), 1), '%') AS absence_percentage
FROM 
    absents
INNER JOIN (
    SELECT 
        student_name,
        subject_name,
        COUNT(DISTINCT student_name) AS total_days
    FROM
        absents
    WHERE
        class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode
    GROUP BY 
        student_name, subject_name
) AS total_days_table 
ON absents.student_name = total_days_table.student_name AND absents.subject_name = total_days_table.subject_name
GROUP BY 
    absents.subject_name, absents.student_name
HAVING 
    (COUNT(*) / (SELECT total_days FROM (SELECT student_name, subject_name, COUNT(DISTINCT student_name) AS total_days  FROM absents WHERE class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode GROUP BY student_name, subject_name) AS total_days_table_internal WHERE total_days_table_internal.student_name = absents.student_name AND total_days_table_internal.subject_name = absents.subject_name) * 10) >= 30
ORDER BY 
    absents.student_name ASC;
");

$stmt->bindParam(':class_name', $class_name);
$stmt->bindParam(':department_name', $department_name);
$stmt->bindParam(':study_mode', $study_mode);

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unique students
$unique_students = array_unique(array_column($results, 'student_name'));
$total_students = count($unique_students);

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
                        <a href="absents.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                        <h4 class="fw-bold m-0">Absent RE-EXAM REPORT</h4>
                    </div>
                    <div class="d-flex card-body bg-white">
                        <div class="d-flex flex-column">
                            <div>
                                <strong>Class Name:</strong> <?php echo $class_name .' ('.$study_mode. ')'; ?>
                            </div>
                            <div>
                                <strong>Semester:</strong> <?php echo $semester; ?>
                            </div>
                            <div>
                                <strong>Departments Name:</strong> <?php echo $department_name; ?>
                            </div>
                            <div>
                                <strong>Academic Year:</strong> <?php echo $academic; ?>
                            </div>
                            <div>
                                <strong>Faculty Name:</strong> <?php echo $faculty; ?>
                            </div>
                            <div><strong>Total Number of Students:</strong> <?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="btn-group">
                                    <a class="dropdown-item btn btn-primary" href="download_exam_report.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>&faculty=<?php echo urlencode($faculty); ?>"> Exam Report </a>
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
                                            <th>Absence Percentage</th>
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

                                            // Determine badge color based on absence percentage
                                            $percentage_value = floatval(str_replace(['Absent- ', '%'], '', $student['absence_percentage']));
                                            $badge_color = 'success';

                                            if ($percentage_value == 10) {
                                                $badge_color = 'success';
                                            } elseif ($percentage_value == 20) {
                                                $badge_color = 'warning';
                                            } elseif ($percentage_value >= 30) {
                                                $badge_color = 'danger';
                                            }

                                            echo '<td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($student['absence_percentage']) . '</span></td>';
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