<?php
/**
 * Fix ALL Student IDs - Remove invisible characters
 */

include 'connection/connect.php';

try {
    echo "<h2>Fixing ALL Student IDs...</h2>";
    
    // Get ALL students
    $query = "SELECT id, student_id, full_name FROM students";
    $stmt = $conn->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total students: " . count($students) . "</p>";
    echo "<hr>";
    
    $fixed_count = 0;
    $errors = [];
    
    foreach ($students as $student) {
        $old_id = $student['student_id'];
        
        // Remove ALL invisible characters and spaces
        $new_id = $old_id;
        $new_id = preg_replace('/^\x{FEFF}/u', '', $new_id); // UTF-8 BOM
        $new_id = preg_replace('/^[\x{200B}-\x{200D}\x{FEFF}]/u', '', $new_id); // Zero-width
        $new_id = trim($new_id);
        $new_id = preg_replace('/[^\x20-\x7E]/','', $new_id); // Remove non-printable
        
        if ($old_id !== $new_id) {
            try {
                // Check if new ID already exists
                $check_stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
                $check_stmt->execute([$new_id, $student['id']]);
                
                if ($check_stmt->fetch()) {
                    echo "<p style='color: red;'>⚠ Cannot fix: " . htmlspecialchars($student['full_name']) . " - ID '{$new_id}' already exists</p>";
                    $errors[] = "Duplicate: {$new_id}";
                } else {
                    // Update the student_id
                    $update_stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE id = ?");
                    $update_stmt->execute([$new_id, $student['id']]);
                    
                    echo "<p style='color: green;'>✓ Fixed: " . htmlspecialchars($student['full_name']) . " - '{$old_id}' → '{$new_id}'</p>";
                    $fixed_count++;
                }
            } catch (PDOException $e) {
                $error_msg = "Failed: " . $e->getMessage();
                $errors[] = $error_msg;
                echo "<p style='color: red;'>✗ Error fixing " . htmlspecialchars($student['full_name']) . ": {$error_msg}</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h2>Summary:</h2>";
    echo "<p style='font-size: 20px;'><strong>Total fixed: {$fixed_count}</strong></p>";
    
    if (!empty($errors)) {
        echo "<p><strong>Errors: " . count($errors) . "</strong></p>";
    }
    
    if ($fixed_count > 0) {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ SUCCESS! All student IDs are now clean.</strong></p>";
        echo "<p style='color: blue;'><strong>Now test your dashboard search - it should work!</strong></p>";
    } else {
        echo "<p style='color: blue;'><strong>No problems found.</strong></p>";
    }
    
    echo "<p style='color: red; font-size: 16px;'><strong>DELETE THIS FILE NOW!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
