<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Teacher Management</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" />
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
<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
<div id="successImportToaster" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
<div class="toast-header bg-success text-white">
            <i class="bx bx-bell me-2"></i>
            <div class="me-auto fw-semibold">Success</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body  bg-success text-white">
          
        </div>
</div>

<div id="errorImportToaster" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
<div class="toast-header bg-danger text-white">
            <i class="bx bx-error me-2"></i>
            <div class="me-auto fw-semibold">Error</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-danger text-white">
            <!-- This will be dynamically updated based on the error -->
        </div>
</div>

    <!-- Success Toast -->
    <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="bx bx-bell me-2"></i>
            <div class="me-auto fw-semibold">Success</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Teacher added successfully!
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
            Teacher edited successfully!
        </div>
    </div>

    <!-- Error Toast -->
    <div id="teacherExistsToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <i class="bx bx-error me-2"></i>
            <div class="me-auto fw-semibold">Error</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <!-- This will be dynamically updated based on the error -->
        </div>
    </div>

    <!-- Warning Toast for Existing Teacher -->
    <div id="teacherExistsWarningToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-white">
            <i class="bx bx-bell me-2"></i>
            <div class="me-auto fw-semibold">Warning</div>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Teacher already exists!
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
        <h4 class="fw-bold py-3 mb-4">Teacher Management</h4>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title d-flex justify-content-between">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#basicModal">
                            Add Teacher
                    </button>
                    <!-- Search Input and Search Button -->
                    <div class="d-flex align-items-center">
                        <input type="text" class="form-control me-2" id="searchTeacher" placeholder="Search for a teacher..." style="width: 300px;">
                        <button type="button" class="btn btn-primary" id="searchButton"><i class='bx bx-search-alt-2' ></i></button>
                    </div>
                    
                    <!-- Button trigger modal -->
                    <div>
                      
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importTeacherModal">Import Teacher</button>
                    </div>
                </h5>
                <div class="table-responsive">
                    <table class="table table-striped" id="teacherTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Teacher data will be inserted here via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="content-backdrop fade"></div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel1">Add Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addTeacherForm">
                    <div class="mb-3">
                        <label for="id" class="form-label">ID</label>
                        <input type="number" class="form-control" id="id" name="id" required>
                    </div>
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Teacher</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTeacherForm">
                    <div class="mb-3">
                        <label for="editTeacherID" class="form-label">ID</label>
                        <input type="number" class="form-control" id="editTeacherID" readonly name="id" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editFullName" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </form>
            </div>
        </div>
    </div>
</div>







<!-- Import Teachers Modal -->

<div class="modal fade" id="importTeacherModal" tabindex="-1" aria-labelledby="importTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <!-- <h5 class="modal-title" id="importTeacherModalLabel">Import Teacher</h5> -->

                <div class=" d-flex flex-column text-capitalize">
                  <h5 class="modal-title" id="importTeacherModalLabel">Import Teacher</h5>
                <p class="modal-title" id="importSubjectModalLabel">*the excel file must to contain four  column that is: </p>
                <p class="modal-title" id="importSubjectModalLabel">* Firts column Teacher id  </p>
                <p class="modal-title" id="importSubjectModalLabel">* Second column Teacher name  </p>
                <p class="modal-title" id="importSubjectModalLabel">* Third column Teacher password  </p>
              
                <p class="modal-title" id="importSubjectModalLabel">* firts row is included  </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/attendanceproject1/Database_users/teacher/import_teacher.php" id="importTeacherForm" method="POST" enctype="multipart/form-data">
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
                       
                        <button class="btn btn-outline-primary" type="submit" id="inputGroupFileAddon04">Upload</button>
                        
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to handle search button click (if needed)
    document.getElementById('searchButton').addEventListener('click', function() {
        var searchTerm = document.getElementById('searchTeacher').value.trim();
        // Perform search logic or AJAX call if required
        // Example: fetchTeacherList(searchTerm);
    });

    // Your existing JavaScript for AJAX and other functionality goes here
</script>
    <!-- Core JS -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script>
