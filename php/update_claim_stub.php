<?php
session_start();
header('Content-Type: application/json'); // Ensure the response is JSON

// Include the database connection file
require_once __DIR__ . '/database.php';

$response = ['success' => false, 'message' => ''];

// --- Basic Authentication Check ---
// Ensure only logged-in admins can access this endpoint
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'admin') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true); // Decode JSON data

// Check if req_id is provided and is a valid integer
if (isset($data['req_id']) && filter_var($data['req_id'], FILTER_VALIDATE_INT)) {
    $reqId = $data['req_id'];

    try {
        // IMPORTANT SECURITY CHECK:
        // Before updating, re-verify if all office statuses for this req_id are 'Completed'.
        // This prevents a user from bypassing the front-end check or race conditions.
        $stmtCheck = $pdo->prepare("
            SELECT
                COUNT(cs.status_code) AS total_offices,
                SUM(CASE WHEN rs.description = 'Completed' THEN 1 ELSE 0 END) AS completed_offices
            FROM
                clearance_status cs
            JOIN
                request_status rs ON cs.status_code = rs.status_code
            WHERE
                cs.req_id = :req_id
        ");
        $stmtCheck->bindParam(':req_id', $reqId, PDO::PARAM_INT);
        $stmtCheck->execute();
        $statusCounts = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($statusCounts && $statusCounts['total_offices'] > 0 && $statusCounts['total_offices'] == $statusCounts['completed_offices']) {
            // All offices are completed, proceed with updating claim_stub
            $stmtUpdate = $pdo->prepare("
                UPDATE clearance_request
                SET claim_stub = 1
                WHERE req_id = :req_id AND claim_stub = 0
            ");
            $stmtUpdate->bindParam(':req_id', $reqId, PDO::PARAM_INT);
            $stmtUpdate->execute();

            if ($stmtUpdate->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Claim stub updated successfully.';
            } else {
                // If rowCount is 0, it means either req_id not found or claim_stub was already 1
                $response['message'] = 'Claim stub was already released or request ID not found.';
            }
        } else {
            $response['message'] = 'Cannot release claim stub: Not all office clearances are completed for this request.';
        }

    } catch (PDOException $e) {
        error_log("Database error updating claim stub for req_id $reqId: " . $e->getMessage());
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request ID provided.';
}

echo json_encode($response);
exit();
