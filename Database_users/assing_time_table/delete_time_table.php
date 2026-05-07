<?php
// Set timezone to Somalia (East Africa Time)
date_default_timezone_set('Africa/Mogadishu');

// Suppress PHP warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Include the faculty session management
include "../../Account_users/session_faculty.php";

// Include database connection
include "../../connection/connect.php";

// Clear any unexpected output from includes
ob_clean();

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get faculty information from session
    $sessionInfo = getSessionInfo();
    if (!$sessionInfo) {
        throw new Exception("Session error - please login again");
    }

    $faculty_id = $sessionInfo['faculty_id'];

    // Check if timetable entry ID is provided
    if (!isset($_POST['id'])) {
        throw new Exception('Timetable entry ID not provided.');
    }

    // Sanitize the ID
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    // Begin transaction
    $conn->beginTransaction();

    // Check if timetable entry exists
    $checkEntry = $conn->prepare("SELECT * FROM timetable WHERE id = :id");
    $checkEntry->bindParam(':id', $id, PDO::PARAM_INT);
    $checkEntry->execute();

    if ($checkEntry->rowCount() > 0) {
        // Timetable entry exists, delete it
        $deleteEntry = $conn->prepare("DELETE FROM timetable WHERE id = :id");
        $deleteEntry->bindParam(':id', $id, PDO::PARAM_INT);

        if (!$deleteEntry->execute()) {
            throw new Exception('Failed to delete timetable entry.');
        }

        // Commit transaction
        $conn->commit();
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Timetable entry deleted successfully.']);
    } else {
        // No timetable entry found
        throw new Exception('No timetable entry found with the provided ID.');
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

ob_end_flush();
?>
