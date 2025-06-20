<?php
session_start();

require_once __DIR__ . '/database.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access.');
}

$requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($requestId === 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Bad Request: Missing or invalid request ID.');
}

try {
    $stmt = $pdo->prepare("SELECT consent_letter FROM clearance_request WHERE req_id = :request_id");
    
    $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
    
    $stmt->execute();
    
    $consent_letter = $stmt->fetchColumn();

    if ($consent_letter !== false && $consent_letter !== null) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="consent_letter_request_' . $requestId . '.pdf"');
        echo $consent_letter;
    } else {
        header('HTTP/1.1 404 Not Found');
        exit('Consent file not found for this request or request ID does not exist.');
    }

} catch (PDOException $e) {
    error_log("Database error in get_consent_file.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Internal Server Error: A database error occurred.');
}
?>