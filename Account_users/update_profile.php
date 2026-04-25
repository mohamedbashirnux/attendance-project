<?php
// Include the faculty session management
include 'session_faculty.php';

// Get current user info
$currentUser = getCurrentFacultyUser();
$sessionInfo = getSessionInfo();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newUsername = trim($_POST['username']);
    $currentPassword = $_POST['currentPassword'];
    $newPassword = trim($_POST['password']);
    
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Verify current password
        if (!password_verify($currentPassword, $currentUser['password'])) {
            $response['message'] = 'Current password is incorrect.';
            echo json_encode($response);
            exit();
        }
        
        // Check if username already exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM faculty_users WHERE username = :username AND id != :current_id");
        $stmt->bindParam(':username', $newUsername);
        $stmt->bindParam(':current_id', $sessionInfo['faculty_user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response['message'] = 'Username already exists. Please choose a different username.';
            echo json_encode($response);
            exit();
        }
        
        // Prepare update query
        if (!empty($newPassword)) {
            // Update both username and password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE faculty_users SET username = :username, password = :password WHERE id = :id");
            $stmt->bindParam(':username', $newUsername);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $sessionInfo['faculty_user_id']);
        } else {
            // Update only username
            $stmt = $conn->prepare("UPDATE faculty_users SET username = :username WHERE id = :id");
            $stmt->bindParam(':username', $newUsername);
            $stmt->bindParam(':id', $sessionInfo['faculty_user_id']);
        }
        
        if ($stmt->execute()) {
            // Update session
            $_SESSION['username'] = $newUsername;
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully!';
        } else {
            $response['message'] = 'Failed to update profile. Please try again.';
        }
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// If not POST request, redirect to dashboard
header('Location: dashboard.php');
exit();
?>