<?php
// Get faculty information from session
$sessionInfo = getSessionInfo();
$faculty = $sessionInfo['faculty_name'];
$username = $sessionInfo['username'];
?>

<style>
    .faculty-welcome {
        display: flex;
        align-items: center;
        font-size: 1.25rem;
        /* Adjust the size as needed */
        color: #007bff;
        /* Bootstrap primary color */
        font-weight: bold;
        padding-right: 1rem;
    }

    .faculty-welcome i {
        margin-right: 0.5rem;
        /* Space between icon and text */
    }
</style>

<!-- Navbar -->
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>
    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center">
            <!-- Display faculty name -->
            <li class="nav-item lh-1 me-3">
                <span class="faculty-welcome">
                    <i class="bx bx-user" style="font-size: 1.5rem;"></i> Welcome, <?php echo htmlspecialchars($faculty); ?>
                </span>
            </li>
        </ul>
        <!-- User -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAB4CAYAAAA5ZDbSAAAAAXNSR0IArs4c6QAAAplJREFUeF7t2jFuA1EMQ8H4/odOGhdpg30GCGXcLyFxvjq/vvxON/A6vZ3lvgAffwSAAR9v4Ph6Lhjw8QaOr+eCAR9v4Ph6Lhjw8QaOr+eCAR9v4Ph6Lhjw8QaOr+eCAR9v4Ph6Lhjw8QaOr+eCAR9v4Ph6Lhjw8QaOr+eCAR9v4Ph6Lhjw8QaOr+eCAR9v4Ph6Lhjwnxv4/vMXPvjdQHp0adh7SsDPHmxqkoYBfib7/jo1ScMAA04aOB6SHl0a5oKTp5eapGGAAScNHA9Jjy4Nc8HJ00tN0jDA/wM42VJI08AnLriZTErSAOCkxt0QwLs2yWSAkxp3QwDv2iSTAU5q3A0BvGuTTAY4qXE3BPCuTTIZ4KTG3RDAuzbJZICTGndDAO/aJJMBTmrcDfkEsP9FP/NOTdKw916AAT9r4PjX6dGlYS44eXqpSRoGGHDSwPGQ9OjSMBecPL3UJA0DDDhp4HhIenRpmAtOnl5qkoYl6wlJGwCc1rkXBnjPJJ0IcFrnXhjgPZN0IsBpnXthgPdM0okAp3XuhQHeM0knApzWuRcGeM8knQhwWudeGOA9k3QiwGmde2GA90zSiT4B7H/Rz4hSkzTsvRdgwM8aOP51enRpmAtOnl5qkoYBBpw0cDwkPbo0zAUnTy81ScMAA04aOB6SHl0a5oKTp5eapGHJekLSBgCnde6FAd4zSScCnNa5FwZ4zySdCHBa514Y4D2TdCLAaZ17YYD3TNKJAKd17oUB3jNJJwKc1rkXBnjPJJ0IcFrnXhjgPZN0IsBpnXthgPdM0okAp3XuhQHeM0knApzWuRcGeM8knQhwWudeGOA9k3QiwGmde2GA90zSiQCnde6FAd4zSScCnNa5FwZ4zySdCHBa517YDy5FHnnwZ7L+AAAAAElFTkSuQmCC"/>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="capital.png" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block"><?php echo htmlspecialchars($faculty); ?></span>
                                    <small class="text-muted">User</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <!-- Trigger the modal with this button -->
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bx bx-cog me-2"></i>
                            <span class="align-middle">Security Guidelines</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <span class="d-flex align-items-center align-middle">
                                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                                <span class="flex-grow-1 align-middle">Billing</span>
                                <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="../Account_users/logout.php">
                            <i class="bx bx-power-off me-2"></i>
                            <span class="align-middle">Log out</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
<!-- /Navbar -->

<!-- Update Profile Modal -->
<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProfileModalLabel">Update Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateProfileForm" action="update_profile.php" method="POST">
                <div class="modal-body">
                    <!-- Faculty Name (Read-only) -->
                    <div class="mb-3">
                        <label for="faculty" class="form-label">Faculty</label>
                        <input type="text" class="form-control" id="faculty" name="faculty" value="<?php echo htmlspecialchars($faculty); ?>" readonly>
                    </div>
                    <!-- New Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">New Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                    </div>
                    <!-- Current Password -->
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                    </div>
                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password (leave blank if not changing)</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Update Profile Modal -->
<!-- Security Overview Modal -->
<div class="modal fade" id="securityOverviewModal" tabindex="-1" aria-labelledby="securityOverviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="securityOverviewModalLabel">
                    <i class="bx bx-shield-quarter me-2"></i>Security Guidelines & Disclaimer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-4">
                    <i class="bx bx-error-circle me-2"></i>
                    <strong>Important:</strong> Please read these security guidelines carefully to protect your account and university data.
                </div>

                <h6 class="fw-bold mb-3"><i class="bx bx-lock-alt me-2"></i>Password Security</h6>
                <ul class="list-group mb-4">
                    <li class="list-group-item">
                        <i class="bx bx-check text-success me-2"></i>
                        Use strong passwords with at least 8 characters, including uppercase, lowercase, numbers, and special characters
                    </li>
                    <li class="list-group-item">
                        <i class="bx bx-check text-success me-2"></i>
                        Never share your password with anyone, including IT staff
                    </li>
                    <li class="list-group-item">
                        <i class="bx bx-check text-success me-2"></i>
                        Change your password regularly and avoid reusing old passwords
                    </li>
                </ul>

                <h6 class="fw-bold mb-3"><i class="bx bx-log-in-circle me-2"></i>Login & Session Security</h6>
                <ul class="list-group mb-4">
                    <li class="list-group-item">
                        <i class="bx bx-check text-success me-2"></i>
                        Always log out when leaving your computer or mobile device
                    </li>
                    <li class="list-group-item">
                        <i class="bx bx-check text-success me-2"></i>
                        Do not allow browsers to save your login credentials
                    </li>
                    <li class="list-group-item">
                        <i class="bx bx-check text-success me-2"></i>
                        Never access the system from untrusted devices or public computers
                    </li>
                </ul>

                <h6 class="fw-bold mb-3"><i class="bx bx-error me-2"></i>Security Disclaimer</h6>
                <div class="alert alert-secondary">
                    <p class="mb-2">By using this system, you acknowledge and agree that:</p>
                    <ul class="mb-0">
                        <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                        <li>Unauthorized access attempts are strictly prohibited and may be subject to legal action</li>
                        <li>The developers are not responsible for data breaches resulting from user negligence or failure to follow these security guidelines</li>
                        <li>Any tampering, corruption, or theft of data due to compromised user credentials is the responsibility of the user</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Modified Settings Link -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update the settings link to trigger the security modal
    const settingsLink = document.querySelector('a.dropdown-item i.bx-cog').parentElement;
    settingsLink.setAttribute('data-bs-toggle', 'modal');
    settingsLink.setAttribute('data-bs-target', '#securityOverviewModal');
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update the settings link to trigger the security modal
    const settingsLink = document.querySelector('a.dropdown-item i.bx-cog').parentElement;
    settingsLink.setAttribute('data-bs-toggle', 'modal');
    settingsLink.setAttribute('data-bs-target', '#securityOverviewModal');
});
</script>