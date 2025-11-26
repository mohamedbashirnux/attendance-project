<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$departmentName = $_GET['department_name'] ?? '';
$faculty_name = $_GET['faculty'] ?? '';
$searchInput = $_GET['search_subject'] ?? ''; // Capture the search input

include "../connection/connect.php";

try {
    // Prepare SQL query for fetching subjects
    $sql = "SELECT * FROM subjects WHERE department_name = ? AND faculty_name = ?";
    
    // If search input is provided, add a filter for search input
    if (!empty($searchInput)) {
        $sql .= " AND subject_name LIKE ?";
    }

    // Add ORDER BY clause to sort the result by subject_name
    $sql .= " ORDER BY subject_name ASC"; // Order subjects alphabetically by subject_name

    $stmt = $conn->prepare($sql);

    // Bind parameters with wildcard for search if provided
    if (!empty($searchInput)) {
        $searchParam = '%' . $searchInput . '%'; // Add wildcards for partial match
        $stmt->execute([$departmentName, $faculty_name, $searchParam]);
    } else {
        $stmt->execute([$departmentName, $faculty_name]);
    }

    // Fetch the result
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Close the statement
    $stmt = null;
    $conn = null;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
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
                            <h4 class="fw-bold m-0">Selected Class Details</h4>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Department Name:</strong> <?php echo $departmentName; ?>
                                </div>
                               
                                <div class="mb-3">
                                    <strong>Faculty Name:</strong> <?php echo $faculty; ?>
                                </div>
                                <div class="mb-3">
                                <div><strong>Total Number of Subject:</strong> <?php echo count($subjects); ?></div>
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
                                <div class="table-">
                                    <table class="table table-striped">
                                        <thead>
                                     <tr>
                                    <th>Subject Name</th>
                                    <th>Department Name</th>
                                  
                                    
                                    <th class="text-end">Action</th>
                                    </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                            <!-- Student data will be dynamically inserted here -->
                                            <?php if (!empty($subjects)) {
                                                foreach ($subjects as $sub) {
                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($sub['subject_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($sub['department_name']) . '</td>';
                                                    echo '<td class="text-end">
                                                        <button class="btn btn-sm btn-danger delete-btn" data-id="' . $sub['id'] . '" data-subject_name="' . $sub['subject_name'] . '" data-department_name="' . $sub['department_name'] . '">Delete</button>
                                                        <!--
                                                       <button class="btn btn-sm btn-primary edit-btn" 
    data-id="' . $sub['id'] . '" 
    data-subject-name="' . $sub['subject_name'] . '" 
    data-department-name="' . $sub['department_name'] . '" 
    data-faculty-name="' . $sub['faculty_name'] . '"
    style="display: none;">
    >

    Edit
</button>
-->


                                                    </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">No subjects found for this departmetn</td></tr>';
                                            } ?>

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
                        <input type="text" class="form-control" id="subjectName" name="new_subject" required>
                    </div>
                    <!-- <div class="mb-3">
                        <label for="className" class="form-label">Semester</label>
                        <input type="text" class="form-control" id="semester" name="semester" >
                    </div> -->
                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="departmentName" name="department_name" value="<?php echo $departmentName; ?>" readonly>
                    </div>
                  
                  
                    <div class="mb-3">
                        <label for="facultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="facultyName" name="faculty_name" value="<?php echo $faculty; ?>" readonly>
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
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="originalSubjectName" name="original_subject_name">
                    <div class="mb-3">
                        <label for="editSubjectName" class="form-label">Current Subject Name</label>
                        <input type="text" class="form-control" id="editSubjectName" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDepartmentName" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="editDepartmentName" name="department_name" readonly>
                    </div>
                    <!-- <div class="mb-3">
                        <label for="editClassName" class="form-label">semester</label>
                        <input type="text" class="form-control" id="editsemester" name="semester">
                    </div> -->
                  
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
            
                <div class=" d-flex flex-column">
                   <h5 class="modal-title" id="importSubjectModalLabel">Import Subject</h5>
                <p class="modal-title" id="importSubjectModalLabel">*the excel file must to contain only one column that is Subjects Names </p>
                <p class="modal-title" id="importSubjectModalLabel">* firts row is included  </p>
                </div>
                
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form  action="../Database_users/subject/import_subject.php" id="importSubjectForm" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <input
                            type="file"
                            class="form-control"
                            id="inputGroupFile04"
                            name="file"
                            aria-describedby="inputGroupFileAddon04"
                            aria-label="Upload"
                            required
                        />
                        <input type="text" class="form-control" id="department_name" name="department_name" hidden readonly value="<?php echo $departmentName; ?>">
                        <input type="text" class="form-control" id="faculty" name="faculty" readonly hidden value="<?php echo $faculty; ?>">
                        <button class="btn btn-outline-primary" type="submit" id="inputGroupFileAddon04">Upload</button>
                        
                    </div>
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
        var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
        var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
        var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
        var errorimporttoaster = new bootstrap.Toast(document.getElementById('errorimporttoaster'));
        var deleteAllConfirmToast = new bootstrap.Toast(document.getElementById('deleteAllConfirmToast'));

        // Handle form submission for adding subjects
        $('#addSubjectForm').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: '/attendanceproject1/Database_users/subject/add_subject.php',
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
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                        $('#addSuccessToast').toast('show');
                    } else {
                        $('#subjectExistsToast').toast('show');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + ' - ' + error);
                    alert("An error occurred while adding the subject. Please try again.");
                }
            });
        });

        // Handle delete button click
        $(document).on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            var subjectName = $(this).data('subject_name');
            var department_name = $(this).data('department_name');

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
                    url: '/attendanceproject1/Database_users/subject/delete_subject.php',
                    type: 'POST',
                    data: {
                        id: id,
                        subject_name: subjectName,
                        department_name: department_name
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            setTimeout(function() {
                                window.location.reload();
                            }, 800);
                            deleteSuccessToast.show();
                        } else {
                            alert("An error occurred: " + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, "-", error);
                        alert("An error occurred while deleting the subject. Please try again.");
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

            $('#id').val(id);
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
                url: '/attendanceproject1/Database_users/subject/edit_subject.php',
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
                        if (typeof editSuccessToast !== 'undefined') {
                            editSuccessToast.show();
                        }
                    } else {
                        alert("An error occurred: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + ' - ' + error);
                    alert("An error occurred while editing the subject. Please try again.");
                }
            });
        });

        // Reset form when edit modal is hidden
        $('#editSubjectModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
        });

     // Handle import subject form submission
