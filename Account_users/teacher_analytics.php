<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

include "../connection/connect.php";

try {
    // Get total teachers assigned to this faculty (through subject_class)
    $total_teachers_sql = "SELECT COUNT(DISTINCT ats.teacher_id) as total 
                          FROM attendance_sessions ats
                          JOIN subject_class sc ON ats.subject_class_id = sc.id
                          WHERE sc.faculty_id = ?";
    $total_teachers_stmt = $conn->prepare($total_teachers_sql);
    $total_teachers_stmt->execute([$faculty_id]);
    $total_teachers = $total_teachers_stmt->fetchColumn();

    // Get active teachers (who have taken attendance in this faculty)
    $active_teachers_sql = "SELECT COUNT(DISTINCT ats.teacher_id) as total 
                           FROM attendance_sessions ats
                           JOIN subject_class sc ON ats.subject_class_id = sc.id
                           WHERE sc.faculty_id = ?";
    $active_teachers_stmt = $conn->prepare($active_teachers_sql);
    $active_teachers_stmt->execute([$faculty_id]);
    $active_teachers = $active_teachers_stmt->fetchColumn();

    // Teachers who take attendance most regularly (top 10)
    $most_active_sql = "SELECT 
                            t.teacher_id,
                            t.full_name,
                            COUNT(DISTINCT ats.id) as total_sessions,
                            COUNT(DISTINCT ats.class_id) as classes_taught,
                            MAX(ats.session_datetime) as last_session,
                            ROUND(AVG(ats.attendance_percentage), 2) as avg_attendance_rate
                        FROM teachers t
                        JOIN attendance_sessions ats ON t.id = ats.teacher_id
                        JOIN subject_class sc ON ats.subject_class_id = sc.id
                        WHERE sc.faculty_id = ?
                        GROUP BY t.id, t.teacher_id, t.full_name
                        ORDER BY total_sessions DESC
                        LIMIT 10";
    $most_active_stmt = $conn->prepare($most_active_sql);
    $most_active_stmt->execute([$faculty_id]);
    $most_active_teachers = $most_active_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Teachers who haven't taken attendance recently (30+ days or never)
    // First get all teachers who have taught in this faculty
    $inactive_teachers_sql = "SELECT 
                                t.teacher_id,
                                t.full_name,
                                MAX(ats.session_datetime) as last_session,
                                DATEDIFF(NOW(), MAX(ats.session_datetime)) as days_since_last
                            FROM teachers t
                            JOIN attendance_sessions ats ON t.id = ats.teacher_id
                            JOIN subject_class sc ON ats.subject_class_id = sc.id
                            WHERE sc.faculty_id = ?
                            GROUP BY t.id, t.teacher_id, t.full_name
                            HAVING last_session IS NULL OR days_since_last >= 30
                            ORDER BY days_since_last DESC";
    $inactive_teachers_stmt = $conn->prepare($inactive_teachers_sql);
    $inactive_teachers_stmt->execute([$faculty_id]);
    $inactive_teachers = $inactive_teachers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // All teachers with their statistics (only teachers who taught in this faculty)
    $all_teachers_sql = "SELECT 
                            t.teacher_id,
                            t.full_name,
                            COUNT(DISTINCT ats.id) as total_sessions,
                            COUNT(DISTINCT ats.class_id) as classes_taught,
                            MAX(ats.session_datetime) as last_session,
                            ROUND(AVG(ats.attendance_percentage), 2) as avg_attendance_rate,
                            SUM(ats.total_students) as total_expected,
                            SUM(ats.present_students) as total_present
                        FROM teachers t
                        JOIN attendance_sessions ats ON t.id = ats.teacher_id
                        JOIN subject_class sc ON ats.subject_class_id = sc.id
                        WHERE sc.faculty_id = ?
                        GROUP BY t.id, t.teacher_id, t.full_name
                        ORDER BY total_sessions DESC";
    $all_teachers_stmt = $conn->prepare($all_teachers_sql);
    $all_teachers_stmt->execute([$faculty_id]);
    $all_teachers = $all_teachers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Teachers by department - count teachers who have taught in each department
    $teachers_by_dept_sql = "SELECT 
                                d.department_name,
                                COUNT(DISTINCT ats.teacher_id) as teacher_count,
                                COUNT(DISTINCT ats.id) as total_sessions,
                                ROUND(AVG(ats.attendance_percentage), 2) as avg_attendance_rate
                            FROM departments d
                            LEFT JOIN classes c ON d.id = c.department_id
                            LEFT JOIN attendance_sessions ats ON c.id = ats.class_id
                            WHERE d.faculty_id = ?
                            GROUP BY d.id, d.department_name
                            HAVING teacher_count > 0
                            ORDER BY teacher_count DESC";
    $teachers_by_dept_stmt = $conn->prepare($teachers_by_dept_sql);
    $teachers_by_dept_stmt->execute([$faculty_id]);
    $teachers_by_dept = $teachers_by_dept_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Teacher Analytics</title>
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
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">
                            <span class="text-muted fw-light">Analytics /</span> Teacher Analytics
                        </h4>

                        <!-- Faculty Info Card -->
                        <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title text-white mb-2">
                                    <i class='bx bx-user-circle'></i> <?php echo htmlspecialchars($faculty); ?>
                                </h5>
                                <p class="mb-0">Teacher performance and attendance tracking analytics</p>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Teachers</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_teachers); ?></h4>
                                                </div>
                                                <small>In faculty</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class='bx bx-group bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Active Teachers</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($active_teachers); ?></h4>
                                                </div>
                                                <small>Taking attendance</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class='bx bx-check-circle bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Inactive Teachers</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format(count($inactive_teachers)); ?></h4>
                                                </div>
                                                <small>30+ days or never</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-warning">
                                                    <i class='bx bx-error-circle bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Most Active Teachers -->
                        <?php if (!empty($most_active_teachers)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-trophy text-success'></i> Most Active Teachers
                                </h5>
                                <small class="text-muted">Top 10 teachers by attendance sessions taken</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Teacher ID</th>
                                                <th>Teacher Name</th>
                                                <th>Sessions</th>
                                                <th>Classes</th>
                                                <th>Avg Rate</th>
                                                <th>Last Session</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($most_active_teachers as $index => $teacher): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($index == 0): ?>
                                                        <i class='bx bxs-medal' style='color: gold; font-size: 24px;'></i>
                                                    <?php elseif ($index == 1): ?>
                                                        <i class='bx bxs-medal' style='color: silver; font-size: 24px;'></i>
                                                    <?php elseif ($index == 2): ?>
                                                        <i class='bx bxs-medal' style='color: #cd7f32; font-size: 24px;'></i>
                                                    <?php else: ?>
                                                        <strong><?php echo $index + 1; ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo $teacher['total_sessions']; ?></span></td>
                                                <td><span class="badge bg-info"><?php echo $teacher['classes_taught']; ?></span></td>
                                                <td>
                                                    <span class="badge <?php echo $teacher['avg_attendance_rate'] >= 80 ? 'bg-success' : ($teacher['avg_attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo number_format($teacher['avg_attendance_rate'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td><small><?php echo date('M d, Y', strtotime($teacher['last_session'])); ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Inactive Teachers -->
                        <?php if (!empty($inactive_teachers)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-error text-warning'></i> Inactive Teachers
                                </h5>
                                <small class="text-muted">Teachers who haven't taken attendance in 30+ days or never</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Teacher ID</th>
                                                <th>Teacher Name</th>
                                                <th>Last Session</th>
                                                <th>Days Since</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inactive_teachers as $teacher): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                                <td>
                                                    <?php if ($teacher['last_session']): ?>
                                                        <?php echo date('M d, Y', strtotime($teacher['last_session'])); ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($teacher['days_since_last']): ?>
                                                        <span class="badge bg-warning"><?php echo $teacher['days_since_last']; ?> days</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Never taken</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- All Teachers Statistics -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-list-ul'></i> All Teachers Statistics
                                </h5>
                                <small class="text-muted">Complete list with performance metrics</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Teacher ID</th>
                                                <th>Teacher Name</th>
                                                <th>Sessions</th>
                                                <th>Classes</th>
                                                <th>Avg Rate</th>
                                                <th>Present/Expected</th>
                                                <th>Last Session</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($all_teachers as $teacher): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                                <td>
                                                    <?php if ($teacher['total_sessions'] > 0): ?>
                                                        <span class="badge bg-primary"><?php echo $teacher['total_sessions']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($teacher['classes_taught'] > 0): ?>
                                                        <span class="badge bg-info"><?php echo $teacher['classes_taught']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($teacher['avg_attendance_rate']): ?>
                                                        <span class="badge <?php echo $teacher['avg_attendance_rate'] >= 80 ? 'bg-success' : ($teacher['avg_attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                            <?php echo number_format($teacher['avg_attendance_rate'], 1); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($teacher['total_expected']): ?>
                                                        <?php echo number_format($teacher['total_present']); ?> / <?php echo number_format($teacher['total_expected']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($teacher['last_session']): ?>
                                                        <small><?php echo date('M d, Y', strtotime($teacher['last_session'])); ?></small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Teachers by Department -->
                        <?php if (!empty($teachers_by_dept)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-buildings'></i> Teachers by Department
                                </h5>
                                <small class="text-muted">Teachers who have taught in each department</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Teachers</th>
                                                <th>Total Sessions</th>
                                                <th>Avg Attendance Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teachers_by_dept as $dept): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                                <td><span class="badge bg-primary"><?php echo $dept['teacher_count']; ?></span></td>
                                                <td><span class="badge bg-info"><?php echo $dept['total_sessions'] ?? 0; ?></span></td>
                                                <td>
                                                    <?php if ($dept['avg_attendance_rate']): ?>
                                                        <span class="badge <?php echo $dept['avg_attendance_rate'] >= 80 ? 'bg-success' : ($dept['avg_attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                            <?php echo number_format($dept['avg_attendance_rate'], 1); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

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
