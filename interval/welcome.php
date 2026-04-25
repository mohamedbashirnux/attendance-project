<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Featcher's</title>
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
            background: #f5f7fb;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(149, 157, 165, 0.2);
            padding: 30px;
            margin-bottom: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: #2E4B77;
            padding-bottom: 20px;
            border-bottom: 2px solid #eaeef3;
        }

        .header h1 {
            font-size: 32px;
            margin: 0;
            margin-bottom: 10px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e1e4e8;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            border-color: #c8d3e3;
        }

        .feature-card h3 {
            color: #2E4B77;
            margin: 0 0 15px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .status-badge {
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-completed {
            background-color: #e6f4ea;
            color: #1e7e34;
        }

        .status-progress {
            background-color: #e8f1fd;
            color: #1a73e8;
        }

        .status-required {
            background-color: #ffe8e8;
            color: #dc3545;
        }

        .feature-card p {
            color: #546E7A;
            margin: 0;
            line-height: 1.6;
        }

        .dashboard-button {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 40px auto 0;
            padding: 16px 32px;
            background: #2E4B77;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(46, 75, 119, 0.15);
        }

        .dashboard-button:hover {
            background: #3a5d94;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 75, 119, 0.2);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .feature-icon i {
            font-size: 24px;
            color: #2E4B77;
        }

        .update-alert {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }

        .action-required {
            color: #dc3545;
            font-weight: 600;
            margin-top: 10px;
        }

        .checkbox-container {
            margin-top: 15px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .checkbox-label input {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="main-card">
            <div class="update-alert">
                <strong>Important Notice:</strong> A new application update is available with new features. All users and teachers must update their applications before proceeding.
            </div>
           
            <div class="header">
                <h1>New Semester Setup</h1>
                <p style="color: #546E7A;">System Status and Updates Overview</p>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-data'></i>
                    </div>
                    <h3>
                        Semester Backup
                        <span class="status-badge status-completed">Completed</span>
                    </h3>
                    <p>Last semester's attendance data has been successfully backed up in the database.</p>
                    <div class="update-alert" style="margin-top: 15px;">
                        <strong⚠️ Important Backup Policy:</strong>
                        <ul style="margin-top: 10px; margin-bottom: 5px;">
                            <li>System backups are performed only at the end of each semester</li>
                            <li>Additional backups are done twice per year</li>
                            <li>No daily, weekly, or monthly backups are available</li>
                            <li>The developer is not responsible for data loss during the semester</li>
                            <li>Please be careful when deleting data as it cannot be recovered until the next scheduled backup</li>
                        </ul>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-refresh'></i>
                    </div>
                    <h3>
                        Application Update
                        <span class="status-badge status-required">Required</span>
                    </h3>
                    <p>New features available. All users must update their application.</p>
                    <div class="checkbox-container">
                        <label class="checkbox-label">
                            <input type="checkbox" id="updateConfirm" required>Waa aqbalay in aan update gareeyo applicationka.
                        </label>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-transfer'></i>
                    </div>
                    <h3>
                        Semester Change
                        <span class="status-badge status-required">Required</span>
                    </h3>
                    <p>Update semester column in classes table for the new academic period.</p>
                    <div class="checkbox-container">
                        <label class="checkbox-label">
                            <input type="checkbox" id="semesterConfirm" required>waa aqbalay in aan update gareeyo semester column
                        </label>
                    </div>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class='bx bx-user-pin'></i>
                    </div>
                    <h3>
                        Teacher Allocation
                        <span class="status-badge status-progress">In Progress</span>
                    </h3>
                    <p>Assign teachers to new semester classes and ensure they have updated their applications.</p>
                </div>
            </div>

            <button onclick="checkRequirements()" class="dashboard-button">
                Proceed to Dashboard
            </button>
        </div>
    </div>

    <script>
        function checkRequirements() {
            const updateConfirmed = document.getElementById('updateConfirm').checked;
            const semesterConfirmed = document.getElementById('semesterConfirm').checked;

            if (!updateConfirmed || !semesterConfirmed) {
                alert('Fadlan check ta saar labadaan. waxee kuyaalaan application update iyo semester change hoos tooda fiiri:\n\n' +
                      '- Aqbal application update\n' +
                      '- Aqbal semester column update');
                return;
            }

            window.location.href = '../Account_users/dashboard.php';
        }
    </script>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboards-analytics.js"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>
</html>