$('#importSubjectForm').on('submit', function(event) {
    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: '/attendanceproject1/Database_users/subject/import_subject.php', // Ensure this path is correct
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('button[type="submit"]').prop('disabled', true);
        },
        success: function(response) {
            $('button[type="submit"]').prop('disabled', false);
            
            // Parse JSON response
            try {
                var res = JSON.parse(response);
            } catch (e) {
                console.error("Invalid JSON response", response);
                alert("An error occurred. Please try again.");
                return;
            }

            if (res.status === 'success') {
                $('#importSubjectForm')[0].reset();
                $('#importSubjectModal').modal('hide');

                // Prepare the message
                var message = res.message;
                if (res.duplicates.length > 0) {
                    message += '\nDuplicates removed: ' + res.duplicates.join(', ');
                }

                // Display success message with duplicates
                $('#addSuccessToast .toast-body').text(message);
                $('#addSuccessToast').toast('show');

                // Reload after 3 seconds
                setTimeout(function() {
                    location.reload();
                }, 3000);

            } else {
                // Show the error message from the server in the toast
                $('#errorimporttoaster .toast-body').text(res.message);
                $('#errorimporttoaster').toast('show');
            }
        },
        error: function(xhr, status, error) {
            $('button[type="submit"]').prop('disabled', false);
            console.error("AJAX Error: " + status + ' - ' + error);
            alert("An error occurred while importing the subject. Please try again.");
        }
    });
});





        // Handle search input
        $('#searchStudentId').on('input', function() {
            var searchStudentId = this.value;
            var params = new URLSearchParams(window.location.search);
            params.set('search_subject', searchStudentId);
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
            fetchStudentData(searchStudentId);
        });

        // Handle delete all subjects
        $('#deleteAllBtn').on('click', function() {
            deleteAllConfirmToast.show();
        });

        $('#confirmDeleteAll').on('click', function() {
            $.ajax({
                url: '/attendanceproject1/Database_users/subject/delete_all.php',
                type: 'POST',
                data: {
                    department_name: '<?php echo $departmentName; ?>',
                    faculty_name: '<?php echo $faculty; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        deleteAllConfirmToast.hide();
                        $('#deleteSuccessToast').toast('show');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert("An error occurred: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, "-", error);
                    alert("An error occurred while deleting all subjects. Please try again.");
                }
            });
        });

        // Initial fetch of subject list on page load
        fetchSubjectList();
    });

    function fetchStudentData(searchStudentId) {
        var xhr = new XMLHttpRequest();
        var params = new URLSearchParams(window.location.search);
        params.set('search_subject', searchStudentId);
        xhr.open('GET', `${window.location.pathname}?${params.toString()}`, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(xhr.responseText, 'text/html');
                var newTableBody = doc.getElementById('studentTableBody').innerHTML;
                document.getElementById('studentTableBody').innerHTML = newTableBody;
                var newPagination = doc.querySelector('.pagination').innerHTML;
                document.querySelector('.pagination').innerHTML = newPagination;
            }
        };
        xhr.send();
    }

    function fetchSubjectList() {
        // Implement this function to fetch and update the subject list
        // This function is called after editing a subject and on page load
    }
