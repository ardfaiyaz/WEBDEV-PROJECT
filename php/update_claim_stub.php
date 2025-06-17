<?php
session_start(); // Start the session to access user data

header('Content-Type: application/json'); // Ensure the response is JSON

// TEMPORARY DEBUGGING: Dump session data to see what's available
// You'll see this output in the Network tab's "Response" for update_claim_stub.php
// REMOVE THIS SECTION AFTER DEBUGGING!
ob_start(); // Start output buffering
var_dump($_SESSION);
$session_dump = ob_get_clean();
// END TEMPORARY DEBUGGING

// Include the database connection file
require_once __DIR__ . '/database.php'; // Adjust path if necessary based on where this file is located relative to database.php

// --- Basic Authentication Check ---
// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type'])) {
    // Include the session dump in the unauthorized message for debugging
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in as an administrator.', 'debug_session' => $session_dump]);
    exit();
}

$response = ['success' => false, 'message' => ''];

// Get the raw POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['req_id'])) {
    $response['message'] = 'Request ID (req_id) is missing.';
    echo json_encode($response);
    exit();
}

$reqId = $data['req_id'];

try {
    // Start a transaction for atomicity
    $pdo->beginTransaction();

    // Re-verify server-side: Check if all offices for this request are 'Completed'
    $stmtCheckStatus = $pdo->prepare("
        SELECT
            rs.description AS status
        FROM
            clearance_status cs
        JOIN
            request_status rs ON cs.status_code = rs.status_code
        WHERE
            cs.req_id = :req_id
    ");
    $stmtCheckStatus->bindParam(':req_id', $reqId, PDO::PARAM_INT);
    $stmtCheckStatus->execute();
    $officeStatuses = $stmtCheckStatus->fetchAll(PDO::FETCH_ASSOC);

    // If there are no offices, or any office is not completed, deny the request
    if (empty($officeStatuses)) {
        $response['message'] = 'No office statuses found for this request.';
        $pdo->rollBack(); // Rollback transaction
        echo json_encode($response);
        exit();
    }

    $allOfficesCompleted = true;
    foreach ($officeStatuses as $officeStatus) {
        if (strtoupper($officeStatus['status']) !== 'COMPLETED') {
            $allOfficesCompleted = false;
            break;
        }
    }

    if (!$allOfficesCompleted) {
        $response['message'] = 'Claim stub cannot be released unless all offices have completed the clearance.';
        $pdo->rollBack(); // Rollback transaction
        echo json_encode($response);
        exit();
    }

    // Check if the claim stub has already been released (claim_stub = 1)
    $stmtCheckClaimStub = $pdo->prepare("SELECT claim_stub FROM clearance_request WHERE req_id = :req_id");
    $stmtCheckClaimStub->bindParam(':req_id', $reqId, PDO::PARAM_INT);
    $stmtCheckClaimStub->execute();
    $currentClaimStubStatus = $stmtCheckClaimStub->fetchColumn();

    if ($currentClaimStubStatus === false) { // Request ID not found
        $response['message'] = 'Request not found.';
        $pdo->rollBack(); // Rollback transaction
        echo json_encode($response);
        exit();
    }

    if ($currentClaimStubStatus == 1) { // Already released
        $response['message'] = 'Claim stub has already been released for this request.';
        $pdo->rollBack(); // Rollback transaction
        echo json_encode($response);
        exit();
    }

    // If all checks pass, update the claim_stub to 1
    $stmtUpdate = $pdo->prepare("UPDATE clearance_request SET claim_stub = 1 WHERE req_id = :req_id");
    $stmtUpdate->bindParam(':req_id', $reqId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    // Commit the transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Claim stub successfully released.';

} catch (PDOException $e) {
    $pdo->rollBack(); // Rollback on error
    error_log("Database error updating claim stub: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack(); // Rollback on other errors
    error_log("General error updating claim stub: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>
