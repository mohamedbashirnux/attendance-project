<?php
// Check if super admin is logged in
include 'seassion_super-admin.php';
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Faculty Management</title>
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
    <!-- Page CSS -->
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
                Faculty added successfully!
            </div>
        </div>

        <div id="editSuccessToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Faculty edited successfully!
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
                Faculty deleted successfully!
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
                Are you sure you want to delete this faculty?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>

        <div id="facultyExistsToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Faculty already exists!
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
                        <h4 class="fw-bold py-3 mb-4">Faculty Management</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title d-flex justify-content-between">
                                    Faculty List
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                                        Add Faculty
                                    </button>
                                </h5>
                                <table class="table" id="facultyTable">
                                    <thead>
                                        <tr>
                                            <th>NO.</th>
                                            <th>Faculty Name</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Faculty data will be inserted here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination-container d-flex justify-content-center mt-2" id="pagination-controls">
                                <!-- Pagination controls will be populated here by JavaScript -->
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Faculty Modal -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addFacultyForm">
                        <div class="mb-3">
                            <label for="facultyName" class="form-label">Faculty Name</label>
                            <input type="text" class="form-control" id="facultyName" name="facultyName" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Faculty</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Faculty Modal -->
    <div class="modal fade" id="editFacultyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editFacultyForm">
                        <input type="hidden" id="originalFacultyName" name="originalFacultyName">
                        <div class="mb-3">
                            <label for="editFacultyName" class="form-label">Faculty Name</label>
                            <input type="text" class="form-control" id="editFacultyName" name="editFacultyName" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Faculty</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Success Toast for Deletion -->
<div id="deleteSuccessToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-danger text-white">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Success</div>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        Department deleted successfully!
    </div>
</div>

<!-- Confirmation Toast for Deletion -->
<div id="deleteConfirmToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header bg-warning text-white">
        <i class="bx bx-bell me-2"></i>
        <div class="me-auto fw-semibold">Confirm Deletion</div>
        <small>Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
        <!-- Content will be dynamically inserted by JavaScript -->
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
    <script>
    // Initialize toasts
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    var facultyExistsToast = new bootstrap.Toast(document.getElementById('facultyExistsToast'));

    // Event listener for adding faculty
    document.getElementById('addFacultyForm').addEventListener('submit', function(event) {
        event.preventDefault();
        var facultyName = document.getElementById('facultyName').value;

        fetch('../Database/Faculty/add_faculty.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'facultyName': facultyName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addSuccessToast.show();
                loadFacultyData(); // Refresh the faculty list
                document.getElementById('facultyName').value = ''; // Clear input field
                var addFacultyModal = bootstrap.Modal.getInstance(document.getElementById('addFacultyModal'));
                addFacultyModal.hide(); // Close the modal after successful add
            } else if (data.error === 'Faculty exists') {
                facultyExistsToast.show();
            } else {
                console.error('Error:', data.error || 'Unknown error');
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Event listener for editing faculty
    document.getElementById('editFacultyForm').addEventListener('submit', function(event) {
        event.preventDefault();
        var originalFacultyName = document.getElementById('originalFacultyName').value;
        var editFacultyName = document.getElementById('editFacultyName').value;

        fetch('../Database/Faculty/edit_faculty.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'originalFacultyName': originalFacultyName,
                'editFacultyName': editFacultyName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                editSuccessToast.show();
                loadFacultyData(); // Refresh the faculty list
                var editFacultyModal = bootstrap.Modal.getInstance(document.getElementById('editFacultyModal'));
                editFacultyModal.hide(); // Close the modal after successful edit
            } else if (data.error === 'Faculty exists') {
                facultyExistsToast.show();
            } else {
                console.error('Error:', data.error || 'Unknown error');
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Function to handle deletion of faculty
    function handleDeleteFaculty(facultyName) {
        fetch('../Database/Faculty/delete_faculty.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                'facultyName': facultyName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                deleteConfirmToast.hide(); // Hide the confirmation toast
                deleteSuccessToast.show(); // Show the success toast
                loadFacultyData(); // Refresh the faculty list
            } else {
                console.error('Error:', data.error || 'Unknown error');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Event delegation for delete confirmation
    document.getElementById('deleteConfirmToast').addEventListener('click', function(event) {
        if (event.target.id === 'confirmDelete') {
            var facultyName = event.target.getAttribute('data-faculty-name'); // Get faculty name from button data attribute
            handleDeleteFaculty(facultyName);
        }
    });

    // Function to load faculty data
    function loadFacultyData() {
    fetch('../Database/Faculty/show_faculty.php')
        .then(response => response.json())
        .then(data => {
            var tableBody = document.querySelector('#facultyTable tbody');
            tableBody.innerHTML = '';
            
            if (data.success && data.faculties.length > 0) {
                data.faculties.forEach((faculty, index) => {
                    var row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td class="d-none">${faculty.fid}</td>
                        <td>${faculty.faculty_name}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-warning edit-btn" data-name="${faculty.faculty_name}">Edit</button>
                            <button class="btn btn-sm btn-danger delete-btn" data-name="${faculty.faculty_name}">Delete</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });

                // Attach event listeners for edit buttons
                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        var facultyName = this.getAttribute('data-name');
                        editFaculty(facultyName);
                    });
                });

                // Attach event listeners for delete buttons
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        var facultyName = this.getAttribute('data-name');
                        confirmDelete(facultyName);
                    });
                });
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            <p class="mb-0">No faculties found. Click "Add Faculty" to create one.</p>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Function to open edit faculty modal and populate with current name
    function editFaculty(facultyName) {
        document.getElementById('originalFacultyName').value = facultyName;
        document.getElementById('editFacultyName').value = facultyName;
        var editFacultyModal = new bootstrap.Modal(document.getElementById('editFacultyModal'));
        editFacultyModal.show();
    }

    // Function to open delete confirmation toast
    function confirmDelete(facultyName) {
        var deleteConfirmToastElement = document.getElementById('deleteConfirmToast');
        deleteConfirmToastElement.querySelector('.toast-body').innerHTML = `
            <p>Are you sure you want to delete the faculty <strong>"${facultyName}"</strong>?</p>
            <p class="text-danger"><strong>This action cannot be undone and will permanently delete all data related to the faculty.</strong></p>
            <div class="mt-3">
                <button type="button" class="btn btn-danger me-2" id="confirmDelete" data-faculty-name="${facultyName}">Delete Faculty</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `;
        var deleteConfirmToastInstance = new bootstrap.Toast(deleteConfirmToastElement);
        deleteConfirmToastInstance.show();
    }

    // Clear add faculty form data when modal is hidden
    var addFacultyModal = document.getElementById('addFacultyModal');
    addFacultyModal.addEventListener('hide.bs.modal', function () {
        document.getElementById('facultyName').value = '';
    });

    // Load faculty data on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadFacultyData();
    });
</script>

</body>
</html>
