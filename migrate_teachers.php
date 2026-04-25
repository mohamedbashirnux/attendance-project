<?php
/**
 * Teacher Data Migration Script
 * Migrates teacher data from old table structure to new teachers table
 * 
 * Old structure: tid, teacher_name, username, password
 * New structure: teacher_id, faculty_id, full_name, username, password (hashed)
 */

// Include database connection
include "connection/connect.php";

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Teacher Data Migration</h2>";
echo "<p>Starting migration process...</p>";

try {
    // You need to specify which faculty_id to assign to these teachers
    // Replace this with the actual faculty_id from your faculty table
    $default_faculty_id = 4; // CHANGE THIS TO YOUR ACTUAL FACULTY ID
    
    echo "<p><strong>Note:</strong> All teachers will be assigned to faculty_id: $default_faculty_id</p>";
    echo "<p>If you need different faculty assignments, modify the script accordingly.</p>";
    echo "<hr>";
    
    // Old teacher data array (from your provided data)
    $old_teachers = [
        ['tid' => '2', 'teacher_name' => 'Dr Alas Hassan Mohamed', 'username' => 'alas', 'password' => 'alas4971'],
        ['tid' => '3', 'teacher_name' => 'Dr Abdinasir Ismacil Hashi', 'username' => 'nasir', 'password' => 'nasir123@'],
        ['tid' => '4', 'teacher_name' => 'mohamed ali culusow', 'username' => 'culusow', 'password' => 'culusow905'],
        ['tid' => '5', 'teacher_name' => 'Mohamed Daud', 'username' => 'daud', 'password' => 'daud4009'],
        ['tid' => '6', 'teacher_name' => 'Dr Mahamud Ahmed Jimcale', 'username' => 'drjimale@123', 'password' => 'drjimale@123@'],
        ['tid' => '7', 'teacher_name' => 'Abukar Abdullahi Haji Addan', 'username' => 'Abukar', 'password' => 'Abukar123@'],
        ['tid' => '8', 'teacher_name' => 'dr abdalla abdalla', 'username' => 'abdalla abdalla', 'password' => 'abdalla5644'],
        ['tid' => '10', 'teacher_name' => 'Fatima Shiekh Ahmed Abdullah', 'username' => 'ayaan', 'password' => 'ayaan11'],
        ['tid' => '11', 'teacher_name' => 'Abdullahi Ahmed Hussein', 'username' => 'FOC', 'password' => 'FOC'],
        ['tid' => '12', 'teacher_name' => 'Abdullahi Mohamed Dhaqane', 'username' => 'dhaqane', 'password' => 'dhaqane8214'],
        ['tid' => '13', 'teacher_name' => 'Abdullahi maxamuud weedo', 'username' => 'weydow', 'password' => 'weydow@'],
        ['tid' => '14', 'teacher_name' => 'Abdukadir Ahmed Elmis', 'username' => 'axafi', 'password' => 'axafi@'],
        ['tid' => '15', 'teacher_name' => 'Abdirizak Mohamed Abdi', 'username' => 'Abdirizak', 'password' => 'Abdirizak123#'],
        ['tid' => '17', 'teacher_name' => 'Ali Mohamed Hussein', 'username' => 'afey', 'password' => 'afey6403'],
        ['tid' => '19', 'teacher_name' => 'Saed Mire Alasow', 'username' => 'saed', 'password' => 'saed11'],
        ['tid' => '21', 'teacher_name' => 'mohamed shakur', 'username' => 'wazir', 'password' => 'wazir11'],
        ['tid' => '34', 'teacher_name' => 'Farah Mohamed Abdulle', 'username' => 'farah', 'password' => 'farah11'],
        ['tid' => '36', 'teacher_name' => 'Mohamed Abdulkadir Ibrahim(Maadey)', 'username' => 'maadey', 'password' => 'maadey12@'],
        ['tid' => '38', 'teacher_name' => 'Mohamed Ahmed Abdi', 'username' => 'somane', 'password' => 'somane123'],
        ['tid' => '40', 'teacher_name' => 'Isse Mohamud Abdi', 'username' => 'Isse123', 'password' => 'Isse123@'],
        ['tid' => '41', 'teacher_name' => 'Abdifatah Nor Rage', 'username' => 'Compiler', 'password' => 'Compiler@12'],
        ['tid' => '45', 'teacher_name' => 'Abdullahi Ahmed Rage', 'username' => 'raage', 'password' => 'raage7272'],
        ['tid' => '50', 'teacher_name' => 'Ayub Ibrahim Abdi', 'username' => 'Ayub123', 'password' => 'Ayub123@'],
        ['tid' => '51', 'teacher_name' => 'Abas Omar Hassan', 'username' => 'abas', 'password' => 'abas11'],
        ['tid' => '56', 'teacher_name' => 'Abdihamiid Ali Mohamuud', 'username' => 'abdihamid', 'password' => 'abdihamid11'],
        ['tid' => '62', 'teacher_name' => 'Isma\'il Nor Xaji', 'username' => 'ismail', 'password' => 'ismail11'],
        ['tid' => '64', 'teacher_name' => 'Abukar Hassan Mohamed', 'username' => 'Abukar11', 'password' => 'Abukar11@'],
        ['tid' => '70', 'teacher_name' => 'Abdirahman Omar Addow', 'username' => 'addow', 'password' => 'addow6661'],
        ['tid' => '72', 'teacher_name' => 'Prof. Abddullahi Mohamud Mohamed', 'username' => 'profabdullahi@', 'password' => 'profabdullahi123@'],
        ['tid' => '74', 'teacher_name' => 'Abdullahi Sheikh Ali Ahmed', 'username' => 'drabdullahi@123', 'password' => 'drabdullahi@123@'],
        ['tid' => '76', 'teacher_name' => 'Abdalla Ibrahim Shiekh', 'username' => 'abdalla ibrahim', 'password' => 'abdalla0239'],
        ['tid' => '80', 'teacher_name' => 'Abdiaziz Mohamed Gure', 'username' => 'guure', 'password' => 'guure11'],
        ['tid' => '82', 'teacher_name' => 'Dr Salah Ahmed abukar', 'username' => 'DrSalah@123', 'password' => 'DrSalah@123@'],
        ['tid' => '83', 'teacher_name' => 'Yahye Ahmed Nageye', 'username' => 'drnageye@123', 'password' => 'drnageye@123@'],
        ['tid' => '85', 'teacher_name' => 'Abdikani Abdulle Dhimbil', 'username' => 'dhimbil', 'password' => 'dhimbil9660'],
        ['tid' => '106', 'teacher_name' => 'Dr. Omar Mohamud Eybakar', 'username' => 'profomar@123', 'password' => 'profomar@123@'],
        ['tid' => '117', 'teacher_name' => 'Dr. Ahmed Hassan Mohamed', 'username' => 'drahmedsalad@', 'password' => 'drahmedsalad123@'],
        ['tid' => '155', 'teacher_name' => 'Hamdi Mukhtar', 'username' => 'hamdi', 'password' => 'hamdi9920'],
        ['tid' => '156', 'teacher_name' => 'Roun Ali Hassan', 'username' => 'ruun', 'password' => 'ruun3329'],
        ['tid' => '157', 'teacher_name' => 'Mohamed Ali Jirow', 'username' => 'jiirow', 'password' => 'jiirow'],
        ['tid' => '160', 'teacher_name' => 'Dr.Abdullahi Farah Isse', 'username' => 'drisse@123', 'password' => 'drisse@123@'],
        ['tid' => '163', 'teacher_name' => 'Omer sh. Sharif nor', 'username' => 'Omersh', 'password' => 'Omer@33'],
        ['tid' => '164', 'teacher_name' => 'Hassan Abdullahi Hassan', 'username' => 'hassan', 'password' => 'hassan1765'],
        ['tid' => '173', 'teacher_name' => 'Muna Abdullahi Roble', 'username' => 'muna', 'password' => 'muna1804'],
        ['tid' => '174', 'teacher_name' => 'Omar Mohamed Hamud', 'username' => 'hamud', 'password' => 'hamud1331'],
        ['tid' => '179', 'teacher_name' => 'Dr Mohamed Abdullahi Diriye', 'username' => 'drdiiriye@123', 'password' => 'drdiiriye@123@'],
        ['tid' => '188', 'teacher_name' => 'Dr Abdikarim Ahmed Mohamud', 'username' => 'geedi@123', 'password' => 'geedi@123@'],
        ['tid' => '189', 'teacher_name' => 'Mukhtar Mohamud Fidow', 'username' => 'mukhtar', 'password' => 'mukhtar'],
        ['tid' => '190', 'teacher_name' => 'Yasin Mohamed Ali', 'username' => 'mosow', 'password' => 'mosow6201'],
        ['tid' => '191', 'teacher_name' => 'Mohamed Hassan Mohamud', 'username' => 'mohamed', 'password' => 'mohamed11'],
        ['tid' => '195', 'teacher_name' => 'Abdullahi Mohamud Omar(Muke)', 'username' => 'Muke', 'password' => 'Muke@123'],
        ['tid' => '197', 'teacher_name' => 'Yahye elmi osman', 'username' => 'yahye', 'password' => 'yahye8392'],
        ['tid' => '201', 'teacher_name' => 'Alas Mohamed Mohamud', 'username' => 'calas@', 'password' => 'calas@'],
        ['tid' => '203', 'teacher_name' => 'Ahmed Mohamud Mohamed', 'username' => 'uburi', 'password' => 'uburi9640'],
        ['tid' => '205', 'teacher_name' => 'Ali Hilowle Ali', 'username' => 'ali123', 'password' => 'ali123@'],
        ['tid' => '206', 'teacher_name' => 'Aweis Ahmed Hussein', 'username' => 'Jarras@12', 'password' => 'Jarras@12'],
        ['tid' => '207', 'teacher_name' => 'Abdimalik Aden Ibrahim', 'username' => 'Abdimalik', 'password' => 'Abdimalik123@'],
        ['tid' => '211', 'teacher_name' => 'Alas Mohamed Mohamud', 'username' => 'calas', 'password' => 'clas123'],
        ['tid' => '214', 'teacher_name' => 'Dr Abukar Hassan Mohamed', 'username' => 'daaci@123', 'password' => 'daaci@123@'],
        ['tid' => '215', 'teacher_name' => 'Ahmed Abdulkadir Ahmed', 'username' => 'Ahmed', 'password' => 'ahmed11'],
        ['tid' => '218', 'teacher_name' => 'Ahmed Rage Mohamed', 'username' => 'rage', 'password' => 'rage2033'],
        ['tid' => '219', 'teacher_name' => 'Isac Muhiadin Awale', 'username' => 'isac', 'password' => 'isac11'],
        ['tid' => '220', 'teacher_name' => 'Mohamed Abdi Nor', 'username' => 'Mohamed123', 'password' => 'Mohamed123@'],
        ['tid' => '221', 'teacher_name' => 'Zakaria Ahmed Mohamed', 'username' => 'zakaria', 'password' => 'zakaria11'],
        ['tid' => '222', 'teacher_name' => 'Ibrahim Mohamud Ali', 'username' => 'Alxaqani', 'password' => 'Alxaqani@12'],
        ['tid' => '226', 'teacher_name' => 'Abukar Abdulle', 'username' => 'abukar', 'password' => 'abukar4387'],
        ['tid' => '227', 'teacher_name' => 'Farhia Hassan Ali', 'username' => 'farhia', 'password' => 'farhia6460'],
        ['tid' => '228', 'teacher_name' => 'Abdifitah Ahmed Ga.al', 'username' => 'gacal', 'password' => 'gacal8014'],
        ['tid' => '230', 'teacher_name' => 'Mohamed Said Mohamud', 'username' => 'drsaid@123', 'password' => 'drsaid@123@'],
        ['tid' => '231', 'teacher_name' => 'Said ali abuubakar sheikh ahmed', 'username' => 'said123', 'password' => 'said123@'],
        ['tid' => '236', 'teacher_name' => 'Abddishakur Elmi Warsame', 'username' => 'warsame', 'password' => 'warsame8421'],
        ['tid' => '238', 'teacher_name' => 'Anisa Hassan Mohamed', 'username' => 'anisa', 'password' => 'anisa8408'],
        ['tid' => '239', 'teacher_name' => 'Daud Abdulle Mohamed Daud', 'username' => 'jaran', 'password' => 'jaran'],
        ['tid' => '240', 'teacher_name' => 'Osman Aded Osman', 'username' => 'Osman', 'password' => 'osman111'],
        ['tid' => '245', 'teacher_name' => 'Abdullahi Ilyas Osman', 'username' => 'Abdullahi', 'password' => 'abdullahi11'],
        ['tid' => '246', 'teacher_name' => 'Mohamed Gabow Kasim', 'username' => 'gabow', 'password' => 'gabow11'],
        ['tid' => '247', 'teacher_name' => 'Dr Mohamed Ali Hussein', 'username' => 'drciisey@123', 'password' => 'drciisey@123@'],
        ['tid' => '248', 'teacher_name' => 'Dr Abdulsalam Ahmed Mohamed', 'username' => 'drgacal@123', 'password' => 'drgacal@123@'],
        ['tid' => '249', 'teacher_name' => 'Ibrahim Abdullahi Abdi', 'username' => 'ibrahim', 'password' => 'ibra4722'],
        ['tid' => '251', 'teacher_name' => 'Abdulkadir Abdullahi Sharif', 'username' => 'Abdulkadir', 'password' => 'abdulkadir11'],
        ['tid' => '254', 'teacher_name' => 'Mohamed Sheikh Hassan Sheikh Ahmed (Qalaf)', 'username' => 'drmohamed123', 'password' => 'drmohamed123@'],
        ['tid' => '258', 'teacher_name' => 'Ismahan Mohamud Abdulle', 'username' => 'ismahan', 'password' => 'ismahan1758'],
        ['tid' => '259', 'teacher_name' => 'Abdiweli Mohamed Abdi', 'username' => 'abdiweli', 'password' => 'abdiweli9682'],
        ['tid' => '260', 'teacher_name' => 'Mohamed Hassan Cibar', 'username' => 'cibar', 'password' => 'cibar798'],
        ['tid' => '261', 'teacher_name' => 'RODGERS SITUMA MAKOKHA', 'username' => 'mrmakokha@123', 'password' => 'mrmakokha@123@'],
        ['tid' => '262', 'teacher_name' => 'Ali Abdi Yusuf', 'username' => 'ali', 'password' => 'ali123'],
        ['tid' => '263', 'teacher_name' => 'Hassan Adan Ali', 'username' => 'Hassan', 'password' => 'hassan12'],
        ['tid' => '264', 'teacher_name' => 'Faduma Bare Ali', 'username' => 'faduma', 'password' => 'faduma8670']
    ];
    
    $success_count = 0;
    $error_count = 0;
    $skipped_count = 0;
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>Teacher ID</th><th>Full Name</th><th>Username</th><th>Status</th><th>Message</th>";
    echo "</tr>";
    
    foreach ($old_teachers as $teacher) {
        $teacher_id = $teacher['tid'];
        $full_name = $teacher['teacher_name'];
        $username = $teacher['username'];
        $password = $teacher['password'];
        
        try {
            // Check if teacher already exists
            $check_sql = "SELECT id FROM teachers WHERE teacher_id = ? OR username = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$teacher_id, $username]);
            
            if ($check_stmt->rowCount() > 0) {
                echo "<tr style='background-color: #fff3cd;'>";
                echo "<td>$teacher_id</td>";
                echo "<td>$full_name</td>";
                echo "<td>$username</td>";
                echo "<td>⚠️ SKIPPED</td>";
                echo "<td>Already exists</td>";
                echo "</tr>";
                $skipped_count++;
                continue;
            }
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert teacher
            $insert_sql = "INSERT INTO teachers (teacher_id, faculty_id, full_name, username, password) 
                          VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->execute([$teacher_id, $default_faculty_id, $full_name, $username, $hashed_password]);
            
            echo "<tr style='background-color: #d4edda;'>";
            echo "<td>$teacher_id</td>";
            echo "<td>$full_name</td>";
            echo "<td>$username</td>";
            echo "<td>✅ SUCCESS</td>";
            echo "<td>Migrated successfully</td>";
            echo "</tr>";
            $success_count++;
            
        } catch (PDOException $e) {
            echo "<tr style='background-color: #f8d7da;'>";
            echo "<td>$teacher_id</td>";
            echo "<td>$full_name</td>";
            echo "<td>$username</td>";
            echo "<td>❌ ERROR</td>";
            echo "<td>" . htmlspecialchars($e->getMessage()) . "</td>";
            echo "</tr>";
            $error_count++;
        }
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Migration Summary</h3>";
    echo "<p>✅ Successfully migrated: <strong>$success_count</strong> teachers</p>";
    echo "<p>⚠️ Skipped (already exists): <strong>$skipped_count</strong> teachers</p>";
    echo "<p>❌ Errors: <strong>$error_count</strong> teachers</p>";
    echo "<p>📊 Total processed: <strong>" . count($old_teachers) . "</strong> teachers</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
