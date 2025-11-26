<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

$faculty = $_SESSION['faculty'];
if (isset($_GET['department_name'])) {
    $department_name = $_GET['department_name'];
} else {
    exit("No department name provided.");
}

include "../../connection/connect.php";

try {
    // Prepare the SQL statement using PDO
    $sql = "SELECT id, class_name, study_mode, department_name FROM classes WHERE department_name = ? AND faculty_name = ?";
    $stmt = $conn->prepare($sql);
    
    // Execute the query with the parameters
    $stmt->execute([$department_name, $faculty]);

    $options = "";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options .= "<option value=\"" . htmlspecialchars($row['id']) . "\" 
                            data-class-name=\"" . htmlspecialchars($row['class_name']) . "\" 
                            data-study-mode=\"" . htmlspecialchars($row['study_mode']) . "\" 
                            data-department-name=\"" . htmlspecialchars($row['department_name']) . "\">
                            " . htmlspecialchars($row['class_name']) . " (" . htmlspecialchars($row['study_mode']) . ")
                    </option>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the connection
$conn = null;

echo $options;
?>
