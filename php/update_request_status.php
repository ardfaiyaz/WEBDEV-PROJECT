<?php
// update_request_status.php

session_start(); // Start session to access session variables


require_once 'api_config.php';
require_once 'database.php'; // This provides the $pdo object

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->request_id)) {
        echo json_encode(["success" => false, "message" => "Request ID not provided."]);
        exit();
    }

    // --- CRITICAL ADDITION: Get current user's office_code from session ---
    if (!isset($_SESSION['office_code'])) {
        echo json_encode(["success" => false, "message" => "User office code not found in session. Please re-login."]);
        exit();
    }
    $userOfficeCode = $_SESSION['office_code'];
    // --- END CRITICAL ADDITION ---

    $requestId = $data->request_id;

    // Update status_code to 'COMP'
    // CRITICAL CHANGE: Add office_code to the WHERE clause
    $sql = "UPDATE clearance_status
            SET status_code = 'COMP'
            WHERE req_id = :request_id AND office_code = :office_code";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':request_id' => $requestId,
            ':office_code' => $userOfficeCode // Bind the logged-in user's office code
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Request status updated to COMPLETED."]);
        } else {
            // This might mean the request_id didn't exist for this office or status was already 'COMP'
            echo json_encode(["success" => false, "message" => "No record found for this office or status already completed for Request ID: " . $requestId]);
        }
    } catch (PDOException $e) {
        error_log("Database error in update_request_status.php: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database query error: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid request method. Only POST is allowed."]);
}
?>