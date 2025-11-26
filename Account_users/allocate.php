<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';



// Handle GET request to fetch class data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $departmentName = isset($_GET['department_name']) ? htmlspecialchars($_GET['department_name']) : '';
    $className = isset($_GET['class_name']) ? htmlspecialchars($_GET['class_name']) : '';
    $studyMode = isset($_GET['study_mode']) ? htmlspecialchars($_GET['study_mode']) : '';
    $facultyName = isset($_GET['faculty_name']) ? htmlspecialchars($_GET['faculty_name']) : '';
    $semester = isset($_GET['semester']) ? htmlspecialchars($_GET['semester']) : '';
    $classHidden = isset($_GET['classHidden']) ? htmlspecialchars($_GET['classHidden']) : '';

    if (empty($departmentName) || empty($className) || empty($studyMode) || empty($facultyName)) {
        header("Content-Type: application/json");
        echo json_encode(["error" => "Missing required parameters"]);
        exit();
    }

    // Save conditions in session variables
    $_SESSION['department_name'] = $departmentName;
    $_SESSION['class_name'] = $className;
    $_SESSION['study_mode'] = $studyMode;
    $_SESSION['faculty_name'] = $facultyName;
    $_SESSION['classHidden'] = $semester;
    $_SESSION['semester'] = $semester;


    include "../connection/connect.php";

    $sql = "SELECT *
            FROM allocate_teacher_subject 
            WHERE faculty_name = :faculty_name AND department_name = :department_name AND study_mode = :study_mode AND c_id = :class_name";
    $stmt = $conn->prepare($sql);
    
    // Use bindParam with the correct variable names
    $stmt->bindParam(':faculty_name', $facultyName);
    $stmt->bindParam(':department_name', $departmentName);
    $stmt->bindParam(':study_mode', $studyMode);
    $stmt->bindParam(':class_name', $className);
    
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['classes'] = $classes;
    $conn = null; // Close connection
}

// Check if classes data is available in the session
$classes = isset($_SESSION['classes']) ? $_SESSION['classes'] : [];
?>


<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Allocate management</title>
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
        .form-control, .form-select {
            cursor: pointer;
        }
    </style>
</head>
<body>
   <!-- Toast Notifications -->
<!-- Toast Notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Delete data successfully!
        </div>
    </div>
    <div id="warningToast" class="toast bg-warning text-dark" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-white">
            <strong class="me-auto">Warning</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Error
        </div>
    </div>
    <div id="updatetoster" class="toast bg-warning text-dark" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-white">
            <strong class="me-auto">update</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            successfully updated the time
        </div>
    </div>
    <div id="errorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <strong class="me-auto">Error</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            An error occurred. Please try again.
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
            Are you sure you want to delete this Teacher allocates
            <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Toast Notifications -->

   <!-- Toast Notifications -->

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <a href="selection_teacher.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
        <h4 class="fw-bold py-3 mb-4">Teacher in Class</h4>
        <div class="d-flex flex-column bg-white p-2 m-2">
                                    <div>
                                        <strong>Class Name:</strong> <?php echo $classHidden .' ('.$studyMode. ')'; ?>
                                    </div>
                                    <div>
                                        <strong>Semester :</strong> <?php echo $semester; ?>
                                    </div>
                                    
                                    <div>
                                    <strong>Departments Name:</strong> <?php echo $departmentName; ?>
                                    </div>
                                    <div>
                                    <strong>Faculty Name:</strong> <?php echo $faculty; ?>
                                    </div>
                           
              </div>
        <div class="card">
            
            <div class="card-body">
            <div class="d-flex justify-content-end align-items-center">
                    
                    <button id="refreshButton" class="btn btn-sm btn-outline-primary">
                        <i class='bx bx-refresh'></i>
                    </button>
                </div>
                
                </div>
                <div class="card">
                            <div class="card-body">
                <?php 
