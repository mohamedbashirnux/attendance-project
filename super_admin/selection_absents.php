<?php
date_default_timezone_set('Africa/Mogadishu');
include 'seassion_super-admin.php';
include '../connection/connect.php';
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Select Class - Absents</title>
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
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <?php include 'menu.php'; ?>
            <div class="layout-page">
                <?php include 'navbar.php'; ?>
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <h4 class="fw-bold py-3 mb-4"><i class='bx bx-bell-minus'></i> Select Class for Attendance</h4>

                        <div class="card">
                            <div class="card-body">
                                <form action="absents.php" method="GET">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="facultySelect" class="form-label">Faculty</label>
                                            <select class="form-select" id="facultySelect" name="faculty_id" required>
                                                <option value="" disabled selected>Choose faculty</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="departmentSelect" class="form-label">Department</label>
                                            <select class="form-select" id="departmentSelect" name="department_id" required>
                                                <option value="" disabled selected>Choose department</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="classSelect" class="form-label">Class</label>
                                            <select class="form-select" id="classSelect" name="class_id" required>
                                                <option value="" disabled selected>Choose a class</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary w-100">View Absents</button>
                                            </div>
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
    $(document).ready(function () {

        // Load faculties
        $.get('../Database/super_admin/report_absents/get_faculties.php', function (res) {
            let opts = '<option value="" disabled selected>Choose faculty</option>';
            (res.faculties || []).forEach(f => {
                opts += `<option value="${f.id}">${f.faculty_name}</option>`;
            });
            $('#facultySelect').html(opts);
        });

        // Faculty change → load departments
        $('#facultySelect').change(function () {
            const fid = $(this).val();
            $('#departmentSelect').html('<option value="" disabled selected>Loading...</option>');
            $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');

            $.get('../Database/super_admin/report_absents/get_departments.php?faculty_id=' + fid, function (res) {
                let opts = '<option value="" disabled selected>Choose department</option>';
                (res.departments || []).forEach(d => {
                    opts += `<option value="${d.id}">${d.department_name}</option>`;
                });
                $('#departmentSelect').html(opts);
            });
        });

        // Department change → load classes
        $('#departmentSelect').change(function () {
            const did = $(this).val();
            $('#classSelect').html('<option value="" disabled selected>Loading...</option>');

            $.get('../Database/super_admin/report_absents/get_classes.php?department_id=' + did, function (res) {
                let opts = '<option value="" disabled selected>Choose a class</option>';
                (res.classes || []).forEach(c => {
                    opts += `<option value="${c.id}">${c.class_name} (${c.study_mode}) - ${c.semester}</option>`;
                });
                $('#classSelect').html(opts);
            });
        });
    });
    </script>
</body>
</html>
