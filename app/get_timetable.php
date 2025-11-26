<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "conn.php";

// Get the parameters from the request
$class_name = $_POST['class_name'] ?? $_GET['class_name'] ?? null;
$department_name = $_POST['department_name'] ?? $_GET['department_name'] ?? null;
$study_mode = $_POST['study_mode'] ?? $_GET['study_mode'] ?? null;

// Debug: Log what we received
error_log("=== TIMETABLE API DEBUG ===");
error_log("Class: '$class_name', Department: '$department_name', Study Mode: '$study_mode'");

// Check if required parameters are provided
if (empty($class_name) || empty($department_name)) {
    echo json_encode(array(
        "status" => "fail", 
        "message" => "Class name and department name are required",
        "debug" => array(
            "received_class" => $class_name,
            "received_department" => $department_name,
            "received_study_mode" => $study_mode
        )
    ));
    exit;
}

try {
    // Prepare and execute the SQL statement to find timetable
    // Match by class_name, department_name, and study_mode
    $stmt = $conn->prepare("SELECT * FROM timetable WHERE class_name = ? AND department_name = ? AND study_mode = ?");
    $stmt->execute(array($class_name, $department_name, $study_mode));

    // Fetch results as associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($results) . " timetable records");
    
    if ($results) {
        echo json_encode(array(
            "status" => "success", 
            "timetable" => $results,
            "count" => count($results),
            "debug" => array(
                "query_params" => array(
                    "class_name" => $class_name,
                    "department_name" => $department_name,
                    "study_mode" => $study_mode
                )
            )
        ));
    } else {
        echo json_encode(array(
            "status" => "success", 
            "timetable" => [],
            "message" => "No timetable records found for this class",
            "debug" => array(
                "query_params" => array(
                    "class_name" => $class_name,
                    "department_name" => $department_name,
                    "study_mode" => $study_mode
                )
            )
        ));
    }

} catch (PDOException $e) {
    echo json_encode(array(
        "status" => "fail", 
        "message" => "Database error: " . $e->getMessage()
    ));
} catch (Exception $e) {
    echo json_encode(array(
        "status" => "fail", 
        "message" => "Error: " . $e->getMessage()
    ));
}
?>