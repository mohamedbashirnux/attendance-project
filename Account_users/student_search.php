<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['faculty'])) {
    echo json_encode(['error' => 'Unauthorized access']);
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
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Prepare SQL statement
    $sql = "SELECT student_id, student_name, tell, password, department_name, class_name, study_mode, faculty_name
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

    // Generate HTML for table rows
    ob_start();
    if (!empty($students)) {
        $counter = 1;
        foreach ($students as $student) {
            echo '<tr>';
            echo '<td>' . $counter . '</td>';
            echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
            echo '<td>' . htmlspecialchars($student['student_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['tell']) . '</td>';
            echo '<td>' . htmlspecialchars($student['password']) . '</td>';
            echo '<td class="text-end">
                <button class="btn btn-sm btn-warning edit-btn" 
                    data-id="' . htmlspecialchars($student['student_id']) . '"
                    data-name="' . htmlspecialchars($student['student_name']) . '"
                    data-department="' . htmlspecialchars($student['department_name']) . '"
                    data-class="' . htmlspecialchars($student['class_name']) . '"
                    data-faculty="' . htmlspecialchars($student['faculty_name']) . '"
                    data-tell="' . htmlspecialchars($student['tell']) . '"
                    data-password="' . htmlspecialchars($student['password']) . '">
                    Edit
                </button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="' . $student['student_id'] . '">Delete</button>
            </td>';
            echo '</tr>';
            $counter++;
        }
    } else {
        echo '<tr><td colspan="6" class="text-center">No data for Students available</td></tr>';
    }
    $tableBody = ob_get_clean();

    echo json_encode(['tableBody' => $tableBody]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}