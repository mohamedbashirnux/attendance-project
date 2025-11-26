<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '';

// Include the database connection
// include "../app/conn.php";
include ".././connection/connect.php";

// Initialize variables and save them to the session if they exist in the URL parameters
$class_name = isset($_GET['class_name']) ? $_GET['class_name'] : (isset($_SESSION['class_name']) ? $_SESSION['class_name'] : '');
$department_name = isset($_GET['department_name']) ? $_GET['department_name'] : (isset($_SESSION['department_name']) ? $_SESSION['department_name'] : '');
$study_mode = isset($_GET['study_mode']) ? $_GET['study_mode'] : (isset($_SESSION['study_mode']) ? $_SESSION['study_mode'] : '');
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : (isset($_SESSION['faculty']) ? $_SESSION['faculty'] : '');
$semester = isset($_GET['semester']) ? $_GET['semester'] : (isset($_SESSION['semester']) ? $_SESSION['semester'] : '');
$academic = isset($_GET['academic']) ? $_GET['academic'] : (isset($_SESSION['academic']) ? $_SESSION['academic'] : '');
$search_term = isset($_GET['search_student_id']) ? $_GET['search_student_id'] : (isset($_SESSION['search_student_id']) ? $_SESSION['search_student_id'] : '');

// Save the data to the session
$_SESSION['class_name'] = $class_name;
$_SESSION['department_name'] = $department_name;
$_SESSION['study_mode'] = $study_mode;
$_SESSION['faculty'] = $faculty;
$_SESSION['semester'] = $semester;
$_SESSION['academic'] = $academic;
$_SESSION['search_student_id'] = $search_term;

