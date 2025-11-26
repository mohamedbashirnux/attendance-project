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
                            <option value="Full time morning">Full time morning</option>
                            <option value="Full time afternoon">Full time afternoon</option>
                            <option value="Full time evening">Full time evening</option>
                            <option value="Weekend">Weekend</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <!-- Semester options will be generated dynamically by JavaScript -->
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
                        <input type="text" class="form-control" id="facultyName" name="facultyName" value="<?php echo htmlspecialchars($_SESSION['faculty']); ?>" readonly>
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
                            <option value="Full time morning">Full time morning</option>
                            <option value="Full time afternoon">Full time afternoon</option>
                            <option value="Full time evening">Full time evening</option>
                            <option value="Weekend">Weekend</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editSemester" class="form-label">Semester</label>
                        <select class="form-select" id="editSemester" name="semester" required>
                            <!-- Semester options will be generated dynamically by JavaScript -->
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
                        <input type="text" class="form-control" id="editFacultyName" name="editFacultyName" value="<?php echo htmlspecialchars($_SESSION['faculty']); ?>" readonly>
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
    // Function to generate semester options
    function populateEditSemesterDropdown() {
        const semesterSelect = document.getElementById('editSemester');
        // Clear existing options
        semesterSelect.innerHTML = '';

        // Create the "Choose Semester" option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose Semester';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        semesterSelect.appendChild(defaultOption);

        // Generate semester options from 1 to 12
        for (let i = 1; i <= 12; i++) {
            const option = document.createElement('option');
            option.value = i; // Set the value to the number
            option.textContent = `Semester ${i}`; // Display text
            semesterSelect.appendChild(option);
        }
    }

    // Function to generate academic year options
    function populateEditAcademicYearDropdown() {
        const academicYearSelect = document.getElementById('editAcademicYear');
        const startYear = 2018; // Start from 2018
        const currentYear = new Date().getFullYear();
        const endYear = currentYear + 5; // End year is current year + 5

        // Clear existing options
        academicYearSelect.innerHTML = '';

        // Create the "Choose Academic Year" option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose Academic Year';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        academicYearSelect.appendChild(defaultOption);

        // Generate academic year options from startYear to endYear
        for (let year = startYear; year <= endYear; year++) {
            const option = document.createElement('option');
            option.value = `${year}-${year + 1}`; // e.g., "2024-2025"
            option.textContent = `${year}-${year + 1}`; // e.g., "2024-2025"
            academicYearSelect.appendChild(option);
        }
    }

    // Call the functions when the edit modal is shown
    document.getElementById('editClassModal').addEventListener('show.bs.modal', function (event) {
        populateEditSemesterDropdown();
        populateEditAcademicYearDropdown();
    });
