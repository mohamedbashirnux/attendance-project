<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

// Database connection
include "../connection/connect.php";

// Today's date in the desired format
$today_date = date('d-m-Y');

// Query to get the list of classes
$queryClasses = "SELECT DISTINCT class_name, study_mode, department_name FROM students WHERE faculty_name = :faculty_name";
$stmtClasses = $conn->prepare($queryClasses);
$stmtClasses->bindParam(':faculty_name', $faculty);
$stmtClasses->execute();
$resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

// Array to store the absent students count per class
$classAbsenceData = [];

foreach ($resultClasses as $classRow) {
    $class_name = $classRow['class_name'];
    $study_mode = $classRow['study_mode'];
    $department_name = $classRow['department_name'];

    // Query to get the total number of students in this class
    $queryTotal = "SELECT COUNT(student_id) AS total_students FROM students WHERE class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode AND faculty_name = :faculty_name";
    $stmtTotal = $conn->prepare($queryTotal);
    $stmtTotal->bindParam(':class_name', $class_name);
    $stmtTotal->bindParam(':faculty_name', $faculty);
    $stmtTotal->bindParam(':study_mode', $study_mode);
    $stmtTotal->bindParam(':department_name', $department_name);
    $stmtTotal->execute();
    $totalStudentsRow = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $total_students = $totalStudentsRow['total_students'];

    // Query to get the number of absent students in this class today
    $queryAbsent = "
        SELECT COUNT(DISTINCT student_id) AS absent_students 
        FROM absents 
        WHERE DATE_FORMAT(STR_TO_DATE(SUBSTRING_INDEX(absent_date, '-', -3), '%d-%m-%Y'), '%d-%m-%Y') = :today_date 
        AND statuses = 'absent' 
        AND class_name = :class_name 
        AND study_mode = :study_mode 
        AND department_name = :department_name 
        AND faculty_name = :faculty_name";
    $stmtAbsent = $conn->prepare($queryAbsent);
    $stmtAbsent->bindParam(':today_date', $today_date);
    $stmtAbsent->bindParam(':class_name', $class_name);
    $stmtAbsent->bindParam(':faculty_name', $faculty);
    $stmtAbsent->bindParam(':study_mode', $study_mode);
    $stmtAbsent->bindParam(':department_name', $department_name);
    $stmtAbsent->execute();
    $absentStudentsRow = $stmtAbsent->fetch(PDO::FETCH_ASSOC);
    $absent_students = $absentStudentsRow['absent_students'];

    // Calculate the attendance rate
    $absent_rate = ($total_students > 0) ? ($absent_students / $total_students) * 100 : 0;

    // Store the data in the array
    $classAbsenceData[] = [
        'class_name' => $class_name,
        'study_mode' => $study_mode,
        'department_name' => $department_name,
        'total_students' => $total_students,
        'absent_students' => $absent_students,
        'absent_rate' => round($absent_rate, 2),
        'faculty_name' => $faculty // Include faculty_name here
    ];
    
}

