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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Admin Management</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Icons -->
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <!-- Helpers -->
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast.bg-success {
            background-color: #28a745; /* Success color */
            color: #fff; /* Text color for readability */
        }
        .toast.bg-warning {
            background-color: #ffc107; /* Warning color */
            color: #000; /* Text color for readability */
        }
        .toast-header.bg-success {
            background-color: #28a745; /* Success color */
            color: #fff; /* Text color for readability */
        }
        .toast-header.bg-warning {
            background-color: #ffc107; /* Warning color */
            color: #000; /* Text color for readability */
        }
    </style>
</head>
<body>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <!-- Success Toast -->
        <div id="successToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Notification</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Admin added successfully!
            </div>
        </div>

        <!-- Warning Toast -->
        <div id="adminExistsToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Admin Username already exists!
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
                        <h4 class="fw-bold py-3 mb-4">Admin Management</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title d-flex justify-content-between">
                                    Admin List
                                    <!-- Button trigger modal -->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#basicModal">
                                        Add Admin
                                    </button>
                                </h5>
                                <table class="table" id="adminTable">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Admin data will be inserted here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true" >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAdminForm" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editAdminForm">
                    <input type="hidden" id="originalUsername" name="originalUsername">
                    
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="editPassword" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>



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
        fetchAdminList();

        // Handle Add Admin Form submission
        $('#addAdminForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: '../database/Admin/add_admin.php',
                type: 'POST',
                data: $(this).serialize(),
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false);

                    if (response.success) {
                        $('#addAdminForm')[0].reset();
                        fetchAdminList();
                        showToast('successToast', 'Admin added successfully!', 'bg-success');
                        $('#basicModal').modal('hide');
                    } else if (response.message === 'Username already exists!') {
                        showToast('adminExistsToast', 'Admin Username already exists!', 'bg-warning');
                    } else {
                        showToast('adminExistsToast', 'Failed to add admin.', 'bg-warning');
                    }
                },
                error: function(xhr, status, error) {
                    $('button[type="submit"]').prop('disabled', false);
                    showToast('adminExistsToast', 'Failed to add admin.', 'bg-warning');
                }
            });
        });

        // Handle Edit Button Click
        window.editAdmin = function(username, password) {
            $('#originalUsername').val(username);
            $('#editUsername').val(username);
            $('#editPassword').val(password);
            $('#editAdminModal').modal('show');
        };

        // Handle Edit Admin Form submission
        $('#editAdminForm').on('submit', function(e) {
            e.preventDefault();

            var originalUsername = $('#originalUsername').val();
            var newUsername = $('#editUsername').val();
            var newPassword = $('#editPassword').val();

            // Check if data is unchanged
            if (newUsername === originalUsername && newPassword === '') {
                showToast('adminExistsToast', 'No changes were made!', 'bg-warning');
                return;
            }

            $.ajax({
                url: '../database/Admin/edit_admin.php',
                type: 'POST',
                data: $(this).serialize(),
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    $('button[type="submit"]').prop('disabled', false);

                    if (response.success) {
                        fetchAdminList();
                        showToast('successToast', 'Admin updated successfully!', 'bg-success');
                        $('#editAdminModal').modal('hide');
                    } else if (response.message === 'Username already exists!') {
                        showToast('adminExistsToast', 'Username already exists!', 'bg-warning');
                    } else {
                        showToast('adminExistsToast', 'Failed to update admin.', 'bg-warning');
                    }
                },
                error: function(xhr, status, error) {
                    $('button[type="submit"]').prop('disabled', false);
                    showToast('adminExistsToast', 'Failed to update admin.', 'bg-warning');
                }
            });
        });

        // Handle Delete Button Click
        window.deleteAdmin = function(username) {
            if (confirm('Are you sure you want to delete this admin?')) {
                $.ajax({
                    url: '../database/Admin/delete_admin.php',
                    type: 'POST',
                    data: { username: username },
                    beforeSend: function() {
                        // Optionally disable delete button or show a loader
                    },
                    success: function(response) {
                        if (response.success) {
                            fetchAdminList();
                            showToast('successToast', 'Admin deleted successfully!', 'bg-success');
                        } else {
                            showToast('adminExistsToast', 'Failed to delete admin.', 'bg-warning');
                        }
                    },
                    error: function(xhr, status, error) {
                        showToast('adminExistsToast', 'Failed to delete admin.', 'bg-warning');
                    }
                });
            }
        };
    });

    function fetchAdminList() {
        $.ajax({
            url: '../database/Admin/show_admin.php',
            type: 'GET',
            success: function(response) {
                $('#adminTable tbody').html(response);
            }
        });
    }

    function showToast(id, message, toastClass) {
        var toast = document.getElementById(id);
        toast.querySelector('.toast-body').textContent = message;
        toast.classList.remove('bg-success', 'bg-warning', 'bg-danger'); // Remove existing color classes
        toast.classList.add(toastClass); // Add the correct color class
        var bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
</script>





</body>
</html>
