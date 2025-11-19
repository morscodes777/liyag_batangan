<?php
// Set headers to allow YOUR JavaScript code to access this proxy
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Since this proxy is on your domain, this is safe.

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if (empty($lat) || empty($lon)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing latitude or longitude parameters.']);
    exit;
}

// 1. Construct the full Nominatim URL
$nominatim_url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lon}&addressdetails=1";

// 2. Fetch the data from Nominatim using cURL (PHP's built-in tool for network requests)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $nominatim_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// IMPORTANT: Add a descriptive User-Agent as required by Nominatim's usage policy
curl_setopt($ch, CURLOPT_USERAGENT, 'YourAppName/1.0 (your-email@example.com)'); 

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 3. Output the response directly to the browser
if ($http_code === 200) {
    echo $response;
} else {
    // Forward the error status code and message
    http_response_code($http_code);
    echo json_encode(['error' => 'Nominatim request failed. Status: ' . $http_code]);
}
?>