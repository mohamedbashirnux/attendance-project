<?php
include "../../connection/connect.php";

$output = '';

try {
    $sql = "SELECT username, password FROM admintable";
    $stmt = $conn->query($sql);

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $username = htmlspecialchars($row['username']);
            $password = htmlspecialchars($row['password']);
            $output .= "
                <tr>
                    <td>{$username}</td>
                    <td>{$password}</td>
                    <td class='text-end'>
                        <button class='btn btn-sm btn-warning' onclick='editAdmin(\"{$username}\", \"{$password}\")'>Edit</button>
                        <button class='btn btn-sm btn-danger' onclick='deleteAdmin(\"{$username}\")'>Delete</button>
                    </td>
                </tr>
            ";
        }
    } else {
        $output .= "<tr><td colspan='3'>No results found</td></tr>";
    }
} catch (PDOException $e) {
    $output = "<tr><td colspan='3'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

$conn = null; // Close the connection

echo $output;
?>
