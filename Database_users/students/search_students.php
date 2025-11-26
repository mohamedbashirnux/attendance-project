<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    header("Location: ../interval/Auth_user.php");
    exit();
}

include "../connection/connect.php";

// Retrieve data from GET parameters
$departmentName = $_GET['department_name'] ?? '';
$className = $_GET['class_name'] ?? '';
$studyMode = $_GET['study_mode'] ?? '';
$faculty = $_GET['faculty'] ?? '';
$semester = $_GET['semester'] ?? '';
$searchInput = $_GET['search_student_id'] ?? '';

// Validate required parameters
if (empty($departmentName) || empty($className) || empty($studyMode) || empty($faculty)) {
    echo "Missing required parameters.";
    exit();
}

try {
    // Prepare SQL statement
    $sql = "SELECT student_id, student_name, tell, department_name, class_name, study_mode, faculty_name 
            FROM students 
            WHERE department_name = :department_name 
              AND class_name = :class_name 
              AND study_mode = :study_mode 
              AND faculty_name = :faculty_name";

    // If search input is provided, add partial match conditions
    if (!empty($searchInput)) {
        $sql .= " AND (student_id LIKE :search_input OR student_name LIKE :search_input)";
    }

    // Add ORDER BY clause to sort the result by student_name
    $sql .= " ORDER BY student_name ASC";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':department_name', $departmentName);
    $stmt->bindParam(':class_name', $className);
    $stmt->bindParam(':study_mode', $studyMode);
    $stmt->bindParam(':faculty_name', $faculty);

    // Bind search input with wildcards for partial match
    if (!empty($searchInput)) {
        $searchWildcard = '%' . $searchInput . '%';
        $stmt->bindParam(':search_input', $searchWildcard);
    }

    // Execute the statement
    $stmt->execute();

    // Fetch results
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no students found
    if (count($students) == 0) {
        echo "<p>No students found.</p>";
    } else {
        // Display students in an HTML table
        echo "<table class='table'>";
        echo "<thead><tr><th>ID</th><th>Name</th><th>Tell</th><th>Department</th></tr></thead>";
        echo "<tbody>";
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($student['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['tell']) . "</td>";
            echo "<td>" . htmlspecialchars($student['department_name']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    // Close the connection
    $stmt = null;
    $conn = null;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
