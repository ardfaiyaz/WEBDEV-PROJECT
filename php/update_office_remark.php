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
$office_remarks = isset($data['office_remarks']) ? trim($data['office_remarks']) : '';

if ($req_id === 0) {
    echo json_encode(['error' => 'Invalid request ID.']);
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
    $sql = "UPDATE clearance_status SET office_remarks = ? WHERE req_id = ? AND office_code = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("sis", $office_remarks, $req_id, $office_code);
} else {
    // Insert new record (assuming default status 'PENDING' if not explicitly set)
    $sql = "INSERT INTO clearance_status (req_id, office_code, status_code, office_remarks) VALUES (?, ?, 'PENDING', ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iss", $req_id, $office_code, $office_remarks);
}


if ($stmt->execute()) {
    echo json_encode(['success' => 'Office remarks updated successfully.']);
} else {
    echo json_encode(['error' => 'Failed to update office remarks: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>