</script>

<script>
$(document).ready(function() {
    // Initialize toasts
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
    var subjectExistsToast = new bootstrap.Toast(document.getElementById('subjectExistsToast'));

    // Show edit modal with subject details
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var subjectName = $(this).data('subject-name');
        var departmentName = $(this).data('department-name');
        var facultyName = $(this).data('faculty-name');

        // Populate the modal with data
        $('#id').val(id);
        $('#editSubjectName').val(subjectName);
        $('#editDepartmentName').val(departmentName);
        $('#editFacultyName').val(facultyName);
        $('#originalSubjectName').val(subjectName); // Store original name to compare later

        var editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
        editModal.show();
    });

    // Handle form submission for editing a subject
    $('#editSubjectForm').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: '/attendanceproject1/Database_users/subject/edit_subject.php', // Adjust the URL as needed
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false);

                // Check if the edit was successful
                if (response.status === 'success') {
                    $('#editSubjectModal').modal('hide');  // Hide the modal
                    setTimeout(function() {
                        location.reload();  // Reload page after 1 second
                    }, 1000);
                    editSuccessToast.show();  // Show success toast

                // Handle case where subject already exists
                } else if (response.status === 'exists') {
                    // Set the message for the toast
                    $('#subjectExistsToast .toast-body').text(response.message || "This subject already exists.");
                    subjectExistsToast.show();  // Show the "subject exists" toast

                // Handle unexpected errors
                } else if (response.status === 'error') {
                    // Log error message quietly
                    console.error("Error: " + (response.message || "An unknown error occurred."));
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false);
                // Log AJAX errors quietly without alerting
                console.error("AJAX Error: " + status + ' - ' + error);
                // If you want to handle specific status codes here, you can do so without showing alerts.
            }
        });
    });

    // Remove lingering modal backdrop when the modal is hidden
    $('#editSubjectModal').on('hidden.bs.modal', function () {
        $('.modal-backdrop').remove();  // Ensure the modal backdrop is removed
    });
});
</script>
</body>
</html>
