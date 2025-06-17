<?php
// update_office_remark.php

session_start(); // Start session to access session variables

// REMOVE these temporary debugging lines in a production environment!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'api_config.php';
require_once 'database.php'; // This provides the $pdo object

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the JSON payload from the request body
    $data = json_decode(file_get_contents("php://input"));

    // Validate if both request_id and remark are provided
    if (!isset($data->request_id) || !isset($data->remark)) {
        echo json_encode(["success" => false, "message" => "Request ID or remark not provided."]);
        exit();
    }

    // --- CRITICAL ADDITION: Get current user's office_code from session ---
    if (!isset($_SESSION['office_code'])) {
        // If office_code is not in session, the user is not properly logged in or session is incomplete.
        echo json_encode(["success" => false, "message" => "User office code not found in session. Please re-login."]);
        exit();
    }
    $userOfficeCode = $_SESSION['office_code'];
    // --- END CRITICAL ADDITION ---

    // Get the values.
    $requestId = $data->request_id;
    $remark = $data->remark;

    // Update office_remarks and status_code to 'ON'
    // CRITICAL CHANGE: Add office_code to the WHERE clause to restrict updates to the current office's requests.
    $sql = "UPDATE clearance_status
            SET office_remarks = :remark, status_code = 'ON'
            WHERE req_id = :request_id AND office_code = :office_code";

    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Execute the statement, binding ALL placeholders
        $stmt->execute([
            ':remark' => $remark,
            ':request_id' => $requestId,
            ':office_code' => $userOfficeCode // Bind the logged-in user's office code
        ]);

        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Remark added and status updated to ON-GOING."]);
        } else {
            // This might mean:
            // 1. The request_id didn't exist for this office.
            // 2. The request_id existed, but not for this office_code.
            // 3. The status was already 'ON' and remark was the same.
            echo json_encode(["success" => false, "message" => "No record found for this office or status already ON-GOING for Request ID: " . $requestId]);
        }
    } catch (PDOException $e) {
        error_log("Database error in update_office_remark.php: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database query error: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid request method. Only POST is allowed."]);
}
?>