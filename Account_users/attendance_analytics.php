<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

include "../connection/connect.php";

// Minimum sessions threshold to be considered for ranking
$min_sessions_threshold = 5;

try {
    // Get total attendance sessions
    $sessions_sql = "SELECT COUNT(*) as total 
                     FROM attendance_sessions ats
                     JOIN classes c ON ats.class_id = c.id
                     WHERE c.faculty_id = ?";
    $sessions_stmt = $conn->prepare($sessions_sql);
    $sessions_stmt->execute([$faculty_id]);
    $total_sessions = $sessions_stmt->fetchColumn();

    // Get total absences
    $absences_sql = "SELECT COUNT(*) as total 
                     FROM absences a
                     JOIN classes c ON a.class_id = c.id
                     WHERE c.faculty_id = ?";
    $absences_stmt = $conn->prepare($absences_sql);
    $absences_stmt->execute([$faculty_id]);
    $total_absences = $absences_stmt->fetchColumn();

    // Get overall attendance rate
    $overall_rate_sql = "SELECT 
                            SUM(ats.total_students) as total_expected,
                            SUM(ats.present_students) as total_present
                         FROM attendance_sessions ats
                         JOIN classes c ON ats.class_id = c.id
                         WHERE c.faculty_id = ?";
    $overall_rate_stmt = $conn->prepare($overall_rate_sql);
    $overall_rate_stmt->execute([$faculty_id]);
    $overall_rate = $overall_rate_stmt->fetch(PDO::FETCH_ASSOC);
    
    $attendance_rate = 0;
    if ($overall_rate['total_expected'] > 0) {
        $attendance_rate = ($overall_rate['total_present'] / $overall_rate['total_expected']) * 100;
    }

    // Get class performance (only classes with minimum sessions)
    $class_performance_sql = "SELECT 
                                c.id,
                                c.class_name,
                                c.study_mode,
                                d.department_name,
                                COUNT(DISTINCT ats.id) as total_sessions,
                                SUM(ats.total_students) as total_expected,
                                SUM(ats.present_students) as total_present,
                                SUM(ats.absent_students) as total_absent,
                                ROUND((SUM(ats.present_students) / SUM(ats.total_students)) * 100, 2) as attendance_rate
                              FROM classes c
                              JOIN departments d ON c.department_id = d.id
                              LEFT JOIN attendance_sessions ats ON c.id = ats.class_id
                              WHERE c.faculty_id = ?
                              GROUP BY c.id, c.class_name, c.study_mode, d.department_name
                              HAVING total_sessions >= ?
                              ORDER BY attendance_rate DESC";
    $class_performance_stmt = $conn->prepare($class_performance_sql);
    $class_performance_stmt->execute([$faculty_id, $min_sessions_threshold]);
    $class_performance = $class_performance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get classes with no attendance taken
    $no_attendance_sql = "SELECT 
                            c.id,
                            c.class_name,
                            c.study_mode,
                            d.department_name,
                            COUNT(s.id) as student_count
                          FROM classes c
                          JOIN departments d ON c.department_id = d.id
                          LEFT JOIN students s ON c.id = s.class_id
                          LEFT JOIN attendance_sessions ats ON c.id = ats.class_id
                          WHERE c.faculty_id = ? AND ats.id IS NULL
                          GROUP BY c.id, c.class_name, c.study_mode, d.department_name
                          ORDER BY student_count DESC";
    $no_attendance_stmt = $conn->prepare($no_attendance_sql);
    $no_attendance_stmt->execute([$faculty_id]);
    $no_attendance_classes = $no_attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get classes with insufficient sessions (less than threshold)
    $insufficient_sessions_sql = "SELECT 
                                    c.id,
                                    c.class_name,
                                    c.study_mode,
                                    d.department_name,
                                    COUNT(DISTINCT ats.id) as total_sessions
                                  FROM classes c
                                  JOIN departments d ON c.department_id = d.id
                                  LEFT JOIN attendance_sessions ats ON c.id = ats.class_id
                                  WHERE c.faculty_id = ?
                                  GROUP BY c.id, c.class_name, c.study_mode, d.department_name
                                  HAVING total_sessions > 0 AND total_sessions < ?
                                  ORDER BY total_sessions ASC";
    $insufficient_sessions_stmt = $conn->prepare($insufficient_sessions_sql);
    $insufficient_sessions_stmt->execute([$faculty_id, $min_sessions_threshold]);
    $insufficient_sessions_classes = $insufficient_sessions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get subjects with most absences
    $subject_absences_sql = "SELECT 
                                subj.subject_name,
                                COUNT(a.id) as absence_count,
                                COUNT(DISTINCT a.student_id) as affected_students
                             FROM absences a
                             JOIN subject_class sc ON a.subject_class_id = sc.id
                             JOIN subjects subj ON sc.subject_id = subj.id
                             JOIN classes c ON a.class_id = c.id
                             WHERE c.faculty_id = ?
                             GROUP BY subj.id, subj.subject_name
                             ORDER BY absence_count DESC
                             LIMIT 10";
    $subject_absences_stmt = $conn->prepare($subject_absences_sql);
    $subject_absences_stmt->execute([$faculty_id]);
    $subject_absences = $subject_absences_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get most absent students (top 10)
    $most_absent_sql = "SELECT 
                            s.student_id,
                            s.full_name,
                            c.class_name,
                            c.study_mode,
                            d.department_name,
                            COUNT(a.id) as total_absences,
                            COUNT(DISTINCT a.subject_class_id) as subjects_affected
                        FROM students s
                        JOIN absences a ON s.id = a.student_id
                        JOIN classes c ON s.class_id = c.id
                        JOIN departments d ON c.department_id = d.id
                        WHERE c.faculty_id = ?
                        GROUP BY s.id, s.student_id, s.full_name, c.class_name, c.study_mode, d.department_name
                        ORDER BY total_absences DESC
                        LIMIT 10";
    $most_absent_stmt = $conn->prepare($most_absent_sql);
    $most_absent_stmt->execute([$faculty_id]);
    $most_absent_students = $most_absent_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get students at risk (3+ absences in any single subject)
    $at_risk_sql = "SELECT 
                        s.student_id,
                        s.full_name,
                        c.class_name,
                        d.department_name,
                        subj.subject_name,
                        COUNT(a.id) as absences_in_subject
                    FROM students s
                    JOIN absences a ON s.id = a.student_id
                    JOIN classes c ON s.class_id = c.id
                    JOIN departments d ON c.department_id = d.id
                    JOIN subject_class sc ON a.subject_class_id = sc.id
                    JOIN subjects subj ON sc.subject_id = subj.id
                    WHERE c.faculty_id = ?
                    GROUP BY s.id, s.student_id, s.full_name, c.class_name, d.department_name, subj.subject_name
                    HAVING absences_in_subject >= 3
                    ORDER BY absences_in_subject DESC
                    LIMIT 20";
    $at_risk_stmt = $conn->prepare($at_risk_sql);
    $at_risk_stmt->execute([$faculty_id]);
    $at_risk_students = $at_risk_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get perfect attendance students (students with sessions but zero absences)
    $perfect_attendance_sql = "SELECT DISTINCT
                                s.student_id,
                                s.full_name,
                                c.class_name,
                                c.study_mode,
                                d.department_name,
                                COUNT(DISTINCT ats.id) as sessions_attended
                            FROM students s
                            JOIN classes c ON s.class_id = c.id
                            JOIN departments d ON c.department_id = d.id
                            JOIN attendance_sessions ats ON c.id = ats.class_id
                            LEFT JOIN absences a ON s.id = a.student_id
                            WHERE c.faculty_id = ? AND a.id IS NULL AND s.status = 'approved'
                            GROUP BY s.id, s.student_id, s.full_name, c.class_name, c.study_mode, d.department_name
                            HAVING sessions_attended >= 3
                            ORDER BY sessions_attended DESC
                            LIMIT 10";
    $perfect_attendance_stmt = $conn->prepare($perfect_attendance_sql);
    $perfect_attendance_stmt->execute([$faculty_id]);
    $perfect_attendance_students = $perfect_attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Department comparison
    $dept_comparison_sql = "SELECT 
                                d.department_name,
                                COUNT(DISTINCT c.id) as total_classes,
                                (SELECT COUNT(DISTINCT s2.id) 
                                 FROM students s2 
                                 JOIN classes c2 ON s2.class_id = c2.id 
                                 WHERE c2.department_id = d.id) as total_students,
                                COUNT(DISTINCT ats.id) as total_sessions,
                                SUM(ats.total_students) as total_expected,
                                SUM(ats.present_students) as total_present,
                                SUM(ats.absent_students) as total_absent,
                                ROUND((SUM(ats.present_students) / NULLIF(SUM(ats.total_students), 0)) * 100, 2) as attendance_rate
                            FROM departments d
                            LEFT JOIN classes c ON d.id = c.department_id
                            LEFT JOIN attendance_sessions ats ON c.id = ats.class_id
                            WHERE d.faculty_id = ?
                            GROUP BY d.id, d.department_name
                            HAVING total_sessions > 0
                            ORDER BY attendance_rate DESC";
    $dept_comparison_stmt = $conn->prepare($dept_comparison_sql);
    $dept_comparison_stmt->execute([$faculty_id]);
    $dept_comparison = $dept_comparison_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Absences by day of week
    $day_analysis_sql = "SELECT 
                            DAYNAME(a.absence_date) as day_name,
                            DAYOFWEEK(a.absence_date) as day_number,
                            COUNT(a.id) as absence_count
                        FROM absences a
                        JOIN classes c ON a.class_id = c.id
                        WHERE c.faculty_id = ?
                        GROUP BY day_name, day_number
                        ORDER BY day_number";
    $day_analysis_stmt = $conn->prepare($day_analysis_sql);
    $day_analysis_stmt->execute([$faculty_id]);
    $day_analysis_raw = $day_analysis_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create array with all 7 days
    $all_days = [
        1 => 'Sunday',
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
        7 => 'Saturday'
    ];
    
    // Fill in the data for all days
    $day_analysis = [];
    foreach ($all_days as $num => $name) {
        $found = false;
        foreach ($day_analysis_raw as $day) {
            if ($day['day_number'] == $num) {
                $day_analysis[] = $day;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $day_analysis[] = [
                'day_name' => $name,
                'day_number' => $num,
                'absence_count' => 0
            ];
        }
    }

    // Absences by month
    $month_analysis_sql = "SELECT 
                            DATE_FORMAT(a.absence_date, '%Y-%m') as month,
                            DATE_FORMAT(a.absence_date, '%M %Y') as month_name,
                            COUNT(a.id) as absence_count,
                            COUNT(DISTINCT a.student_id) as students_affected
                        FROM absences a
                        JOIN classes c ON a.class_id = c.id
                        WHERE c.faculty_id = ?
                        GROUP BY month, month_name
                        ORDER BY month DESC";
    $month_analysis_stmt = $conn->prepare($month_analysis_sql);
    $month_analysis_stmt->execute([$faculty_id]);
    $month_analysis = $month_analysis_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Best and worst classes
    $best_classes = array_slice($class_performance, 0, 5);
    $worst_classes = array_slice(array_reverse($class_performance), 0, 5);

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
    <title>Attendance Analytics</title>
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
                            <span class="text-muted fw-light">Analytics /</span> Attendance Analytics
                        </h4>

                        <!-- Faculty Info Card -->
                        <div class="card mb-4" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title text-white mb-2">
                                    <i class='bx bx-line-chart'></i> <?php echo htmlspecialchars($faculty); ?>
                                </h5>
                                <p class="mb-0">Deep attendance analysis and performance metrics</p>
                            </div>
                        </div>

                        <!-- Overall Statistics -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Sessions</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_sessions); ?></h4>
                                                </div>
                                                <small>Attendance recorded</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class='bx bx-calendar-check bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Absences</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_absences); ?></h4>
                                                </div>
                                                <small>Students marked absent</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-danger">
                                                    <i class='bx bx-user-x bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Overall Attendance</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($attendance_rate, 1); ?>%</h4>
                                                </div>
                                                <small class="<?php echo $attendance_rate >= 80 ? 'text-success' : ($attendance_rate >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                                    <?php echo $attendance_rate >= 80 ? 'Excellent' : ($attendance_rate >= 60 ? 'Good' : 'Needs Improvement'); ?>
                                                </small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded <?php echo $attendance_rate >= 80 ? 'bg-label-success' : ($attendance_rate >= 60 ? 'bg-label-warning' : 'bg-label-danger'); ?>">
                                                    <i class='bx bx-trending-up bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Classes Tracked</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo count($class_performance); ?></h4>
                                                </div>
                                                <small>With <?php echo $min_sessions_threshold; ?>+ sessions</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-info">
                                                    <i class='bx bx-bar-chart-alt-2 bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Best Performing Classes -->
                        <?php if (!empty($best_classes)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-trophy text-success'></i> Best Performing Classes
                                </h5>
                                <small class="text-muted">Top 5 classes with highest attendance rates (minimum <?php echo $min_sessions_threshold; ?> sessions)</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Class</th>
                                                <th>Department</th>
                                                <th>Sessions</th>
                                                <th>Attendance Rate</th>
                                                <th>Present/Expected</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($best_classes as $index => $class): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($index == 0): ?>
                                                        <i class='bx bxs-medal' style='color: gold; font-size: 24px;'></i>
                                                    <?php elseif ($index == 1): ?>
                                                        <i class='bx bxs-medal' style='color: silver; font-size: 24px;'></i>
                                                    <?php elseif ($index == 2): ?>
                                                        <i class='bx bxs-medal' style='color: #cd7f32; font-size: 24px;'></i>
                                                    <?php else: ?>
                                                        <?php echo $index + 1; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($class['class_name']) . ' (' . htmlspecialchars($class['study_mode']) . ')'; ?></td>
                                                <td><?php echo htmlspecialchars($class['department_name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $class['total_sessions']; ?></span></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo number_format($class['attendance_rate'], 1); ?>%</span>
                                                </td>
                                                <td><?php echo number_format($class['total_present']); ?> / <?php echo number_format($class['total_expected']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Worst Performing Classes -->
                        <?php if (!empty($worst_classes)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-error text-danger'></i> Classes Needing Attention
                                </h5>
                                <small class="text-muted">Classes with lowest attendance rates (minimum <?php echo $min_sessions_threshold; ?> sessions)</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Department</th>
                                                <th>Sessions</th>
                                                <th>Attendance Rate</th>
                                                <th>Absent/Expected</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($worst_classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_name']) . ' (' . htmlspecialchars($class['study_mode']) . ')'; ?></td>
                                                <td><?php echo htmlspecialchars($class['department_name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $class['total_sessions']; ?></span></td>
                                                <td>
                                                    <span class="badge <?php echo $class['attendance_rate'] >= 80 ? 'bg-success' : ($class['attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo number_format($class['attendance_rate'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($class['total_absent']); ?> / <?php echo number_format($class['total_expected']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Classes with No Attendance -->
                            <?php if (!empty($no_attendance_classes)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class='bx bx-error-circle text-warning'></i> No Attendance Taken
                                        </h5>
                                        <small class="text-muted">Classes with zero attendance sessions</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Class</th>
                                                        <th>Department</th>
                                                        <th>Students</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($no_attendance_classes as $class): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($class['class_name']) . ' (' . htmlspecialchars($class['study_mode']) . ')'; ?></td>
                                                        <td><?php echo htmlspecialchars($class['department_name']); ?></td>
                                                        <td><span class="badge bg-secondary"><?php echo $class['student_count']; ?></span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Classes with Insufficient Sessions -->
                            <?php if (!empty($insufficient_sessions_classes)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class='bx bx-time text-info'></i> Insufficient Data
                                        </h5>
                                        <small class="text-muted">Classes with less than <?php echo $min_sessions_threshold; ?> sessions (not ranked)</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Class</th>
                                                        <th>Department</th>
                                                        <th>Sessions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($insufficient_sessions_classes as $class): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($class['class_name']) . ' (' . htmlspecialchars($class['study_mode']) . ')'; ?></td>
                                                        <td><?php echo htmlspecialchars($class['department_name']); ?></td>
                                                        <td><span class="badge bg-warning"><?php echo $class['total_sessions']; ?></span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Subjects with Most Absences -->
                        <?php if (!empty($subject_absences)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-book-open text-danger'></i> Subjects with Most Absences
                                </h5>
                                <small class="text-muted">Top 10 subjects by absence count</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Subject Name</th>
                                                <th>Total Absences</th>
                                                <th>Affected Students</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subject_absences as $index => $subject): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td><span class="badge bg-danger"><?php echo number_format($subject['absence_count']); ?></span></td>
                                                <td><span class="badge bg-warning"><?php echo number_format($subject['affected_students']); ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- FEATURE 1: Student Performance Tracking -->
                        <h5 class="mt-5 mb-3"><i class='bx bx-user-check'></i> Student Performance Tracking</h5>
                        
                        <div class="row">
                            <!-- Most Absent Students -->
                            <?php if (!empty($most_absent_students)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class='bx bx-user-x text-danger'></i> Most Absent Students
                                        </h5>
                                        <small class="text-muted">Top 10 students with highest absence count</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Student ID</th>
                                                        <th>Name</th>
                                                        <th>Class</th>
                                                        <th>Absences</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($most_absent_students as $student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                        <td><small><?php echo htmlspecialchars($student['class_name']); ?></small></td>
                                                        <td><span class="badge bg-danger"><?php echo $student['total_absences']; ?></span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Students at Risk -->
                            <?php if (!empty($at_risk_students)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class='bx bx-error text-warning'></i> Students at Risk
                                        </h5>
                                        <small class="text-muted">3+ absences in a single subject</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Subject</th>
                                                        <th>Absences</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($at_risk_students as $student): ?>
                                                    <tr>
                                                        <td>
                                                            <small><strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                                            <?php echo htmlspecialchars($student['student_id']); ?></small>
                                                        </td>
                                                        <td><small><?php echo htmlspecialchars($student['subject_name']); ?></small></td>
                                                        <td><span class="badge bg-warning"><?php echo $student['subject_absences']; ?></span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Perfect Attendance Students -->
                        <?php if (!empty($perfect_students)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-trophy text-success'></i> Perfect Attendance Students
                                </h5>
                                <small class="text-muted">Students with zero absences</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($perfect_students as $student): ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="alert alert-success mb-0 py-2">
                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($student['student_id']); ?> - <?php echo htmlspecialchars($student['class_name']); ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- FEATURE 2: Department Comparison -->
                        <?php if (!empty($dept_comparison)): ?>
                        <h5 class="mt-5 mb-3"><i class='bx bx-buildings'></i> Department Comparison</h5>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-bar-chart-alt'></i> Department Rankings
                                </h5>
                                <small class="text-muted">Attendance performance by department</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Department</th>
                                                <th>Classes</th>
                                                <th>Students</th>
                                                <th>Sessions</th>
                                                <th>Attendance Rate</th>
                                                <th>Present/Expected</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dept_comparison as $index => $dept): ?>
                                            <tr>
                                                <td><strong><?php echo $index + 1; ?></strong></td>
                                                <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                                <td><span class="badge bg-info"><?php echo $dept['total_classes']; ?></span></td>
                                                <td><span class="badge bg-primary"><?php echo $dept['total_students']; ?></span></td>
                                                <td><span class="badge bg-secondary"><?php echo $dept['total_sessions']; ?></span></td>
                                                <td>
                                                    <span class="badge <?php echo $dept['attendance_rate'] >= 80 ? 'bg-success' : ($dept['attendance_rate'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo number_format($dept['attendance_rate'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($dept['total_present']); ?> / <?php echo number_format($dept['total_expected']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- FEATURE 3: Time-Based Analytics -->
                        <h5 class="mt-5 mb-3"><i class='bx bx-time'></i> Time-Based Analytics</h5>
                        <div class="row">
                            <!-- Absences by Day of Week -->
                            <?php if (!empty($day_analysis)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class='bx bx-calendar'></i> Absences by Day of Week
                                        </h5>
                                        <small class="text-muted">Which days have most absences?</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Day</th>
                                                        <th>Absences</th>
                                                        <th>Visual</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $max_day_absences = max(array_column($day_analysis, 'absence_count'));
                                                    if ($max_day_absences == 0) $max_day_absences = 1; // Avoid division by zero
                                                    foreach ($day_analysis as $day): 
                                                        $percentage = $max_day_absences > 0 ? ($day['absence_count'] / $max_day_absences) * 100 : 0;
                                                        $bar_color = $day['absence_count'] == 0 ? 'bg-secondary' : 'bg-danger';
                                                    ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($day['day_name']); ?></strong></td>
                                                        <td>
                                                            <?php if ($day['absence_count'] > 0): ?>
                                                                <span class="badge bg-danger"><?php echo number_format($day['absence_count']); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">0</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar <?php echo $bar_color; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                    <?php if ($day['absence_count'] > 0): ?>
                                                                        <?php echo number_format($percentage, 0); ?>%
                                                                    <?php else: ?>
                                                                        No data
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Absences by Month -->
                            <?php if (!empty($month_analysis)): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class='bx bx-calendar-event'></i> Absences by Month
                                        </h5>
                                        <small class="text-muted">All months with absence data</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Month</th>
                                                        <th>Absences</th>
                                                        <th>Trend</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $max_month_absences = max(array_column($month_analysis, 'absence_count'));
                                                    foreach ($month_analysis as $month): 
                                                        $percentage = ($month['absence_count'] / $max_month_absences) * 100;
                                                    ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($month['month_name']); ?></strong></td>
                                                        <td><span class="badge bg-warning"><?php echo number_format($month['absence_count']); ?></span></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo number_format($percentage, 0); ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
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
