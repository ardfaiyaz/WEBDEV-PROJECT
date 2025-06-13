<?php
session_start();

// Include the database connection file
require_once __DIR__ . '/database.php'; // Adjust path as needed

// Check if the user is logged in and is an office staff member (role_code 'SUB_ADMIN' or 'ADMIN')
// This is a security check to ensure only authorized users can view the consent letter.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_code']) || ($_SESSION['role_code'] !== 'SUB_ADMIN' && $_SESSION['role_code'] !== 'ADMIN')) {
    http_response_code(403); // Forbidden
    echo "Access denied. You must be logged in as an authorized office staff to view this document.";
    exit();
}

// Get req_id and user_id from GET parameters
$reqId = $_GET['req_id'] ?? null;
$studentUserId = $_GET['user_id'] ?? null;

if (!$reqId || !$studentUserId) {
    http_response_code(400); // Bad Request
    echo "Missing required parameters (request ID or user ID).";
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT consent_letter
        FROM clearance_request
        WHERE req_id = :req_id AND user_id = :user_id
    ");
    $stmt->bindParam(':req_id', $reqId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $studentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['consent_letter'])) {
        $consentLetterData = $result['consent_letter'];

        // Determine content type (MIME type) using finfo
        // Make sure the 'fileinfo' PHP extension is enabled on your server.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->buffer($consentLetterData);

        if (empty($mime_type) || strpos($mime_type, 'text') !== false) {
             // Fallback for unknown or text types that shouldn't be displayed directly
             $mime_type = 'application/octet-stream'; // Default for unknown binary
             $filename = 'consent_letter_' . $reqId . '.bin'; // Generic filename
        } else {
            // Suggest a filename based on the detected content type
            $extension = 'dat';
            if (strpos($mime_type, 'pdf') !== false) $extension = 'pdf';
            else if (strpos($mime_type, 'image/jpeg') !== false) $extension = 'jpg';
            else if (strpos($mime_type, 'image/png') !== false) $extension = 'png';
            else if (strpos($mime_type, 'image/gif') !== false) $extension = 'gif';
            // Add more common types as needed
            $filename = 'consent_letter_' . $reqId . '.' . $extension;
        }

        // Set headers for download/display
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . strlen($consentLetterData));
        header('Content-Disposition: inline; filename="' . $filename . '"'); // 'inline' to display in browser, 'attachment' to force download
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past to prevent caching

        // Output the blob data
        echo $consentLetterData;
        exit();

    } else {
        http_response_code(404); // Not Found
        echo "Consent letter not found or is empty for this request.";
        exit();
    }

} catch (PDOException $e) {
    error_log("Database error viewing consent letter: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo "An error occurred while retrieving the consent letter.";
    exit();
}
?>