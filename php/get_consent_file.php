<?php
session_start();

// Ensure the database.php file is correctly included, assuming it provides a $pdo object for PDO connections
require_once __DIR__ . '/database.php';

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past

// --- Authentication Check ---
// Ensure the user is logged in to view the file
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access.');
}

// Get the request ID from the GET parameters
$requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

// Validate request ID
if ($requestId === 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Bad Request: Missing or invalid request ID.');
}

try {
    // Prepare the SQL statement using PDO
    // Use $pdo which should be available from database.php
    $stmt = $pdo->prepare("SELECT consent_letter FROM clearance_request WHERE req_id = :request_id");
    
    // Bind the parameter
    $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
    
    // Execute the statement
    $stmt->execute();
    
    // Fetch the result
    $consent_letter = $stmt->fetchColumn(); // Fetches a single column from the next row

    if ($consent_letter !== false && $consent_letter !== null) {
        // Determine the content type. It's best to store MIME type in DB.
        // For now, assuming PDF for consent letters.
        // If it can be images, you'd need logic to check or a stored MIME type.
        header('Content-Type: application/pdf'); // Defaulting to PDF
        // Use a dynamic filename
        header('Content-Disposition: inline; filename="consent_letter_request_' . $requestId . '.pdf"');
        echo $consent_letter;
    } else {
        header('HTTP/1.1 404 Not Found');
        exit('Consent file not found for this request or request ID does not exist.');
    }

} catch (PDOException $e) {
    // Log the error for debugging purposes (check your PHP error logs)
    error_log("Database error in get_consent_file.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Internal Server Error: A database error occurred.');
}
?>
