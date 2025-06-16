<?php
session_start();

require_once 'database.php'; // Adjust path as necessary, assuming it's in the same directory as this file

header('Content-Type: application/json'); // Set header for JSON response

// 1. Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'STUD') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access or session expired. Please log in again.']);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

try {
    $pdo->beginTransaction(); // Start a database transaction

    // Debugging: Verify loggedInUserId
    // This will appear in the browser's Network tab response if prior PHP doesn't exit.
    // error_log("Attempting to delete user_id: " . $loggedInUserId); 

    // 2. Delete related records in dependent tables first

    // a. Find all clearance_request_ids for this user
    try {
        $stmt_get_req_ids = $pdo->prepare("SELECT req_id FROM clearance_request WHERE user_id = :user_id");
        $stmt_get_req_ids->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_get_req_ids->execute();
        $req_ids = $stmt_get_req_ids->fetchAll(PDO::FETCH_COLUMN);
        // error_log("Fetched req_ids: " . implode(',', $req_ids)); // For server-side logging
    } catch (PDOException $e) {
        throw new Exception("Failed to fetch clearance request IDs: " . $e->getMessage(), $e->getCode());
    }


    if (!empty($req_ids)) {
        $placeholders = rtrim(str_repeat('?,', count($req_ids)), ',');

        // b. Delete from document_request (if linked to clearance_request)
        try {
            $stmt_delete_doc_req = $pdo->prepare("DELETE FROM document_request WHERE req_id IN ($placeholders)");
            $stmt_delete_doc_req->execute($req_ids);
            // error_log("Deleted " . $stmt_delete_doc_req->rowCount() . " from document_request.");
        } catch (PDOException $e) {
            throw new Exception("Failed to delete from document_request: " . $e->getMessage(), $e->getCode());
        }

        // c. Delete from clearance_status (linked to clearance_request)
        try {
            $stmt_delete_clearance_status = $pdo->prepare("DELETE FROM clearance_status WHERE req_id IN ($placeholders)");
            $stmt_delete_clearance_status->execute($req_ids);
            // error_log("Deleted " . $stmt_delete_clearance_status->rowCount() . " from clearance_status.");
        } catch (PDOException $e) {
            throw new Exception("Failed to delete from clearance_status: " . $e->getMessage(), $e->getCode());
        }
    }

    // d. Delete from clearance_request
    try {
        $stmt_delete_clearance_request = $pdo->prepare("DELETE FROM clearance_request WHERE user_id = :user_id");
        $stmt_delete_clearance_request->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_clearance_request->execute();
        // error_log("Deleted " . $stmt_delete_clearance_request->rowCount() . " from clearance_request.");
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from clearance_request: " . $e->getMessage(), $e->getCode());
    }

    // e. Delete from notification
    try {
        $stmt_delete_notification = $pdo->prepare("DELETE FROM notification WHERE user_id = :user_id");
        $stmt_delete_notification->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_notification->execute();
        // error_log("Deleted " . $stmt_delete_notification->rowCount() . " from notification.");
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from notification: " . $e->getMessage(), $e->getCode());
    }

    // f. Delete from student_info
    try {
        $stmt_delete_student_info = $pdo->prepare("DELETE FROM student_info WHERE user_id = :user_id");
        $stmt_delete_student_info->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_student_info->execute();
        // error_log("Deleted " . $stmt_delete_student_info->rowCount() . " from student_info.");
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from student_info: " . $e->getMessage(), $e->getCode());
    }

    // 3. Finally, delete the user from the users table
    try {
        $stmt_delete_user = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt_delete_user->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_user->execute();
        // error_log("Deleted " . $stmt_delete_user->rowCount() . " from users table.");
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from users table: " . $e->getMessage(), $e->getCode());
    }

    $pdo->commit(); // Commit the transaction

    // Destroy the session after successful deletion
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Your account has been successfully deleted.']);

} catch (Exception $e) { // Catch both PDOException and custom Exceptions
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback if any error occurs
    }
    // Check if the error is a foreign key constraint violation
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
        $errorMessage = "Deletion failed due to existing related records. This usually means a foreign key constraint was violated: " . $e->getMessage();
    } else {
        $errorMessage = "An error occurred during account deletion: " . $e->getMessage();
    }
    
    error_log("Account deletion error for user_id " . $loggedInUserId . ": " . $e->getMessage()); // Log to server's error log
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}