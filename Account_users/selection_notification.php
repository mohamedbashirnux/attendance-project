<?php
include 'session_faculty.php';
$sessionInfo = getSessionInfo();
$faculty     = $sessionInfo['faculty_name'];
$faculty_id  = $sessionInfo['faculty_id'];
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
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
</head>
<body>

<!-- Toasts -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="successToast" class="toast bg-success text-white" role="alert">
        <div class="toast-header bg-success text-white">
            <i class="bx bx-bell me-2"></i>
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="successMsg">Notification sent successfully!</div>
    </div>
    <div id="errorToast" class="toast bg-danger text-white" role="alert">
        <div class="toast-header bg-danger text-white">
            <i class="bx bx-error me-2"></i>
            <strong class="me-auto">Error</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="errorMsg">Failed to send notification.</div>
    </div>
</div>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?php include 'menu.php'; ?>
        <div class="layout-page">
            <?php include 'navbar.php'; ?>
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    <h4 class="fw-bold py-3 mb-4"><i class='bx bx-bell'></i> Send Class Notification</h4>

                    <div class="card">
                        <div class="card-body">
                            <form id="notificationForm">
                                <input type="hidden" id="faculty_id" value="<?php echo htmlspecialchars($faculty_id); ?>">

                                <!-- Faculty (readonly) -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Faculty</label>
                                        <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($faculty); ?>">
                                    </div>
                                </div>

                                <!-- Send to all checkbox -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="sendToAll" role="switch">
                                            <label class="form-check-label fw-semibold" for="sendToAll">
                                                Send to all classes in this faculty
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Department + Class (hidden when sendToAll is checked) -->
                                <div id="classSelectors" class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="departmentSelect" class="form-label">Department</label>
                                        <select class="form-select" id="departmentSelect">
                                            <option value="" disabled selected>Choose department</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="classSelect" class="form-label">Class</label>
                                        <select class="form-select" id="classSelect">
                                            <option value="" disabled selected>Choose a class</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Title -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="notificationTitle" class="form-label">Notification Title</label>
                                        <input type="text" class="form-control" id="notificationTitle" placeholder="e.g., Important Announcement" maxlength="100" required>
                                        <small class="text-muted">Maximum 100 characters</small>
                                    </div>
                                </div>

                                <!-- Message -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label for="notificationMessage" class="form-label">Message</label>
                                        <textarea class="form-control" id="notificationMessage" rows="5" placeholder="Write your message here..." maxlength="500" required></textarea>
                                        <small class="text-muted">Maximum 500 characters</small>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary px-5" id="sendBtn">
                                        <i class='bx bx-send me-1'></i> Send Notification
                                    </button>
                                </div>
                            </form>

                            <!-- Result summary (shown after send-all) -->
                            <div id="resultSummary" class="mt-4" style="display:none;"></div>
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
    const successToast = new bootstrap.Toast(document.getElementById('successToast'));
    const errorToast   = new bootstrap.Toast(document.getElementById('errorToast'));
    const facultyId    = $('#faculty_id').val();

    // ── Load departments on page load ──
    $.get('../Database_users/Department/show_departments.php?dropdown=true', function (res) {
        const depts = parseResponse(res, 'departments');
        let opts = '<option value="" disabled selected>Choose department</option>';
        depts.forEach(d => opts += `<option value="${d.id}">${d.department_name}</option>`);
        $('#departmentSelect').html(opts);
    });

    // ── Department change → load classes ──
    $('#departmentSelect').change(function () {
        const did = $(this).val();
        $('#classSelect').html('<option value="" disabled selected>Loading...</option>');
        $.get('../Database_users/Classes/show_classes.php?dropdown=true&department_id=' + did, function (res) {
            const classes = parseResponse(res, 'classes');
            let opts = '<option value="" disabled selected>Choose a class</option>';
            classes.forEach(c => opts += `<option value="${c.id}">${c.class_name} (${c.study_mode}) - ${c.semester}</option>`);
            $('#classSelect').html(opts);
        });
    });

    // ── Toggle class selectors when checkbox changes ──
    $('#sendToAll').change(function () {
        if ($(this).is(':checked')) {
            $('#classSelectors').slideUp(200);
            $('#sendBtn').html('<i class="bx bx-broadcast me-1"></i> Send to All Classes').removeClass('btn-primary').addClass('btn-warning');
        } else {
            $('#classSelectors').slideDown(200);
            $('#sendBtn').html('<i class="bx bx-send me-1"></i> Send Notification').removeClass('btn-warning').addClass('btn-primary');
        }
        $('#resultSummary').hide();
    });

    // ── Form submit ──
    $('#notificationForm').on('submit', function (e) {
        e.preventDefault();

        const title   = $('#notificationTitle').val().trim();
        const message = $('#notificationMessage').val().trim();
        const sendAll = $('#sendToAll').is(':checked');

        if (!title || !message) { alert('Please fill in the title and message.'); return; }

        if (sendAll) {
            sendToAllClasses(title, message);
        } else {
            const classId = $('#classSelect').val();
            if (!classId) { alert('Please select a class.'); return; }
            sendToSingleClass(classId, title, message);
        }
    });

    // ── Send to single class ──
    function sendToSingleClass(classId, title, message) {
        $('#sendBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sending...');
        $.ajax({
            url: '../app/send_class_notification.php',
            type: 'POST',
            data: { class_id: classId, title: title, body: message },
            dataType: 'json',
            success: function (res) {
                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send me-1"></i> Send Notification');
                if (res.status === 'success') {
                    $('#successMsg').text(`Notification sent to ${res.sent_count} student(s)!`);
                    successToast.show();
                    resetForm();
                } else {
                    $('#errorMsg').text(res.message || 'Failed to send notification.');
                    errorToast.show();
                }
            },
            error: function () {
                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send me-1"></i> Send Notification');
                $('#errorMsg').text('Network error. Please try again.');
                errorToast.show();
            }
        });
    }

    // ── Send to all classes ──
    function sendToAllClasses(title, message) {
        $('#sendBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Loading classes...');
        $('#resultSummary').hide();

        // Step 1: load all departments
        $.get('../Database_users/Department/show_departments.php?dropdown=true', function (res) {
            const depts = parseResponse(res, 'departments');
            if (!depts.length) {
                alert('No departments found.');
                resetSendBtn(true);
                return;
            }

            let allClassIds = [];
            let pending = depts.length;

            // Step 2: load classes for each department
            depts.forEach(function (dept) {
                $.get('../Database_users/Classes/show_classes.php?dropdown=true&department_id=' + dept.id, function (r) {
                    const classes = parseResponse(r, 'classes');
                    classes.forEach(c => allClassIds.push(c.id));
                    pending--;
                    if (pending === 0) {
                        if (!allClassIds.length) {
                            alert('No classes found in your faculty.');
                            resetSendBtn(true);
                            return;
                        }
                        // Step 3: send to each class
                        dispatchAll(allClassIds, title, message);
                    }
                });
            });
        });
    }

    function dispatchAll(classIds, title, message) {
        $('#sendBtn').html(`<span class="spinner-border spinner-border-sm me-2"></span>Sending to ${classIds.length} classes...`);

        let done = 0, totalSent = 0, failed = 0;
        const total = classIds.length;

        classIds.forEach(function (classId) {
            $.ajax({
                url: '../app/send_class_notification.php',
                type: 'POST',
                data: { class_id: classId, title: title, body: message },
                dataType: 'json',
                success: function (res) {
                    done++;
                    if (res.status === 'success') totalSent += (res.sent_count || 0);
                    else failed++;
                    if (done === total) finishAll(total, totalSent, failed);
                },
                error: function () {
                    done++; failed++;
                    if (done === total) finishAll(total, totalSent, failed);
                }
            });
        });
    }

    function finishAll(total, totalSent, failed) {
        resetSendBtn(true);
        const ok = failed === 0;
        $('#resultSummary').html(`
            <div class="alert alert-${ok ? 'success' : 'warning'} d-flex align-items-center">
                <i class='bx ${ok ? 'bx-check-circle' : 'bx-error-circle'} me-2 fs-5'></i>
                <div>
                    Sent to <strong>${totalSent}</strong> student(s) across <strong>${total}</strong> class(es).
                    ${failed > 0 ? `<br><small class="text-danger">${failed} class(es) failed to send.</small>` : ''}
                </div>
            </div>
        `).show();
        if (ok) resetForm();
    }

    function resetSendBtn(isAll) {
        $('#sendBtn').prop('disabled', false);
        if (isAll) {
            $('#sendBtn').html('<i class="bx bx-broadcast me-1"></i> Send to All Classes');
        } else {
            $('#sendBtn').html('<i class="bx bx-send me-1"></i> Send Notification');
        }
    }

    function resetForm() {
        $('#notificationTitle').val('');
        $('#notificationMessage').val('');
        $('#classSelect').html('<option value="" disabled selected>Choose a class</option>');
        $('#departmentSelect').prop('selectedIndex', 0);
    }

    function parseResponse(res, key) {
        try {
            const data = (typeof res === 'string') ? JSON.parse(res) : res;
            return data[key] || [];
        } catch (e) { return []; }
    }
});
</script>
</body>
</html>