$(document).ready(function() {
    // Initialize Bootstrap toasts
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
    var teacherExistsToast = new bootstrap.Toast(document.getElementById('teacherExistsToast'));
    var errorImportToast = new bootstrap.Toast(document.getElementById('errorimporttoaster'));
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));

    // Fetch initial teacher list
    fetchTeacherList();

    // Add Teacher Form Submission
    $('#addTeacherForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../database/Teacher/add_teacher.php',
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                $('button[type="submit"]').prop('disabled', false);
                try {
                    var res = JSON.parse(response);
                    if (res.success) {
                        addSuccessToast.show();
                        $('#basicModal').modal('hide');
                        $('#addTeacherForm')[0].reset();
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        var errorMessage = "";
                        if (res.error === 'id_exists') {
                            errorMessage = "Teacher ID already exists.";
                        } else if (res.error === 'username_exists') {
                            errorMessage = "Username already exists.";
                        } else if (res.error === 'both_exists') {
                            errorMessage = "Both Teacher ID and Username already exist.";
                        }
                        $('#teacherExistsToast .toast-body').text(errorMessage);
                        teacherExistsToast.show();
                    }
                } catch (e) {
                    console.error("Invalid JSON response", response);
                    $('#errorimporttoaster .toast-body').text("An error occurred. Please try again.");
                    errorImportToast.show();
                }
            },
            error: function(xhr, status, error) {
                $('button[type="submit"]').prop('disabled', false);
                console.error('Error submitting form:', error);
                $('#errorimporttoaster .toast-body').text('An error occurred. Please try again.');
                errorImportToast.show();
            }
        });
    });

    // Import Teacher Form Submission
    $('#importTeacherForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: '/attendanceproject1/Database_users/teacher/import_teacher.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#importTeacherForm')[0].reset(); // Reset the form after submission
            fetchTeacherList(); // Refresh the teacher list (assumed function)
            $('#importTeacherModal').modal('hide'); // Hide the import modal

            try {
                var res = JSON.parse(response);
                if (res.status === 'success') { // Check if the response status is 'success'
                    $('#successImportToaster .toast-body').text(res.message); // Display success message
                    $('#successImportToaster').toast('show'); // Show the success toast
                    setTimeout(function() {
                        location.reload(); // Reload the page after a short delay
                    }, 800);
                } else {
                    $('#errorImportToaster .toast-body').text(res.message); // Display error message
                    $('#errorImportToaster').toast('show'); // Show the error toast
                }
            } catch (e) {
                console.error("Invalid JSON response", response);
                $('#errorImportToaster .toast-body').text("An error occurred. Please try again.");
                $('#errorImportToaster').toast('show');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error importing teachers:', error);
            $('#errorImportToaster .toast-body').text('An error occurred. Please try again.');
            $('#errorImportToaster').toast('show');
        }
    });
});



    // Search Teachers
    $('#searchTeacher').on('input', function() {
        var searchValue = $(this).val().trim();
        fetchTeacherList(searchValue);
    });

    // Fetch Teacher List
    function fetchTeacherList(searchValue = '') {
        $.ajax({
            url: '../database/Teacher/show_teacher.php',
            type: 'GET',
            data: { search: searchValue },
            success: function(response) {
                try {
                    $('#teacherTable tbody').html(response);
                } catch (e) {
                    console.error("Error processing response", response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching teacher list:', error);
            }
        });
    }

    // Open Edit Modal and Populate Data
    window.openEditModal = function(teacherID) {
        $.ajax({
            url: '../database/Teacher/show_teacher.php',
            type: 'GET',
            data: { id: teacherID },
            success: function(response) {
                try {
                    var teacher = JSON.parse(response);
                    if (teacher) {
                        $('#editTeacherID').val(teacher.tid);
                        $('#editFullName').val(teacher.teacher_name);
                        $('#editUsername').val(teacher.username);
                        $('#editPassword').val(teacher.password);
                        $('#editTeacherModal').modal('show');
                    } else {
                        console.error("No teacher found with the provided ID.");
                    }
                } catch (e) {
                    console.error("Invalid JSON response", response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching teacher data:', error);
            }
        });
    };

    $('#editTeacherForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '../database/teacher/edit_teacher.php',
        type: 'POST',
        data: $(this).serialize(),
        beforeSend: function() {
            $('button[type="submit"]').prop('disabled', true);
        },
        success: function(response) {
            $('button[type="submit"]').prop('disabled', false);

            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    fetchTeacherList();  // Reload the teacher list or update UI as needed
                    $('#editTeacherModal').modal('hide');
                    editSuccessToast.show(); // Show success toast or notification
                } else {
                    var errorMessage = res.message || 'An unknown error occurred.';
                    $('#teacherExistsToast .toast-body').text(errorMessage);
                    teacherExistsToast.show();
                }
            } catch (e) {
                console.error("Invalid JSON response", response);
                $('#errorimporttoaster .toast-body').text("An error occurred. Please try again.");
                errorImportToast.show();
            }
        },
        error: function(xhr, status, error) {
            $('button[type="submit"]').prop('disabled', false);
            console.error('Error editing teacher:', error);
            $('#errorimporttoaster .toast-body').text('An error occurred. Please try again.');
            errorImportToast.show();
        }
    });
});



    // Delete Teacher
    window.deleteTeacher = function(teacherID) {
        if (confirm('Are you sure you want to delete this teacher?')) {
            $.ajax({
                url: '../database/Teacher/delete_teacher.php',
                type: 'POST',
                data: { teacherID: teacherID },
                success: function(response) {
                    fetchTeacherList();
                    addSuccessToast.show();
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting teacher:', error);
                    $('#errorimporttoaster .toast-body').text('Failed to delete teacher. Please try again.');
                    errorImportToast.show();
                }
            });
        }
    };
});
</script>


</body>
</html>
