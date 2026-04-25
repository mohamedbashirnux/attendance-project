<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection with absolute path resolution
$connection_path = dirname(__FILE__) . "/../connection/connect.php";
if (!file_exists($connection_path)) {
    // Try alternative path for files in subdirectories
    $connection_path = dirname(__FILE__) . "/../../connection/connect.php";
}
if (file_exists($connection_path)) {
    include $connection_path;
} else {
    // Last resort - try to find it dynamically
    $possible_paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/connection/connect.php',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/connection/connect.php',
        dirname(dirname(__FILE__)) . '/connection/connect.php'
    ];
    
    $found = false;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            include $path;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        die('Database connection file not found. Tried paths: ' . implode(', ', $possible_paths));
    }
}

// Function to check if faculty user is logged in
function checkFacultyLogin() {
    // Check if session variables are set
    if (!isset($_SESSION['faculty_user_logged_in']) || 
        !isset($_SESSION['faculty_user_id']) || 
        !isset($_SESSION['faculty_id']) || 
        $_SESSION['faculty_user_logged_in'] !== true) {
        
        // Redirect to login page if not logged in
        header('Location: ../interval/auth_faculty.php');
        exit();
    }
    
    // Check session timeout (optional - 30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired, destroy and redirect
        session_destroy();
        header('Location: ../interval/auth_faculty.php?timeout=1');
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

// Function to get current faculty user info
function getCurrentFacultyUser() {
    global $conn;
    
    if (!checkFacultyLogin()) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT fu.*, f.faculty_name FROM faculty_users fu 
                               JOIN faculty f ON fu.faculty_id = f.id 
                               WHERE fu.id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['faculty_user_id']);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get faculty info
function getFacultyInfo() {
    global $conn;
    
    if (!checkFacultyLogin()) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM faculty WHERE id = :faculty_id");
        $stmt->bindParam(':faculty_id', $_SESSION['faculty_id']);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to logout faculty user
function logoutFacultyUser() {
    // Destroy all session data
    session_destroy();
    
    // Redirect to login page
    header('Location: ../interval/auth_faculty.php');
    exit();
}

// Function to get session info for display
function getSessionInfo() {
    global $conn;
    
    if (!checkFacultyLogin()) {
        return false;
    }
    
    // Get faculty name from database if not in session
    $faculty_name = $_SESSION['faculty_name'] ?? '';
    if (empty($faculty_name)) {
        try {
            $stmt = $conn->prepare("SELECT faculty_name FROM faculty WHERE id = :faculty_id");
            $stmt->bindParam(':faculty_id', $_SESSION['faculty_id']);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $faculty_name = $result['faculty_name'];
                $_SESSION['faculty_name'] = $faculty_name; // Store in session for future use
            }
        } catch (PDOException $e) {
            $faculty_name = 'Unknown Faculty';
        }
    }
    
    return [
        'faculty_user_id' => $_SESSION['faculty_user_id'],
        'faculty_id' => $_SESSION['faculty_id'],
        'faculty_name' => $faculty_name,
        'username' => $_SESSION['username'] ?? '',
        'logged_in' => $_SESSION['faculty_user_logged_in'],
        'last_activity' => $_SESSION['last_activity']
    ];
}

// Auto-check login when this file is included
checkFacultyLogin();
?>