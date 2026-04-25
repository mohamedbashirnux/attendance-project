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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum=1.0" />
    <title>Send Class Notification</title>
    <link rel="icon" type="image/x-icon" href="capital.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
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
        <div id="successToast" class="toast bg-success text-white" role="alert">
            <div class="toast-header bg-success text-white">
                <i class="bx bx-bell me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">Notification sent successfully!</div>
        </div>
        <div id="errorToast" class="toast bg-danger text-white" role="alert">
            <div class="toast-header bg-danger text-white">
                <i class="bx bx-error me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">Failed to send notification</div>
        </div>
    </div>

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4">
                            <i class='bx bx-bell'></i> Send Class Notification
                        </h4>

                        <div class="card">
                            <div class="card-body">
                                <form id="notificationForm">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_id" required>
                                                <option value="" disabled selected>Choose department</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" name="class_id" required>
                                                <option value="" disabled selected>Choose a class</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="faculty" class="form-label">Faculty</label>
                                            <input type="text" class="form-control" id="faculty" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                            <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="notificationTitle" class="form-label">Notification Title</label>
                                            <input type="text" class="form-control" id="notificationTitle" name="title" placeholder="e.g., Important Announcement" required maxlength="100">
                                            <small class="text-muted">Maximum 100 characters</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="notificationMessage" class="form-label">Message</label>
                                            <textarea class="form-control" id="notificationMessage" name="message" rows="5" placeholder="Write your message here..." required maxlength="500"></textarea>
                                            <small class="text-muted">Maximum 500 characters</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary" id="sendBtn">
                                                <i class='bx bx-send'></i> Send Notification
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
    <script src="../assets/js/main.js"></script>
    
    <script>
        $(document).ready(function() {
            var successToast = new bootstrap.Toast(document.getElementById('successToast'));
            var errorToast = new bootstrap.Toast(document.getElementById('errorToast'));

            // Load departments
            $.ajax({
                url: '../Database_users/Department/show_departments.php?dropdown=true',
                type: 'GET',
                success: function(response) {
                    try {
                        let departments = [];
                        if (typeof response === 'string') {
                            try {
                                const jsonResponse = JSON.parse(response);
                                departments = jsonResponse.departments || [];
                            } catch (e) {
                                $('#departmentSelect').html('<option value="" disabled selected>Choose department</option>' + response);
                                return;
                            }
                        } else {
                            departments = response.departments || [];
                        }

                        let options = '<option value="" disabled selected>Choose department</option>';
                        departments.forEach(function(dept) {
                            options += `<option value="${dept.id}">${dept.department_name}</option>`;
                        });
                        $('#departmentSelect').html(options);
                    } catch (error) {
                        console.error('Error processing departments:', error);
                    }
                },
                error: function() {
                    console.error('Error loading departments');
                }
            });

            // Load classes when department changes
            $('#departmentSelect').change(function() {
                var departmentId = $(this).val();
                if (!departmentId) {
                    $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');
                    return;
                }

                $.ajax({
                    url: '../Database_users/Classes/show_classes.php?dropdown=true&department_id=' + departmentId,
                    type: 'GET',
                    success: function(response) {
                        try {
                            let classes = [];
                            if (typeof response === 'string') {
                                try {
                                    const jsonResponse = JSON.parse(response);
                                    classes = jsonResponse.classes || [];
                                } catch (e) {
                                    $('#classSelect').html('<option value="" disabled selected>Choose a class</option>' + response);
                                    return;
                                }
                            } else {
                                classes = response.classes || [];
                            }

                            let options = '<option value="" disabled selected>Choose a class</option>';
                            classes.forEach(function(cls) {
                                options += `<option value="${cls.id}">${cls.class_name} (${cls.study_mode}) - ${cls.semester}</option>`;
                            });
                            $('#classSelect').html(options);
                        } catch (error) {
                            console.error('Error processing classes:', error);
                        }
                    },
                    error: function() {
                        console.error('Error loading classes');
                    }
                });
            });

            // Handle form submission
            $('#notificationForm').on('submit', function(e) {
                e.preventDefault();

                var classId = $('#classSelect').val();
                var title = $('#notificationTitle').val();
                var message = $('#notificationMessage').val();

                if (!classId || !title || !message) {
                    alert('Please fill all fields');
                    return;
                }

                // Disable button
                $('#sendBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sending...');

                $.ajax({
                    url: '../app/send_class_notification.php',
                    type: 'POST',
                    data: {
                        class_id: classId,
                        title: title,
                        body: message
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i> Send Notification');
                        
                        if (response.status === 'success') {
                            var classInfo = response.class_info ? ` (${response.class_info.class_name})` : '';
                            $('#successToast .toast-body').text(`Notification sent to ${response.sent_count} students in class${classInfo}!`);
                            successToast.show();
                            
                            // Log for debugging
                            console.log('Notification sent:', response);
                            
                            // Reset form
                            $('#notificationForm')[0].reset();
                            $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');
                        } else {
                            $('#errorToast .toast-body').text(response.message || 'Failed to send notification');
                            errorToast.show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i> Send Notification');
                        $('#errorToast .toast-body').text('Error: ' + error);
                        errorToast.show();
                    }
                });
            });
        });
    </script>
</body>
</html>
