<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

$class_name = $_GET['class_name'];
$department_name = $_GET['department_name'];
$study_mode = $_GET['study_mode'];
$subject_name = $_GET['subject_name'];
$semester = $_GET['semester'];
$academic = $_GET['academic'];

// Get date range parameters (optional)
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : null;

// Save the variables in the session
$_SESSION['class_name'] = $class_name;
$_SESSION['department_name'] = $department_name;
$_SESSION['study_mode'] = $study_mode;
$_SESSION['subject_name'] = $subject_name;
$_SESSION['semester'] = $semester;

include "../connection/connect.php";

// Build date condition for the query  
$date_condition = "";
$params = [];

// Handle date filtering for format like "Thu-02-20-2025"
if ($start_date || $end_date) {
    if ($start_date && $end_date) {
        // Both start and end dates provided
        $date_condition = " AND (
            STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') >= STR_TO_DATE(?, '%Y-%m-%d') 
            AND STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') <= STR_TO_DATE(?, '%Y-%m-%d')
        )";
        $params = [$start_date, $end_date];
    } elseif ($start_date) {
        // Only start date provided
        $date_condition = " AND STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') >= STR_TO_DATE(?, '%Y-%m-%d')";
        $params = [$start_date];
    } elseif ($end_date) {
        // Only end date provided
        $date_condition = " AND STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') <= STR_TO_DATE(?, '%Y-%m-%d')";
        $params = [$end_date];
    }
}

// Build the complete SQL query
$sql = "
SELECT 
    students.student_id,
    students.student_name,
    students.class_name,
    students.department_name,
    students.study_mode,
    absents.subject_name,
    COUNT(absents.student_id) AS absence_count,
    CONCAT(COUNT(absents.student_id), ' times') AS absence_display,
    GROUP_CONCAT(absents.absent_date ORDER BY STR_TO_DATE(SUBSTRING(absents.absent_date, -10), '%m-%d-%Y') ASC SEPARATOR ', ') AS absent_dates
FROM
    students
INNER JOIN absents ON students.student_id = absents.student_id 
    AND students.class_name = absents.class_name 
    AND absents.subject_name = ?
    AND absents.study_mode = ?
    AND absents.department_name = ?
    " . $date_condition . "
WHERE 
    students.class_name = ?
GROUP BY 
    students.student_id, students.student_name, absents.subject_name
HAVING 
    COUNT(absents.student_id) > 0
ORDER BY 
    students.student_name ASC
";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Build parameters array in correct order
$all_params = [
    $subject_name,    // for absents.subject_name filter
    $study_mode,      // for absents.study_mode filter  
    $department_name  // for absents.department_name filter
];

// Add date parameters if they exist
$all_params = array_merge($all_params, $params);

// Add final parameter
$all_params[] = $class_name;  // for students.class_name

