<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../connection/connect.php";

// Retrieve data from GET parameters
$departmentName = $_GET['department_name'] ?? '';
$className = $_GET['class_name'] ?? '';
$studyMode = $_GET['study_mode'] ?? '';
$faculty = $_GET['faculty'] ?? '';
$semester = ''; // Remove GET dependency
$academic = ''; // Remove GET dependency

// Validate required parameters
if (empty($departmentName) || empty($className) || empty($studyMode) || empty($faculty)) {
    header("Location: selection_class.php");
    exit();
}

try {
    // First, get semester and academic year from students table for this class
    $classInfoSql = "SELECT semester, academic FROM students 
                     WHERE department_name = :department_name 
                       AND class_name = :class_name 
                       AND study_mode = :study_mode 
                       AND faculty_name = :faculty_name 
                     LIMIT 1";
    
    $classInfoStmt = $conn->prepare($classInfoSql);
    $classInfoStmt->bindParam(':department_name', $departmentName);
    $classInfoStmt->bindParam(':class_name', $className);
    $classInfoStmt->bindParam(':study_mode', $studyMode);
    $classInfoStmt->bindParam(':faculty_name', $faculty);
    $classInfoStmt->execute();
    
    $classInfo = $classInfoStmt->fetch(PDO::FETCH_ASSOC);
    if ($classInfo) {
        $semester = $classInfo['semester'];
        $academic = $classInfo['academic'];
    }

    // Prepare SQL statement to get timetable entries
    $sql = "SELECT id, department_name, class_name, study_mode, semester, academic_year, 
                   faculty_name, teacher_name, subject_name, day_of_week, time_start, time_end, location_hall
            FROM timetable 
            WHERE department_name = :department_name 
              AND class_name = :class_name 
              AND study_mode = :study_mode 
              AND faculty_name = :faculty_name";

    // Add ORDER BY clause to sort the result by day_of_week and time_start
    $sql .= " ORDER BY FIELD(day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), time_start ASC";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':department_name', $departmentName);
    $stmt->bindParam(':class_name', $className);
    $stmt->bindParam(':study_mode', $studyMode);
    $stmt->bindParam(':faculty_name', $faculty);

    // Execute the statement
    $stmt->execute();

    // Fetch results
    $timetables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Close the connection (optional, since PDO closes automatically when script ends)
    $stmt = null;
    $conn = null;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Timetable Management</title>
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
        <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Timetable entry added successfully!
            </div>
        </div>
        <div id="deleteConfirmToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Confirm Delete</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Are you sure you want to delete this timetable entry?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>

        <div id="deleteSuccessToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Timetable entry deleted successfully!
            </div>
        </div>
    </div>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                            <a href="selection_student.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Timetable Management</h4>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-column">
                                    <div>
                                        <strong>Class Name:</strong> <?php echo $className .' ('.$studyMode. ')'; ?>
                                    </div>
                                    <div>
                                        <strong>Semester:</strong> <?php echo $semester; ?>
                                    </div>
                                    <div>
                                        <strong>Department Name:</strong> <?php echo $departmentName; ?>
                                    </div>
                                    <div>
                                        <strong>Academic Year:</strong> <?php echo $academic; ?>
                                    </div>
                                    <div>
                                        <strong>Faculty Name:</strong> <?php echo $faculty; ?>
                                    </div>
                                    <div>
                                        <strong>Total Number of Timetable Entries:</strong> <?php echo count($timetables); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTimetableModal">Add Timetable Entry</button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Subject</th>
                                                <th>Teacher</th>
                                                <th>Day</th>
                                                <th>Time</th>
                                                <th>Location</th>
                                                <th class="text-end">Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody id="timetableTableBody">
                                            <!-- Timetable data will be dynamically inserted here -->
                                            <?php
                                            if (!empty($timetables)) {
                                                $counter = 1; // Initialize the counter
                                                foreach ($timetables as $timetable) {
                                                    echo '<tr>';
                                                    // Use the counter to display the row number
                                                    echo '<td>' . $counter . '</td>';
                                                    echo '<td>' . htmlspecialchars($timetable['subject_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($timetable['teacher_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($timetable['day_of_week']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($timetable['time_start']) . ' - ' . htmlspecialchars($timetable['time_end']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($timetable['location_hall']) . '</td>';
                                                    echo '<td class="text-end">
                                                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $timetable['id'] . '">Delete</button>
                                                </td>';
                                                    echo '</tr>';
                                                    
                                                    $counter++; // Increment the counter
                                                }
                                            } else {
                                                echo '<tr><td colspan="7" class="text-center">No timetable entries available</td></tr>';
                                            }
                                            ?>
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

<!-- Add Timetable Modal -->
<div class="modal fade" id="addTimetableModal" tabindex="-1" role="dialog" aria-labelledby="addTimetableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTimetableModalLabel">Add Timetable Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTimetableForm" action="../Database_users/assing_time_table/add_time_table.php" method="POST">
                <div class="modal-body">
                    <!-- First Row: Subject Only -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="subjectName" class="form-label">Subject Name</label>
                            <select class="form-select" id="subjectName" name="subjectName" required>
                                <option value="">Select Subject</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <!-- Second Row: Teacher (Auto-filled) and Day -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="teacherName" class="form-label">Teacher Name</label>
                            <input type="text" class="form-control" id="teacherName" name="teacherName" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="dayOfWeek" class="form-label">Day of Week</label>
                            <select class="form-select" id="dayOfWeek" name="dayOfWeek" required>
                                <option value="">Select Day</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                               
                            </select>
                        </div>
                    </div>

                    <!-- Third Row: Time Start and Time End -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="timeStart" class="form-label">Time Start</label>
                            <input type="text" class="form-control" id="timeStart" name="timeStart" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="timeEnd" class="form-label">Time End</label>
                            <input type="text" class="form-control" id="timeEnd" name="timeEnd" readonly>
                        </div>
                    </div>

                    <!-- Row for Department Name and Class Name -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="departmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="departmentName" name="departmentName" value="<?php echo htmlspecialchars($departmentName); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="className" class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="className" name="className" value="<?php echo htmlspecialchars($className); ?>" readonly>
                        </div>
                    </div>

                    <!-- Row for Study Mode -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="studyMode" class="form-label">Study Mode</label>
                            <input type="text" class="form-control" id="studyMode" name="studyMode" value="<?php echo htmlspecialchars($studyMode); ?>" readonly>
                        </div>
                    </div>
                    
                    <!-- Row for Semester and Academic -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="semester" name="semester" 
                            value="<?php echo htmlspecialchars($semester); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="academic" class="form-label">Academic</label>
                            <input type="text" class="form-control" id="academic" name="academic" 
                            value="<?php echo htmlspecialchars($academic); ?>" readonly>
                        </div>
                    </div>

                    <!-- Row for Location Hall -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="locationHall" class="form-label">Location/Hall</label>
                            <input type="text" class="form-control" id="locationHall" name="locationHall" required>
                        </div>
                    </div>

                    <!-- Hidden fields for Faculty -->
                    <input type="hidden" name="faculty" value="<?php echo htmlspecialchars($faculty); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Timetable Entry</button>
                </div>
            </form>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize toasts
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));

    // Handle form submission for adding timetable entries
    $('#addTimetableForm').on('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        $.ajax({
            url: '../Database_users/assing_time_table/add_time_table.php', // URL to the PHP script
            type: 'POST',
            data: $(this).serialize(), // Serialize form data
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true); // Disable the submit button to prevent multiple submissions
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false); // Re-enable the submit button
                if (response.success) {
                    $('#addTimetableForm')[0].reset(); // Reset the form
                    $('#addTimetableModal').modal('hide'); // Hide the modal
                    addSuccessToast.show(); // Show success toast

                    // Refresh the page to show the updated list
                    setTimeout(function() {
                        window.location.reload(); // Reload the page after a short delay
                    }, 1000); // Delay for toast to appear before refreshing
                } else {
                    // Handle error messages
                    alert(response.message || 'An error occurred while adding the timetable entry.');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error); // Log error to console
                alert("An error occurred while adding the timetable entry. Please try again.");
            }
        });
    });

    // Add modal show event handler to populate subjects
    $('#addTimetableModal').on('show.bs.modal', function() {
        // Get the current class information from the page
        var departmentName = '<?php echo htmlspecialchars($departmentName); ?>';
        var className = '<?php echo htmlspecialchars($className); ?>';
        var studyMode = '<?php echo htmlspecialchars($studyMode); ?>';
        var faculty = '<?php echo htmlspecialchars($faculty); ?>';
        
        // Load subjects for this specific class
        loadSubjects(departmentName, className, studyMode, faculty);
    });

    // Load subjects function for the selected class from allocate_teacher_subject table
    function loadSubjects(departmentName, className, studyMode, faculty) {
        $.ajax({
            url: '../Database_users/allocate_update_teaher/fetch_subjects_for_class.php',
            type: 'GET',
            data: {
                department_name: departmentName,
                class_name: className,
                study_mode: studyMode,
                faculty_name: faculty
            },
            success: function(data) {
                var subjectOptions = '<option value="">Select Subject</option>' + data;
                $('#subjectName').html(subjectOptions);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading subjects:', textStatus, errorThrown);
                var errorOption = '<option value="">Error loading subjects</option>';
                $('#subjectName').html(errorOption);
            }
        });
    }

    // Auto-fill teacher and time fields when subject is selected
    function autoFillTeacherAndTime(subjectName) {
        var departmentName = '<?php echo htmlspecialchars($departmentName); ?>';
        var className = '<?php echo htmlspecialchars($className); ?>';
        var studyMode = '<?php echo htmlspecialchars($studyMode); ?>';
        var faculty = '<?php echo htmlspecialchars($faculty); ?>';
        
        if (!subjectName) return;
        
        $.ajax({
            url: '../Database_users/allocate_update_teaher/fetch_subject_allocation.php',
            type: 'GET',
            data: {
                subject_name: subjectName,
                department_name: departmentName,
                class_name: className,
                study_mode: studyMode,
                faculty_name: faculty
            },
            success: function(data) {
                try {
                    var allocation = JSON.parse(data);
                    if (allocation.success && allocation.data) {
                        var teacherName = allocation.data.teacher_name;
                        var startTime = allocation.data.start_time;
                        var endTime = allocation.data.end_time;
                        
                        $('#teacherName').val(teacherName);
                        $('#timeStart').val(startTime);
                        $('#timeEnd').val(endTime);
                    }
                } catch (e) {
                    console.error('Error parsing allocation data:', e);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching subject allocation:', textStatus, errorThrown);
            }
        });
    }

    // Auto-fill time fields when subject changes in Add Modal
    $('#subjectName').change(function() {
        var subjectName = $(this).val();
        if (subjectName) {
            autoFillTeacherAndTime(subjectName);
        } else {
            $('#teacherName').val('');
            $('#timeStart').val('');
            $('#timeEnd').val('');
        }
    });

    // Delete button click handler
    $(document).on('click', '.delete-btn', function() {
        var timetableId = $(this).data('id');

        $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete this timetable entry?</p>
            <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            <div class="mt-3">
                <button type="button" class="btn btn-danger me-2" id="confirmDelete">Delete Entry</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);

        // Show the confirmation toast
        deleteConfirmToast.show();
        
        $('#confirmDelete').one('click', function() {
            deleteConfirmToast.hide();
            $.ajax({
                url: '../Database_users/assing_time_table/delete_time_table.php',
                type: 'POST',
                data: { id: timetableId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#deleteSuccessToast').show(); // Show success toast
                        // Refresh the page or update the UI
                        setTimeout(function() {
                            window.location.reload();
                        }, 700);
                    } else {
                        // Handle error response
                        alert(response.message || 'An error occurred while deleting the timetable entry.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + ' - ' + error);
                    alert("An error occurred while deleting the timetable entry. Please try again.");
                }
            });
        });
    });
});

</script>
</script>

</body>
</html>
