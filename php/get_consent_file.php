<?php
include 'database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id === 0) {
    header('HTTP/1.1 400 Bad Request');
    exit();
}

$sql = "SELECT consent_letter FROM clearance_request WHERE req_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    header('HTTP/1.1 500 Internal Server Error');
    exit();
}

$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $consent_letter = $row['consent_letter'];

    if ($consent_letter) {
        // Determine the content type. Assuming it's a PDF for a consent letter.
        // If it could be other types, you might need to store the MIME type in the DB.
        // For simplicity, we'll assume PDF based on the common use case for "consent letter".
        // If it's an image, use image/jpeg, image/png etc.
        header('Content-Type: application/pdf'); // Or appropriate image type
        header('Content-Disposition: inline; filename="consent_letter_' . $request_id . '.pdf"');
        echo $consent_letter;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'Consent file not found for this request.';
    }
} else {
    header('HTTP/1.1 404 Not Found');
    echo 'Request not found.';
}

$stmt->close();
$conn->close();
?>