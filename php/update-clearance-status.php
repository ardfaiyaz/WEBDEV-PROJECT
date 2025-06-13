<?php
session_start();
header('Content-Type: application/json');

// Include the database connection file
require_once __DIR__ . '/database.php'; // Adjust path as needed

$response = ['success' => false, 'message' => ''];

// Check if the user is logged in and is an office staff member (role_code 'SUB_ADMIN' or 'ADMIN')
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_code']) || ($_SESSION['role_code'] !== 'SUB_ADMIN' && $_SESSION['role_code'] !== 'ADMIN')) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserOfficeCode = null;

// Fetch the logged-in user's office code
try {
    $stmtOffice = $pdo->prepare("SELECT office_code FROM users WHERE user_id = :user_id");
    $stmtOffice->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmtOffice->execute();
    $loggedInUserOfficeCode = $stmtOffice->fetchColumn();

    if (!$loggedInUserOfficeCode) {
        $response['message'] = 'User is not assigned to an office or office not found.';
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching logged-in user's office_code: " . $e->getMessage());
    $response['message'] = 'Database error fetching office info.';
    echo json_encode($response);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqId = $_POST['req_id'] ?? null;
    $studentUserId = $_POST['student_user_id'] ?? null;
    $targetOfficeCode = $_POST['office_code'] ?? null;
    $statusCode = $_POST['status_code'] ?? null;
    $officeRemarks = $_POST['office_remarks'] ?? null;

    // Validate inputs
    if (!$reqId || !$studentUserId || !$targetOfficeCode || !$statusCode) {
        $response['message'] = 'Missing required parameters.';
        echo json_encode($response);
        exit();
    }

    // IMPORTANT SECURITY CHECK: Ensure the logged-in office staff can only update THEIR own office's status
    if ($targetOfficeCode !== $loggedInUserOfficeCode) {
        $response['message'] = 'Attempted to update status for an unauthorized office.';
        error_log("Security Alert: User " . $loggedInUserId . " tried to update " . $targetOfficeCode . " but is assigned to " . $loggedInUserOfficeCode);
        echo json_encode($response);
        exit();
    }

    try {
        // Update the clearance_status for the specific request, student, and office
        $stmtUpdate = $pdo->prepare("
            UPDATE clearance_status
            SET
                status_code = :status_code,
                office_remarks = :office_remarks
            WHERE
                req_id = :req_id AND user_id = :student_user_id AND office_code = :office_code
        ");

        $stmtUpdate->bindParam(':status_code', $statusCode, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':office_remarks', $officeRemarks, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':req_id', $reqId, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':student_user_id', $studentUserId, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':office_code', $targetOfficeCode, PDO::PARAM_STR);

        if ($stmtUpdate->execute()) {
            if ($stmtUpdate->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Clearance status updated successfully.';

                // Logic to check if all offices for a request are COMPLETED to update clearance_request.claim_stub
                $stmtCheckAllCompleted = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM clearance_status
                    WHERE req_id = :req_id AND user_id = :student_user_id AND status_code != 'COMP'
                ");
                $stmtCheckAllCompleted->bindParam(':req_id', $reqId, PDO::PARAM_INT);
                $stmtCheckAllCompleted->bindParam(':student_user_id', $studentUserId, PDO::PARAM_INT);
                $stmtCheckAllCompleted->execute();
                $pendingOfficesCount = $stmtCheckAllCompleted->fetchColumn();

                if ($pendingOfficesCount == 0) {
                    // All offices have completed their part for this request
                    $stmtUpdateClaimStub = $pdo->prepare("
                        UPDATE clearance_request
                        SET claim_stub = 1, student_remarks = 'Ready for claiming' -- Example remark for student upon full completion
                        WHERE req_id = :req_id AND user_id = :student_user_id
                    ");
                    $stmtUpdateClaimStub->bindParam(':req_id', $reqId, PDO::PARAM_INT);
                    $stmtUpdateClaimStub->bindParam(':student_user_id', $studentUserId, PDO::PARAM_INT);
                    $stmtUpdateClaimStub->execute();
                    $response['message'] .= ' All offices cleared. Claim stub marked as ready.';
                }


            } else {
                $response['message'] = 'No record found or no changes made for the specified request, student, and office. It might not exist or already be at this status.';
            }
        } else {
            $response['message'] = 'Failed to execute update statement.';
        }

    } catch (PDOException $e) {
        error_log("Database error updating clearance status: " . $e->getMessage());
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>