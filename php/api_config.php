<?php
// php/api_config.php - Add this file

// Set CORS headers
header("Access-Control-Allow-Origin: *"); // Be more specific in production (your frontend's domain)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Set content type to JSON
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests (common for modern web applications)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Optional: Error reporting for API debugging.
// Turn these OFF in production for security.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// You can also add other API-specific configurations here if needed later
?>