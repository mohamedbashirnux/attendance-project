<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['department_name']) && isset($_GET['class_name']) && isset($_GET['study_mode']) && isset($_GET['semester'])&& isset($_GET['academic'])) {
        $departmentName = htmlspecialchars($_GET['department_name']);
        $className = htmlspecialchars($_GET['class_name']);
        $study_mode = htmlspecialchars($_GET['study_mode']);
        $semester = htmlspecialchars($_GET['semester']);
        $academic = htmlspecialchars($_GET['academic']);
        
    } else {
        echo "Department name, class name, and study mode are required.";
        exit();
    }
} else {
    echo "Invalid request method.";
    exit();
}

// Database connection

include "../connection/connect.php";

try {
    // SQL query to fetch students based on the dynamic department_name
    $sql = "SELECT subject_name, department_name, faculty_name FROM subjects WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$departmentName]);

    // Initialize options string
    $options = '';

    // Fetch students and append to options
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options .= "<label><input type='checkbox' value='" . htmlspecialchars($row['subject_name']) . "' class='student-checkbox'> " . htmlspecialchars($row['subject_name']) . "</label>";
    }

    // Close statement
    $stmt = null;

    // Prepare second SQL query
    $squr = "SELECT * FROM subject_class WHERE class_name = ? AND department_name = ? AND study_mode = ? AND faculty_name = ?";
    $stmt2 = $conn->prepare($squr);

    // Bind parameters to the prepared statement and execute
    $stmt2->execute([$className, $departmentName, $study_mode, $faculty]);

    $subjects = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['subjects'] = $subjects;

    // Close statement and connection
    $stmt2 = null;
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
    <title>Subjects in class</title>
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
        body {
            font-family: 'Public Sans', sans-serif;
        }
        .dropdown-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .dropdown-toggle {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 10px;
            cursor: pointer;
            text-align: left;
            font-size: 16px;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-menu label {
            display: flex;
            align-items: center;
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #dee2e6;
        }
        .dropdown-menu label:hover {
            background-color: #f1f1f1;
        }
        .dropdown-menu input[type="checkbox"] {
            margin-right: 10px;
        }
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 10px;
            font-size: 16px;
            color: #495057;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body>   
<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
    <div id="submitToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Subject added successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <div id="deleted_submit" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Subject deleted successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="errorToastBody">
               
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
                Are you sure you want to delete this department?
                <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                    <button type="button" class="btn btn-sm btn-warning me-3" id="confirmDelete">Delete</button>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="toast">Cancel</button>
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
                        <div class="d-flex align-items-center mb-4">
                            <a href="before_sub_class.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Selected Class Details </h4>
                        </div>
                        
                        <div class="card">

                            <div class="card-body">
                               
                                <div class="d-flex flex-column">
                                    <div>
                                    <strong>Class Name:</strong> <?php echo $className .' ('.$study_mode. ')'; ?>
                                    </div>
                                    <div>
                                    <strong>Department Name:</strong> <?php echo $departmentName; ?>
                                    </div>
                                    <div>
                                    <strong>Academic:</strong> <?php echo $academic; ?>
                                    </div>
                                    <div>
                                    <strong>Semester:</strong> <?php echo $semester; ?>
                                    </div>
                                   
                                </div>
                                
                              
                             
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">

                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Subject</button>
                          
                            <div class="d-flex align-content-center align-items-lg-center">
                            
                            <button type="button" class="btn btn-danger" id="deleteAllBtn">Delete All</button>

                            </div>
                            
                        </div>



                                <div class="table-">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Subject name</th>
                                             
                                               
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="">
                                            <!-- Student data will be dynamically inserted here -->
                                            <?php 
                                                if (!empty($subjects)) {
                                                    $id = 1; // Initialize the ID counter
                                                    foreach ($subjects as $sub) {
                                                        echo '<tr>';
                                                        echo '<td>' . $id . '</td>'; // Display the auto-incremented ID
                                                        echo '<td>' . htmlspecialchars($sub['subject_name']) . '</td>';
                                                        echo '<td class="text-end">
                                                                <button class="btn btn-sm btn-danger delete-btn" data-id="' . $sub['id'] . '" data-subject_name="' . $sub['subject_name'] . '" data-class_name="' . $sub['class_name'] . '" data-department_name="' . $sub['department_name'] . '" data-study_mode="' . $sub['study_mode'] . '">Delete</button>
                                                            </td>';
                                                        echo '</tr>';
                                                        $id++; // Increment the ID counter for the next row
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="3" class="text-center">No subjects found for this calss</td></tr>';
                                                } 
                                                ?>


                                        </tbody>
                                    </table>
                                </div>
                                 <!-- Pagination -->
           
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudentForm" action="../Database_users/subject/add_subject_class.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <!-- Dropdown for Selecting Students -->
                        <div class="dropdown-container col-md-6 mb-3">
                            <div class="dropdown-toggle" id="dropdownToggle">Select Students</div>

                            <div class="dropdown-menu" id="dropdownMenu">
                                <!-- Search input for filtering students -->
                                <div class="p-2">
                                <input type="text" id="searchInput" class="form-control mb-2" placeholder="Search Subjects.....">
                                </div>
                             

                                <!-- Options generated from PHP -->
                                <div id="studentList">
                                    <?php echo $options; // PHP-generated options ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="departmentName" name="departmentName" value="<?php echo htmlspecialchars($departmentName); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="className" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="className" name="className" value="<?php echo htmlspecialchars($className); ?>" readonly>
                    </div>

                    <input type="hidden" id="class_id" name="class_id" value="<?php echo htmlspecialchars($classId); ?>">

                    <div class="mb-3">
                        <label for="studyMode" class="form-label">Study Mode</label>
                        <input type="text" class="form-control" id="studyMode" name="studyMode" value="<?php echo htmlspecialchars($study_mode); ?>" readonly>
                    </div>

                    <input type="hidden" name="faculty" value="<?php echo htmlspecialchars($faculty); ?>">
                </div>

                <input type="hidden" id="selectedStudents" name="selectedSubjects">
                <!-- other form fields -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
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
document.addEventListener('DOMContentLoaded', function () {
    var dropdownToggle = document.getElementById('dropdownToggle');
    var deleteSuccessToast = new bootstrap.Toast(document.getElementById('deleted_submit'));
    var dropdownMenu = document.getElementById('dropdownMenu');
    var selectedStudentsInput = document.getElementById('selectedStudents');
    var selectedCount = document.getElementById('selectedCount');
    var dropdownContainer = document.querySelector('.dropdown-container');

    // Toggle the dropdown menu
    dropdownToggle.addEventListener('click', function () {
        dropdownMenu.classList.toggle('show');
    });

    // Update input field with selected checkboxes
    function updateSelectedOptions() {
        var checkboxes = document.querySelectorAll('.student-checkbox:checked');
        var selectedValues = Array.from(checkboxes).map(cb => cb.value);
        selectedStudentsInput.value = selectedValues.join(', ');
        selectedCount.textContent = `${selectedValues.length} selected`;
    }

    // Attach change event listeners to all checkboxes
    document.querySelectorAll('.student-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedOptions);
    });

    // Click outside to close the dropdown
    document.addEventListener('click', function(event) {
        if (!dropdownContainer.contains(event.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

    // AJAX form submission
    var addStudentForm = document.getElementById('addStudentForm');
    addStudentForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent the default form submission

        var formData = new FormData(addStudentForm);

        fetch(addStudentForm.action, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json()) // Parse JSON response
        .then(data => {
            if (data.status === 'success') {
                // Show the success toast notification
                var toast = new bootstrap.Toast(document.getElementById('submitToast'));
                toast.show();

                // Close the modal
                var addStudentModalElement = document.getElementById('addStudentModal');
                var addStudentModal = bootstrap.Modal.getInstance(addStudentModalElement);
                addStudentModal.hide();

                // Optionally, refresh the students list or the entire page
                setTimeout(() => {
                    location.reload(); // Reload the page to reflect changes (optional)
                }, 700); // Adjust the timeout as needed

            } else if (data.status === 'error') {
                // Show an error toast notification with the message from the server
                var errorToast = new bootstrap.Toast(document.getElementById('errorToast')); // Assuming there's an error toast
                document.getElementById('errorToastBody').innerText = data.message; // Update the error message
                errorToast.show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });

    // Add click event for delete buttons
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function () {
            var id = $(this).data('id');
            var subjectName = $(this).data('subject_name');
            var departmentName = $(this).data('department_name');
            var className = $(this).data('class_name');
            var studyMode = $(this).data('study_mode');

            // Show confirmation toast
            var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
            $('#deleteConfirmToast .toast-body').html(`
                <p>Are you sure you want to delete the subject <strong>${subjectName}</strong>?</p>
                <div class="mt-3">
                    <button type="button" class="btn btn-danger me-2" id="confirmDeleteRow">Delete Subject</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
                </div>
            `);

            deleteConfirmToast.show();

            // Confirm delete action
            $('#confirmDeleteRow').one('click', function() {
                deleteConfirmToast.hide(); // Hide the confirmation toast

                // Perform AJAX request to delete the subject
                $.ajax({
                    url: '../Database_users/subject/delete_sub_class.php',
                    type: 'POST',
                    data: {
                        id: id,
                        subject_name: subjectName,
                        department_name: departmentName,
                        class_name: className,
                        study_mode: studyMode
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            deleteSuccessToast.show(); // Show success toast
                            setTimeout(function () {
                                window.location.reload();
                            }, 800); // Reload after a delay
                        } else {
                            alert("An error occurred: " + response.message); // Keep the alert for error handling
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", status, "-", error);
                        alert("An error occurred while deleting the subject. Please try again."); // Keep the alert for error handling
                    }
                });
            });
        });
    });

    var deleteAllBtn = document.getElementById('deleteAllBtn');
    deleteAllBtn.addEventListener('click', function() {
        const className = "<?php echo htmlspecialchars($className); ?>";
        const departmentName = "<?php echo htmlspecialchars($departmentName); ?>";
        const studyMode = "<?php echo htmlspecialchars($study_mode); ?>";
        const facultyName = "<?php echo htmlspecialchars($faculty); ?>";
        var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
        $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete all subjects <strong></strong>?</p>
            <p class="text-danger"><strong>This action cannot be undone and will permanently delete all</strong></p>
            <div class="mt-3">
                <button type="button" class="btn btn-danger me-2" id="confirmDeleteAll">Delete Subject</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);

        deleteConfirmToast.show();

        $('#confirmDeleteAll').one('click', function() {
            deleteConfirmToast.hide();

            fetch('../Database_users/subject/delete_all_subjects.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'class_name=' + encodeURIComponent(className) +
                      '&department_name=' + encodeURIComponent(departmentName) +
                      '&study_mode=' + encodeURIComponent(studyMode) +
                      '&faculty_name=' + encodeURIComponent(facultyName)
            })
            .then(response => response.json())  // Parse the response as JSON
            .then(data => {
                if (data.status === 'success') {
                    // Show success toaster notification
                    deleteSuccessToast.show();

                    // Reload the page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 700); // Adjust the delay as needed
                } else {
                    // Show error message
                    alert(data.message || 'Failed to delete the subjects. Please try again.'); // Keep the alert for error handling
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting subjects.'); // Keep the alert for error handling
            });
        });
    });

    document.getElementById('searchInput').addEventListener('input', function() {
        var filter = this.value.toLowerCase();
        var subjects = document.querySelectorAll('.dropdown-menu label');

        subjects.forEach(function(subject) {
            var subjectText = subject.textContent.toLowerCase();
            if (subjectText.includes(filter)) {
                subject.style.display = ''; // Show the subject if it matches the filter
            } else {
                subject.style.display = 'none'; // Hide the subject if it doesn't match
            }
        });
    });
});
</script>

</body>
</html>
