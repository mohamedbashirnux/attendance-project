<?php
include 'connection/connect.php';

echo "=== STUDENTS TABLE STRUCTURE ===\n\n";
$stmt = $conn->query('DESCRIBE students');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-20s | %-15s | %-5s | %-5s | %-10s | %s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key'], 
        $row['Default'] ?? 'NULL',
        $row['Extra']
    );
}

echo "\n\n=== SAMPLE STUDENTS DATA ===\n\n";
$stmt = $conn->query('SELECT student_id, class_id, full_name, status FROM students LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n\n=== CHECKING SPECIFIC STUDENTS ===\n\n";
// Check student 4205
$stmt = $conn->prepare('SELECT s.student_id, s.class_id, s.full_name, s.status, c.class_name, c.faculty_id FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.student_id = ?');
$stmt->execute(['4205']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Student 4205:\n";
print_r($result);

// Check student 5089
$stmt->execute(['5089']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nStudent 5089:\n";
print_r($result);
?>
