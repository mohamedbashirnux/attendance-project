<?php
header("Content-Type: application/json");

// 👇 latest required version (update this when you release a new app)
$latest_version = "3.0.0";

// You can also add extra info like update link or message
$response = [
    "version" => $latest_version,
   // "update_url" => "http://yourserver.com/downloads/app-latest.apk",
    "message" => "A new version of the app is available. Please update to continue."
];

echo json_encode($response);
?>