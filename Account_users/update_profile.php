<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['faculty'])) {
    header("Location: login.php");
    exit();
}

// Fetch the faculty name from session
$faculty_name = $_SESSION['faculty'] ?? '';

// Include your connection file
include "../connection/connect.php"; 

// Initialize success message
$successMessage = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $currentPassword = filter_var($_POST['currentPassword'], FILTER_SANITIZE_STRING);
    $newPassword = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

    try {
        // Fetch current password from the database
        $sql = "SELECT password FROM users WHERE faculty_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$faculty_name]);
        $dbPassword = $stmt->fetchColumn();

        // Verify current password
        if ($currentPassword !== $dbPassword) {
            throw new Exception("Current password is incorrect.");
        }

        // Prepare SQL update query
        $updateSql = "UPDATE users SET username = ?";
        
        if (!empty($newPassword)) {
            $updateSql .= ", password = ?";
            $stmt = $conn->prepare($updateSql . " WHERE faculty_name = ?");
            $stmt->execute([$username, $newPassword, $faculty_name]);
        } else {
            $stmt = $conn->prepare($updateSql . " WHERE faculty_name = ?");
            $stmt->execute([$username, $faculty_name]);
        }

        // Update session username
        $_SESSION['username'] = $username; 

        // Set success message
        $successMessage = "Profile updated successfully!";
    } catch (Exception $e) {
        $successMessage = "Error: " . $e->getMessage();
    }
}
?>

<!-- HTML to display success message -->
<?php if ($successMessage): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<!-- Rest of your HTML code goes here -->
