<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function filterRequest($req){
    // Handle JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input[$req])) {
        return htmlspecialchars(strip_tags($input[$req]));
    }
    
    // Handle form data
    if (isset($_POST[$req])) {
        return htmlspecialchars(strip_tags($_POST[$req]));
    }
    
    // Return empty string if not found
    return '';
}
?>