// Execute with parameters
$stmt->execute($all_params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of students
$total_students = count($results);
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Subject absent report</title>
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
        .date-filter-card {
            background: #696cff;
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .date-filter-card .card-title {
            color: white;
        }
        .date-filter-card .form-label {
            color: white;
        }
        .date-input {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
        }
        .filter-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
        }
        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        .date-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        .date-info small {
            color: rgba(255, 255, 255, 0.8);
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
                          <a href="absents.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Absent Single Subject REPORT</h4>
                        </div>

                        <!-- Date Range Filter Card -->
                        <div class="card date-filter-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class='bx bx-calendar'></i> Filter by Date Range</h5>
                                <form method="GET" action="" class="d-flex flex-wrap align-items-end gap-3">
                                    <!-- Hidden fields to preserve existing parameters -->
                                    <input type="hidden" name="class_name" value="<?php echo htmlspecialchars($class_name); ?>">
                                    <input type="hidden" name="department_name" value="<?php echo htmlspecialchars($department_name); ?>">
                                    <input type="hidden" name="study_mode" value="<?php echo htmlspecialchars($study_mode); ?>">
                                    <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>">
                                    <input type="hidden" name="semester" value="<?php echo htmlspecialchars($semester); ?>">
                                    <input type="hidden" name="academic" value="<?php echo htmlspecialchars($academic); ?>">
                                    
                                    <div>
                                        <label class="form-label mb-1">From Date:</label>
                                        <input type="date" name="start_date" class="form-control date-input" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label class="form-label mb-1">To Date:</label>
                                        <input type="date" name="end_date" class="form-control date-input" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                                    </div>
                                    <div>
                                        <button type="submit" class="btn filter-btn">
                                            <i class='bx bx-search'></i> Filter
                                        </button>
                                        <a href="?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&subject_name=<?php echo urlencode($subject_name); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>" class="btn filter-btn ms-2">
                                            <i class='bx bx-refresh'></i> Clear
                                        </a>
                                    </div>
                                </form>
                                <div class="date-info">
                                    <small><i class='bx bx-info-circle'></i> Note: Database stores dates in format "Thu-02-20-2025". The system will automatically convert your selected dates for proper filtering.</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column card-body bg-white">
                            <div>
                                <strong>Class Name:</strong> <?php echo htmlspecialchars($class_name) . ' (' . htmlspecialchars($study_mode) . ')'; ?>
                            </div>
                            <div>
                                <strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?>
                            </div>
                            <div>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?>
                            </div>
                            <div>
                                <strong>Academic:</strong> <?php echo htmlspecialchars($academic); ?>
                            </div>
                            <?php if ($start_date || $end_date): ?>
                            <div>
                                <strong>Date Range:</strong> 
                                <?php 
                                if ($start_date && $end_date) {
                                    echo htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date);
                                } elseif ($start_date) {
                                    echo 'From ' . htmlspecialchars($start_date);
                                } elseif ($end_date) {
                                    echo 'Until ' . htmlspecialchars($end_date);
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <strong>Total Students with Absences:</strong> <?php echo htmlspecialchars($total_students); ?>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="class d-flex ">
                                        <div class="btn-group">
                                             <a class="dropdown-item btn btn-primary" href="pdf_singlesubject_report.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>&subject_name=<?php echo urlencode($subject_name); ?><?php echo $start_date ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date=' . urlencode($end_date) : ''; ?>">
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
                                                <th>Total Absences</th>
                                                <th>Absent Dates</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                        <?php if (!empty($results)) {
                                                foreach ($results as $index => $student) {
                                                    $absence_count = intval($student['absence_count']);
                                                    $badge_color = 'success'; // Default to green

                                                    if ($absence_count == 1) {
                                                        $badge_color = 'success'; // Green
                                                    } elseif ($absence_count == 2) {
                                                        $badge_color = 'warning'; // Yellow/Orange
                                                    } elseif ($absence_count >= 3) {
                                                        $badge_color = 'danger'; // Red
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>' . ($index + 1) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
                                                    echo '<td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($student['absence_display']) . '</span></td>';
                                                    echo '<td><small class="text-muted">' . htmlspecialchars($student['absent_dates'] ?? 'No dates available') . '</small></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">No absences found for the selected criteria.</td></tr>';
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
<?php
 /* 
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

$class_name = $_GET['class_name'];
$department_name = $_GET['department_name'];
$study_mode = $_GET['study_mode'];
$subject_name = $_GET['subject_name'];
$semester = $_GET['semester'];
$academic = $_GET['academic'];

// Save the variables in the session
$_SESSION['class_name'] = $class_name;
$_SESSION['department_name'] = $department_name;
$_SESSION['study_mode'] = $study_mode;
$_SESSION['subject_name'] = $subject_name;
$_SESSION['semester'] = $semester;

include "../connection/connect.php";

// Preparing the SQL statement for fetching data
$stmt = $conn->prepare("
SELECT 
    students.*,
    COALESCE(subjects.subject_name, :subject_name) AS subject_name,
    CONCAT(FORMAT((COUNT(absents.student_id) / total_days_table.total_days), 0), ' times') AS absence_percentage
FROM
    students
LEFT JOIN (
    SELECT DISTINCT 
        student_name,
        subject_name
    FROM 
        absents
    WHERE 
        subject_name = :subject_name
        AND study_mode = :study_mode
        AND class_name = :class_name
        AND department_name = :department_name
) AS subjects ON students.student_name = subjects.student_name
LEFT JOIN absents ON students.student_id = absents.student_id 
    AND students.class_name = absents.class_name 
    AND subjects.subject_name = absents.subject_name
LEFT JOIN (
    SELECT
        student_name,
        subject_name,
        COUNT(DISTINCT CONCAT(subject_name, class_name)) AS total_days
    FROM
        absents
    WHERE 
        subject_name = :subject_name
    GROUP BY 
        student_name, subject_name
) AS total_days_table ON absents.student_name = total_days_table.student_name 
    AND absents.subject_name = total_days_table.subject_name
WHERE 
    students.class_name = :class_name
GROUP BY 
    students.student_name, subjects.subject_name
HAVING 
    COUNT(absents.student_id) > 0  -- Exclude students with no absences
ORDER BY 
    students.student_name ASC;

");

// Preparing the statement
$stmt->bindParam(':class_name', $class_name);
$stmt->bindParam(':department_name', $department_name);
$stmt->bindParam(':study_mode', $study_mode);
$stmt->bindParam(':subject_name', $subject_name);

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of students
$total_students = count($results);
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Subject absent report</title>
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

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                          <a href="absents.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Absent Single Subject REPORT</h4>
                        </div>
                        <div class="d-flex flex-column card-body bg-white">
                            <div>
                                <strong>Class Name:</strong> <?php echo htmlspecialchars($class_name) . ' (' . htmlspecialchars($study_mode) . ')'; ?>
                            </div>
                            <div>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?>
                            </div>
                            <div>
                                <strong>Academic:</strong> <?php echo htmlspecialchars($academic); ?>
                            </div>
                            <div>
                                <strong>Total Students:</strong> <?php echo htmlspecialchars($total_students); ?>
                            </div>
                        </div>
                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="class d-flex ">
                                        <div class="btn-group">
                                             <a class="dropdown-item btn btn-primary" href="pdf_singlesubject_report.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>&subject_name=<?php echo urlencode($subject_name); ?>">Subject Report </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No.</th> <!-- New column for row numbers -->
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Subject Name</th>
                                                <th>Absence Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                        <?php if (!empty($results)) {
                                                foreach ($results as $index => $student) { // Added index for row number
                                                    $percentage_value = floatval(str_replace(['Absent- ', '%'], '', $student['absence_percentage']));
                                                    $badge_color = 'success'; // Default to green

                                                    if ($percentage_value == 1) {
                                                        $badge_color = 'success'; // Green
                                                    } elseif ($percentage_value == 2) {
                                                        $badge_color = 'warning'; // Yellow/Orange (Warning)
                                                    } elseif ($percentage_value >= 3) {
                                                        $badge_color = 'danger'; // Red
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>' . ($index + 1) . '</td>'; // Row number
                                                    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
                                                    echo '<td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($student['absence_percentage']) . '</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5" class="text-center">No data found.</td></tr>'; // Adjusted colspan
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- Add Student Absent Modal -->
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
*/
