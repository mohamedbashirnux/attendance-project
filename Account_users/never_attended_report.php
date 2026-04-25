<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Get parameters from URL
$class_id = $_GET['class_id'] ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id_param = $_GET['faculty_id'] ?? $faculty_id;
$subject_name = $_GET['subject_name'] ?? '';

// Validate required parameters
if (empty($class_id) || empty($subject_name)) {
    header("Location: selection_Absents.php");
    exit();
}

include "../connection/connect.php";

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

    // Get subject_class_id for this subject and class
    $subjectClassQuery = "SELECT sc.id as subject_class_id
                          FROM subject_class sc
                          JOIN subjects s ON sc.subject_id = s.id
                          WHERE sc.class_id = ? AND s.subject_name = ?";
    $stmt = $conn->prepare($subjectClassQuery);
    $stmt->execute([$class_id, $subject_name]);
    $subjectClassInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subjectClassInfo) {
        throw new Exception('Subject not found for this class');
    }
    
    $subject_class_id = $subjectClassInfo['subject_class_id'];

    // Get total sessions for this subject
    $total_sessions_sql = "SELECT COUNT(*) as total_sessions 
                          FROM attendance_sessions 
                          WHERE class_id = ? AND subject_class_id = ?";
    $total_stmt = $conn->prepare($total_sessions_sql);
    $total_stmt->execute([$class_id, $subject_class_id]);
    $total_sessions_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_sessions = $total_sessions_result['total_sessions'];

    // Build the SQL query to find students who never attended (absence_count = total_sessions)
    $sql = "
    SELECT 
        s.student_id,
        s.full_name as student_name,
        subj.subject_name,
        COUNT(a.id) AS absence_count,
        ? as total_sessions,
        GROUP_CONCAT(a.absence_date ORDER BY a.absence_date ASC SEPARATOR ', ') AS absent_dates
    FROM
        absences a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN subject_class sc ON a.subject_class_id = sc.id
    INNER JOIN subjects subj ON sc.subject_id = subj.id
    WHERE 
        a.class_id = ?
        AND a.subject_class_id = ?
    GROUP BY 
        s.student_id, s.full_name, subj.subject_name
    HAVING 
        COUNT(a.id) = ? AND COUNT(a.id) > 0
    ORDER BY 
        s.full_name ASC
    ";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Execute with parameters
    $stmt->execute([$total_sessions, $class_id, $subject_class_id, $total_sessions]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of students who never attended
    $total_students = count($results);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Never Attended Report</title>
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
        .warning-card {
            background: #ff4444;
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .warning-card .card-title {
            color: white;
        }
    </style>
</head>
<body>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                          <a href="absents.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Never Attended Report</h4>
                        </div>

                        <!-- Warning Card -->
                        <div class="card warning-card">
                            <div class="card-body">
                                <h5 class="card-title mb-2"><i class='bx bx-error-circle'></i> Critical Attendance Alert</h5>
                                <p class="mb-0">This report shows students who have NEVER attended this subject (100% absent)</p>
                            </div>
                        </div>

                        <div class="d-flex flex-column card-body bg-white">
                            <div>
                                <strong>Class Name:</strong> <?php echo htmlspecialchars($class_info['class_name']) . ' (' . htmlspecialchars($class_info['study_mode']) . ')'; ?>
                            </div>
                            <div>
                                <strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?>
                            </div>
                            <div>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?>
                            </div>
                            <div>
                                <strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?>
                            </div>
                            <div>
                                <strong>Department:</strong> <?php echo htmlspecialchars($class_info['department_name']); ?>
                            </div>
                            <div>
                                <strong>Faculty:</strong> <?php echo htmlspecialchars($faculty); ?>
                            </div>
                            <div>
                                <strong>Total Sessions:</strong> <span class="badge bg-info"><?php echo $total_sessions; ?></span>
                            </div>
                            <div>
                                <strong>Students Who Never Attended:</strong> <span class="badge bg-danger"><?php echo htmlspecialchars($total_students); ?></span>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="class d-flex ">
                                        <div class="btn-group">
                                             <a class="dropdown-item btn btn-danger" href="pdf_never_attended_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>&subject_name=<?php echo urlencode($subject_name); ?>">
                                                 <i class='bx bx-download'></i> Download PDF Report
                                             </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Subject Name</th>
                                                <th>Total Sessions</th>
                                                <th>Absences</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                        <?php if (!empty($results)) {
                                                foreach ($results as $index => $student) {
                                                    $absence_count = intval($student['absence_count']);
                                                    $total_sessions_count = intval($student['total_sessions']);

                                                    echo '<tr>';
                                                    echo '<td>' . ($index + 1) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
                                                    echo '<td><span class="badge bg-info">' . $total_sessions_count . '</span></td>';
                                                    echo '<td><span class="badge bg-danger">' . $absence_count . ' times</span></td>';
                                                    echo '<td><span class="badge bg-danger">NEVER ATTENDED</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="7" class="text-center">No students found who never attended. Great news!</td></tr>';
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
