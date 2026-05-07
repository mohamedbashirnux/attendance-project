<?php
date_default_timezone_set('Africa/Mogadishu');
include 'seassion_super-admin.php';
include '../connection/connect.php';

$class_id     = $_GET['class_id']     ?? '';
$department_id = $_GET['department_id'] ?? '';
$faculty_id   = $_GET['faculty_id']   ?? '';
$search_term  = $_GET['search_student_id'] ?? '';

if (empty($class_id) || empty($department_id)) {
    header("Location: selection_absents.php");
    exit();
}

try {
    // Get class info — no faculty restriction for super admin
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

    if (!$class_info) {
        header("Location: selection_absents.php");
        exit();
    }

    $faculty_name = $class_info['faculty_name'];

    // Get absences data — same query as Account_users/absents.php
    $attendance_sql = "SELECT
        s.student_id as student_varchar_id,
        COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) as student_name,
        COALESCE(subj.subject_name, 'Unknown Subject') as subject_name,
        COUNT(*) as absent_count,
        COALESCE((
            SELECT COUNT(*)
            FROM attendance_sessions ats
            WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
        ), 0) as total_sessions,
        CASE
            WHEN COALESCE((
                SELECT COUNT(*)
                FROM attendance_sessions ats
                WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
            ), 0) > 0 THEN
                ROUND(((COALESCE((
                    SELECT COUNT(*)
                    FROM attendance_sessions ats
                    WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
                ), 0) - COUNT(*)) * 100.0) / COALESCE((
                    SELECT COUNT(*)
                    FROM attendance_sessions ats
                    WHERE ats.subject_class_id = sc.id AND ats.class_id = a.class_id
                ), 1), 2)
            ELSE 100.00
        END as attendance_percentage
    FROM absences a
    JOIN students s ON a.student_id = s.id
    LEFT JOIN subject_class sc ON a.subject_class_id = sc.id
    LEFT JOIN subjects subj ON sc.subject_id = subj.id
    WHERE a.class_id = ?";

    $params = [$class_id];

    if (!empty($search_term)) {
        $attendance_sql .= " AND (a.student_id LIKE ? OR s.full_name LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $attendance_sql .= " GROUP BY s.student_id, a.subject_class_id, subj.subject_name, sc.id
                         HAVING absent_count > 0
                         ORDER BY subj.subject_name ASC, COALESCE(s.full_name, CONCAT('Student ID: ', s.student_id)) ASC";

    $attendance_stmt = $conn->prepare($attendance_sql);
    $attendance_stmt->execute($params);
    $results = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Absent Details</title>
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
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
    </style>
</head>
<body>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">Action completed successfully!</div>
    </div>
    <div id="errorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <strong class="me-auto">Error</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">An error occurred. Please try again.</div>
    </div>
</div>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php include 'menu.php'; ?>
        <div class="layout-page">
            <?php include 'navbar.php'; ?>
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

                    <!-- Back + Title -->
                    <div class="d-flex align-items-center mb-4">
                        <a href="selection_absents.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                        <h4 class="fw-bold m-0">Absent Details</h4>
                    </div>

                    <!-- Class Info -->
                    <div class="d-flex card-body bg-white mb-2">
                        <div class="d-flex flex-column bg-white p-2 m-2">
                            <div><strong>Class Name:</strong> <?php echo htmlspecialchars($class_info['class_name'] . ' (' . $class_info['study_mode'] . ')'); ?></div>
                            <div><strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?></div>
                            <div><strong>Academic Year:</strong> <?php echo htmlspecialchars($class_info['academic_year']); ?></div>
                            <div><strong>Department Name:</strong> <?php echo htmlspecialchars($class_info['department_name']); ?></div>
                            <div><strong>Faculty Name:</strong> <?php echo htmlspecialchars($faculty_name); ?></div>
                        </div>
                    </div>

                    <!-- Table Card -->
                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <!-- Search -->
                                <div class="d-flex align-items-center">
                                    <input type="text" class="form-control me-2" id="searchStudentId" placeholder="Search student by ID or name..." style="width: 300px;">
                                    <button type="button" class="btn btn-primary"><i class='bx bx-search-alt-2'></i></button>
                                </div>
                                <!-- Download Reports -->
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class='bx bx-download'></i> Reports
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="download_absents_pdf.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>">Class Report</a></li>
                                        <li><a class="dropdown-item" href="re-exam_report.php?class_id=<?php echo urlencode($class_id); ?>&department_id=<?php echo urlencode($department_id); ?>&faculty_id=<?php echo urlencode($faculty_id); ?>">Exam Report</a></li>
                                        <li><a class="dropdown-item" href="#" id="subjectReportLink">Subject Report</a></li>
                                        <li><a class="dropdown-item" href="#" id="neverAttendedLink">Never Attended Report</a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Subject Name</th>
                                            <th>Absent Sessions</th>
                                            <th>Total Sessions</th>
                                            <th>Attendance %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentTableBody">
                                    <?php if (!empty($results)): ?>
                                        <?php foreach ($results as $student):
                                            $attendance_percentage = floatval($student['attendance_percentage']);
                                            $badge_color = 'danger';
                                            if ($attendance_percentage >= 75) $badge_color = 'success';
                                            elseif ($attendance_percentage >= 50) $badge_color = 'warning';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_varchar_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['subject_name']); ?></td>
                                            <td><span class="badge bg-danger"><?php echo intval($student['absent_count']); ?></span></td>
                                            <td><span class="badge bg-info"><?php echo intval($student['total_sessions']); ?></span></td>
                                            <td><span class="badge bg-<?php echo $badge_color; ?>"><?php echo htmlspecialchars($student['attendance_percentage']); ?>%</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center">No absence data found.</td></tr>
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

