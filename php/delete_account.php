<?php
session_start();

require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'STUD') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access or session expired. Please log in again.']);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    try {
        $stmt_get_req_ids = $pdo->prepare("SELECT req_id FROM clearance_request WHERE user_id = :user_id");
        $stmt_get_req_ids->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_get_req_ids->execute();
        $req_ids = $stmt_get_req_ids->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        throw new Exception("Failed to fetch clearance request IDs: " . $e->getMessage(), $e->getCode());
    }

    if (!empty($req_ids)) {
        $placeholders = rtrim(str_repeat('?,', count($req_ids)), ',');

        try {
            $stmt_delete_doc_req = $pdo->prepare("DELETE FROM document_request WHERE req_id IN ($placeholders)");
            $stmt_delete_doc_req->execute($req_ids);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete from document_request: " . $e->getMessage(), $e->getCode());
        }

        try {
            $stmt_delete_clearance_status = $pdo->prepare("DELETE FROM clearance_status WHERE req_id IN ($placeholders)");
            $stmt_delete_clearance_status->execute($req_ids);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete from clearance_status: " . $e->getMessage(), $e->getCode());
        }
    }

    try {
        $stmt_delete_clearance_request = $pdo->prepare("DELETE FROM clearance_request WHERE user_id = :user_id");
        $stmt_delete_clearance_request->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_clearance_request->execute();
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from clearance_request: " . $e->getMessage(), $e->getCode());
    }

    try {
        $stmt_delete_notification = $pdo->prepare("DELETE FROM notification WHERE user_id = :user_id");
        $stmt_delete_notification->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_notification->execute();
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from notification: " . $e->getMessage(), $e->getCode());
    }

    try {
        $stmt_delete_student_info = $pdo->prepare("DELETE FROM student_info WHERE user_id = :user_id");
        $stmt_delete_student_info->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_student_info->execute();
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from student_info: " . $e->getMessage(), $e->getCode());
    }

    try {
        $stmt_delete_user = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt_delete_user->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmt_delete_user->execute();
    } catch (PDOException $e) {
        throw new Exception("Failed to delete from users table: " . $e->getMessage(), $e->getCode());
    }

    $pdo->commit();

    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Your account has been successfully deleted.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
        $errorMessage = "Deletion failed due to existing related records. This usually means a foreign key constraint was violated: " . $e->getMessage();
    } else {
        $errorMessage = "An error occurred during account deletion: " . $e->getMessage();
    }
    
    error_log("Account deletion error for user_id " . $loggedInUserId . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}