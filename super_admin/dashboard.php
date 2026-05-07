<?php
include 'seassion_super-admin.php';
include '../connection/connect.php';

// Stats
$totalStudents  = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalClasses   = $conn->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalTeachers  = $conn->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalFaculties = $conn->query("SELECT COUNT(*) FROM faculty")->fetchColumn();

// Get all faculties
$faculties = $conn->query("SELECT id, faculty_name FROM faculty ORDER BY faculty_name")->fetchAll(PDO::FETCH_ASSOC);

// Build class data for ALL faculties
$classAbsenceData = [];

foreach ($faculties as $fac) {
    $fac_id   = $fac['id'];
    $fac_name = $fac['faculty_name'];

    $stmtC = $conn->prepare("
        SELECT c.id AS class_id, c.class_name, c.study_mode, c.semester, c.academic_year,
               d.id AS department_id, d.department_name
        FROM classes c
        JOIN departments d ON c.department_id = d.id
        WHERE c.faculty_id = :fid
    ");
    $stmtC->execute([':fid' => $fac_id]);
    $classes = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    foreach ($classes as $row) {
        $class_id = $row['class_id'];

        $stmtTotal = $conn->prepare("SELECT COUNT(id) AS total_students FROM students WHERE class_id = :class_id");
        $stmtTotal->bindParam(':class_id', $class_id);
        $stmtTotal->execute();
        $total_students = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_students'];

        $stmtAbsent = $conn->prepare("
            SELECT COUNT(DISTINCT a.student_id) AS absent_students
            FROM absences a
            JOIN students s ON a.student_id = s.id
            WHERE DATE(a.absence_date) = CURDATE()
            AND s.class_id = :class_id
        ");
        $stmtAbsent->bindParam(':class_id', $class_id);
        $stmtAbsent->execute();
        $absent_students = $stmtAbsent->fetch(PDO::FETCH_ASSOC)['absent_students'];

        $absent_rate = ($total_students > 0) ? ($absent_students / $total_students) * 100 : 0;

        $classAbsenceData[] = [
            'faculty_id'      => $fac_id,
            'faculty_name'    => $fac_name,
            'department_id'   => $row['department_id'],
            'department_name' => $row['department_name'],
            'class_id'        => $class_id,
            'class_name'      => $row['class_name'],
            'study_mode'      => $row['study_mode'],
            'semester'        => $row['semester'],
            'academic_year'   => $row['academic_year'],
            'total_students'  => $total_students,
            'absent_students' => $absent_students,
            'absent_rate'     => round($absent_rate, 2),
        ];
    }
}

// Get all unique departments across all faculties (for initial "all" state)
$allDepartments = $conn->query("SELECT DISTINCT department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_COLUMN);
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
    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
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

                        <!-- Stats Cards -->
                        <div class="row mb-2">
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

                        <h4 class="fw-bold py-3 px-2 mb-2 mt-3 badge bg-label-primary rounded-pill">Daily absence rate:</h4>

                        <!-- Faculty Filter -->
                        <div class="mb-2">
                            <button class="btn btn-primary me-2 mb-2 faculty-filter" data-faculty="all">All Faculties</button>
                            <?php foreach ($faculties as $fac): ?>
                                <button class="btn btn-outline-primary me-2 mb-2 faculty-filter" data-faculty="<?php echo $fac['id']; ?>">
                                    <?php echo htmlspecialchars($fac['faculty_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Department Filter -->
                        <div class="mb-3" id="deptFilterWrap">
                            <button class="btn btn-primary me-2 mb-2 department-filter" data-department="all">All Departments</button>
                            <?php foreach ($allDepartments as $dept): ?>
                                <button class="btn btn-outline-primary me-2 mb-2 department-filter" data-department="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Class Cards -->
                        <div class="row mt-2" id="classCards">
                            <?php foreach ($classAbsenceData as $classData): ?>
                                <div class="col-md-4 col-lg-3 col-xl-3 order-0 mb-3 class-card"
                                     data-department="<?php echo htmlspecialchars($classData['department_name']); ?>"
                                     data-faculty="<?php echo $classData['faculty_id']; ?>">
                                    <div class="card h-100">
                                        <div class="card-header d-flex align-items-start justify-content-between pb-0 text-white">
                                            <div class="card-title mb-0" style="min-width:0; flex:1;">
                                                <div class="m-0 me-2 mb-2 text-black text-capitalize fw-semibold" style="word-break:break-word; white-space:normal; line-height:1.4;"><?php echo htmlspecialchars($classData['class_name'] . ' (' . $classData['study_mode'] . ')'); ?></div>
                                                <div class="m-0 me-2 mb-2 text-capitalize" style="color:#696cff; font-size:0.85rem; word-break:break-word; white-space:normal;"><?php echo htmlspecialchars($classData['department_name']); ?></div>
                                                <div class="m-0 me-2 mb-3">
                                                    <small style="color:#03c3ec; font-size:0.75rem;">&#9679; <?php echo htmlspecialchars($classData['faculty_name']); ?></small>
                                                </div>
                                            </div>
                                            <div class="dropdown flex-shrink-0 ms-1">
                                                <button class="btn p-0 text-black" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end text-black">
                                                    <a class="dropdown-item text-black" href="dashboard.php">Refresh</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="d-flex flex-column align-items-center gap-1">
                                                    <h3 class="mb-2 text-primary"><?php echo $classData['absent_rate']; ?>%</h3>
                                                    <span class="text-muted">Absent Rate</span>
                                                </div>
                                                <div>
                                                    <small class="badge bg-label-warning rounded-pill">Absent Students: <strong><?php echo $classData['absent_students']; ?></strong></small>
                                                </div>
                                            </div>
                                            <div class="progress mb-3" style="height: 8px;">
                                                <div class="progress-bar bg-danger" role="progressbar"
                                                     style="width: <?php echo $classData['absent_rate']; ?>%;"
                                                     aria-valuenow="<?php echo $classData['absent_rate']; ?>"
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <ul class="p-0 m-0">
                                                <li class="d-flex mb-4 pb-1">
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                        <div class="me-2">
                                                            <h6 class="mb-0">Total Students</h6>
                                                            <small class="badge bg-label-primary rounded-pill"><?php echo $classData['total_students']; ?></small>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- No results -->
                        <div id="noResults" class="text-center py-5" style="display:none;">
                            <i class="bx bx-search bx-lg text-muted"></i>
                            <p class="text-muted mt-2">No classes found for the selected filters.</p>
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
    <script async defer src="https://buttons.github.io/buttons.js"></script>

    <script>
    // Build faculty -> departments map from cards
    const facultyDeptMap = {};
    document.querySelectorAll('.class-card').forEach(card => {
        const fid  = card.dataset.faculty;
        const dept = card.dataset.department;
        if (!facultyDeptMap[fid]) facultyDeptMap[fid] = new Set();
        facultyDeptMap[fid].add(dept);
    });

    let activeFaculty = 'all';
    let activeDept    = 'all';

    // Rebuild department buttons based on selected faculty
    function renderDeptButtons(facultyId) {
        const wrap = document.getElementById('deptFilterWrap');
        wrap.innerHTML = '';

        const allBtn = document.createElement('button');
        allBtn.className = 'btn btn-primary me-2 mb-2 department-filter';
        allBtn.dataset.department = 'all';
        allBtn.textContent = 'All Departments';
        wrap.appendChild(allBtn);

        let depts = new Set();
        if (facultyId === 'all') {
            document.querySelectorAll('.class-card').forEach(c => depts.add(c.dataset.department));
        } else {
            if (facultyDeptMap[facultyId]) facultyDeptMap[facultyId].forEach(d => depts.add(d));
        }

        [...depts].sort().forEach(dept => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-outline-primary me-2 mb-2 department-filter';
            btn.dataset.department = dept;
            btn.textContent = dept;
            wrap.appendChild(btn);
        });

        attachDeptEvents();
    }

    function filterCards() {
        const cards = document.querySelectorAll('.class-card');
        let visible = 0;
        cards.forEach(card => {
            const facMatch  = activeFaculty === 'all' || card.dataset.faculty     === activeFaculty;
            const deptMatch = activeDept    === 'all' || card.dataset.department  === activeDept;
            if (facMatch && deptMatch) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });
        document.getElementById('noResults').style.display = visible === 0 ? '' : 'none';
    }

    function setActive(buttons, activeBtn) {
        buttons.forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-primary');
        });
        activeBtn.classList.remove('btn-outline-primary');
        activeBtn.classList.add('btn-primary');
    }

    // Faculty filter click
    document.querySelectorAll('.faculty-filter').forEach(btn => {
        btn.addEventListener('click', function () {
            activeFaculty = this.dataset.faculty;
            activeDept    = 'all';
            setActive(document.querySelectorAll('.faculty-filter'), this);
            renderDeptButtons(activeFaculty);
            filterCards();
        });
    });

    function attachDeptEvents() {
        document.querySelectorAll('.department-filter').forEach(btn => {
            btn.addEventListener('click', function () {
                activeDept = this.dataset.department;
                setActive(document.querySelectorAll('.department-filter'), this);
                filterCards();
            });
        });
    }

    // Init
    attachDeptEvents();
    </script>
</body>
</html>
