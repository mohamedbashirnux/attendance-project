<?php
// Check available Gemini models
$apiKey = 'AIzaSyBiPq50YH9mIAWLrcEhG5f8B4C9qK4nbHc';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    echo "Available Models:\n";
    echo "=================\n\n";
    
    if (isset($data['models'])) {
        foreach ($data['models'] as $model) {
            echo "Name: " . $model['name'] . "\n";
            if (isset($model['supportedGenerationMethods'])) {
                echo "Supported Methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
            }
            echo "\n";
        }
    }
} else {
    echo "Error: " . $response . "\n";
}
?>
