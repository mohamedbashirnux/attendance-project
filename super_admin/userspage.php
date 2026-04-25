<?php
// Enable error reporting to see what's causing white page
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if super admin is logged in
include 'seassion_super-admin.php';
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
                                            <th>NO.</th>
                                            <th>Faculty</th>
                                            <th>Username</th>
                                            <th class="text-end">Actions</th>
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
                                                <option value="" disabled selected>Choose a faculty</option>
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
    <input type="hidden" id="editUserId" name="user_id">
    <div class="mb-3">
        <label for="editFaculty" class="form-label">Faculty</label>
        <input type="text" class="form-control" id="editFaculty" readonly>
    </div>
    <div class="mb-3">
        <label for="editUsername" class="form-label">Username</label>
        <input type="text" class="form-control" id="editUsername" name="username" required>
    </div>
    <div class="mb-3">
        <label for="editPassword" class="form-label">Password (leave blank to keep current)</label>
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
    fetchUserList();
    fetchFaculties();

    const userToast = new bootstrap.Toast(document.getElementById('userToast'));
    const warningToast = new bootstrap.Toast(document.getElementById('warningToast'));

    $('#addUserModal').on('hidden.bs.modal', function() {
        $('#addUserForm')[0].reset();
    });

    $('#editUserModal').on('hidden.bs.modal', function() {
        $('#editUserForm')[0].reset();
    });

    // Add user
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../Database/faculty_usres/add_users.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#addUserForm')[0].reset();
                    fetchUserList();
                    showToast('User added successfully', 'bg-success text-white');
                    $('#addUserModal').modal('hide');
                } else {
                    showToast('Error: ' + response.error, 'bg-danger text-white');
                }
                $('button[type="submit"]').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                showToast('Error: ' + xhr.responseText, 'bg-danger text-white');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Edit user
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../Database/faculty_usres/edit_users.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $('#editUserForm')[0].reset();
                    fetchUserList();
                    showToast('User updated successfully', 'bg-success text-white');
                    $('#editUserModal').modal('hide');
                } else {
                    showToast('Error: ' + response.error, 'bg-danger text-white');
                }
                $('button[type="submit"]').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                showToast('Error: ' + xhr.responseText, 'bg-danger text-white');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Load user data into edit modal
    $(document).on('click', '.edit-user', function() {
        const userId = $(this).data('userid');
        const facultyName = $(this).data('facultyname');
        const username = $(this).data('username');

        $('#editUserId').val(userId);
        $('#editFaculty').val(facultyName);
        $('#editUsername').val(username);
        $('#editPassword').val('');
        $('#editUserModal').modal('show');
    });

    // Delete user
    $(document).on('click', '.delete-user', function() {
        const userId = $(this).data('userid');
        const facultyName = $(this).data('facultyname');
        
        $('#warningToast .toast-body p').text(`Do you need to delete the user: ${facultyName}? If you proceed, the faculty will not be able to enter their faculty and manage their data.`);
        $('#warningToast').toast('show');
        $('#warningToast').data('userid', userId);
    });

    $(document).on('click', '#confirmDelete', function() {
        const userId = $('#warningToast').data('userid');
        $.ajax({
            url: '../Database/faculty_usres/delete_users.php',
            type: 'POST',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    fetchUserList();
                    showToast('User deleted successfully', 'bg-success text-white');
                } else {
                    showToast('Error: ' + response.error, 'bg-danger text-white');
                }
            },
            error: function(xhr, status, error) {
                showToast('Error: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
        $('#warningToast').toast('hide');
    });

    function fetchUserList() {
        $.ajax({
            url: '../Database/faculty_usres/show_users.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let tableBody = $('#userTable tbody');
                tableBody.html('');
                
                if (response.success && response.users.length > 0) {
                    response.users.forEach((user, index) => {
                        tableBody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${user.faculty_name}</td>
                                <td>${user.username}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-warning edit-user" data-userid="${user.id}" data-facultyname="${user.faculty_name}" data-username="${user.username}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-user" data-userid="${user.id}" data-facultyname="${user.faculty_name}">Delete</button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tableBody.append(`
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <p class="mb-0">No users found. Click "Add User" to create one.</p>
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(xhr, status, error) {
                showToast('Error fetching users: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
    }

    function fetchFaculties() {
        $.ajax({
            url: '../Database/faculty_usres/show_users.php?action=get_faculties',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let facultySelect = $('#faculty');
                    facultySelect.html('<option value="" disabled selected>Choose a faculty</option>');
                    response.faculties.forEach(faculty => {
                        facultySelect.append(`<option value="${faculty.id}">${faculty.faculty_name}</option>`);
                    });
                }
            },
            error: function(xhr, status, error) {
                showToast('Error fetching faculties: ' + xhr.responseText, 'bg-danger text-white');
            }
        });
    }

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
