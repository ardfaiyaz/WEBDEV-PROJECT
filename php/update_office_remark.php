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

    // Check for request_id, office_code, remark, and status_code in the payload
    if (!isset($data->request_id) || !isset($data->office_code) || !isset($data->remark) || !isset($data->status_code)) {
        echo json_encode(["success" => false, "message" => "Required data (request_id, office_code, remark, or status_code) not provided."]);
        exit();
    }

    // Validate user's office_code from session for security
    if (!isset($_SESSION['office_code']) || $_SESSION['office_code'] !== $data->office_code) {
        echo json_encode(["success" => false, "message" => "Unauthorized access or session mismatch."]);
        exit();
    }
    $userOfficeCode = $_SESSION['office_code'];

    // Get the values from the payload
    $requestId = $data->request_id;
    $remark = $data->remark;
    $newStatusCode = $data->status_code; // This will be 'ISSUE' from the frontend

    // Update office_remarks and status_code to the provided value
    $sql = "UPDATE clearance_status
            SET office_remarks = :remark, status_code = :new_status_code
            WHERE req_id = :request_id AND office_code = :office_code";

    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);

        // Execute the statement, binding ALL placeholders
        $stmt->execute([
            ':remark' => $remark,
            ':new_status_code' => $newStatusCode,
            ':request_id' => $requestId,
            ':office_code' => $userOfficeCode
        ]);

        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Remark added and status updated to " . $newStatusCode . "."]);
        } else {
            echo json_encode(["success" => false, "message" => "No record found or remark/status already " . $newStatusCode . " for Request ID: " . $requestId]);
        }
    } catch (PDOException $e) {
        error_log("Database error in update_office_remark.php: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database query error: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid request method. Only POST is allowed."]);
}
?>