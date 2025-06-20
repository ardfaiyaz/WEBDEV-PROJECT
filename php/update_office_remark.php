<?php
session_start();

require_once 'api_config.php';
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->request_id) || !isset($data->office_code) || !isset($data->remark) || !isset($data->status_code)) {
        echo json_encode(["success" => false, "message" => "Required data (request_id, office_code, remark, or status_code) not provided."]);
        exit();
    }

    if (!isset($_SESSION['office_code']) || $_SESSION['office_code'] !== $data->office_code) {
        echo json_encode(["success" => false, "message" => "Unauthorized access or session mismatch."]);
        exit();
    }
    $userOfficeCode = $_SESSION['office_code'];

    $requestId = $data->request_id;
    $remark = $data->remark;
    $newStatusCode = $data->status_code;

    $sql = "UPDATE clearance_status
            SET office_remarks = :remark, status_code = :new_status_code
            WHERE req_id = :request_id AND office_code = :office_code";

    try {
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':remark' => $remark,
            ':new_status_code' => $newStatusCode,
            ':request_id' => $requestId,
            ':office_code' => $userOfficeCode
        ]);

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