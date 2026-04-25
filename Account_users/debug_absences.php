<!DOCTYPE html>
<html>
<head>
    <title>Debug Absences</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .box { border: 2px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug file to check absences data
include "../connection/connect.php";

$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '5978';

echo "<h2>Debug Absences for Student ID: $student_id</h2>";

// Get student internal ID
$stmt = $conn->prepare("SELECT id, student_id, full_name FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "<h3>Student Info:</h3>";
    echo "<pre>";
    print_r($student);
    echo "</pre>";
    
    $internal_student_id = $student['id'];
    
    // Check absences table
    echo "<h3>Absences Table (using internal student ID: $internal_student_id):</h3>";
    $stmt = $conn->prepare("SELECT * FROM absences WHERE student_id = ?");
    $stmt->execute([$internal_student_id]);
    $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($absences)) {
        echo "<p style='color: red;'>NO RECORDS FOUND IN ABSENCES TABLE!</p>";
    } else {
        echo "<pre>";
        print_r($absences);
        echo "</pre>";
    }
    
    // Check attendance_sessions for this student's class
    echo "<h3>Attendance Sessions for Student's Class:</h3>";
    $stmt = $conn->prepare("
        SELECT ats.*, s.subject_name, c.class_name
        FROM attendance_sessions ats
        JOIN subject_class sc ON ats.subject_class_id = sc.id
        JOIN subjects s ON sc.subject_id = s.id
        JOIN classes c ON ats.class_id = c.id
        WHERE ats.class_id = (SELECT class_id FROM students WHERE student_id = ?)
        ORDER BY ats.session_datetime DESC
        LIMIT 10
    ");
    $stmt->execute([$student_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($sessions);
    echo "</pre>";
    
    // Try the full JOIN query
    echo "<h3>Full JOIN Query Result:</h3>";
    
    try {
        $stmt = $conn->prepare("
            SELECT a.*, s.full_name as student_name, s.student_id as student_varchar_id, 
                   subj.subject_name, c.class_name, c.study_mode, d.department_name,
                   t.full_name as teacher_name, a.absence_date, a.excuse
            FROM absences a 
            JOIN students s ON a.student_id = s.id
            JOIN classes c ON a.class_id = c.id
            JOIN departments d ON c.department_id = d.id
            JOIN subject_class sc ON a.subject_class_id = sc.id
            JOIN subjects subj ON sc.subject_id = subj.id
            JOIN teachers t ON a.teacher_id = t.id
            WHERE s.student_id = ?
            ORDER BY subj.subject_name ASC, a.absence_date DESC
        ");
        $stmt->execute([$student_id]);
        $full_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Query executed successfully. Found " . count($full_results) . " records.</p>";
    } catch (Exception $e) {
        echo "<p class='error'>ERROR executing query: " . $e->getMessage() . "</p>";
        $full_results = [];
    }
    
    if (empty($full_results)) {
        echo "<p style='color: red;'>NO RESULTS FROM FULL JOIN QUERY!</p>";
        
        // Try to find out why
        echo "<h3>Checking each JOIN step:</h3>";
        
        // Step 1: Just absences
        $stmt = $conn->prepare("SELECT * FROM absences WHERE student_id = ?");
        $stmt->execute([$internal_student_id]);
        $step1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Step 1 - Absences only: " . count($step1) . " records</p>";
        
        if (!empty($step1)) {
            $first_absence = $step1[0];
            echo "<pre>";
            print_r($first_absence);
            echo "</pre>";
            
            // Check if subject_class_id exists
            $sc_id = $first_absence['subject_class_id'];
            $class_id = $first_absence['class_id'];
            $teacher_id = $first_absence['teacher_id'];
            
            echo "<p><strong>Checking subject_class_id: $sc_id</strong></p>";
            
            $stmt = $conn->prepare("SELECT * FROM subject_class WHERE id = ?");
            $stmt->execute([$sc_id]);
            $sc_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sc_record) {
                echo "<p style='color: green;'>✓ Subject_class record FOUND:</p>";
                echo "<pre>";
                print_r($sc_record);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>✗ Subject_class record NOT FOUND for ID: $sc_id</p>";
            }
            
            // Check if class exists
            echo "<p><strong>Checking class_id: $class_id</strong></p>";
            $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
            $stmt->execute([$class_id]);
            $class_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($class_record) {
                echo "<p style='color: green;'>✓ Class record FOUND:</p>";
                echo "<pre>";
                print_r($class_record);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>✗ Class record NOT FOUND for ID: $class_id</p>";
            }
            
            // Check if teacher exists
            echo "<p><strong>Checking teacher_id: $teacher_id</strong></p>";
            $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
            $stmt->execute([$teacher_id]);
            $teacher_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($teacher_record) {
                echo "<p style='color: green;'>✓ Teacher record FOUND:</p>";
                echo "<pre>";
                print_r($teacher_record);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>✗ Teacher record NOT FOUND for ID: $teacher_id</p>";
            }
            
            // Try step by step JOIN
            echo "<h3>Testing JOINs step by step:</h3>";
            
            // Test 1: absences + students
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM absences a 
                JOIN students s ON a.student_id = s.id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            echo "<p>Test 1 (absences + students): " . $stmt->fetchColumn() . " records</p>";
            
            // Test 2: + classes
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM absences a 
                JOIN students s ON a.student_id = s.id
                JOIN classes c ON a.class_id = c.id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            echo "<p>Test 2 (+ classes): " . $stmt->fetchColumn() . " records</p>";
            
            // Test 3: + departments
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM absences a 
                JOIN students s ON a.student_id = s.id
                JOIN classes c ON a.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            echo "<p>Test 3 (+ departments): " . $stmt->fetchColumn() . " records</p>";
            
            // Test 4: + subject_class
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM absences a 
                JOIN students s ON a.student_id = s.id
                JOIN classes c ON a.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN subject_class sc ON a.subject_class_id = sc.id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            $test4_count = $stmt->fetchColumn();
            echo "<p>Test 4 (+ subject_class): <strong style='color: " . ($test4_count > 0 ? 'green' : 'red') . "'>$test4_count records</strong></p>";
            
            if ($test4_count == 0) {
                echo "<p style='color: red; font-weight: bold;'>⚠ JOIN FAILS AT subject_class! The subject_class_id doesn't exist.</p>";
            }
            
            // Test 5: + subjects
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM absences a 
                JOIN students s ON a.student_id = s.id
                JOIN classes c ON a.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN subject_class sc ON a.subject_class_id = sc.id
                JOIN subjects subj ON sc.subject_id = subj.id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            echo "<p>Test 5 (+ subjects): " . $stmt->fetchColumn() . " records</p>";
            
            // Test 6: + teachers
            $stmt = $conn->prepare("
                SELECT COUNT(*) as cnt FROM absences a 
                JOIN students s ON a.student_id = s.id
                JOIN classes c ON a.class_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN subject_class sc ON a.subject_class_id = sc.id
                JOIN subjects subj ON sc.subject_id = subj.id
                JOIN teachers t ON a.teacher_id = t.id
                WHERE s.student_id = ?
            ");
            $stmt->execute([$student_id]);
            echo "<p>Test 6 (+ teachers): " . $stmt->fetchColumn() . " records</p>";
        }
    } else {
        echo "<pre>";
        print_r($full_results);
        echo "</pre>";
    }
    
} else {
    echo "<p style='color: red;'>Student not found!</p>";
}
?>
</body>
</html>
