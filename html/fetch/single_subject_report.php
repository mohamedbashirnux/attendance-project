<?php
// Remove session dependencies for super admin access
include "../../connection/connect.php";

// Get the GET parameters
$class_name = $_GET['class_name'] ?? '';
$department_name = $_GET['department_name'] ?? '';
$study_mode = $_GET['study_mode'] ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$semester = $_GET['semester'] ?? '';
$academic = $_GET['academic'] ?? '';
$faculty = $_GET['faculty'] ?? '';

// Validate required parameters
if (empty($class_name) || empty($department_name) || empty($study_mode) || empty($subject_name)) {
    header("Location: ../selection_Absents.php");
    exit();
}

try {
    // First, get total sessions for this subject
    $sessionQuery = "SELECT COUNT(*) as total_sessions 
                     FROM submit_session 
                     WHERE class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?";
    $stmt = $conn->prepare($sessionQuery);
    $stmt->execute([$class_name, $department_name, $study_mode, $subject_name]);
    $totalSessions = $stmt->fetch(PDO::FETCH_ASSOC)['total_sessions'];

    // Get all students in the class
    $studentsQuery = "SELECT student_id, student_name 
                      FROM students 
                      WHERE class_name = ? AND department_name = ? AND study_mode = ?";
    $stmt = $conn->prepare($studentsQuery);
    $stmt->execute([$class_name, $department_name, $study_mode]);
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reportData = [];
    $totalAbsences = 0;
    $totalPossibleAttendances = 0;

    foreach ($allStudents as $student) {
        $studentId = $student['student_id'];
        $studentName = $student['student_name'];

        // Count absences for this student in this subject
        $absenceQuery = "SELECT COUNT(*) as absence_count 
                         FROM absents 
                         WHERE student_id = ? AND class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?";
        $stmt = $conn->prepare($absenceQuery);
        $stmt->execute([$studentId, $class_name, $department_name, $study_mode, $subject_name]);
        $absenceCount = $stmt->fetch(PDO::FETCH_ASSOC)['absence_count'];

        // Get absence dates for this student in this subject
        $datesQuery = "SELECT absent_date, statuses, excuses 
                       FROM absents 
                       WHERE student_id = ? AND class_name = ? AND department_name = ? AND study_mode = ? AND subject_name = ?
                       ORDER BY absent_date DESC";
        $stmt = $conn->prepare($datesQuery);
        $stmt->execute([$studentId, $class_name, $department_name, $study_mode, $subject_name]);
        $absenceDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate attendance rate
        $attendanceRate = $totalSessions > 0 ? (($totalSessions - $absenceCount) / $totalSessions) * 100 : 100;
        
        // Determine status
        $status = 'Good';
        $statusColor = 'success';
        if ($attendanceRate < 60) {
            $status = 'Critical';
            $statusColor = 'danger';
        } elseif ($attendanceRate < 80) {
            $status = 'Warning';
            $statusColor = 'warning';
        }

        $studentData = [
            'student_id' => $studentId,
            'student_name' => $studentName,
            'absences' => $absenceCount,
            'total_sessions' => $totalSessions,
            'attendance_rate' => round($attendanceRate, 1),
            'status' => $status,
            'status_color' => $statusColor,
            'absence_dates' => $absenceDates
        ];

        $reportData[] = $studentData;
        $totalAbsences += $absenceCount;
        $totalPossibleAttendances += $totalSessions;
    }

    // Calculate class attendance rate
    $classAttendanceRate = $totalPossibleAttendances > 0 ? 
        (($totalPossibleAttendances - $totalAbsences) / $totalPossibleAttendances) * 100 : 100;

    // Sort by attendance rate (worst first)
    usort($reportData, function($a, $b) {
        return $a['attendance_rate'] <=> $b['attendance_rate'];
    });

} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Subject Report</title>
    <link rel="icon" type="image/x-icon" href="../capital.png" />
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
</head>
<body>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php include '../menu.php'; ?>
        <div class="layout-page">
            <?php include '../navbar.php'; ?>

            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <div class="d-flex align-items-center mb-4">
                        <a href="../absents.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>&faculty=<?php echo urlencode($faculty); ?>" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                        <h4 class="fw-bold m-0">Subject Report - <?php echo htmlspecialchars($subject_name); ?></h4>
                    </div>
                    
                    <div class="d-flex card-body bg-white">
                        <div class="d-flex flex-column">
                            <div><strong>Subject Name:</strong> <?php echo htmlspecialchars($subject_name); ?></div>
                            <div><strong>Class Name:</strong> <?php echo htmlspecialchars($class_name) . ' (' . htmlspecialchars($study_mode) . ')'; ?></div>
                            <div><strong>Department:</strong> <?php echo htmlspecialchars($department_name); ?></div>
                            <div><strong>Faculty:</strong> <?php echo htmlspecialchars($faculty); ?></div>
                            <div><strong>Semester:</strong> <?php echo htmlspecialchars($semester); ?></div>
                            <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($academic); ?></div>
                            <?php if (isset($totalSessions)): ?>
                            <div><strong>Total Sessions:</strong> <?php echo $totalSessions; ?></div>
                            <div><strong>Class Attendance Rate:</strong> <?php echo isset($classAttendanceRate) ? round($classAttendanceRate, 1) . '%' : 'N/A'; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Absences</th>
                                            <th>Total Sessions</th>
                                            <th>Attendance Rate</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (isset($reportData) && !empty($reportData)): ?>
                                        <?php foreach ($reportData as $index => $student): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                            <td><?php echo $student['absences']; ?></td>
                                            <td><?php echo $student['total_sessions']; ?></td>
                                            <td><?php echo $student['attendance_rate']; ?>%</td>
                                            <td><span class="badge bg-<?php echo $student['status_color']; ?>"><?php echo $student['status']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">No data found.</td></tr>
                                    <?php endif; ?>
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
