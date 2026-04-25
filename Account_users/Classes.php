<?php
// Include the faculty session management
include 'session_faculty.php';

// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$faculty_id = $sessionInfo['faculty_id'];
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Class Management</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <!-- Icons. Uncomment required icon fonts -->
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
                Class added successfully!
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
                Class edited successfully!
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
                Class deleted successfully!
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
                Are you sure you want to delete this class?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            </div>
        </div>

        <div id="classExistsToast" class="toast bg-warning text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-white">
                <i class="bx bx-bell me-2"></i>
                <div class="me-auto fw-semibold">Warning</div>
                <small>Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Class already exists!
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
                        <h4 class="fw-bold py-3 mb-4 px-3 badge bg-label-primary rounded-pill">Class Management</h4>
                        <div class="card">
                            <div class="card-body">
                                
                                <h5 class="card-title d-flex justify-content-between">
                                    Class List
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                                        Add Class
                                    </button>
                                    <button id="sortButton" class="btn btn-primary">Sort by Department</button>
<div id="departmentSortContainer" class="mt-3 d-none">
    <select id="departmentSortDropdown" class="form-select">
        <option value="" selected disabled>Choose Department</option>
    </select>
    <button id="viewClassesButton" class="btn btn-success mt-2">View Classes</button>
                                </h5>
                                <table class="table" id="classTable">
                                    <thead>
                                        <tr>
                                            <th>Department Name</th>
                                            <th>Class Name</th>
                                            <th>Study Mode</th>
                                            <th>Semester</th>
                                            <th>Academic</th>
                                         
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Class data will be inserted here via AJAX -->
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

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClassForm">
                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Department Name</label>
                        <select class="form-select" id="departmentName" name="departmentName" required>
                            <!-- Department options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="className" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="className" name="className" required>
                    </div>
                    <div class="mb-3">
                        <label for="studyMode" class="form-label">Study Mode</label>
                        <select class="form-select" id="studyMode" name="studyMode" required>
                            <option value="" disabled selected>Choose Study Mode</option>
                            <!-- Options will be loaded dynamically from database -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="" disabled selected>Choose Semester</option>
                            <!-- Options will be loaded dynamically from database -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="academicYear" class="form-label">Academic Year</label>
                        <select class="form-select" id="academicYear" name="academicYear" required>
                            <!-- Academic Year options will be generated dynamically by JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="facultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="facultyName" name="facultyName" value="<?php echo htmlspecialchars($faculty); ?>" readonly>
                        <input type="hidden" id="facultyId" name="facultyId" value="<?php echo htmlspecialchars($faculty_id); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Class</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editClassForm">
                    <!-- Hidden fields to store original values -->
                    <input type="hidden" id="originalDepartmentName" name="originalDepartmentName">
                    <input type="hidden" id="originalClassName" name="originalClassName">
                    <input type="hidden" id="originalStudyMode" name="originalStudyMode">
                    <input type="hidden" id="originalSemester" name="originalSemester">
                    <input type="hidden" id="originalAcademicYear" name="originalAcademicYear">
                    
                    <div class="mb-3">
                        <label for="editDepartmentName" class="form-label">Department Name</label>
                        <select class="form-select" id="editDepartmentName" name="departmentName" required>
                            <!-- Department options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editClassName" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="editClassName" name="className" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStudyMode" class="form-label">Study Mode</label>
                        <select class="form-select" id="editStudyMode" name="studyMode" required>
                            <option value="" disabled selected>Choose Study Mode</option>
                            <!-- Options will be loaded dynamically from database -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSemester" class="form-label">Semester</label>
                        <select class="form-select" id="editSemester" name="semester" required>
                            <option value="" disabled selected>Choose Semester</option>
                            <!-- Options will be loaded dynamically from database -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editAcademicYear" class="form-label">Academic Year</label>
                        <select class="form-select" id="editAcademicYear" name="academicYear" required>
                            <!-- Academic Year options will be generated dynamically by JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editFacultyName" class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" id="editFacultyName" name="editFacultyName" value="<?php echo htmlspecialchars($faculty); ?>" readonly>
                        <input type="hidden" id="editFacultyId" name="editFacultyId" value="<?php echo htmlspecialchars($faculty_id); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>







    <!-- Department Details Modal -->
    <div class="modal fade" id="departmentDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Department Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="departmentDetailsContent">
                        <!-- Department details will be loaded here -->
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
    // Function to generate academic year options (this stays the same)
    function populateAcademicYearDropdown() {
        const academicYearSelect = document.getElementById('academicYear');
        if (!academicYearSelect) return; // Safety check
        
        const startYear = 2018;
        const currentYear = new Date().getFullYear();
        const endYear = currentYear + 5;

        academicYearSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose Academic Year';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        academicYearSelect.appendChild(defaultOption);

        for (let year = startYear; year <= endYear; year++) {
            const option = document.createElement('option');
            option.value = `${year}/${year + 1}`;
            option.textContent = `${year}/${year + 1}`;
            academicYearSelect.appendChild(option);
        }
        
        console.log('Academic year dropdown populated with', academicYearSelect.children.length, 'options');
    }

    // Function to generate edit academic year options
    function populateEditAcademicYearDropdown() {
        const academicYearSelect = document.getElementById('editAcademicYear');
        if (!academicYearSelect) return; // Safety check
        
        const startYear = 2018;
        const currentYear = new Date().getFullYear();
        const endYear = currentYear + 5;

        academicYearSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose Academic Year';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        academicYearSelect.appendChild(defaultOption);

        for (let year = startYear; year <= endYear; year++) {
            const option = document.createElement('option');
            option.value = `${year}/${year + 1}`;
            option.textContent = `${year}/${year + 1}`;
            academicYearSelect.appendChild(option);
        }
        
        console.log('Edit academic year dropdown populated with', academicYearSelect.children.length, 'options');
    }

    // Call the functions when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing...');
        fetchFormData();
        populateAcademicYearDropdown();
    });

    // Call the functions when the edit modal is shown
    document.getElementById('editClassModal').addEventListener('show.bs.modal', function (event) {
        fetchFormData();
        populateEditAcademicYearDropdown();
    });
    </script>


    <script>
