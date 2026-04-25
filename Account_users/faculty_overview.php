<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

include "../connection/connect.php";

try {
    // Get total students
    $students_sql = "SELECT COUNT(DISTINCT s.id) as total 
                     FROM students s 
                     JOIN classes c ON s.class_id = c.id 
                     WHERE c.faculty_id = ?";
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->execute([$faculty_id]);
    $total_students = $students_stmt->fetchColumn();

    // Get total classes
    $classes_sql = "SELECT COUNT(*) as total FROM classes WHERE faculty_id = ?";
    $classes_stmt = $conn->prepare($classes_sql);
    $classes_stmt->execute([$faculty_id]);
    $total_classes = $classes_stmt->fetchColumn();

    // Get total departments
    $departments_sql = "SELECT COUNT(*) as total FROM departments WHERE faculty_id = ?";
    $departments_stmt = $conn->prepare($departments_sql);
    $departments_stmt->execute([$faculty_id]);
    $total_departments = $departments_stmt->fetchColumn();

    // Get total teachers
    $teachers_sql = "SELECT COUNT(*) as total FROM teachers WHERE faculty_id = ?";
    $teachers_stmt = $conn->prepare($teachers_sql);
    $teachers_stmt->execute([$faculty_id]);
    $total_teachers = $teachers_stmt->fetchColumn();

    // Get total subjects
    $subjects_sql = "SELECT COUNT(DISTINCT s.id) as total 
                     FROM subjects s 
                     WHERE s.faculty_id = ?";
    $subjects_stmt = $conn->prepare($subjects_sql);
    $subjects_stmt->execute([$faculty_id]);
    $total_subjects = $subjects_stmt->fetchColumn();

    // Get students per department
    $dept_students_sql = "SELECT d.department_name, COUNT(DISTINCT s.id) as student_count
                          FROM departments d
                          LEFT JOIN classes c ON d.id = c.department_id
                          LEFT JOIN students s ON c.id = s.class_id
                          WHERE d.faculty_id = ?
                          GROUP BY d.id, d.department_name
                          ORDER BY student_count DESC";
    $dept_students_stmt = $conn->prepare($dept_students_sql);
    $dept_students_stmt->execute([$faculty_id]);
    $dept_students = $dept_students_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get classes per department
    $dept_classes_sql = "SELECT d.department_name, COUNT(c.id) as class_count
                         FROM departments d
                         LEFT JOIN classes c ON d.id = c.department_id
                         WHERE d.faculty_id = ?
                         GROUP BY d.id, d.department_name
                         ORDER BY class_count DESC";
    $dept_classes_stmt = $conn->prepare($dept_classes_sql);
    $dept_classes_stmt->execute([$faculty_id]);
    $dept_classes = $dept_classes_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get approved vs pending students
    $status_sql = "SELECT s.status, COUNT(*) as count
                   FROM students s
                   JOIN classes c ON s.class_id = c.id
                   WHERE c.faculty_id = ?
                   GROUP BY s.status";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->execute([$faculty_id]);
    $status_data = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $approved_students = 0;
    $pending_students = 0;
    foreach ($status_data as $status) {
        if ($status['status'] == 'approved') {
            $approved_students = $status['count'];
        } else {
            $pending_students = $status['count'];
        }
    }

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
    <title>Faculty Overview</title>
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
                            <span class="text-muted fw-light">Analytics /</span> Faculty Overview
                        </h4>

                        <!-- Faculty Info Card -->
                        <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="card-body">
                                <h5 class="card-title text-white mb-2">
                                    <i class='bx bxs-school'></i> <?php echo htmlspecialchars($faculty); ?>
                                </h5>
                                <p class="mb-0">Complete overview of faculty statistics and data</p>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Students</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_students); ?></h4>
                                                </div>
                                                <small class="text-success">
                                                    <i class='bx bx-user'></i> Approved: <?php echo $approved_students; ?>
                                                </small>
                                                <br>
                                                <small class="text-warning">
                                                    <i class='bx bx-time'></i> Pending: <?php echo $pending_students; ?>
                                                </small>
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

                            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Classes</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_classes); ?></h4>
                                                </div>
                                                <small>Active classes</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class='bx bx-book-open bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Departments</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_departments); ?></h4>
                                                </div>
                                                <small>Academic departments</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-info">
                                                    <i class='bx bx-buildings bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Teachers</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_teachers); ?></h4>
                                                </div>
                                                <small>Faculty members</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-warning">
                                                    <i class='bx bx-user-circle bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Stats -->
                        <div class="row mb-4">
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Subjects</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($total_subjects); ?></h4>
                                                </div>
                                                <small>Available subjects</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-danger">
                                                    <i class='bx bx-book bx-lg'></i>
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
                                                <p class="card-text">Avg Students/Class</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo $total_classes > 0 ? number_format($total_students / $total_classes, 1) : '0'; ?></h4>
                                                </div>
                                                <small>Average class size</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-secondary">
                                                    <i class='bx bx-calculator bx-lg'></i>
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
                                                <p class="card-text">Avg Classes/Dept</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo $total_departments > 0 ? number_format($total_classes / $total_departments, 1) : '0'; ?></h4>
                                                </div>
                                                <small>Per department</small>
                                            </div>
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-dark">
                                                    <i class='bx bx-pie-chart-alt bx-lg'></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Students per Department -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between">
                                        <h5 class="card-title mb-0">Students per Department</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Department</th>
                                                        <th class="text-end">Students</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dept_students as $dept): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                                        <td class="text-end">
                                                            <span class="badge bg-primary"><?php echo number_format($dept['student_count']); ?></span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Classes per Department -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between">
                                        <h5 class="card-title mb-0">Classes per Department</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Department</th>
                                                        <th class="text-end">Classes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dept_classes as $dept): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                                        <td class="text-end">
                                                            <span class="badge bg-success"><?php echo number_format($dept['class_count']); ?></span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
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
