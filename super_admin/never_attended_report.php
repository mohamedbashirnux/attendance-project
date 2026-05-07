<?php
include 'seassion_super-admin.php';
include '../connection/connect.php';

$class_id     = $_GET['class_id']     ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id   = $_GET['faculty_id']   ?? '';
$subject_name = $_GET['subject_name'] ?? '';

if (empty($class_id) || empty($subject_name)) {
    header("Location: selection_absents.php"); exit();
}

try {
    $class_stmt = $conn->prepare("
        SELECT c.class_name, c.study_mode, c.semester, c.academic_year,
               d.department_name, f.faculty_name
        FROM classes c
        JOIN departments d ON c.department_id = d.id
        JOIN faculty f ON c.faculty_id = f.id
        WHERE c.id = ?
    ");
    $class_stmt->execute([$class_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_info) { header("Location: selection_absents.php"); exit(); }

    $scStmt = $conn->prepare("SELECT sc.id as subject_class_id FROM subject_class sc JOIN subjects s ON sc.subject_id = s.id WHERE sc.class_id = ? AND s.subject_name = ?");
    $scStmt->execute([$class_id, $subject_name]);
    $scInfo = $scStmt->fetch(PDO::FETCH_ASSOC);
    if (!$scInfo) throw new Exception('Subject not found for this class');
    $subject_class_id = $scInfo['subject_class_id'];

    $totalStmt = $conn->prepare("SELECT COUNT(*) as total_sessions FROM attendance_sessions WHERE class_id = ? AND subject_class_id = ?");
    $totalStmt->execute([$class_id, $subject_class_id]);
    $total_sessions = $totalStmt->fetch(PDO::FETCH_ASSOC)['total_sessions'];

    $stmt = $conn->prepare("
        SELECT s.student_id, s.full_name as student_name, subj.subject_name,
               COUNT(a.id) AS absence_count, ? as total_sessions,
               GROUP_CONCAT(a.absence_date ORDER BY a.absence_date ASC SEPARATOR ', ') AS absent_dates
        FROM absences a
        INNER JOIN students s ON a.student_id = s.id
        INNER JOIN subject_class sc ON a.subject_class_id = sc.id
        INNER JOIN subjects subj ON sc.subject_id = subj.id
        WHERE a.class_id = ? AND a.subject_class_id = ?
        GROUP BY s.student_id, s.full_name, subj.subject_name
        HAVING COUNT(a.id) = ? AND COUNT(a.id) > 0
        ORDER BY s.full_name ASC
    ");
    $stmt->execute([$total_sessions, $class_id, $subject_class_id, $total_sessions]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_students = count($results);

} catch (Exception $e) { echo "Error: " . $e->getMessage(); exit(); }
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
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
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <style>
        .warning-card { background:#ff4444; color:white; border-radius:10px; margin-bottom:20px; }
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

                    <div class="card warning-card">
                        <div class="card-body">
                            <h5 class="mb-1"><i class='bx bx-error-circle'></i> Critical Attendance Alert</h5>
                            <p class="mb-0">Students who have NEVER attended this subject (100% absent)</p>
                        </div>
                    </div>

                    <div class="d-flex flex-column card-body bg-white mb-3">
                        <div><strong>Class:</strong> <?php echo htmlspecialchars($class_info['class_name'] . ' (' . $class_info['study_mode'] . ')'); ?></div>
                        <div><strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?></div>
                        <div><strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?></div>
                        <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?></div>
                        <div><strong>Department:</strong> <?php echo htmlspecialchars($class_info['department_name']); ?></div>
                        <div><strong>Faculty:</strong> <?php echo htmlspecialchars($class_info['faculty_name']); ?></div>
                        <div><strong>Total Sessions:</strong> <span class="badge bg-info"><?php echo $total_sessions; ?></span></div>
                        <div><strong>Students Who Never Attended:</strong> <span class="badge bg-danger"><?php echo $total_students; ?></span></div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="mb-4">
                                <a class="btn btn-danger" href="pdf_never_attended_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>&subject_name=<?php echo urlencode($subject_name); ?>">
                                    <i class='bx bx-download'></i> Download PDF Report
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr><th>No.</th><th>Student ID</th><th>Student Name</th><th>Subject</th><th>Total Sessions</th><th>Absences</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($results)): ?>
                                        <?php foreach ($results as $i => $s): ?>
                                        <tr>
                                            <td><?php echo $i+1; ?></td>
                                            <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($s['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo intval($s['total_sessions']); ?></span></td>
                                            <td><span class="badge bg-danger"><?php echo intval($s['absence_count']); ?> times</span></td>
                                            <td><span class="badge bg-danger">NEVER ATTENDED</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">No students found who never attended. Great news!</td></tr>
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
<script src="../assets/js/main.js"></script>
</body>
</html>