</script>

    <script>
    // Function to generate semester options
    function populateSemesterDropdown() {
        const semesterSelect = document.getElementById('semester');
        // Clear existing options
        semesterSelect.innerHTML = '';

        // Create the "Choose Semester" option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose Semester';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        semesterSelect.appendChild(defaultOption);

        // Generate semester options from 1 to 12
        for (let i = 1; i <= 12; i++) {
            const option = document.createElement('option');
            option.value = i; // Set the value to the number
            option.textContent = `Semester ${i}`; // Display text
            semesterSelect.appendChild(option);
        }
    }

    // Function to generate academic year options
    function populateAcademicYearDropdown() {
        const academicYearSelect = document.getElementById('academicYear');
        const startYear = 2018; // Start from 2018
        const currentYear = new Date().getFullYear();
        const endYear = currentYear + 5; // End year is current year + 5

        // Clear existing options
        academicYearSelect.innerHTML = '';

        // Create the "Choose Academic Year" option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Choose Academic Year';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        academicYearSelect.appendChild(defaultOption);

        // Generate academic year options from startYear to endYear
        for (let year = startYear; year <= endYear; year++) {
            const option = document.createElement('option');
            option.value = `${year}-${year + 1}`; // e.g., "2024-2025"
            option.textContent = `${year}-${year + 1}`; // e.g., "2024-2025"
            academicYearSelect.appendChild(option);
        }
    }

    // Call the functions when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        populateSemesterDropdown();
        populateAcademicYearDropdown();
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

    // Function to fetch department names based on logged-in faculty
    function fetchDepartmentNames(callback) {
        $.ajax({
            url: '../Database_users/Classes/get_department_names.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var departments = response.data;
                    var options = '<option value="" selected disabled>Choose department</option>';
                    departments.forEach(function(department) {
                        options += '<option value="' + department + '">' + department + '</option>';
                    });
                    $('#departmentName').html(options);
                    $('#editDepartmentName').html(options);
                    if (callback) callback();
                } 
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, '-', error);
                console.log('Response:', xhr.responseText);
                alert('An error occurred while fetching department names. Please check the console for details.');
            }
        });
    }

    // Call fetchDepartmentNames when the add class modal is shown
    $('#addClassModal').on('shown.bs.modal', fetchDepartmentNames);

    // Clear input when Add Class modal is closed
    $('#addClassModal').on('hidden.bs.modal', function () {
        $('#addClassForm')[0].reset();
    });

   // Call fetchDepartmentNames and populate edit form when the edit class modal is shown
   $('#editClassModal').on('shown.bs.modal', function() {
    var editModal = $(this);
    var row = editModal.data('row');
    
    // Fetch existing values
    var departmentName = row.find('td:eq(0)').text().trim();
    var className = row.find('td:eq(1)').text().trim();
    var studyMode = row.find('td:eq(2)').text().trim();
    var semester = row.find('td:eq(3)').text().trim();
    var academicYear = row.find('td:eq(4)').text().trim(); 

    // Fetch department names and populate fields
    fetchDepartmentNames(function() {
        // Set original values in hidden fields
        editModal.find('#originalDepartmentName').val(departmentName);
        editModal.find('#originalClassName').val(className);
        editModal.find('#originalStudyMode').val(studyMode);
        editModal.find('#originalSemester').val(semester);
        editModal.find('#originalAcademicYear').val(academicYear);
        
        // Populate the edit fields
        editModal.find('#editDepartmentName').val(departmentName).change(); // Update department dropdown
        editModal.find('#editClassName').val(className); // Update class name
        editModal.find('#editStudyMode').val(studyMode).change(); // Update study mode dropdown
        editModal.find('#editSemester').val(semester).change(); // Update semester dropdown
        editModal.find('#editAcademicYear').val(academicYear); // Update academic year dropdown
    });
});




    // Function to fetch and update the class list
    function fetchClassList(page = 1) {
        $.ajax({
            url: '../Database_users/Classes/show_classes.php',
            type: 'GET',
            data: {
                page: page,
                per_page: 10 // Number of items per page
            },
            success: function(response) {
                const data = JSON.parse(response);
                const classes = data.classes;
                const totalPages = data.total_pages;
                const currentPage = data.current_page;

                $('#classTable tbody').empty();
                $('#pagination-controls').empty();

                if (classes.length === 0) {
                    $('#classTable tbody').append(
                        '<tr><td colspan="5">No classes found.</td></tr>'
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
                                    <button class="btn btn-sm btn-warning edit-btn  data-id="${classData.id}"">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${classData.id}" data-department="${classData.department_name}" data-study="${classData.study_mode}" data-name="${classData.class_name}">Delete</button>

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
                alert("An error occurred while fetching class data. Please check the console for details.");
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
        $.ajax({
            url: '../Database_users/Classes/add_class.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addClassForm')[0].reset();
                    $('#addClassModal').modal('hide');
                    fetchClassList();
                    addSuccessToast.show();
                } else if (response.status === 'warning' && response.message === 'Class already exists') {
                    classExistsToast.show();
                } else {
                    console.error("Error adding class:", response.message);
                    alert("An error occurred: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ' - ' + error);
                console.log('Response:', xhr.responseText);
                alert("An error occurred while adding the class. Please check the console for details.");
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
    var id = $(this).data('id');
    var class_name = $(this).data('name');
    var department_name = $(this).data('department');
    var study_mode = $(this).data('study');
  
    $('#deleteConfirmToast .toast-body').html(`
        <p>HADDI AAD DELETE GAREESO CLASS-KAN WAXA LUMAAYO DHAMAAN XOGTA (STUDENTS, ALLOCATES)<strong>"${class_name}"</strong>?</p>
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
            data: { 
                id: id,
                class_name: class_name,
                department_name: department_name,
                study_mode: study_mode,
            },
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
                alert("An error occurred while deleting the classes. Please try again.");
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
    $.ajax({
        url: '../Database_users/Classes/edit_class.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            console.log("Server response:", response); // Add this line for debugging
            if (response.status === 'success') {
                $('#editClassModal').modal('hide');
                fetchClassList();
                editSuccessToast.show();
            } else if (response.status === 'warning' && response.message === 'Class already exists') {
                classExistsToast.show();
            } else {
                console.error("Error editing class:", response.message);
                alert("An error occurred: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: " + status + ' - ' + error);
            console.log('Response:', xhr.responseText);
            alert("An error occurred while editing the class. Please check the console for details.");
        }
    });
});
});
</script>
<script>
    $(document).ready(function() {
    // Fetch and display department list in the dropdown
    $('#sortButton').on('click', function() {
        $.ajax({
            url: '../Database_users/Classes/get_department_names.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const departments = response.data;
                    const dropdown = $('#departmentSortDropdown');
                    dropdown.empty();
                    dropdown.append('<option value="" selected disabled>Choose Department</option>');
                    departments.forEach(function(department) {
                        dropdown.append(`<option value="${department}">${department}</option>`);
                    });
                    $('#departmentSortContainer').removeClass('d-none');
                } else {
                    alert('Failed to fetch departments.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching departments:', error);
                alert('An error occurred. Please check the console for details.');
            }
        });
    });

    // Fetch and display classes for the selected department
    $('#viewClassesButton').on('click', function() {
        const selectedDepartment = $('#departmentSortDropdown').val();
        if (!selectedDepartment) {
            alert('Please select a department.');
            return;
        }

        $.ajax({
            url: '../Database_users/Classes/get_classes_by_department.php',
            type: 'GET',
            data: { department: selectedDepartment },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const classes = response.data;
                    const tbody = $('#classTable tbody');
                    tbody.empty();
                    if (classes.length === 0) {
                        tbody.append('<tr><td colspan="6">No classes found for this department.</td></tr>');
                    } else {
                        classes.forEach(function(classData) {
                            tbody.append(
                                `<tr>
                                    <td>${classData.department_name}</td>
                                    <td>${classData.class_name}</td>
                                    <td>${classData.study_mode}</td>
                                    <td>${classData.semester}</td>
                                    <td>${classData.academic}</td>
                                    <td class="text-end">
                                        <button class="btn btn-primary btn-sm">Edit</button>
                                        <button class="btn btn-danger btn-sm">Delete</button>
                                    </td>
                                </tr>`
                            );
                        });
                    }
                } else {
                    alert('Failed to fetch classes.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching classes:', error);
                alert('An error occurred. Please check the console for details.');
            }
        });
    });
});

</script>
</body>
</html>