$stmt = $conn->prepare("
SELECT 
    absents.*,
    CONCAT('Absent- ', FORMAT((COUNT(*) / total_days_table.total_days * 10), 1), '%') AS absence_percentage
FROM 
    absents
INNER JOIN (
    SELECT 
        student_name,
        subject_name,
        COUNT(DISTINCT CONCAT(subject_name, class_name)) AS total_days
    FROM
        absents
    WHERE
        class_name = :class_name AND department_name = :department_name AND study_mode = :study_mode
    " . ($search_term ? " AND (student_id LIKE :search_term OR student_name LIKE :search_term)" : "") . "
    GROUP BY 
        student_name, subject_name
) AS total_days_table 
ON absents.student_name = total_days_table.student_name AND absents.subject_name = total_days_table.subject_name
GROUP BY 
    absents.student_name, absents.subject_name, absents.statuses
    ORDER BY subject_name ASC
");

$stmt->bindParam(':class_name', $class_name);
$stmt->bindParam(':department_name', $department_name);
$stmt->bindParam(':study_mode', $study_mode);
if ($search_term) {
    $search_term_param = "%{$search_term}%";
    $stmt->bindParam(':search_term', $search_term_param);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Class absents</title>
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

<div class="toast-container position-fixed top-0 end-0 p-3">
    <!-- Success Toast -->
    <div id="addSuccessToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Delete data successfully!
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

    <!-- Error Toast -->
    <div id="errorToast" class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <strong class="me-auto">Error</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            An error occurred. Please try again.
        </div>
    </div>
</div>
<!--  -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="d-flex align-items-center mb-4">
                            <a href="selection_absents.php" class="btn btn-secondary me-3"><i class='bx bx-arrow-back'></i></a>
                            <h4 class="fw-bold m-0">Absent Details  </h4>
                        </div>
                        <div class="d-flex card-body bg-white">
                        <div class="d-flex flex-column bg-white p-2 m-2">
                                    <div>
                                        <strong>Class Name:</strong> <?php echo $class_name .' ('.$study_mode. ')'; ?>
                                    </div>
                                    
                                    <div>
                                    <strong>Semester:</strong> <?php echo $semester; ?>
                                    </div>
                                    <div>
                                    <strong>academic:</strong> <?php echo $academic; ?>
                                    </div>
                                    <div>
                                    <strong>Departments Name:</strong> <?php echo $department_name; ?>
                                    </div>
                                    <div>
                                    <strong>Faculty Name:</strong> <?php echo $faculty; ?>
                                    </div>
                           
              </div>
                            </div>
                        <div class="card mt-4">
                            
                       
                            <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                
                                  <!-- Add Student Button -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAbsentModal">
    Add Student
</button>

                                    <div class="d-flex align-content-center align-items-lg-center">
                                        <!-- Search -->
                                        <input type="text" class="form-control me-2" id="searchStudentId" placeholder="Search for a student by ID..." style="width: 300px;">
                                        <button type="button" class="btn btn-primary" id="searchButton"><i class='bx bx-search-alt-2'></i></button>
                                    </div>
                                 <div class="class d-flex ">
                                 <button type="button" class="btn btn-danger mx-2 delete_whole">
                                    clear
                                 </button>
                                 <div class="btn-group">
                                        <button
                                            type="button"
                                            class="btn btn-primary dropdown-toggle"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                          <i class='bx bx-download'></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                           <a href="download_absents_pdf.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&faculty=<?php echo urlencode($faculty); ?>" class="dropdown-item">Class Report</a>
                                           <li><a class="dropdown-item" href="re-exam_report.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>&faculty=<?php echo urlencode($faculty); ?>">Exam Report</a></li>
                                           <li><a class="dropdown-item" href="singlesubject_report.php?class_name=<?php echo urlencode($class_name); ?>&department_name=<?php echo urlencode($department_name); ?>&study_mode=<?php echo urlencode($study_mode); ?>&semester=<?php echo urlencode($semester); ?>&academic=<?php echo urlencode($academic); ?>&faculty=<?php echo urlencode($faculty); ?>">Subject Report</a></li>
                                           
                                        </ul>
                                        </div>
                                </div>
                                 </div>


                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Student id</th>
                                                <th>Student Name</th>
                                                <th>Subject Name</th>
                                                <!-- <th>Class Name</th> -->
                                                <th>Absence Percentage  </th>
                                              
                                            </tr>
                                        </thead>
                                        <tbody id="studentTableBody">
                                        <?php if (!empty($results)) {
                                                foreach ($results as $student) {
                                                    $percentage_value = floatval(str_replace(['Absent- ', '%'], '', $student['absence_percentage']));
                                                    $badge_color = 'success'; // Default to green

                                                    if ($percentage_value == 10) {
                                                        $badge_color = 'success'; // Green
                                                    } elseif ($percentage_value == 20) {
                                                        $badge_color = 'warning'; // Yellow/Orange (Warning)
                                                    } elseif ($percentage_value >= 30) {
                                                        $badge_color = 'danger'; // Red
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($student['subject_name']) . '</td>';
                                                    // echo '<td>' . htmlspecialchars($student['class_name']) . '</td>';
                                                    echo '<td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($student['absence_percentage']) . '</span></td>';
                                                   
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">No data found.</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- Add Student Absent Modal -->
<div class="modal fade" id="addAbsentModal" tabindex="-1" aria-labelledby="addAbsentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAbsentModalLabel">Add Absent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAbsentForm">
                    <div class="mb-3">
                        <label for="studentIdInput" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="studentIdInput" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentName" class="form-label">Student Name</label>
                        <input type="text" class="form-control" id="studentName" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="subjectSelect" class="form-label">Subject</label>
                        <select class="form-control" id="subjectSelect" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="absentDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="absentDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="statuses" class="form-label">Status</label>
                        <input type="text" class="form-control" id="statuses" required readonly value="absent">
                    </div>
                    <div class="mb-3">
                    <label for="cudurDaar" class="form-label">Cudur Daar</label>
                        <select class="form-control" id="cudurDaar" required>
                        <option value="" disabled selected>Dooro sababta</option>
                            <option value="Cudur daar la'aan">Cudur daar la'aan</option>
                            <option value="Medical">Medical</option>
                            <option value="Family Emergency">Family Emergency</option>
                            <option value="Personal reasons">Personal reasons</option>
                        </select>
                    </div>
                    <input type="hidden" name="faculty" id="faculty" value="<?php echo htmlspecialchars($faculty); ?>">
                    <button type="submit" class="btn btn-primary">Add Absent</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal subject to generate the report -->

<div class="modal fade" id="subjectReportModal" tabindex="-1" aria-labelledby="subjectReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="subjectReportModalLabel">Subject Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select a subject to generate the report:</p>
                <select id="subjectSelectReport" class="form-control">
                    <option value="">Select Subject</option>
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmSubjectReport" class="btn btn-primary">Generate Report</a>
            </div>
        </div>
    </div>
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
    
    function goToDetails(studentId, faculty) {
    window.location.href = 'single_student.php?student_id=' + studentId + '&faculty=' + faculty;
}

document.getElementById('searchStudentId').addEventListener('input', function() {
    var searchStudentId = this.value;
    var params = new URLSearchParams(window.location.search);
    params.set('search_student_id', searchStudentId);
    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
    fetchStudentData(searchStudentId);
});

function fetchStudentData(searchStudentId) {
    var xhr = new XMLHttpRequest();
    var params = new URLSearchParams(window.location.search);
    params.set('search_student_id', searchStudentId);
    xhr.open('GET', `${window.location.pathname}?${params.toString()}`, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xhr.responseText, 'text/html');
            var newTableBody = doc.getElementById('studentTableBody').innerHTML;
            document.getElementById('studentTableBody').innerHTML = newTableBody;
            var newPagination = doc.querySelector('.pagination') ? doc.querySelector('.pagination').innerHTML : '';
            document.querySelector('.pagination').innerHTML = newPagination;
        }
    };
    xhr.send();
}

document.getElementById('studentIdInput').addEventListener('change', function () {
    var studentId = this.value;
    var faculty = document.getElementById('faculty').value;
    var className = "<?php echo $class_name; ?>";
    var departmentName = "<?php echo $department_name; ?>";
    var studyMode = "<?php echo $study_mode; ?>";
   
    // console.log(departmentName)
    // console.log(studyMode)
    // Fetch Student Name
    fetch(`/attendanceproject1/Database_users/absent/get_student_name.php?student_id=${studentId}&faculty=${faculty}&class_name=${className}&department_name=${departmentName}&study_mode=${studyMode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('studentName').value = data.student_name;
            } else {
                document.getElementById('studentName').value = 'Student not found';
                // alert('Student not found');
            }
        });

    // Fetch Subjects
    var className = "<?php echo $class_name; ?>";
    var departmentName = "<?php echo $department_name; ?>";
    var studyMode = "<?php echo $study_mode; ?>";

    fetch(`/attendanceproject1/Database_users/absent/get_subjects.php?class_name=${className}&department_name=${departmentName}&study_mode=${studyMode}`)
        .then(response => response.json())
        .then(data => {
            var subjectSelect = document.getElementById('subjectSelect');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            data.subjects.forEach(function (subject) {
                var option = document.createElement('option');
                option.value = subject.subject_name;
                option.textContent = subject.subject_name;
                subjectSelect.appendChild(option);
            });
        });
});

document.getElementById('addAbsentForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent the default form submission

    // Get the form data
    var studentId = document.getElementById('studentIdInput').value;
    var studentName = document.getElementById('studentName').value;
    var subject = document.getElementById('subjectSelect').value;
    var absentDate = document.getElementById('absentDate').value;
    var status = document.getElementById('statuses').value;
    var cudurDaar = document.getElementById('cudurDaar').value;
    var faculty = document.getElementById('faculty').value;
    var className = "<?php echo $class_name; ?>";
    var departmentName = "<?php echo $department_name; ?>";
    var studyMode = "<?php echo $study_mode; ?>";

    // Check if studentName is "Student not found"
    if (studentName === "Student not found") {
        alert('Cannot submit data: Student not found.');
        return; // Stop further execution if student not found
    }

    // Format the date as "d-d-mon-year"
    var formattedDate = new Date(absentDate);
    var dayName = formattedDate.toLocaleString('en-GB', { weekday: 'short' }); // 'Wed'
    var day = String(formattedDate.getDate()).padStart(2, '0'); // '14'
    var month = String(formattedDate.getMonth() + 1).padStart(2, '0'); // '08'
    var year = formattedDate.getFullYear(); // '2024'
    var formattedDateString = `${dayName}-${day}-${month}-${year}`;

    // Prepare the data to be sent
    var formData = {
        student_id: studentId,
        student_name: studentName,
        class_name: className, // Make sure to pass these values from your form
        department_name: departmentName,
        study_mode: studyMode,
        subject: subject,
        absent_date: formattedDateString,
        status: status,
        cudur_daar: cudurDaar,
        faculty: faculty
    };

    // Send the data using fetch
    fetch('/attendanceproject1/Database_users/absent/submit_absent.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast
            var successToast = new bootstrap.Toast(document.getElementById('addSuccessToast'));
            successToast.show();
            setTimeout(function() {
                location.reload();
            }, 800);

            document.getElementById('addAbsentForm').reset(); // Clear the form
            // Close the modal if necessary
            var addAbsentModal = new bootstrap.Modal(document.getElementById('addAbsentModal'));
            addAbsentModal.hide();
        } else {
            // Show error toast
            var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
            errorToast.show();
        }
    })
    .catch(error => console.error('Error:', error));
});

document.querySelector('.btn-danger.mx-2').addEventListener('click', function() {
    var deleteConfirmToast = new bootstrap.Toast(document.getElementById('deleteConfirmToast'));
    // Confirm the deletion
    $('#deleteConfirmToast .toast-body').html(`
            <p>Are you sure you want to delete all absent</p>
            <p class="text-danger"><strong>This action cannot be back </strong></p>
            <div class="mt-3">
                <button type="button" class="btn btn-danger me-2" id="confirmDelete">Delete absent</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="toast">Cancel</button>
            </div>
        `);

        // Show the confirmation toast
        deleteConfirmToast.show();
        $('#confirmDelete').one('click', function(){
            deleteAbsences();
        })
        // Proceed with the deletion
      
    
});
function deleteAbsences() {
    // Get the values for class_name, study_mode, department_name
    var class_name = "<?php echo urlencode($class_name); ?>";
    var study_mode = "<?php echo urlencode($study_mode); ?>";
    var department_name = "<?php echo urlencode($department_name); ?>";
    var faculty = "<?php echo urlencode($faculty); ?>";

    // Create an XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Configure it: POST-request to the deletion script
    xhr.open('POST', '/attendanceproject1/Database_users/absent/delete_absents.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    // Send the request over the network
    xhr.send(`class_name=${class_name}&study_mode=${study_mode}&department_name=${department_name}&faculty=${faculty}`);

    // Handle the response
    xhr.onload = function() {
        if (xhr.status == 200) {
            $('#addSuccessToast').toast('show');
    // Refresh the page after a short delay
    setTimeout(function() {
        location.reload();
    }, 800);
            // location.reload(); // Reload the page to reflect the changes
        } else {
            alert('An error occurred while trying to delete absences. Please try again.');
        }
    };
}

// single subject report

document.querySelector('.dropdown-item[href*="singlesubject_report.php"]').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent the default action
    var link = this.href; // Store the link href

    // Show the modal
    var subjectReportModal = new bootstrap.Modal(document.getElementById('subjectReportModal'));
    subjectReportModal.show();

    // Extract class, department, and study mode from the URL
    var urlParams = new URLSearchParams(link.split('?')[1]);
    var className = urlParams.get('class_name');
    var departmentName = urlParams.get('department_name');
    var studyMode = urlParams.get('study_mode');

    // Fetch subjects and populate the select element
    fetch(`/attendanceproject1/Database_users/absent/get_subjects.php?class_name=${className}&department_name=${departmentName}&study_mode=${studyMode}`)
        .then(response => response.json())
        .then(data => {
            var subjectSelect = document.getElementById('subjectSelectReport'); // Ensure the ID matches
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            data.subjects.forEach(function(subject) {
                var option = document.createElement('option');
                option.value = subject.subject_name;
                option.textContent = subject.subject_name;
                console.log(option.textContent = subject.subject_name);
                subjectSelect.appendChild(option);
            });
        });
    // Update the modal's confirm button to include the selected subject
    document.getElementById('confirmSubjectReport').addEventListener('click', function() {
        var selectedSubject = document.getElementById('subjectSelectReport').value; // Ensure the ID matches
        if (selectedSubject) {
            window.location.href = `${link}&subject_name=${encodeURIComponent(selectedSubject)}`;
        } else {
            alert('Please select a subject before generating the report.');
        }
    });
});
// single subject report
</script>
</body>
</html>
