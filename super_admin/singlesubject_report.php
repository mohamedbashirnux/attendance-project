<?php
include 'seassion_super-admin.php';
include '../connection/connect.php';

$class_id     = $_GET['class_id']     ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id   = $_GET['faculty_id']   ?? '';
$subject_name = $_GET['subject_name'] ?? '';
$start_date   = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date     = isset($_GET['end_date'])   && !empty($_GET['end_date'])   ? $_GET['end_date']   : null;

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

    $params = [$class_id, $subject_class_id];
    $date_condition = '';
    if ($start_date && $end_date)   { $date_condition = " AND a.absence_date >= ? AND a.absence_date <= ?"; $params[] = $start_date; $params[] = $end_date; }
    elseif ($start_date)            { $date_condition = " AND a.absence_date >= ?"; $params[] = $start_date; }
    elseif ($end_date)              { $date_condition = " AND a.absence_date <= ?"; $params[] = $end_date; }

    $sql = "SELECT s.student_id, s.full_name as student_name, subj.subject_name,
                   COUNT(a.id) AS absence_count,
                   GROUP_CONCAT(a.absence_date ORDER BY a.absence_date ASC SEPARATOR ', ') AS absent_dates
            FROM absences a
            INNER JOIN students s ON a.student_id = s.id
            INNER JOIN subject_class sc ON a.subject_class_id = sc.id
            INNER JOIN subjects subj ON sc.subject_id = subj.id
            WHERE a.class_id = ? AND a.subject_class_id = ? $date_condition
            GROUP BY s.student_id, s.full_name, subj.subject_name
            HAVING COUNT(a.id) > 0
            ORDER BY s.full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_students = count($results);

} catch (Exception $e) { echo "Error: " . $e->getMessage(); exit(); }
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Subject Absent Report</title>
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
        .date-filter-card { background:#696cff; color:white; border-radius:10px; margin-bottom:20px; }
        .date-filter-card .form-label { color:white; }
        .date-input { background:rgba(255,255,255,0.9); border:none; border-radius:5px; padding:8px 12px; }
        .filter-btn { background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); color:white; }
        .filter-btn:hover { background:rgba(255,255,255,0.3); color:white; }
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
                        <h4 class="fw-bold m-0">Subject Absent Report</h4>
                    </div>

                    <!-- Date Filter -->
                    <div class="card date-filter-card">
                        <div class="card-body">
                            <h5 class="mb-3"><i class='bx bx-calendar'></i> Filter by Date Range</h5>
                            <form method="GET" class="d-flex flex-wrap align-items-end gap-3">
                                <input type="hidden" name="class_id"      value="<?php echo htmlspecialchars($class_id); ?>">
                                <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($department_id); ?>">
                                <input type="hidden" name="faculty_id"    value="<?php echo htmlspecialchars($faculty_id); ?>">
                                <input type="hidden" name="subject_name"  value="<?php echo htmlspecialchars($subject_name); ?>">
                                <div>
                                    <label class="form-label mb-1">From Date:</label>
                                    <input type="date" name="start_date" class="form-control date-input" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="form-label mb-1">To Date:</label>
                                    <input type="date" name="end_date" class="form-control date-input" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                                </div>
                                <div>
                                    <button type="submit" class="btn filter-btn"><i class='bx bx-search'></i> Filter</button>
                                    <a href="?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>&subject_name=<?php echo urlencode($subject_name); ?>" class="btn filter-btn ms-2"><i class='bx bx-refresh'></i> Clear</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="d-flex flex-column card-body bg-white mb-3">
                        <div><strong>Class:</strong> <?php echo htmlspecialchars($class_info['class_name'] . ' (' . $class_info['study_mode'] . ')'); ?></div>
                        <div><strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?></div>
                        <div><strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?></div>
                        <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?></div>
                        <div><strong>Department:</strong> <?php echo htmlspecialchars($class_info['department_name']); ?></div>
                        <div><strong>Faculty:</strong> <?php echo htmlspecialchars($class_info['faculty_name']); ?></div>
                        <div><strong>Total Students with Absences:</strong> <?php echo $total_students; ?></div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="mb-4">
                                <a class="btn btn-primary" href="pdf_singlesubject_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>&subject_name=<?php echo urlencode($subject_name); ?><?php echo $start_date ? '&start_date='.urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date='.urlencode($end_date) : ''; ?>">
                                    <i class='bx bx-download'></i> Download PDF Report
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No.</th><th>Student ID</th><th>Student Name</th><th>Subject</th><th>Total Absences</th><th>Absent Dates</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($results)): ?>
                                        <?php foreach ($results as $i => $s):
                                            $c = intval($s['absence_count']);
                                            $bc = $c >= 3 ? 'danger' : ($c == 2 ? 'warning' : 'success');
                                        ?>
                                        <tr>
                                            <td><?php echo $i+1; ?></td>
                                            <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($s['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                            <td><span class="badge bg-<?php echo $bc; ?>"><?php echo $c; ?> times</span></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($s['absent_dates'] ?? ''); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">No absences found.</td></tr>
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
