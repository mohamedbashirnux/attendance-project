<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendanceproject";

// Retrieve data from GET parameters
if (isset($_GET['department_name']) && isset($_GET['class_name']) && isset($_GET['study_mode']) && isset($_GET['faculty'])) {
    $departmentName = $_GET['department_name'];
    $className = $_GET['class_name'];
    $studyMode = $_GET['study_mode'];
    $faculty = $_GET['faculty'];
} else {
    // Redirect if parameters are missing
    header("Location: selection_class.php");
    exit();
}

// Fetch data from the allocate_teacher_subject table
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT tid, teacher_name, department_name, class_name, study_mode, subject_name, faculty_name 
            FROM allocate_teacher_subject 
            WHERE department_name = ? AND class_name = ? AND study_mode = ? AND faculty_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $departmentName, $className, $studyMode, $faculty);
    $stmt->execute();
    $result = $stmt->get_result();
    $allocations = array();
    while ($row = $result->fetch_assoc()) {
        $allocations[] = $row;
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Class Subjects</title>
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
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <!-- Toasts for various actions -->
        <!-- Add your existing toast notifications here -->
    </div>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                            <a href="teacher_subject_page.php" class="btn btn-secondary me-3">Back</a>
                            <h4 class="fw-bold m-0">Selected Class Allocated Teachers Details</h4>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Department Name:</strong> <?php echo htmlspecialchars($departmentName); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Class Name:</strong> <?php echo htmlspecialchars($className); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Study Mode:</strong> <?php echo htmlspecialchars($studyMode); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Faculty Name:</strong> <?php echo htmlspecialchars($faculty); ?>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title">Allocated Teachers</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>tid</th>
                                                <th>Teacher Name</th>
                                                <th>Department</th>
                                                <th>Class</th>
                                                <th>Study Mode</th>
                                                <th>Subject Name</th>
                                                <th>Faculty Name</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($allocations)): ?>
                                                <?php foreach ($allocations as $allocation): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($allocation['tid']); ?></td>
                                                        <td><?php echo htmlspecialchars($allocation['teacher_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($allocation['department_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($allocation['class_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($allocation['study_mode']); ?></td>
                                                        <td><?php echo htmlspecialchars($allocation['subject_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($allocation['faculty_name']); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary editBtn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editSubjectModal"
                                                                    data-tid="<?php echo htmlspecialchars($allocation['tid']); ?>"
                                                                    data-teacher="<?php echo htmlspecialchars($allocation['teacher_name']); ?>"
                                                                    data-department="<?php echo htmlspecialchars($allocation['department_name']); ?>"
                                                                    data-class="<?php echo htmlspecialchars($allocation['class_name']); ?>"
                                                                    data-study-mode="<?php echo htmlspecialchars($allocation['study_mode']); ?>"
                                                                    data-subject="<?php echo htmlspecialchars($allocation['subject_name']); ?>"
                                                                    data-faculty="<?php echo htmlspecialchars($allocation['faculty_name']); ?>">
                                                                Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-danger deleteBtn" data-tid="<?php echo htmlspecialchars($allocation['tid']); ?>">Delete</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No allocated teachers found for this class.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Subject Modal -->
                        <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form id="editAllocationForm">
                                        <div class="modal-body">
                                            <div class="row">
                                                <!-- Teacher ID (Read-Only) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editTid" class="form-label">Teacher ID</label>
                                                    <input type="text" class="form-control" id="editTid" name="tid" readonly>
                                                </div>

                                                <!-- Teacher Name (Read-Only) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editTeacherName" class="form-label">Teacher Name</label>
                                                    <input type="text" class="form-control" id="editTeacherName" name="teacherName" readonly>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Department (Editable Dropdown) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editDepartment" class="form-label">Department</label>
                                                    <select class="form-select" id="editDepartment" name="department">
                                                        <!-- Options will be populated dynamically using JS -->
                                                    </select>
                                                </div>

                                                <!-- Class (Editable Dropdown) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editClass" class="form-label">Class</label>
                                                    <select class="form-select" id="editClass" name="class">
                                                        <!-- Options will be populated dynamically using JS -->
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Study Mode (Read-Only) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editStudyMode" class="form-label">Study Mode</label>
                                                    <input type="text" class="form-control" id="editStudyMode" name="studyMode" readonly>
                                                </div>

                                                <!-- Subject (Editable Dropdown) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editSubject" class="form-label">Subject</label>
                                                    <select class="form-select" id="editSubject" name="subject">
                                                        <!-- Options will be populated dynamically using JS -->
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Faculty (Read-Only) -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="editFaculty" class="form-label">Faculty</label>
                                                    <input type="text" class="form-control" id="editFaculty" name="faculty" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php include 'footer.php'; ?>

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script>

    <script>
$(document).ready(function() {
    // Handle Edit button click
    $('.editBtn').on('click', function() {
        const modal = $('#editSubjectModal');
        const tid = $(this).data('tid');
        const teacherName = $(this).data('teacher');
        const departmentName = $(this).data('department');
        const className = $(this).data('class');
        const studyMode = $(this).data('study-mode');
        const subjectName = $(this).data('subject');
        const facultyName = $(this).data('faculty');
        
        // Set values in the modal form
        modal.find('#editTid').val(tid);
        modal.find('#editTeacherName').val(teacherName);
        modal.find('#editFacultyName').val(facultyName);
        
        // Fetch and populate dropdown options
        populateDepartments(departmentName, className, studyMode, subjectName);
    });

    // Fetch and populate departments
    function populateDepartments(selectedDepartment = null, selectedClass = null, selectedStudyMode = null, selectedSubject = null) {
        console.log('Fetching departments...');
        $.ajax({
            url: '../Database_users/subject/fetch_departments.php',
            type: 'GET',
            success: function(data) {
                console.log('Departments fetched:', data);
                $('#editDepartment').html(data);
                if (selectedDepartment) {
                    $('#editDepartment').val(selectedDepartment);
                    fetchClasses(selectedDepartment, selectedClass, selectedStudyMode, selectedSubject);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching departments:', textStatus, errorThrown);
            }
        });
    }

    // Fetch classes based on selected department
    function fetchClasses(department, selectedClass = null, selectedStudyMode = null, selectedSubject = null) {
        console.log('Fetching classes for department:', department);
        $.ajax({
            url: '../Database_users/teacher/fetch_classes.php',
            type: 'GET',
            data: { department_name: department },
            success: function(data) {
                console.log('Classes fetched:', data);
                $('#editClass').html(data);
                if (selectedClass) {
                    $('#editClass').val(selectedClass);
                    fetchStudyModes(selectedClass, selectedStudyMode, selectedSubject);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching classes:', textStatus, errorThrown);
            }
        });
    }

    // Fetch study modes based on selected class
    function fetchStudyModes(className, selectedStudyMode = null, selectedSubject = null) {
        console.log('Fetching study modes for class:', className);
        $.ajax({
            url: '../Database_users/allocate_update_teaher/fetch_study_mode.php',
            type: 'GET',
            data: { class_name: className },
            success: function(data) {
                console.log('Study modes fetched:', data);
                $('#editStudyMode').html(data);
                if (selectedStudyMode) {
                    $('#editStudyMode').val(selectedStudyMode);
                }
                fetchSubjects(className, selectedSubject);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching study modes:', textStatus, errorThrown);
            }
        });
    }

    // Fetch subjects based on selected class and department
function fetchSubjects(className, selectedSubject = null) {
    const department = $('#editDepartment').val(); // Get selected department
    console.log('Fetching subjects for class:', className, 'and department:', department);
    $.ajax({
        url: '../Database_users/subject/fetch_subjects.php', // Updated file name
        type: 'GET',
        data: { class_name: className, department_name: department },
        success: function(data) {
            console.log('Subjects fetched:', data);
            $('#editSubjectName').html(data);
            if (selectedSubject) {
                $('#editSubjectName').val(selectedSubject);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error fetching subjects:', textStatus, errorThrown);
        }
    });
}


    // Handle department change
    $('#editDepartment').on('change', function() {
        const department = $(this).val();
        fetchClasses(department);
    });

    // Handle class change
    $('#editClass').on('change', function() {
        const className = $(this).val();
        fetchStudyModes(className);
    });

    // Handle study mode change
    $('#editStudyMode').on('change', function() {
        const className = $('#editClass').val();
        fetchSubjects(className);
    });

    // Handle form submission for updating the record
    $('#editAllocationForm').on('submit', function(event) {
        event.preventDefault();

        $.ajax({
            url: '../Database_users/subject/update_allocation.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                console.log('Update response:', response);
                if (response.success) {
                    showToast('Subject updated successfully!', 'success');
                    location.reload();
                } else {
                    showToast('Error updating subject.', 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error updating subject:', textStatus, errorThrown);
            }
        });
    });

    // Handle delete button click
    $('.deleteBtn').on('click', function() {
        const tid = $(this).data('tid');
        if (confirm('Are you sure you want to delete this record?')) {
            $.ajax({
                url: '../Database_users/subject/delete_allocation.php',
                type: 'POST',
                data: { tid: tid },
                success: function(response) {
                    console.log('Delete response:', response);
                    if (response.success) {
                        showToast('Subject deleted successfully!', 'success');
                        location.reload();
                    } else {
                        showToast('Error deleting subject.', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error deleting subject:', textStatus, errorThrown);
                }
            });
        }
    });

    // Show toast notifications
    function showToast(message, type) {
        $('.toast-container').append(`
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);
        $('.toast').toast('show');
    }
});
</script>

</body>
</html>
