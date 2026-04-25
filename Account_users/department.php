<?php
include 'session_faculty.php';
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Department Management</title>
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
    <div class="toast-container">
        <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">Department added successfully!</div>
        </div>
        <div id="editSuccessToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">Department edited successfully!</div>
        </div>
        <div id="deleteSuccessToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Success</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">Department deleted successfully!</div>
        </div>
        <div id="deleteConfirmToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Confirm Delete</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Are you sure you want to delete this department?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>
        <div id="departmentExistsToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">Department already exists!</div>
        </div>
    </div>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4 p-2 badge bg-label-primary rounded-pill">Department Management</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title d-flex justify-content-between">
                                    Department List
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">Add Department</button>
                                </h5>
                                <table class="table" id="departmentTable">
                                    <thead>
                                        <tr>
                                            <th>Department Name</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="pagination-container d-flex justify-content-center mt-2" id="pagination-controls"></div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDepartmentForm">
                        <div class="mb-3">
                            <label for="departmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="departmentName" name="departmentName" required>
                        </div>
                        <div class="mb-3">
                            <label for="facultyName" class="form-label">Faculty Name</label>
                            <input type="text" class="form-control" id="facultyName" name="facultyName" value="<?php echo htmlspecialchars($faculty); ?>" readonly>
                            <input type="hidden" id="facultyId" name="facultyId" value="<?php echo htmlspecialchars($faculty_id); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Department</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDepartmentForm">
                        <input type="hidden" id="originalDepartmentName" name="originalDepartmentName">
                        <div class="mb-3">
                            <label for="editDepartmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="editDepartmentName" name="editDepartmentName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editFacultyName" class="form-label">Faculty Name</label>
                            <input type="text" class="form-control" id="editFacultyName" name="editFacultyName" readonly>
                            <input type="hidden" id="editFacultyId" name="editFacultyId">
                        </div>
                        <button type="submit" class="btn btn-primary" id="saveChangesBtn">Save Changes</button>
                    </form>
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
    <script src="../assets/js/dashboards-analytics.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script>
$(document).ready(function() {
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    var departmentExistsToast = new bootstrap.Toast(document.getElementById('departmentExistsToast'));

    function fetchDepartmentList(page = 1) {
        $.ajax({
            url: '../Database_users/Department/show_departments.php',
            type: 'GET',
            data: { page: page, per_page: 4 },
            dataType: 'json',
            success: function(response) {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.error) {
                    $('#departmentTable tbody').html('<tr><td colspan="2">Error: ' + data.error + '</td></tr>');
                    return;
                }
                const departments = data.departments || [];
                const totalPages = data.total_pages || 1;
                const currentPage = data.current_page || 1;
                $('#departmentTable tbody').empty();
                $('#pagination-controls').empty();
                if (departments.length === 0) {
                    $('#departmentTable tbody').append('<tr><td colspan="2">No departments found.</td></tr>');
                } else {
                    departments.forEach(function(department) {
                        $('#departmentTable tbody').append(`<tr><td>${department.department_name}</td><td class="text-end"><button class="btn btn-sm btn-warning edit-btn" onclick='editDepartment("${department.department_name}")'>Edit</button> <button class="btn btn-sm btn-danger delete-btn" data-department-name="${department.department_name}">Delete</button></td></tr>`);
                    });
                    let paginationHtml = `<nav><ul class="pagination"><li class="page-item first ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0);" data-page="1"><i class="tf-icon bx bx-chevrons-left"></i></a></li><li class="page-item prev ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${currentPage - 1}"><i class="tf-icon bx bx-chevron-left"></i></a></li>`;
                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a></li>`;
                    }
                    paginationHtml += `<li class="page-item next ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${currentPage + 1}"><i class="tf-icon bx bx-chevron-right"></i></a></li><li class="page-item last ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0);" data-page="${totalPages}"><i class="tf-icon bx bx-chevrons-right"></i></a></li></ul></nav>`;
                    $('#pagination-controls').html(paginationHtml);
                }
            },
            error: function(xhr) {
                $('#departmentTable tbody').html('<tr><td colspan="2">Error loading departments</td></tr>');
            }
        });
    }

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        fetchDepartmentList($(this).data('page'));
    });

    fetchDepartmentList();

    $('#addDepartmentModal').on('hidden.bs.modal', function () {
        $('#addDepartmentForm')[0].reset();
    });

    $('#addDepartmentForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../Database_users/Department/add_department.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (typeof response === 'string') response = JSON.parse(response);
                if (response.status === 'success') {
                    $('#addDepartmentForm')[0].reset();
                    $('#addDepartmentModal').modal('hide');
                    fetchDepartmentList();
                    addSuccessToast.show();
                } else if (response.message === 'Department already exists') {
                    departmentExistsToast.show();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("An error occurred");
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        var departmentName = $(this).data('department-name');
        $('#deleteConfirmToast .toast-body').html(`<p>Delete "${departmentName}"?</p><div class="mt-3"><button type="button" class="btn btn-danger me-2" id="confirmDelete">Delete</button><button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button></div>`);
        deleteConfirmToast.show();
        $('#confirmDelete').one('click', function() {
            deleteConfirmToast.hide();
            $.ajax({
                url: '../Database_users/Department/delete_department.php',
                type: 'POST',
                data: { department_name: departmentName },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        fetchDepartmentList();
                        deleteSuccessToast.show();
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("An error occurred");
                }
            });
        });
    });

    $(document).on('click', '.edit-btn', function() {
        var departmentName = $(this).closest('tr').find('td:first').text().trim();
        $('#editDepartmentName').val(departmentName);
        $('#editFacultyName').val("<?php echo htmlspecialchars($faculty); ?>");
        $('#editFacultyId').val("<?php echo htmlspecialchars($faculty_id); ?>");
        $('#originalDepartmentName').val(departmentName);
        $('#editDepartmentModal').modal('show');
    });

    $('#editDepartmentForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../Database_users/Department/edit_department.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#editDepartmentModal').modal('hide');
                    fetchDepartmentList();
                    editSuccessToast.show();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("An error occurred");
            }
        });
    });
});
</script>
</body>
</html>