// Function to search for a student by ID
function searchStudentById($conn, $searchTerm, $faculty) {
    $query = "SELECT student_id FROM students WHERE student_id = :search AND faculty_name = :faculty LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':faculty', $faculty, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle the search
$searchResult = null; // Initialize search result
if (isset($_GET['student_search']) && !empty($_GET['student_search'])) {
    $searchTerm = trim($_GET['student_search']);

    // Search for student by ID (no validation on the format)
    $searchResult = searchStudentById($conn, $searchTerm, $faculty);
    
    if ($searchResult) {
        // Redirect to single_student.php if a student is found
        header("Location: single_student.php?student_id=" . urlencode($searchResult['student_id']) . "&faculty=" . urlencode($faculty));
        exit();
    } else {
        // Student not found
        $error_message = "No student found with the given ID.";
    }
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
                        
                        <div class="card">
                            <div class="card-body">
                                <form class="d-flex" action="" method="GET">
                                    <input class="form-control me-2" type="number" placeholder="Search student by ID" name="student_search" aria-label="Search" required />
                                    <button class="btn btn-outline-primary" type="submit">Search</button>
                                </form>

                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger mt-3" role="alert">
                                        <?php echo ($error_message); ?>
                                    </div>
                                <?php elseif (isset($_GET['student_search']) && empty($searchResult)): ?>
                                    <div class="alert alert-danger mt-3" role="alert">
                                        No student found with the given ID.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h4 class="fw-bold py-3 px-2 mb-2 mt-3 badge bg-label-primary rounded-pill">Daily absence rate:</h4><?php
// Get unique departments for the faculty
$queryDepartments = "SELECT DISTINCT department_name FROM students WHERE faculty_name = :faculty_name";
$stmtDepartments = $conn->prepare($queryDepartments);
$stmtDepartments->bindParam(':faculty_name', $faculty);
$stmtDepartments->execute();
$departments = $stmtDepartments->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Add the filter buttons -->
<div class="mb-3">
    <button class="btn btn-primary me-2 department-filter" data-department="all">All Departments</button>
    <?php foreach ($departments as $department): ?>
        <button class="btn btn-outline-primary me-2 department-filter" data-department="<?php echo htmlspecialchars($department); ?>">
            <?php echo htmlspecialchars($department); ?>
        </button>
    <?php endforeach; ?>
</div>
<div class="row mt-2" id="classCards">
                            <?php foreach ($classAbsenceData as $classData) : ?>
                                <div class="col-md-4 col-lg-3 col-xl-3 order-0 mb-3 class-card" data-department="<?php echo htmlspecialchars($classData['department_name']); ?>">
                                    <div class="card h-100">
                                        <div class="card-header d-flex align-items-center justify-content-between pb-0  text-white">
                                            <div class="card-title mb-0">
                                                <div class="m-0 me-2 mb-2 text-black text-capitalize"><?php echo ($classData['class_name'] . ' (' . $classData['study_mode']. ')'  ); ?></div>
                                                <div class="m-0 me-2 mb-4 text-black text-capitalize"><?php echo ($classData['department_name'] ); ?></div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn p-0 text-black" type="button" id="classStatisticsDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end text-black" aria-labelledby="classStatisticsDropdown">
                                                    <a class="dropdown-item text-black" href="/attendanceproject1/Account_users/dashboard.php">Refresh</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    <h3 class="mb-2 text-primary"><?php echo ($classData['absent_rate']); ?>%</h3>
                                                    <span class="text-muted">Absent Rate</span>
                                                </div>
                                                <div>
                                                    <small class=" badge bg-label-warning rounded-pill">Absent Students: <strong><?php echo ($classData['absent_students']); ?></strong></small>
                                                </div>
                                            </div>
                                            <div class="progress mb-3" style="height: 8px;">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($classData['absent_rate']); ?>%;" aria-valuenow="<?php echo ($classData['absent_rate']); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <ul class="p-0 m-0">
                                                <li class="d-flex mb-4 pb-1">
                                                    <!-- <div class="avatar flex-shrink-0 me-3">
                                                        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-user"></i></span>
                                                    </div> -->
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                        <div class="me-2">
                                                            <h6 class="mb-0">Total Students</h6>
                                                            <small class=" badge bg-label-primary rounded-pill"><?php echo ($classData['total_students']); ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="btn-group">
                                                    <a class="btn btn-primary text-white" 
   href="allocate.php?class_name=<?php echo urlencode($classData['class_name']); ?>&classHidden=<?php echo urlencode($classData['class_name']); ?>&department_name=<?php echo urlencode($classData['department_name']); ?>&study_mode=<?php echo urlencode($classData['study_mode']); ?>&faculty_name=<?php echo urlencode($classData['faculty_name']); ?>">
   Allocate
</a>

                                                </div>

                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
        setInterval(function() {
    $.ajax({
        url: '../Database_users/allocate_update_teaher/time_pending.php',// Update to the correct path of your PHP script
        type: 'GET', // Use GET to fetch the updated status
        success: function(response) {
            // console.log('Status updated');
            console.log(response); // Log the server response
            // Optionally, you can refresh the page or update the table here
            // location.reload(); // Reload the page to reflect the changes
        },
        error: function(xhr, status, error) {
            console.error('Error: ' + status + ' ' + error); // Log any errors
        }
    });
}, 60); 
    </script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.department-filter');
    const classCards = document.querySelectorAll('.class-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const selectedDepartment = this.getAttribute('data-department');
            
            // Update active button state
            filterButtons.forEach(btn => btn.classList.remove('btn-primary'));
            filterButtons.forEach(btn => btn.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            // Filter cards
            classCards.forEach(card => {
                const cardDepartment = card.getAttribute('data-department');
                if (selectedDepartment === 'all' || selectedDepartment === cardDepartment) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>
</body>
</html>
