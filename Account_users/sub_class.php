<?php
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
    header('Location: before_sub_class.php');
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
        header('Location: before_sub_class.php');
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
    <title>Subject Class Assignment</title>
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
                Subject assigned successfully!
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
                Assignment removed successfully!
            </div>
        </div>

        <div id="deleteConfirmToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Confirm Remove</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Are you sure you want to remove this assignment?
                <div class="mt-3 pt-3 border-top d-flex justify-content-start">
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmRemove">Remove</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>

        <div id="errorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Error</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <div class="layout-wrapper layout-content-navbar">
        <?php include 'menu.php'; ?>
        <div class="layout-container">
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                            <a href="before_sub_class.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Subject Class Assignment - <?php echo htmlspecialchars($class_info['class_name']); ?></h4>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Department:</strong> <?php echo htmlspecialchars($department_name); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Faculty:</strong> <?php echo htmlspecialchars($faculty); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Class:</strong> <?php echo htmlspecialchars($class_info['class_name']); ?> - <?php echo htmlspecialchars($class_info['study_mode']); ?> (<?php echo htmlspecialchars($class_info['semester']); ?>, <?php echo htmlspecialchars($class_info['academic_year']); ?>)
                                </div>
                                <div class="mb-3">
                                    <div><strong>Total Assigned Subjects:</strong> <span id="totalAssignments">Loading...</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject to Class</button>
                                    <div class="d-flex">
                                        <input type="text" class="form-control me-2" id="searchSubject" placeholder="Search for a Subject..." style="width: 300px;">
                                        <button type="button" class="btn btn-primary" id="searchButton"><i class='bx bx-search-alt-2'></i></button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped" id="assignmentTable">
                                        <thead>
                                            <tr>
                                                <th>Subject Name</th>
                                                <th>Department</th>
                                                <th>Assigned Date</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="assignmentTableBody">
                                            <!-- Assignment data will be dynamically inserted here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Add Subject Modal -->
                        <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add Subjects to Class</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="addSubjectForm" method="POST">
                                            <div class="mb-3">
                                                <label for="subjectSearch" class="form-label">Search Subjects</label>
                                                <input type="text" class="form-control" id="subjectSearch" placeholder="Search for subjects...">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Select Subjects</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                    <label class="form-check-label" for="selectAll">
                                                        <strong>Select All</strong>
                                                    </label>
                                                </div>
                                                <div id="subjectsList" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                                                    <div class="text-center">
                                                        <div class="spinner-border spinner-border-sm" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        Loading subjects...
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="className" class="form-label">Class</label>
                                                <input type="text" class="form-control" id="className" value="<?php echo htmlspecialchars($class_info['class_name']); ?>" readonly>
                                                <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                                                <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($department_id); ?>">
                                                <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Assign Selected Subjects</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </form>
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
    
    <script>
    $(document).ready(function() {
        // Initialize toasts
        var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
        var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
        var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
        var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));

        // Load assignments and count on page load
        fetchAssignmentList();
        fetchAssignmentCount();

        // Function to fetch assignment count
        function fetchAssignmentCount() {
            var classId = '<?php echo $class_id; ?>';
            var facultyId = '<?php echo $faculty_id; ?>';
            
            $.ajax({
                url: '../Database_users/subject_class/show_assignments.php?action=count&class_id=' + classId + '&faculty_id=' + facultyId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#totalAssignments').text(response.total || 0);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching assignment count:', error);
                    $('#totalAssignments').text('Error');
                }
            });
        }

        // Function to fetch assignment list
        function fetchAssignmentList(searchValue = '') {
            var classId = '<?php echo $class_id; ?>';
            var facultyId = '<?php echo $faculty_id; ?>';
            
            $.ajax({
                url: '../Database_users/subject_class/show_assignments.php',
                type: 'GET',
                data: { 
                    search: searchValue,
                    class_id: classId,
                    faculty_id: facultyId
                },
                success: function(response) {
                    $('#assignmentTableBody').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching assignment list:', error);
                    $('#assignmentTableBody').html('<tr><td colspan="4">Error loading assignments</td></tr>');
                }
            });
        }

        // Function to load all subjects (not just available ones)
        function loadAllSubjects() {
            var departmentId = '<?php echo $department_id; ?>';
            var classId = '<?php echo $class_id; ?>';
            var facultyId = '<?php echo $faculty_id; ?>';
            
            console.log('Loading subjects with params:', {
                department_id: departmentId,
                class_id: classId,
                faculty_id: facultyId
            });
            
            $.ajax({
                url: '../Database_users/subject_class/get_all_subjects.php',
                type: 'GET',
                data: { 
                    department_id: departmentId,
                    class_id: classId,
                    faculty_id: facultyId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Subjects response:', response);
                    if (response.success) {
                        displaySubjects(response.subjects);
                    } else {
                        console.error('Error in response:', response.message);
                        $('#subjectsList').html('<div class="text-center text-danger"><p>Error: ' + (response.message || 'Unknown error') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error loading subjects:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $('#subjectsList').html('<div class="text-center text-danger"><p>Error loading subjects. Check console for details.</p></div>');
                }
            });
        }

        // Function to display subjects with checkboxes
        function displaySubjects(subjects) {
            var html = '';
            if (subjects.length > 0) {
                subjects.forEach(function(subject) {
                    var isAssigned = subject.is_assigned ? ' (Already Assigned)' : '';
                    var isDisabled = subject.is_assigned ? ' disabled' : '';
                    var textClass = subject.is_assigned ? ' text-muted' : '';
                    
                    html += '<div class="form-check mb-2 subject-item">';
                    html += '<input class="form-check-input subject-checkbox" type="checkbox" value="' + subject.id + '" id="subject_' + subject.id + '"' + isDisabled + '>';
                    html += '<label class="form-check-label' + textClass + '" for="subject_' + subject.id + '">';
                    html += subject.subject_name + isAssigned;
                    html += '</label>';
                    html += '</div>';
                });
            } else {
                html = '<div class="text-center text-muted"><p>No subjects found</p></div>';
            }
            $('#subjectsList').html(html);
        }

        // Function to filter subjects based on search
        function filterSubjects(searchTerm) {
            $('.subject-item').each(function() {
                var subjectName = $(this).find('label').text().toLowerCase();
                if (subjectName.includes(searchTerm.toLowerCase())) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        // Load all subjects when modal is opened
        $('#addSubjectModal').on('show.bs.modal', function() {
            loadAllSubjects();
        });

        // Handle search input
        $('#subjectSearch').on('input', function() {
            var searchTerm = $(this).val();
            filterSubjects(searchTerm);
        });

        // Handle select all checkbox
        $('#selectAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.subject-checkbox:not(:disabled)').prop('checked', isChecked);
        });

        // Update select all checkbox when individual checkboxes change
        $(document).on('change', '.subject-checkbox', function() {
            var totalCheckboxes = $('.subject-checkbox:not(:disabled)').length;
            var checkedCheckboxes = $('.subject-checkbox:not(:disabled):checked').length;
            
            if (checkedCheckboxes === 0) {
                $('#selectAll').prop('indeterminate', false).prop('checked', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                $('#selectAll').prop('indeterminate', false).prop('checked', true);
            } else {
                $('#selectAll').prop('indeterminate', true);
            }
        });

        // Handle form submission for adding multiple subjects to class
        $('#addSubjectForm').on('submit', function(event) {
            event.preventDefault();
            
            var selectedSubjects = [];
            $('.subject-checkbox:checked').each(function() {
                selectedSubjects.push($(this).val());
            });
            
            console.log('Selected subjects:', selectedSubjects);
            
            if (selectedSubjects.length === 0) {
                $('#errorToast .toast-body').text("Please select at least one subject to assign.");
                errorToast.show();
                return;
            }
            
            var postData = {
                subject_ids: selectedSubjects,
                class_id: '<?php echo $class_id; ?>',
                department_id: '<?php echo $department_id; ?>',
                faculty_id: '<?php echo $faculty_id; ?>'
            };
            
            console.log('Sending assignment data:', postData);
            
            $.ajax({
                url: '../Database_users/subject_class/add_multiple_assignments.php',
                type: 'POST',
                data: postData,
                dataType: 'json',
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    console.log('Assignment response:', response);
                    $('button[type="submit"]').prop('disabled', false);
                    if (response.success) {
                        $('#addSubjectModal').modal('hide');
                        fetchAssignmentList();
                        fetchAssignmentCount();
                        
                        var message = response.assigned_count + ' subject(s) assigned successfully';
                        if (response.already_assigned_count > 0) {
                            message += '. ' + response.already_assigned_count + ' subject(s) were already assigned.';
                        }
                        
                        $('#addSuccessToast .toast-body').text(message);
                        addSuccessToast.show();
                    } else {
                        console.error('Assignment failed:', response.message);
                        $('#errorToast .toast-body').text(response.message || "An error occurred while assigning subjects.");
                        errorToast.show();
                    }
                },
                error: function(xhr, status, error) {
                    $('button[type="submit"]').prop('disabled', false);
                    console.error("AJAX Error:", {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $('#errorToast .toast-body').text("An error occurred while assigning subjects. Check console for details.");
                    errorToast.show();
                }
            });
        });

        // Handle remove button click
        $(document).on('click', '.remove-btn', function() {
            var assignmentId = $(this).data('id');
            var subjectName = $(this).data('subject-name');

            $('#deleteConfirmToast .toast-body').html(`
                <p>Are you sure you want to remove the subject <strong>"${subjectName}"</strong> from this class?</p>
                <div class="mt-3 pt-3 border-top d-flex justify-content-start">
                    <button type="button" class="btn btn-sm btn-danger me-3" id="confirmRemove">Remove</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            `);
            deleteConfirmToast.show();

            $('#confirmRemove').one('click', function() {
                deleteConfirmToast.hide();
                $.ajax({
                    url: '../Database_users/subject_class/remove_assignment.php',
                    type: 'POST',
                    data: { id: assignmentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            fetchAssignmentList();
                            fetchAssignmentCount();
                            loadAvailableSubjects();
                            deleteSuccessToast.show();
                        } else {
                            $('#errorToast .toast-body').text("An error occurred: " + response.message);
                            errorToast.show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, "-", error);
                        $('#errorToast .toast-body').text("An error occurred while removing the assignment. Please try again.");
                        errorToast.show();
                    }
                });
            });
        });

        // Handle search input
        $('#searchSubject').on('input', function() {
            var searchValue = $(this).val().trim();
            fetchAssignmentList(searchValue);
        });

        // Remove lingering modal backdrop when modals are hidden
        $('#addSubjectModal').on('hidden.bs.modal', function () {
            $('.modal-backdrop').remove();
            // Clear search input
            $('#subjectSearch').val('');
            // Reset form
            $('#addSubjectForm')[0].reset();
            // Uncheck all checkboxes
            $('.subject-checkbox').prop('checked', false);
            $('#selectAll').prop('checked', false).prop('indeterminate', false);
            // Clear subjects list
            $('#subjectsList').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading subjects...</div>');
        });
    });
    </script>
</body>
</html>