$(document).ready(function() {
    // Initialize toasts
    var addSuccessToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
    var editSuccessToast = new bootstrap.Toast(document.getElementById('editSuccessToast'));
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleteSuccessToast'));
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    var classExistsToast = new bootstrap.Toast(document.getElementById('classExistsToast'));
    var classExistsToast = new bootstrap.Toast(document.getElementById('classExistsToast'));

    // Function to fetch form data (departments and ENUM values)
    function fetchFormData(callback) {
        $.ajax({
            url: '../Database_users/Classes/show_classes.php?action=form_data',
            type: 'GET',
            dataType: 'json', // This tells jQuery to automatically parse JSON
            success: function(response) {
                console.log('Form data response:', response);
                if (response.status === 'success') {
                    // Populate department dropdowns
                    var departments = response.departments;
                    var departmentOptions = '<option value="" selected disabled>Choose department</option>';
                    departments.forEach(function(department) {
                        departmentOptions += '<option value="' + department.id + '" data-name="' + department.department_name + '">' + department.department_name + '</option>';
                    });
                    $('#departmentName').html(departmentOptions);
                    $('#editDepartmentName').html(departmentOptions);
                    
                    // Populate study mode dropdowns
                    if (response.study_modes) {
                        var studyModeOptions = '<option value="" disabled selected>Choose Study Mode</option>';
                        response.study_modes.forEach(function(mode) {
                            studyModeOptions += '<option value="' + mode + '">' + mode + '</option>';
                        });
                        $('#studyMode').html(studyModeOptions);
                        $('#editStudyMode').html(studyModeOptions);
                    }
                    
                    // Populate semester dropdowns
                    if (response.semesters) {
                        var semesterOptions = '<option value="" disabled selected>Choose Semester</option>';
                        response.semesters.forEach(function(semester) {
                            semesterOptions += '<option value="' + semester + '">' + semester + '</option>';
                        });
                        $('#semester').html(semesterOptions);
                        $('#editSemester').html(semesterOptions);
                    }
                    
                    if (callback) callback();
                } else {
                    console.error('Error fetching form data:', response.message);
                    alert('Error fetching form data: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, '-', error);
                console.log('Response:', xhr.responseText);
                alert('An error occurred while fetching form data. Please check the console for details.');
            }
        });
    }

    // Call fetchFormData when the add class modal is shown
    $('#addClassModal').on('shown.bs.modal', function() {
        fetchFormData();
        populateAcademicYearDropdown(); // Call this when modal is shown
    });

    // Clear input when Add Class modal is closed
    $('#addClassModal').on('hidden.bs.modal', function () {
        $('#addClassForm')[0].reset();
    });

   // Call fetchFormData and populate edit form when the edit class modal is shown
   $('#editClassModal').on('shown.bs.modal', function() {
    var editModal = $(this);
    var row = editModal.data('row');
    
    // Fetch existing values
    var departmentName = row.find('td:eq(0)').text().trim();
    var className = row.find('td:eq(1)').text().trim();
    var studyMode = row.find('td:eq(2)').text().trim();
    var semester = row.find('td:eq(3)').text().trim();
    var academicYear = row.find('td:eq(4)').text().trim();
    var classId = row.find('.edit-btn').data('id');

    // First populate academic year dropdown
    populateEditAcademicYearDropdown();

    // Fetch form data (departments and ENUM values), then populate fields
    fetchFormData(function() {
        // Set original values in hidden fields
        editModal.find('#originalDepartmentName').val(departmentName);
        editModal.find('#originalClassName').val(className);
        editModal.find('#originalStudyMode').val(studyMode);
        editModal.find('#originalSemester').val(semester);
        editModal.find('#originalAcademicYear').val(academicYear);
        
        // Add hidden field for class ID
        if (!editModal.find('#classId').length) {
            editModal.find('#editClassForm').append('<input type="hidden" id="classId" name="classId">');
        }
        editModal.find('#classId').val(classId);
        
        // Find and select the correct department by name
        $('#editDepartmentName option').each(function() {
            if ($(this).data('name') === departmentName) {
                $(this).prop('selected', true);
                return false;
            }
        });
        
        // Populate the edit fields
        editModal.find('#editClassName').val(className);
        editModal.find('#editStudyMode').val(studyMode);
        editModal.find('#editSemester').val(semester);
        editModal.find('#editAcademicYear').val(academicYear);
    });
});




    // Function to fetch and update the class list
    function fetchClassList(page = 1) {
        $.ajax({
            url: '../Database_users/Classes/show_classes.php',
            type: 'GET',
            dataType: 'json', // This tells jQuery to automatically parse JSON
            data: {
                page: page,
                per_page: 10 // Number of items per page
            },
            success: function(response) {
                console.log('Fetch classes response:', response);
                
                // Check if there's an error in the response
                if (response.status === 'error') {
                    console.error('Server error:', response.error);
                    $('#classTable tbody').empty();
                    $('#classTable tbody').append(
                        '<tr><td colspan="6">Error: ' + response.error + '</td></tr>'
                    );
                    $('#pagination-controls').empty();
                    return;
                }
                
                const data = response; // No need to parse, jQuery already did it
                const classes = data.classes || [];
                const totalPages = data.total_pages || 1;
                const currentPage = data.current_page || 1;
                const totalRecords = data.total_records || 0;

                console.log('Classes array:', classes);
                console.log('Total pages:', totalPages);
                console.log('Current page:', currentPage);
                console.log('Total records:', totalRecords);

                $('#classTable tbody').empty();
                $('#pagination-controls').empty();

                if (classes.length === 0) {
                    $('#classTable tbody').append(
                        '<tr><td colspan="6">No classes found.</td></tr>'
                    );
                } else {
                    classes.forEach(function(classData) {
                        $('#classTable tbody').append(
                            `<tr>
                                <td>${classData.department_name}</td>
                                <td>${classData.class_name}</td>
                                <td>${classData.study_mode}</td>
                                <td>${classData.semester}</td>
                                 <td>${classData.academic}</td>
                              
                                <td class="text-end">
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="${classData.id}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${classData.id}" data-name="${classData.class_name}">Delete</button>
                                </td>
                            </tr>`
                        );
                    });

                    let paginationHtml = `<nav aria-label="Page navigation">
                                              <ul class="pagination">
                                                <li class="page-item first ${currentPage === 1 ? 'disabled' : ''}">
                                                  <a class="page-link" href="javascript:void(0);" data-page="1"><i class="tf-icon bx bx-chevrons-left"></i></a>
                                                </li>
                                                <li class="page-item prev ${currentPage === 1 ? 'disabled' : ''}">
                                                  <a class="page-link" href="javascript:void(0);" data-page="${currentPage - 1}"><i class="tf-icon bx bx-chevron-left"></i></a>
                                                </li>`;

                    for (let i = 1; i <= totalPages; i++) {
                        paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                                            <a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                                          </li>`;
                    }

                    paginationHtml += `<li class="page-item next ${currentPage === totalPages ? 'disabled' : ''}">
                                          <a class="page-link" href="javascript:void(0);" data-page="${currentPage + 1}"><i class="tf-icon bx bx-chevron-right"></i></a>
                                        </li>
                                        <li class="page-item last ${currentPage === totalPages ? 'disabled' : ''}">
                                          <a class="page-link" href="javascript:void(0);" data-page="${totalPages}"><i class="tf-icon bx bx-chevrons-right"></i></a>
                                        </li>
                                      </ul>
                                    </nav>`;
                    $('#pagination-controls').html(paginationHtml);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error);
                console.log('Response:', xhr.responseText);
                $('#classTable tbody').empty();
                $('#classTable tbody').append(
                    '<tr><td colspan="6">Error loading classes. Please refresh the page.</td></tr>'
                );
                $('#pagination-controls').empty();
            }
        });
    }

    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        fetchClassList(page);
    });

    // Initial fetch of class list
    fetchClassList();

    // Handle form submission for adding a class
    $('#addClassForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        console.log('Submitting add class form:', formData);
        
        $.ajax({
            url: '../Database_users/Classes/add_class.php',
            type: 'POST',
            dataType: 'json', // This tells jQuery to automatically parse JSON
            data: formData,
            beforeSend: function() {
                $('#addClassForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                console.log('Add class response:', response);
                $('#addClassForm button[type="submit"]').prop('disabled', false);
                
                if (response.status === 'success') {
                    $('#addClassForm')[0].reset();
                    $('#addClassModal').modal('hide');
                    fetchClassList();
                    addSuccessToast.show();
                } else if (response.message === 'Class already exists') {
                    classExistsToast.show();
                } else {
                    console.error("Error adding class:", response.message);
                    alert("An error occurred: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Add Class AJAX Error: " + status + ' - ' + error);
                console.log('Response:', xhr.responseText);
                $('#addClassForm button[type="submit"]').prop('disabled', false);
                alert("An error occurred while adding the class. Please check the console for details.");
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var class_name = $(this).data('name');
      
        $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete the class <strong>"${class_name}"</strong>?</p>
            <p class="text-danger"><strong>This action will permanently delete all related data including students and allocations.</strong></p>
            <div class="mt-3 pt-3 border-top d-flex justify-content-start">
                <button type="button" class="btn btn-sm btn-danger me-3" id="confirmDelete">Delete</button>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);
        deleteConfirmToast.show();

        $('#confirmDelete').one('click', function() {
            deleteConfirmToast.hide();
            $.ajax({
                url: '../Database_users/Classes/delete_class.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        fetchClassList();
                        deleteSuccessToast.show();
                    } else {
                        alert("An error occurred: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, "-", error);
                    console.log('Response:', xhr.responseText);
                    alert("An error occurred while deleting the class. Please try again.");
                }
            });
        });
    });


    // Handle edit button click to store row data and show edit modal
    $(document).on('click', '.edit-btn', function() {
        var row = $(this).closest('tr');
        $('#editClassModal').data('row', row).modal('show');
    });

    // Handle form submission for editing a class
    $('#editClassForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        console.log('Submitting edit class form:', formData);
        
        $.ajax({
            url: '../Database_users/Classes/edit_class.php',
            type: 'POST',
            dataType: 'json', // This tells jQuery to automatically parse JSON
            data: formData,
            beforeSend: function() {
                $('#editClassForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                console.log("Edit class response:", response);
                $('#editClassForm button[type="submit"]').prop('disabled', false);
                
                if (response.status === 'success') {
                    $('#editClassModal').modal('hide');
                    fetchClassList();
                    editSuccessToast.show();
                } else if (response.message === 'Class already exists') {
                    classExistsToast.show();
                } else {
                    console.error("Error editing class:", response.message);
                    alert("An error occurred: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Edit Class AJAX Error: " + status + ' - ' + error);
                console.log('Response:', xhr.responseText);
                $('#editClassForm button[type="submit"]').prop('disabled', false);
                alert("An error occurred while editing the class. Please check the console for details.");
            }
        });
    });

    // Handle Sort by Department button click
    $('#sortButton').on('click', function() {
        var container = $('#departmentSortContainer');
        
        if (container.hasClass('d-none')) {
            // Show the container and populate departments
            container.removeClass('d-none');
            $(this).text('Hide Sort');
            
            // Fetch departments and populate dropdown
            $.ajax({
                url: '../Database_users/Classes/show_classes.php?action=form_data',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var departments = response.departments;
                        var departmentOptions = '<option value="" selected disabled>Choose Department</option>';
                        departments.forEach(function(department) {
                            departmentOptions += '<option value="' + department.id + '">' + department.department_name + '</option>';
                        });
                        $('#departmentSortDropdown').html(departmentOptions);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching departments:', error);
                    alert('Error loading departments. Please try again.');
                }
            });
        } else {
            // Hide the container and reset to show all classes
            container.addClass('d-none');
            $(this).text('Sort by Department');
            $('#departmentSortDropdown').val('');
            fetchClassList(); // Reset to show all classes
        }
    });

    // Handle View Classes button click
    $('#viewClassesButton').on('click', function() {
        var departmentId = $('#departmentSortDropdown').val();
        
        if (!departmentId) {
            alert('Please select a department first.');
            return;
        }
        
        // Fetch classes filtered by department
        $.ajax({
            url: '../Database_users/Classes/show_classes.php',
            type: 'GET',
            dataType: 'json',
            data: {
                department_id: departmentId,
                page: 1,
                per_page: 10
            },
            success: function(response) {
                console.log('Filtered classes response:', response);
                
                if (response.status === 'error') {
                    console.error('Server error:', response.error);
                    $('#classTable tbody').empty();
                    $('#classTable tbody').append(
                        '<tr><td colspan="6">Error: ' + response.error + '</td></tr>'
                    );
                    $('#pagination-controls').empty();
                    return;
                }
                
                const classes = response.classes || [];
                const totalPages = response.total_pages || 1;
                const currentPage = response.current_page || 1;

                $('#classTable tbody').empty();
                $('#pagination-controls').empty();

                if (classes.length === 0) {
                    $('#classTable tbody').append(
                        '<tr><td colspan="6">No classes found for this department.</td></tr>'
                    );
                } else {
                    classes.forEach(function(classData) {
                        $('#classTable tbody').append(
                            `<tr>
                                <td>${classData.department_name}</td>
                                <td>${classData.class_name}</td>
                                <td>${classData.study_mode}</td>
                                <td>${classData.semester}</td>
                                <td>${classData.academic}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="${classData.id}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${classData.id}" data-name="${classData.class_name}">Delete</button>
                                </td>
                            </tr>`
                        );
                    });

                    // Add pagination if needed
                    if (totalPages > 1) {
                        let paginationHtml = `<nav aria-label="Page navigation">
                                                  <ul class="pagination">
                                                    <li class="page-item first ${currentPage === 1 ? 'disabled' : ''}">
                                                      <a class="page-link dept-page" href="javascript:void(0);" data-page="1" data-dept="${departmentId}"><i class="tf-icon bx bx-chevrons-left"></i></a>
                                                    </li>
                                                    <li class="page-item prev ${currentPage === 1 ? 'disabled' : ''}">
                                                      <a class="page-link dept-page" href="javascript:void(0);" data-page="${currentPage - 1}" data-dept="${departmentId}"><i class="tf-icon bx bx-chevron-left"></i></a>
                                                    </li>`;

                        for (let i = 1; i <= totalPages; i++) {
                            paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                                                <a class="page-link dept-page" href="javascript:void(0);" data-page="${i}" data-dept="${departmentId}">${i}</a>
                                              </li>`;
                        }

                        paginationHtml += `<li class="page-item next ${currentPage === totalPages ? 'disabled' : ''}">
                                              <a class="page-link dept-page" href="javascript:void(0);" data-page="${currentPage + 1}" data-dept="${departmentId}"><i class="tf-icon bx bx-chevron-right"></i></a>
                                            </li>
                                            <li class="page-item last ${currentPage === totalPages ? 'disabled' : ''}">
                                              <a class="page-link dept-page" href="javascript:void(0);" data-page="${totalPages}" data-dept="${departmentId}"><i class="tf-icon bx bx-chevrons-right"></i></a>
                                            </li>
                                          </ul>
                                        </nav>`;
                        $('#pagination-controls').html(paginationHtml);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error);
                console.log('Response:', xhr.responseText);
                $('#classTable tbody').empty();
                $('#classTable tbody').append(
                    '<tr><td colspan="6">Error loading classes. Please try again.</td></tr>'
                );
                $('#pagination-controls').empty();
            }
        });
    });

    // Handle pagination for department-filtered results
    $(document).on('click', '.dept-page', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const departmentId = $(this).data('dept');
        
        $.ajax({
            url: '../Database_users/Classes/show_classes.php',
            type: 'GET',
            dataType: 'json',
            data: {
                department_id: departmentId,
                page: page,
                per_page: 10
            },
            success: function(response) {
                // Same rendering logic as viewClassesButton
                const classes = response.classes || [];
                $('#classTable tbody').empty();
                
                if (classes.length === 0) {
                    $('#classTable tbody').append(
                        '<tr><td colspan="6">No classes found for this department.</td></tr>'
                    );
                } else {
                    classes.forEach(function(classData) {
                        $('#classTable tbody').append(
                            `<tr>
                                <td>${classData.department_name}</td>
                                <td>${classData.class_name}</td>
                                <td>${classData.study_mode}</td>
                                <td>${classData.semester}</td>
                                <td>${classData.academic}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="${classData.id}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${classData.id}" data-name="${classData.class_name}">Delete</button>
                                </td>
                            </tr>`
                        );
                    });
                }
            }
        });
    });
});
</script>
</body>
</html>