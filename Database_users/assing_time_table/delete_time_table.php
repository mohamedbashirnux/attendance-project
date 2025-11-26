<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

// Include database connection
include "../../connection/connect.php";

// Check if timetable entry ID is provided
if (isset($_POST['id'])) {
    // Sanitize the ID
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
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

            if ($deleteEntry->execute()) {
                // Commit transaction
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Timetable entry deleted successfully.']);
            } else {
                throw new Exception('Failed to delete timetable entry.');
            }
        } else {
            // No timetable entry found
            echo json_encode(['success' => false, 'message' => 'No timetable entry found with the provided ID.']);
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    // ID not provided
    echo json_encode(['success' => false, 'message' => 'Timetable entry ID not provided.']);
}

// Close connection
$conn = null;
?>