if (!empty($classes)) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover">';
    echo '<thead><tr><th>TID</th><th>Teacher Name</th><th>Subject Name</th><th>Start Time</th><th>End Time</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($classes as $class) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($class['tid']) . '</td>';
        echo '<td>' . htmlspecialchars($class['teacher_name']) . '</td>';
        echo '<td class="d-none">' . htmlspecialchars($class['department_name']) . '</td>';
        echo '<td class="d-none">' . htmlspecialchars($class['class_name']) . '</td>';
        echo '<td class="d-none">' . htmlspecialchars($class['study_mode']) . '</td>';
        echo '<td>' . htmlspecialchars($class['subject_name']) . '</td>';
        echo '<td>' . htmlspecialchars($class['start_time']) . '</td>';
        echo '<td>' . htmlspecialchars($class['end_time']) . '</td>';
        echo '<td><span role="button" class="badge status-toggle pe-auto ' . (htmlspecialchars($class['status']) === 'approved' ? 'bg-label-success' : 'bg-label-danger') . '">' . htmlspecialchars($class['status']) . '</span></td>';
        echo '<td>
                <button class="btn btn-sm btn-warning edit-btn d-none" data-id="' . htmlspecialchars($class['tid']) . '">Edit</button>
                <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item delete-btn" href="javascript:void(0);" data-id="' . htmlspecialchars($class['tid']) . '"><i class="bx bx-trash me-1"></i> Delete</a>
                        <a class="dropdown-item change-btn" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#timeModal" data-id="' . htmlspecialchars($class['tid']) . '" data-start_time="' . htmlspecialchars($class['start_time']) . '" data-end_time="' . htmlspecialchars($class['end_time']) . '">
                            <i class="bx bx-timer me-1"></i> Change Time
                        </a>
                    </div>
                </div>
              </td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
} else {
    echo '<div class="alert alert-warning" role="alert">No data found for the selected criteria.</div>';
}
?>

                            </div>
                </div>
        </div>
    </div>
    <div class="content-backdrop fade"></div>
</div>


<div class="modal fade" id="timeModal" tabindex="-1" aria-labelledby="timeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeModalLabel">Change Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" required />
                </div>
                <div class="">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" required />
                </div>
                <!-- Hidden fields for department, subject, study_mode, and class_name -->
                <input type="hidden" id="department_name" name="department_name">
                <input type="hidden" id="subject_name" name="subject_name">
                <input type="hidden" id="study_mode" name="study_mode">
                <input type="hidden" id="class_name" name="class_name">
                <input type="hidden" id="tid" name="tid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitTimeChange" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>



    <!--  Modal -->

<script src="../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../assets/vendor/libs/popper/popper.js"></script>
<script src="../assets/vendor/js/bootstrap.js"></script>
<script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../assets/vendor/js/menu.js"></script>
<script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
<script src="../assets/js/main.js"></script>
<script>
$(document).on('click', '.status-toggle', function() {
    var $statusBadge = $(this);
    var originalStatus = $statusBadge.text().trim(); // Store the original status
    var tid = $(this).closest('tr').find('.edit-btn').data('id');
    var subject = $(this).closest('tr').find('td:nth-child(6)').text();
    var className = $(this).closest('tr').find('td:nth-child(4)').text();
    var studyMode = $(this).closest('tr').find('td:nth-child(5)').text();
    var currentStatus = originalStatus.toLowerCase();

    // Get current time in 'HH:MM' format
    var now = new Date();
    var currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    console.log(currentTime)
    // Toggle status based on current status
    var newStatus = (currentStatus === 'pending') ? 'waiting' : (currentStatus === 'waiting' ? 'approved' : 'pending');

    // Update status to "waiting" initially
    $statusBadge.text("Waiting...").addClass('bg-label-warning');

    $.ajax({
        url: '../Database_users/allocate_update_teaher/pending.php',
        type: 'POST',
        data: { 
            action: 'update_status', 
            tid: tid, 
            status: newStatus, 
            subject: subject,
            class_name: className,
            study_mode: studyMode,
            current_time: currentTime // Pass the current time in HH:MM format
        }
    }).done(function(response) {
        if (response.status) {
            var updatedStatus = response.status.charAt(0).toUpperCase() + response.status.slice(1);
            $statusBadge.text(updatedStatus);

            // Toggle the status badge color based on the new status
            if (response.status === 'approved') {
                $statusBadge.removeClass('bg-label-warning bg-label-danger').addClass('bg-label-success');
            } else if (response.status === 'waiting') {
                $statusBadge.removeClass('bg-label-success bg-label-danger').addClass('bg-label-warning');
            } else {
                $statusBadge.removeClass('bg-label-success bg-label-warning').addClass('bg-label-danger');
            }
        } else {
            alert(response.error); // Show the error message
            $statusBadge.text(originalStatus); // Revert the text back to the original status
        }
    }).fail(function(xhr, status, error) {
        console.error("AJAX Error: " + status + ' - ' + error);
        alert("An error occurred while updating the status. Please try again.");
        $statusBadge.text(originalStatus); // Revert the text back to the original status
    });
});

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
}, 60); // Run every 60 seconds





    var tidToDelete, subjectToDelete, classNameToDelete, studyModeToDelete;

