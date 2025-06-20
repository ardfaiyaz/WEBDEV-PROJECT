<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'api_config.php';
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->request_id)) {
        echo json_encode(["success" => false, "message" => "Request ID not provided."]);
        exit();
    }

    if (!isset($_SESSION['office_code'])) {
        echo json_encode(["success" => false, "message" => "User office code not found in session. Please re-login."]);
        exit();
    }
    $userOfficeCode = $_SESSION['office_code'];

    $requestId = $data->request_id;

    $sql = "UPDATE clearance_status
            SET status_code = 'COMP'
            WHERE req_id = :request_id AND office_code = :office_code";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':request_id' => $requestId,
            ':office_code' => $userOfficeCode
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Request status updated to COMPLETED."]);
        } else {
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