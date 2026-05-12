<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

include "../connection/connect.php";

// Retrieve data from GET parameters
$department_id = $_GET['department_id'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Validate required parameters
if (empty($department_id) || empty($class_id)) {
    header("Location: time_table.php");
    exit();
}

// Get department and class information
$departmentName = '';
$className = '';
$studyMode = '';
$semester = '';
$academic = '';

try {
    // Get department name
    $dept_sql = "SELECT department_name FROM departments WHERE id = ? AND faculty_id = ?";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->execute([$department_id, $faculty_id]);
    $dept_result = $dept_stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept_result) {
        $departmentName = $dept_result['department_name'];
    }

    // Get class information
    $class_sql = "SELECT class_name, study_mode, semester, academic_year FROM classes WHERE id = ? AND faculty_id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id, $faculty_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        header("Location: time_table.php");
        exit();
    }
    
    $className = $class_info['class_name'];
    $studyMode = $class_info['study_mode'];
    $semester = $class_info['semester'];
    $academic = $class_info['academic_year'];

    // Check if timetable table has the new structure (class_id and allocation_id columns)
    $check_columns_sql = "SHOW COLUMNS FROM timetable LIKE 'class_id'";
    $check_stmt = $conn->query($check_columns_sql);
    $has_new_structure = ($check_stmt->rowCount() > 0);

    if ($has_new_structure) {
        // Use new structure with JOINs
        $sql = "SELECT tt.id, d.department_name, c.class_name, c.study_mode, c.semester, c.academic_year,
                       f.faculty_name, t.full_name as teacher_name, s.subject_name, 
                       tt.day_of_week, tt.time_start, tt.time_end, tt.location_hall
                FROM timetable tt
                JOIN classes c ON tt.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN faculty f ON c.faculty_id = f.id
                JOIN teacher_subject_allocation tsa ON tt.allocation_id = tsa.id
                JOIN teachers t ON tsa.teacher_id = t.id
                JOIN subjects s ON tsa.subject_id = s.id
                WHERE tt.class_id = :class_id AND c.faculty_id = :faculty_id
                ORDER BY FIELD(tt.day_of_week, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), tt.time_start ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
    } else {
        // Use old structure with text fields (fallback for backward compatibility)
        $sql = "SELECT id, department_name, class_name, study_mode, semester, academic_year, 
                       faculty_name, teacher_name, subject_name, day_of_week, time_start, time_end, location_hall
                FROM timetable 
                WHERE department_name = :department_name 
                  AND class_name = :class_name 
                  AND study_mode = :study_mode 
                  AND faculty_name = :faculty_name
                ORDER BY FIELD(day_of_week, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), time_start ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':department_name', $departmentName);
        $stmt->bindParam(':class_name', $className);
        $stmt->bindParam(':study_mode', $studyMode);
        $stmt->bindParam(':faculty_name', $faculty);
    }

    // Execute the statement
    $stmt->execute();

    // Fetch results
    $timetables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
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
                Timetable entry added successfull!
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
                            <a href="time_table.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
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
                    <!-- Subject Selection -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="subjectName" class="form-label">Subject Name</label>
                            <select class="form-select" id="subjectName" name="subjectName" required>
                                <option value="">Select Subject</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <!-- Teacher (Auto-filled) and Day -->
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

                    <!-- Time Start and Time End -->
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

                    <!-- Location Hall -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="locationHall" class="form-label">Location/Hall</label>
                            <input type="text" class="form-control" id="locationHall" name="locationHall" placeholder="e.g., Room 101, Lab A" required>
                        </div>
                    </div>

                    <!-- Hidden fields -->
                    <input type="hidden" name="faculty" value="<?php echo htmlspecialchars($faculty); ?>">
                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                    <input type="hidden" name="allocation_id" id="allocationId">
                    <input type="hidden" name="departmentName" value="<?php echo htmlspecialchars($departmentName); ?>">
                    <input type="hidden" name="className" value="<?php echo htmlspecialchars($className); ?>">
                    <input type="hidden" name="studyMode" value="<?php echo htmlspecialchars($studyMode); ?>">
                    <input type="hidden" name="semester" value="<?php echo htmlspecialchars($semester); ?>">
                    <input type="hidden" name="academic" value="<?php echo htmlspecialchars($academic); ?>">
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
        
        // Validate that allocation_id is set
        var allocationId = $('#allocationId').val();
        if (!allocationId) {
            alert('Please select a subject first.');
            return;
        }
        
        $.ajax({
            url: '../Database_users/assing_time_table/add_time_table.php', // URL to the PHP script
            type: 'POST',
            data: $(this).serialize(), // Serialize form data
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true).text('Adding...'); // Disable the submit button to prevent multiple submissions
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false).text('Add Timetable Entry'); // Re-enable the submit button
                if (response.success) {
                    $('#addTimetableForm')[0].reset(); // Reset the form
                    $('#addTimetableModal').modal('hide'); // Hide the modal
                    addSuccessToast.show(); // Show success toast

                    // Refresh the page to show the updated list
                    setTimeout(function() {
                        window.location.reload(); // Reload the page after a short delay
                    }, 1000); // Delay for toast to appear before refreshing
                } else {
                    // Handle error messages - show the actual error from server
                    alert(response.message || 'An error occurred while adding the timetable entry.');
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false).text('Add Timetable Entry');
                console.error("AJAX Error: " + status + ' - ' + error); // Log error to console
                console.error("Response: " + xhr.responseText); // Log response text
                
                // Try to parse error response
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    alert(errorResponse.message || "An error occurred while adding the timetable entry. Please try again.");
                } catch(e) {
                    alert("An error occurred while adding the timetable entry. Please check the console for details.");
                }
            }
        });
    });

    // Add modal show event handler to populate subjects
    $('#addTimetableModal').on('show.bs.modal', function() {
        // Get the current class ID
        var classId = '<?php echo $class_id; ?>';
        
        // Load subjects for this specific class
        loadSubjects(classId);
    });

    // Load subjects function for the selected class from teacher_subject_allocation table
    function loadSubjects(classId) {
        $.ajax({
            url: '../Database_users/assing_time_table/fetch_subjects_for_class.php',
            type: 'GET',
            data: {
                class_id: classId
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
    $('#subjectName').change(function() {
        var selectedOption = $(this).find('option:selected');
        var teacherName = selectedOption.data('teacher');
        var startTime = selectedOption.data('start-time');
        var endTime = selectedOption.data('end-time');
        var allocationId = selectedOption.data('allocation-id');
        
        if (teacherName && startTime && endTime) {
            $('#teacherName').val(teacherName);
            $('#timeStart').val(startTime);
            $('#timeEnd').val(endTime);
            $('#allocationId').val(allocationId);
        } else {
            $('#teacherName').val('');
            $('#timeStart').val('');
            $('#timeEnd').val('');
            $('#allocationId').val('');
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
