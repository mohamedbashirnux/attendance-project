<?php
session_start();

// Check if the admin is not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to the login page
    header("Location: ../interval/Auth_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Admin Management</title>
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
</head>
<style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
<body>
 <!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="userToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      <!-- Message will be set dynamically -->
    </div>
  </div>
</div>
<!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="warningToast" class="toast bg-warning text-dark" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto">Warning</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      <p>Do you need to delete this user? If you proceed, the faculty will not be able to enter their faculty and manage their data.</p>
      <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
      <button type="button" class="btn btn-secondary" data-bs-dismiss="toast">Cancel</button>
    </div>
  </div>
</div>

<!-- Optional: Modal for Confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this user? If you proceed, the faculty will not be able to access their data.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="modalConfirmDelete">Delete</button>
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
                        <h4 class="fw-bold py-3 mb-4">Users Management</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title d-flex justify-content-between">
                                    Users List
                                    <!-- Button trigger modal -->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#addUserModal">
                                        Add User
                                    </button>
                                </h5>
                                <table class="table" id="userTable">
                                    <thead>
                                        <tr>
                                            <th>Faculty</th>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- User data will be loaded here dynamically using AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Modal -->
                    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="addUserForm">
                                        <div class="mb-3">
                                            <label for="faculty" class="form-label">Select Faculty</label>
                                            <select class="form-select" id="faculty" name="faculty" required>
                                                <!-- Faculties will be loaded here dynamically using AJAX -->
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Add User</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    

                  <!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="editUserForm">
    <input type="hidden" id="editUserId" name="faculty_name"> <!-- Hidden input for faculty_name -->
    <div class="mb-3">
        <label for="editFaculty" class="form-label">Faculty</label>
        <input type="text" class="form-control" id="editFaculty" name="faculty" readonly>
    </div>
    <div class="mb-3">
        <label for="editUsername" class="form-label">Username</label>
        <input type="text" class="form-control" id="editUsername" name="username">
    </div>
    <div class="mb-3">
        <label for="editPassword" class="form-label">Password</label>
        <input type="password" class="form-control" id="editPassword" name="password">
    </div>
    <button type="submit" class="btn btn-primary">Update User</button>
</form>

            </div>
        </div>
    </div>
</div>
                <div class="content-backdrop fade"></div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <div class="drag-target"></div>
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
    // Fetch initial user data
    fetchUserList();

    // Fetch faculties for dropdown
    fetchFaculties();

    // Initialize toast elements
    const userToast = new bootstrap.Toast(document.getElementById('userToast'));
    const warningToast = new bootstrap.Toast(document.getElementById('warningToast'));

    // Reset the form when the modal is closed
    $('#addUserModal').on('hidden.bs.modal', function() {
        $('#addUserForm')[0].reset();  // Reset the form fields
        $('#addUserModalLabel').text('Add New User');  // Reset modal title to default
        $('#userId').val('');  // Clear any user ID stored for editing
    });

    // Reset edit modal when it's closed
    $('#editUserModal').on('hidden.bs.modal', function() {
        $('#editUserForm')[0].reset();  // Reset the form fields
        $('#editUserModalLabel').text('Edit User');  // Reset modal title
        $('#editUserId').val('');  // Clear user ID
    });

    // Handle form submission for adding users
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../database/User/add_users.php',
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                console.log('Response from server:', response);
                if (response.includes('User added successfully')) {
                    $('#addUserForm')[0].reset();  // Reset form on success
                    fetchUserList();
                    showToast('User added successfully', 'bg-success text-white');
                    $('#addUserModal').modal('hide');  // Close modal on success
                } else {
                    showToast('Error: ' + response, 'bg-danger text-white');
                }
                $('button[type="submit"]').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showToast('Error: ' + xhr.responseText, 'bg-danger text-white');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Handle form submission for editing users
$('#editUserForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '../database/User/edit_users.php',
        type: 'POST',
        data: $(this).serialize(),
        beforeSend: function() {
            $('button[type="submit"]').prop('disabled', true);
        },
        success: function(response) {
            console.log('Response from server:', response);
            if (response.includes('User updated successfully')) {
                $('#editUserForm')[0].reset();  // Reset form on success
                fetchUserList();  // Refresh user list
                showToast('User updated successfully', 'bg-success text-white');
                $('#editUserModal').modal('hide');  // Close modal on success
            } else {
                showToast('Error: ' + response, 'bg-danger text-white');
            }
            $('button[type="submit"]').prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            showToast('Error: ' + xhr.responseText, 'bg-danger text-white');
            $('button[type="submit"]').prop('disabled', false);
        }
    });
});

    // Edit user: load user data into the edit modal
    $(document).on('click', '.edit-user', function() {
        const facultyName = $(this).data('facultyname');
        $.ajax({
            url: '../database/User/get_user.php',
            type: 'GET',
            data: { faculty_name: facultyName },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    console.error('Error fetching user data:', response.error);
                    showToast('Error: ' + response.error, 'bg-danger text-white');
                    return;
                }

                $('#editFaculty').val(response.faculty_name).prop('disabled', true);  // Display faculty name and disable it
                $('#editUsername').val(response.username);  // Set username
                $('#editPassword').val(response.password);  // Set password
                $('#editUserId').val(response.faculty_name);  // Store faculty name for form submission
                $('#editUserModal').modal('show');  // Show the edit modal
            },
            error: function(xhr, status, error) {
                console.error('Error fetching user data:', error);
                showToast('Error fetching user data: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
    });

    // Delete user
    $(document).on('click', '.delete-user', function() {
        const facultyName = $(this).data('facultyname');
        $('#warningToast .toast-body p').text(`Do you need to delete the user: ${facultyName}? If you proceed, the faculty will not be able to enter their faculty and manage their data.`);
        
        // Show confirmation toast
        $('#warningToast').toast('show');

        // Store the faculty name for the confirm delete action
        $('#warningToast').data('facultyname', facultyName);
    });

    // Handle confirm delete button click in toast
    $(document).on('click', '#confirmDelete', function() {
        const facultyName = $('#warningToast').data('facultyname');
        $.ajax({
            url: '../database/User/delete_users.php',
            type: 'POST',
            data: { faculty_name: facultyName },
            success: function(response) {
                console.log('Response from server:', response);
                if (response.includes('User deleted successfully')) {
                    fetchUserList();
                    showToast('User deleted successfully', 'bg-success text-white');
                } else {
                    showToast('Error deleting user: ' + response, 'bg-danger text-white');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error deleting user:', error);
                showToast('Error deleting user: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
        $('#warningToast').toast('hide');
    });

    // Handle cancel button click in toast
    $(document).on('click', '.btn-secondary', function() {
        $('#warningToast').toast('hide');
    });

    // Fetch and display the user list
    function fetchUserList() {
        $.ajax({
            url: '../database/User/show_users.php',
            type: 'GET',
            success: function(response) {
                $('#userTable tbody').html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching users:', error);
                showToast('Error fetching users: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
    }

    // Fetch and populate the faculty dropdown
    function fetchFaculties() {
        $('#faculty').html('<option disabled selected>Choose a faculty</option>'); // Default placeholder option
        $.ajax({
            url: '../database/Faculty/fetch_faculties.php',
            type: 'GET',
            success: function(response) {
                $('#faculty').append(response);  // Populate faculties dynamically
                $('#editFaculty').append(response);  // Populate for edit modal as well
            },
            error: function(xhr, status, error) {
                console.error('Error fetching faculties:', error);
                showToast('Error fetching faculties: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
    }

    // Show toast messages
    function showToast(message, type) {
        let toastElement = $('#userToast');
        toastElement.find('.toast-body').text(message);
        toastElement.find('.toast-header').removeClass('bg-success bg-danger bg-warning text-white text-dark').addClass(type);
        userToast.show();
    }
});
</script>
</body>
</html>
