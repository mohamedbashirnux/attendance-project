<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];

// Get department info from URL parameters
$department_id = $_GET['department_id'] ?? '';
$department_name = '';

if ($department_id) {
    // Get department name
    include "../connection/connect.php";
    try {
        $dept_sql = "SELECT department_name FROM departments WHERE id = ? AND faculty_id = ?";
        $dept_stmt = $conn->prepare($dept_sql);
        $dept_stmt->execute([$department_id, $faculty_id]);
        $dept_result = $dept_stmt->fetch(PDO::FETCH_ASSOC);
        if ($dept_result) {
            $department_name = $dept_result['department_name'];
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>




<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Subjects managment</title>
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
                Subject added successfully!
            </div>
        </div>
        <div id="deleteAllConfirmToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-danger text-white">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Confirm Delete All</div>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Are you sure you want to delete ALL subjects? This action cannot be undone.
        <div class="mt-3 pt-3 border-top d-flex justify-content-start">
            <button type="button" class="btn btn-sm btn-light me-3" id="confirmDeleteAll">Delete All</button>
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="toast">Cancel</button>
        </div>
    </div>
</div>



        <!-- Edit Success Toast -->
<div id="editSuccessToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-warning text-white">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Warning</div>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Subject edited successfully!
    </div>
</div>

<!-- Subject Exists Toast -->
<div id="subjectExistsToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-warning text-white">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Warning</div>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Subject already exists!
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
                Subject deleted successfully!
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
        Are you sure you want to delete this subject?
        <div class="mt-3 pt-3 border-top d-flex justify-content-start">
            <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
        </div>
    </div>
</div>


        <div id="departmentExistsToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                subject seccuffly edit
            </div>
        </div>

        <div id="errorimporttoaster" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
               
            </div>
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
                            <a href="selection_class.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Subject Management - <?php echo htmlspecialchars($department_name); ?></h4>
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
                                    <div><strong>Total Number of Subjects:</strong> <span id="totalSubjects">Loading...</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">Add Subject</button>
                                    <div class="d-flex">
                                    <input type="text" class="form-control me-2" id="searchStudentId" placeholder="Search for a Subject..." style="width: 300px;">
                                <button type="button" class="btn btn-primary" id="searchButton"><i class='bx bx-search-alt-2' ></i></button>
                                    </div>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importSubjectModal">Import</button>
                                    <button type="button" class="btn btn-danger" id="deleteAllBtn">Delete All</button>

                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped" id="subjectTable">
                                        <thead>
                                            <tr>
                                                <th>Subject Name</th>
                                                <th>Department</th>
                                                <th>Faculty</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="subjectTableBody">
                                            <!-- Subject data will be dynamically inserted here via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                                <div class="pagination-container d-flex justify-content-center mt-2" id="pagination-controls">
        <!-- Pagination controls will be populated here by JavaScript -->
                                 </div>
                            </div>
                        </div>

                        <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSubjectForm" method="POST">
                    <div class="mb-3">
                        <label for="subjectName" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="subjectName" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Department</label>
                        <input type="text" class="form-control" id="departmentName" name="department_name" value="<?php echo htmlspecialchars($department_name); ?>" readonly>
                        <input type="hidden" id="departmentId" name="department_id" value="<?php echo htmlspecialchars($department_id); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="facultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="facultyName" name="faculty_name" value="<?php echo htmlspecialchars($faculty); ?>" readonly>
                        <input type="hidden" id="facultyId" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
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
            <div class="modal-body">
                <form id="editSubjectForm">
                    <input type="hidden" id="editSubjectId" name="id">
                    <input type="hidden" id="originalSubjectName" name="original_subject_name">
                    <div class="mb-3">
                        <label for="editSubjectName" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="editSubjectName" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDepartmentName" class="form-label">Department</label>
                        <input type="text" class="form-control" id="editDepartmentName" name="department_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editFacultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="editFacultyName" name="faculty_name" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- import Subject Modal -->
<div class="modal fade" id="importSubjectModal" tabindex="-1" aria-labelledby="importSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex flex-column">
                   <h5 class="modal-title" id="importSubjectModalLabel">Import Subjects</h5>
                   <div class="alert alert-warning mt-2 mb-0">
                       <strong>⚠️ CSV ONLY!</strong>
                       <ul class="mb-0 mt-2">
                           <li>Create Excel with ONE column: Subject Names</li>
                           <li>File → Save As → CSV UTF-8</li>
                           <li>Upload the CSV file (NOT .xlsx!)</li>
                       </ul>
                   </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importSubjectForm" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <input
                            type="file"
                            class="form-control"
                            id="inputGroupFile04"
                            name="file"
                            accept=".csv"
                            aria-describedby="inputGroupFileAddon04"
                            aria-label="Upload"
                            required
                        />
                        <input type="hidden" id="facultyIdImport" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                        <input type="hidden" id="departmentIdImport" name="department_id" value="<?php echo htmlspecialchars($department_id); ?>">
                        <button class="btn btn-outline-primary" type="submit" id="inputGroupFileAddon04">Upload</button>
                    </div>
                    <div class="form-text text-danger mt-2"><strong>⚠️ ONLY CSV files (.csv)</strong></div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- import Subject Modal -->

                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script>
    <script src="../assets/js/custom.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
$(document).ready(function() {
    // Initialize toasts
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
    var subjectExistsToast = new bootstrap.Toast(document.getElementById('subjectExistsToast'));
    var errorImportToast = new bootstrap.Toast(document.getElementById('errorimporttoaster'));
    var deleteAllConfirmToast = new bootstrap.Toast(document.getElementById('deleteAllConfirmToast'));

    // Load subjects and count on page load
    fetchSubjectList();
    fetchSubjectCount();

    // Function to fetch subject count
    function fetchSubjectCount() {
        var departmentId = '<?php echo $department_id; ?>';
        $.ajax({
            url: '../Database_users/subject/show_subject.php?action=count&department_id=' + departmentId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#totalSubjects').text(response.total || 0);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching subject count:', error);
                $('#totalSubjects').text('Error');
            }
        });
    }

    // Function to fetch subject list
    function fetchSubjectList(searchValue = '') {
        var departmentId = '<?php echo $department_id; ?>';
        $.ajax({
            url: '../Database_users/subject/show_subject.php',
            type: 'GET',
            data: { 
                search: searchValue,
                department_id: departmentId
            },
            success: function(response) {
                $('#subjectTableBody').html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching subject list:', error);
                $('#subjectTableBody').html('<tr><td colspan="4">Error loading subjects</td></tr>');
            }
        });
    }

    // Handle form submission for adding subjects
    $('#addSubjectForm').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: '../Database_users/subject/add_subject.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false);
                if (response.success) {
                    $('#addSubjectForm')[0].reset();
                    $('#addSubjectModal').modal('hide');
                    fetchSubjectList();
                    fetchSubjectCount();
                    addSuccessToast.show();
                } else {
                    subjectExistsToast.show();
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false);
                console.error("AJAX Error: " + status + ' - ' + error);
                $('#errorimporttoaster .toast-body').text("An error occurred while adding the subject. Please try again.");
                errorImportToast.show();
            }
        });
    });

    // Handle delete button click
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var subjectName = $(this).data('subject-name');

        $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete the subject <strong>"${subjectName}"</strong>?</p>
            <div class="mt-3 pt-3 border-top d-flex justify-content-start">
                <button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);
        deleteConfirmToast.show();

        $('#confirmDelete').one('click', function() {
            deleteConfirmToast.hide();
            $.ajax({
                url: '../Database_users/subject/delete_subject.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        fetchSubjectList();
                        fetchSubjectCount();
                        deleteSuccessToast.show();
                    } else {
                        $('#errorimporttoaster .toast-body').text("An error occurred: " + response.message);
                        errorImportToast.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, "-", error);
                    $('#errorimporttoaster .toast-body').text("An error occurred while deleting the subject. Please try again.");
                    errorImportToast.show();
                }
            });
        });
    });

    // Show edit modal with subject details
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var subjectName = $(this).data('subject-name');
        var departmentName = $(this).data('department-name');
        var facultyName = $(this).data('faculty-name');

        $('#editSubjectId').val(id);
        $('#editSubjectName').val(subjectName);
        $('#editDepartmentName').val(departmentName);
        $('#editFacultyName').val(facultyName);
        $('#originalSubjectName').val(subjectName);

        var editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
        editModal.show();
    });

    // Handle form submission for editing a subject
    $('#editSubjectForm').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: '../Database_users/subject/edit_subject.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false);
                if (response.status === 'success') {
                    $('#editSubjectModal').modal('hide');
                    fetchSubjectList();
                    editSuccessToast.show();
                } else if (response.status === 'exists') {
                    $('#subjectExistsToast .toast-body').text(response.message || "This subject already exists.");
                    subjectExistsToast.show();
                } else {
                    $('#errorimporttoaster .toast-body').text("An error occurred: " + response.message);
                    errorImportToast.show();
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false);
                console.error("AJAX Error: " + status + ' - ' + error);
                $('#errorimporttoaster .toast-body').text("An error occurred while editing the subject. Please try again.");
                errorImportToast.show();
            }
        });
    });

    // Handle import subject form submission
    $('#importSubjectForm').on('submit', function(event) {
        event.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: '../Database_users/subject/import_subject.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(res) {
                $('button[type="submit"]').prop('disabled', false);

                if (res.status === 'success') {
                    $('#importSubjectForm')[0].reset();
                    $('#importSubjectModal').modal('hide');

                    var message = res.message;
                    if (res.duplicates && res.duplicates.length > 0) {
                        message += '\nDuplicates skipped: ' + res.duplicates.join(', ');
                    }

                    $('#addSuccessToast .toast-body').text(message);
                    addSuccessToast.show();
                    
                    fetchSubjectList();
                    fetchSubjectCount();
                } else {
                    $('#errorimporttoaster .toast-body').text(res.message);
                    errorImportToast.show();
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false);
                console.error("AJAX Error:", error);
                console.error("Response:", xhr.responseText);
                $('#errorimporttoaster .toast-body').text("An error occurred. Please try again.");
                errorImportToast.show();
            }
        });
    });

    // Handle search input
    $('#searchStudentId').on('input', function() {
        var searchValue = $(this).val().trim();
        fetchSubjectList(searchValue);
    });

    // Handle delete all subjects
    $('#deleteAllBtn').on('click', function() {
        deleteAllConfirmToast.show();
    });

    $('#confirmDeleteAll').on('click', function() {
        var departmentId = '<?php echo $department_id; ?>';
        $.ajax({
            url: '../Database_users/subject/delete_all_subject.php',
            type: 'POST',
            data: { department_id: departmentId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    deleteAllConfirmToast.hide();
                    $('#deleteSuccessToast .toast-body').text('All subjects deleted successfully (' + response.deleted_count + ' subjects)');
                    deleteSuccessToast.show();
                    fetchSubjectList();
                    fetchSubjectCount();
                } else {
                    $('#errorimporttoaster .toast-body').text("An error occurred: " + response.message);
                    errorImportToast.show();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, "-", error);
                $('#errorimporttoaster .toast-body').text("An error occurred while deleting all subjects. Please try again.");
                errorImportToast.show();
            }
        });
    });

    // Remove lingering modal backdrop when modals are hidden
    $('#editSubjectModal, #addSubjectModal, #importSubjectModal').on('hidden.bs.modal', function () {
        $('.modal-backdrop').remove();
    });
});
</script>
</body>
</html>
