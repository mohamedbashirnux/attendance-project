<?php
// Check if super admin is logged in
include 'seassion_super-admin.php';

// Include database connection
include '../connection/connect.php';

// Simple stats
$totalStudents = 0;
$totalClasses = 0;
$totalTeachers = 0;
$totalFaculties = 0;

try {
    // Count students
    $totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
    
    // Count classes
    $totalClasses = $conn->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    
    // Count teachers - try different possible table names
    try {
        $totalTeachers = $conn->query("SELECT COUNT(*) FROM teacher")->fetchColumn();
    } catch (PDOException $e) {
        try {
            $totalTeachers = $conn->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
        } catch (PDOException $e2) {
            $totalTeachers = 0;
        }
    }
    
    // Count faculties - try different possible table names
    try {
        $totalFaculties = $conn->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    } catch (PDOException $e) {
        try {
            $totalFaculties = $conn->query("SELECT COUNT(*) FROM faculties")->fetchColumn();
        } catch (PDOException $e2) {
            try {
                $totalFaculties = $conn->query("SELECT COUNT(*) FROM facultytable")->fetchColumn();
            } catch (PDOException $e3) {
                $totalFaculties = 0;
            }
        }
    }
} catch (PDOException $e) {
    // Ignore errors and keep zeros
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Dashboard</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <!-- Page CSS -->
    <!-- Helpers -->
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
                        <h4 class="fw-bold py-3 mb-4">Super Admin Dashboard</h4>

                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="card-info">
                                                <p class="card-text">Total Students</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($totalStudents); ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-primary rounded p-2">
                                                    <i class="bx bx-user bx-sm"></i>
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
                                                    <h4 class="mb-0 me-2"><?php echo number_format($totalClasses); ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-success rounded p-2">
                                                    <i class="bx bx-book bx-sm"></i>
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
                                                    <h4 class="mb-0 me-2"><?php echo number_format($totalTeachers); ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-warning rounded p-2">
                                                    <i class="bx bx-user-check bx-sm"></i>
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
                                                <p class="card-text">Total Faculties</p>
                                                <div class="d-flex align-items-end mb-2">
                                                    <h4 class="mb-0 me-2"><?php echo number_format($totalFaculties); ?></h4>
                                                </div>
                                            </div>
                                            <div class="card-icon">
                                                <span class="badge bg-label-info rounded p-2">
                                                    <i class="bx bx-building bx-sm"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Welcome Card -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Welcome to Super Admin Dashboard</h5>
                                <p class="card-text">Use the menu on the left to manage the system.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript imports -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>
    <!-- Page JS -->
    <script src="../assets/js/dashboards-analytics.js"></script>
    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script>
    // Simple dashboard - no complex logic needed
    </script>
</body>
</html>
