<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Include database connection
include "../connection/connect.php";

// Get parameters from URL
$department_id = $_GET['department_id'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Validate parameters
if (empty($department_id) || empty($class_id)) {
    header('Location: selection_teacher.php');
    exit();
}

// Get department and class information
$department_name = '';
$class_info = [];

try {
    // Get department name
    $dept_sql = "SELECT department_name FROM departments WHERE id = ? AND faculty_id = ?";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->execute([$department_id, $faculty_id]);
    $dept_result = $dept_stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept_result) {
        $department_name = $dept_result['department_name'];
    }

    // Get class information
    $class_sql = "SELECT class_name, study_mode, semester, academic_year FROM classes WHERE id = ? AND faculty_id = ?";
    $class_stmt = $conn->prepare($class_sql);
    $class_stmt->execute([$class_id, $faculty_id]);
    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_info) {
        header('Location: selection_teacher.php');
        exit();
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
    <title>Teacher Allocations</title>
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
                <strong class="me-auto">Update</strong>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Successfully updated the time
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
                Are you sure you want to delete this Teacher allocation?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
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
                        <a href="selection_teacher.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                        <h4 class="fw-bold py-3 mb-4">Teacher in Class</h4>
                        
                        <div class="d-flex flex-column bg-white p-2 m-2">
                            <div><strong>Class Name:</strong> <?php echo htmlspecialchars($class_info['class_name']) . ' (' . htmlspecialchars($class_info['study_mode']) . ')'; ?></div>
                            <div><strong>Semester:</strong> <?php echo htmlspecialchars($class_info['semester']); ?></div>
                            <div><strong>Department Name:</strong> <?php echo htmlspecialchars($department_name); ?></div>
                            <div><strong>Faculty Name:</strong> <?php echo htmlspecialchars($faculty); ?></div>
                            <div><strong>Current Server Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?> (<?php echo date_default_timezone_get(); ?>)</div>
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
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>TID</th>
                                                    <th>Teacher Name</th>
                                                    <th>Subject Name</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="allocationTableBody">
                                                <!-- Allocation data will be dynamically inserted here via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-backdrop fade"></div>
                    </div>

                    <!-- Time Modal -->
                    <div class="modal fade" id="timeModal" tabindex="-1" aria-labelledby="timeModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="timeModalLabel">Change Time</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Teacher & Subject</label>
                                        <input type="text" class="form-control" id="teacherSubjectDisplay" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" required />
                                    </div>
                                    <div class="mb-3">
                                        <label for="end_time" class="form-label">End Time</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" required />
                                    </div>
                                    <input type="hidden" id="allocation_id" name="allocation_id">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" id="submitTimeChange" class="btn btn-primary">Submit</button>
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
    
    <script>
    $(document).ready(function() {
        var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
        var warningToast = new bootstrap.Toast(document.getElementById('warningToast'));
        var updatetoster = new bootstrap.Toast(document.getElementById('updatetoster'));
        var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
        var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
        var deleteAllocationId = null;
        
        // Load allocations on page load
        fetchAllocationList();
        autoApproveWaiting(); // Check for auto-approvals on load

        // Auto-refresh every 30 seconds to update time-based statuses
        setInterval(function() {
            autoApproveWaiting(); // Auto-approve waiting classes
            fetchAllocationList();
        }, 30000);

        // Update countdown timers every minute for better user experience
        setInterval(function() {
            fetchAllocationList(); // Refresh to update countdown timers
        }, 60000);

        // Function to auto-approve waiting allocations that have reached their time
        function autoApproveWaiting() {
            $.ajax({
                url: '../Database_users/allocate_teacher/auto_approve_waiting.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.approved_count > 0) {
                            console.log('Auto-approved ' + response.approved_count + ' waiting allocations');
                        }
                        if (response.reverted_count > 0) {
                            console.log('Auto-reverted ' + response.reverted_count + ' ended classes to pending');
                        }
                    }
                },
                error: function() {
                    console.log('Error in auto-approval/revert check');
                }
            });
        }

        // Function to fetch allocation list
        function fetchAllocationList(searchValue = '') {
            var classId = '<?php echo $class_id; ?>';
            var facultyId = '<?php echo $faculty_id; ?>';
            
            $.ajax({
                url: '../Database_users/allocate_teacher/show_allocations.php',
                type: 'GET',
                data: { 
                    search: searchValue,
                    class_id: classId,
                    faculty_id: facultyId,
                    _t: new Date().getTime() // Cache busting parameter
                },
                cache: false, // Disable caching
                success: function(response) {
                    $('#allocationTableBody').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching allocation list:', error);
                    $('#allocationTableBody').html('<tr><td colspan="8">Error loading allocations</td></tr>');
                }
            });
        }

        // Handle refresh button
        $('#refreshButton').on('click', function() {
            autoApproveWaiting();
            fetchAllocationList();
        });

        // Handle status update (single button cycling)
        $(document).on('click', '.status-btn', function() {
            var allocationId = $(this).data('id');
            var teacherName = $(this).data('teacher-name');
            var subject = $(this).data('subject');
            
            console.log('Updating status for allocation ID:', allocationId);
            
            $.ajax({
                url: '../Database_users/allocate_teacher/update_status.php',
                type: 'POST',
                data: { 
                    id: allocationId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Status update response:', response);
                    if (response.success) {
                        $('#addSuccessToast .toast-body').text(response.message);
                        addSuccessToast.show();
                        // Small delay to ensure database update is complete, then refresh
                        setTimeout(function() {
                            console.log('Refreshing allocation list...');
                            fetchAllocationList();
                        }, 100);
                    } else {
                        $('#warningToast .toast-body').text(response.message);
                        warningToast.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating status:', error);
                    $('#errorToast .toast-body').text('Error updating status');
                    errorToast.show();
                }
            });
        });

        // Handle edit time button
        $(document).on('click', '.edit-time-btn', function(e) {
            e.preventDefault();
            var allocationId = $(this).data('id');
            var startTime = $(this).data('start-time');
            var endTime = $(this).data('end-time');
            var teacherName = $(this).data('teacher-name');
            var subject = $(this).data('subject');
            
            $('#allocation_id').val(allocationId);
            $('#teacherSubjectDisplay').val(teacherName + ' - ' + subject);
            $('#start_time').val(startTime);
            $('#end_time').val(endTime);
            
            var timeModal = new bootstrap.Modal(document.getElementById('timeModal'));
            timeModal.show();
        });

        // Handle edit time form submission
        $('#submitTimeChange').on('click', function(event) {
            event.preventDefault();
            
            var allocationId = $('#allocation_id').val();
            var startTime = $('#start_time').val();
            var endTime = $('#end_time').val();
            
            if (!startTime || !endTime) {
                $('#warningToast .toast-body').text('Please fill in both start and end times');
                warningToast.show();
                return;
            }
            
            $.ajax({
                url: '../Database_users/allocate_teacher/edit_time.php',
                type: 'POST',
                data: {
                    id: allocationId,
                    start_time: startTime,
                    end_time: endTime
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#submitTimeChange').prop('disabled', true);
                },
                success: function(response) {
                    $('#submitTimeChange').prop('disabled', false);
                    
                    if (response.success) {
                        $('#timeModal').modal('hide');
                        $('#updatetoster .toast-body').text('Successfully updated the time');
                        updatetoster.show();
                        fetchAllocationList();
                    } else {
                        $('#warningToast .toast-body').text(response.message);
                        warningToast.show();
                    }
                },
                error: function() {
                    $('#submitTimeChange').prop('disabled', false);
                    $('#errorToast .toast-body').text('Error updating time');
                    errorToast.show();
                }
            });
        });

        // Handle delete allocation
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            deleteAllocationId = $(this).data('id');
            var teacherName = $(this).data('teacher-name');
            var subject = $(this).data('subject');
            
            $('#deleteConfirmToast .toast-body').html(
                'Are you sure you want to delete the allocation for ' + teacherName + ' - ' + subject + '?' +
                '<div class="mt-3 pt-3 border-top d-flex justify-content-end">' +
                '<button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>' +
                '<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>' +
                '</div>'
            );
            deleteConfirmToast.show();
        });

        // Handle confirm delete
        $(document).on('click', '#confirmDelete', function() {
            if (deleteAllocationId) {
                $.ajax({
                    url: '../Database_users/allocate_teacher/delete_allocation.php',
                    type: 'POST',
                    data: { id: deleteAllocationId },
                    dataType: 'json',
                    success: function(response) {
                        deleteConfirmToast.hide();
                        if (response.success) {
                            $('#addSuccessToast .toast-body').text('Delete data successfully!');
                            addSuccessToast.show();
                            fetchAllocationList();
                        } else {
                            $('#warningToast .toast-body').text(response.message);
                            warningToast.show();
                        }
                        deleteAllocationId = null;
                    },
                    error: function() {
                        deleteConfirmToast.hide();
                        $('#errorToast .toast-body').text('Error deleting allocation');
                        errorToast.show();
                        deleteAllocationId = null;
                    }
                });
            }
        });
    });
    </script>
</body>
</html>