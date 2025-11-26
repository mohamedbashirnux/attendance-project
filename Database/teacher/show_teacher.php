<?php
include "../../connection/connect.php"; // Assuming this file is updated for PDO connection

// Get search query if exists
$search = isset($_GET['search']) ? $_GET['search'] : '';
$teacherID = isset($_GET['id']) ? $_GET['id'] : '';

try {
    if ($teacherID) {
        // Fetch a single teacher
        $sql = "SELECT tid, teacher_name, username, password FROM teachertable WHERE tid = :tid";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tid', $teacherID);
        $stmt->execute();
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            echo json_encode($teacher);
        } else {
            echo json_encode(null); // No teacher found
        }
    } else {
        // Fetch all teachers
        if ($search) {
            // Use prepared statement to prevent SQL injection
            $sql = "SELECT tid, teacher_name, username, password FROM teachertable WHERE tid LIKE :search OR teacher_name LIKE :search OR username LIKE :search";
            $stmt = $conn->prepare($sql);
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
            $stmt->execute();
        } else {
            $sql = "SELECT tid, teacher_name, username, password FROM teachertable";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results) {
            foreach ($results as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tid'], ENT_QUOTES) . '</td>';
                echo '<td>' . htmlspecialchars($row['teacher_name'], ENT_QUOTES) . '</td>';
                echo '<td>' . htmlspecialchars($row['username'], ENT_QUOTES) . '</td>';
                echo '<td>' . htmlspecialchars($row['password'], ENT_QUOTES) . '</td>'; // Display password in plain text (not recommended)
                echo '<td class="text-end">
                         <button type="button" class="btn btn-warning btn-sm" onclick="openEditModal(\'' . htmlspecialchars($row['tid'], ENT_QUOTES) . '\')">Edit</button>
                         <button class="btn btn-sm btn-danger" onclick="deleteTeacher(\'' . htmlspecialchars($row['tid'], ENT_QUOTES) . '\')">Delete</button>
                      </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5" class="text-center">No teachers found</td></tr>';
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

// Close connection
$conn = null;
?>
