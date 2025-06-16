<?php
include 'database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['office_code'])) {
    echo json_encode(['error' => 'Unauthorized access or office not assigned.']);
    exit();
}

$office_code = $_SESSION['office_code'];
$data = json_decode(file_get_contents('php://input'), true);

$req_id = isset($data['req_id']) ? intval($data['req_id']) : 0;
$status_code = isset($data['status_code']) ? trim($data['status_code']) : '';

if ($req_id === 0 || empty($status_code)) {
    echo json_encode(['error' => 'Invalid request ID or status code.']);
    exit();
}
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

// Check if a record for this req_id and office_code already exists
$check_sql = "SELECT COUNT(*) FROM clearance_status WHERE req_id = ? AND office_code = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $req_id, $office_code);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$row = $check_result->fetch_row();
$exists = $row[0] > 0;
$check_stmt->close();

if ($exists) {
    // Update existing record
    $sql = "UPDATE clearance_status SET status_code = ? WHERE req_id = ? AND office_code = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("sis", $status_code, $req_id, $office_code);
} else {
    // Insert new record
    $sql = "INSERT INTO clearance_status (req_id, office_code, status_code) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iss", $req_id, $office_code, $status_code);
}

if ($stmt->execute()) {
    echo json_encode(['success' => 'Request status updated successfully.']);
} else {
    echo json_encode(['error' => 'Failed to update request status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>