<?php
include "../../connection/connect.php"; // Assuming this includes the PDO connection setup

try {
    // Fetch users
    $query = "SELECT faculty_name, username, password FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Fetch all results
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if any user found
    if (count($result) > 0) {
        foreach ($result as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['faculty_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['username']) . '</td>';
            echo '<td>' . htmlspecialchars($row['password']) . '</td>';
            echo '<td>';
            // Edit icon with link to edit_user.php
            echo '<button class="btn btn-sm btn-warning edit-user" data-facultyname="' . htmlspecialchars($row['faculty_name']) . '"><i class="bi bi-pencil"></i> Edit</button>';
            echo ' ';
            // Delete icon with data attribute for confirmation modal
            echo '<button class="btn btn-sm btn-danger delete-user" data-facultyname="' . htmlspecialchars($row['faculty_name']) . '"><i class="bi bi-trash"></i> Delete</button>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">Wax user ah madiiwan gashna</td></tr>';
    }
} catch (PDOException $e) {
    die("Error executing query: " . $e->getMessage());
}

// Close the connection
$conn = null;
?>
