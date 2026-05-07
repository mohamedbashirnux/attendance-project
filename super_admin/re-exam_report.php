<?php
include 'seassion_super-admin.php';
include '../connection/connect.php';

$class_id     = $_GET['class_id']     ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id   = $_GET['faculty_id']   ?? '';

if (empty($class_id)) { header("Location: selection_absents.php"); exit(); }

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

    $stmt = $conn->prepare("
        SELECT s.student_id, s.full_name as student_name, subj.subject_name, COUNT(a.id) AS absence_count
        FROM absences a
        INNER JOIN students s ON a.student_id = s.id
        INNER JOIN subject_class sc ON a.subject_class_id = sc.id
        INNER JOIN subjects subj ON sc.subject_id = subj.id
        WHERE a.class_id = ?
        GROUP BY s.id, s.student_id, s.full_name, subj.subject_name
        HAVING absence_count >= 3
        ORDER BY s.full_name ASC, subj.subject_name ASC
    ");
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unique_students = [];
    foreach ($results as $row) $unique_students[$row['student_id']] = true;
    $total_students = count($unique_students);
    $total_records  = count($results);

} catch (Exception $e) { echo "Error: " . $e->getMessage(); exit(); }
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Re-Exam Report</title>
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
                        <h4 class="fw-bold m-0">Absent RE-EXAM REPORT</h4>
                    </div>
                    <div class="d-flex card-body bg-white mb-3">
                        <div class="d-flex flex-column">
                            <div><strong>Class Name:</strong> <?php echo htmlspecialchars($class_info['class_name'] . ' (' . $class_info['study_mode'] . ')'); ?></div>
                            <div><strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?></div>
                            <div><strong>Department:</strong> <?php echo htmlspecialchars($class_info['department_name']); ?></div>
                            <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?></div>
                            <div><strong>Faculty:</strong> <?php echo htmlspecialchars($class_info['faculty_name']); ?></div>
                            <div><strong>Total Students Requiring Re-exam:</strong> <?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="mb-4">
                                <a class="btn btn-primary" href="download_exam_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>">
                                    <i class='bx bx-download'></i> Exam Report PDF
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr><th>No.</th><th>Student ID</th><th>Student Name</th><th>Subject Name</th><th>Absences</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($results)):
                                        $prev = ''; $count = 0;
                                        foreach ($results as $s):
                                            $ac = intval($s['absence_count']);
                                            $bc = $ac >= 5 ? 'danger' : 'warning';
                                    ?>
                                    <tr>
                                        <td><?php if ($s['student_name'] !== $prev) { $count++; echo $count; } $prev = $s['student_name']; ?></td>
                                        <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($s['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                        <td><span class="badge bg-<?php echo $bc; ?>"><?php echo $ac; ?> times</span></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="5" class="text-center">No data found.</td></tr>
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