<!-- Subject Report Modal -->
<div class="modal fade" id="subjectReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subject Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Select a subject to generate the report:</p>
                <select id="subjectSelectReport" class="form-control">
                    <option value="">Select Subject</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmSubjectReport" class="btn btn-primary">Generate Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Never Attended Modal -->
<div class="modal fade" id="neverAttendedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Never Attended Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Select a subject to see students who never attended:</p>
                <select id="subjectSelectNeverAttended" class="form-control">
                    <option value="">Select Subject</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmNeverAttended" class="btn btn-danger">Generate Report</a>
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
<script>
const classId     = <?php echo json_encode($class_id); ?>;
const departmentId = <?php echo json_encode($department_id); ?>;
const facultyId   = <?php echo json_encode($faculty_id); ?>;

// Live search
document.getElementById('searchStudentId').addEventListener('input', function () {
    const val = this.value;
    const params = new URLSearchParams(window.location.search);
    params.set('search_student_id', val);
    window.history.replaceState({}, '', window.location.pathname + '?' + params.toString());

    const xhr = new XMLHttpRequest();
    xhr.open('GET', window.location.pathname + '?' + params.toString(), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const doc = new DOMParser().parseFromString(xhr.responseText, 'text/html');
            document.getElementById('studentTableBody').innerHTML = doc.getElementById('studentTableBody').innerHTML;
        }
    };
    xhr.send();
});

// Load subjects helper
function loadSubjects(selectId, callback) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">Loading subjects...</option>';
    sel.disabled = true;

    fetch('../Database/super_admin/report_absents/get_class_subjects.php?class_id=' + classId)
        .then(r => r.json())
        .then(data => {
            sel.disabled = false;
            sel.innerHTML = '<option value="">Select Subject</option>';
            if (data.success && data.subjects && data.subjects.length > 0) {
                data.subjects.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.subject_name;
                    opt.textContent = s.subject_name;
                    sel.appendChild(opt);
                });
            } else {
                sel.innerHTML = '<option value="">No subjects found</option>';
            }
            if (callback) callback();
        })
        .catch(() => {
            sel.disabled = false;
            sel.innerHTML = '<option value="">Error loading subjects</option>';
        });
}

// Subject Report
document.getElementById('subjectReportLink').addEventListener('click', function (e) {
    e.preventDefault();
    loadSubjects('subjectSelectReport');
    new bootstrap.Modal(document.getElementById('subjectReportModal')).show();

    document.getElementById('confirmSubjectReport').onclick = function () {
        const subj = document.getElementById('subjectSelectReport').value;
        if (subj && subj !== 'No subjects found' && subj !== 'Error loading subjects') {
            window.location.href = `singlesubject_report.php?class_id=${classId}&department_id=${departmentId}&faculty_id=${facultyId}&subject_name=${encodeURIComponent(subj)}`;
        } else {
            alert('Please select a valid subject.');
        }
    };
});

// Never Attended Report
document.getElementById('neverAttendedLink').addEventListener('click', function (e) {
    e.preventDefault();
    loadSubjects('subjectSelectNeverAttended');
    new bootstrap.Modal(document.getElementById('neverAttendedModal')).show();

    document.getElementById('confirmNeverAttended').onclick = function () {
        const subj = document.getElementById('subjectSelectNeverAttended').value;
        if (subj && subj !== 'No subjects found' && subj !== 'Error loading subjects') {
            window.location.href = `never_attended_report.php?class_id=${classId}&department_id=${departmentId}&faculty_id=${facultyId}&subject_name=${encodeURIComponent(subj)}`;
        } else {
            alert('Please select a valid subject.');
        }
    };
});
</script>
</body>
</html>