$(document).on('click', '.delete-btn', function() {
    tidToDelete = $(this).closest('tr').find('.edit-btn').data('id');
    subjectToDelete = $(this).closest('tr').find('td:nth-child(6)').text();
    classNameToDelete = $(this).closest('tr').find('td:nth-child(4)').text();
    studyModeToDelete = $(this).closest('tr').find('td:nth-child(5)').text();

    showToast('deleteConfirmToast');
});

$('#confirmDelete').on('click', function() {
    $.ajax({
        url: '../Database_users/allocate_update_teaher/delete_allocate_teacher.php',
        type: 'POST',
        data: { 
            tid: tidToDelete, 
            subject: subjectToDelete,
            class_name: classNameToDelete,
            study_mode: studyModeToDelete
        }
    }).done(function(response) {
        if (response.success) {
            showToast('addSuccessToast');
            setTimeout(function() {
                location.reload();
            }, 700);
        } else {
            showToast('warningToast');
        }
    }).fail(function(xhr, status, error) {
        console.error("AJAX Error: " + status + ' - ' + error);
        showToast('errorToast');
    });

    var deleteConfirmToastElement = document.getElementById('deleteConfirmToast');
    var deleteConfirmToast = new bootstrap.Toast(deleteConfirmToastElement);
    deleteConfirmToast.hide();
});

function showToast(toastId) {
    var toastElement = document.getElementById(toastId);
    var toast = new bootstrap.Toast(toastElement);
    toast.show();
}
document.getElementById('refreshButton').addEventListener('click', function() {
    location.reload(); // Refresh the current page
});




$(document).on('click', '.change-btn', function() {
    // Get values from the table row
    var tid = $(this).data('id');
    var startTime = $(this).data('start_time');
    var endTime = $(this).data('end_time');
    var departmentName = $(this).closest('tr').find('td:nth-child(3)').text();
    var className = $(this).closest('tr').find('td:nth-child(4)').text();
    var studyMode = $(this).closest('tr').find('td:nth-child(5)').text();
    var subjectName = $(this).closest('tr').find('td:nth-child(6)').text();
    
    // Set the values in the modal's hidden input fields
    $('#department_name').val(departmentName);
    $('#class_name').val(className);
    $('#study_mode').val(studyMode);
    $('#subject_name').val(subjectName);
    $('#start_time').val(startTime);
    $('#end_time').val(endTime);
    $('#tid').val(tid);
    
    // console.log(tid)
    // console.log(departmentName)
    // console.log(studyMode)
    // console.log(startTime)
    // console.log(endTime)
});

$('#submitTimeChange').on('click', function() {

var tid = $('#tid').val();
var startTime = $('#start_time').val();
var endTime = $('#end_time').val();
var departmentName = $('#department_name').val();
var className = $('#class_name').val();
var studyMode = $('#study_mode').val();
var subjectName = $('#subject_name').val();

$.ajax({
    url: '../Database_users/allocate_update_teaher/update_allocate_time.php',
    type: 'POST',
    dataType: 'json', // Expecting JSON from the server
    data: {
        tid: tid,
        start_time: startTime,
        end_time: endTime,
        department_name: departmentName,
        class_name: className,
        study_mode: studyMode,
        subject_name: subjectName
    }
}).done(function(response) {
    if (response.success) {
            showToast('updatetoster');
            setTimeout(function() {
                location.reload();
            }, 700);
        } else {
            showToast('errorToast');
        }
}).fail(function(xhr, status, error) {
    console.error("AJAX Error: " + status + ' - ' + error);
    alert("An error occurred while updating the time. Please try again.");
});
});
</script>


</body>
</html>