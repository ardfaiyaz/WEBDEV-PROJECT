<?php
session_start();

header('Content-Type: application/json');

ob_start();
var_dump($_SESSION);
$session_dump = ob_get_clean();

require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in as an administrator.', 'debug_session' => $session_dump]);
    exit();
}

$response = ['success' => false, 'message' => ''];

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['req_id'])) {
    $response['message'] = 'Request ID (req_id) is missing.';
    echo json_encode($response);
    exit();
}

$reqId = $data['req_id'];

try {
    $pdo->beginTransaction();

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

    if (empty($officeStatuses)) {
        $response['message'] = 'No office statuses found for this request.';
        $pdo->rollBack();
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
        $pdo->rollBack();
        echo json_encode($response);
        exit();
    }

    $stmtCheckClaimStub = $pdo->prepare("SELECT claim_stub FROM clearance_request WHERE req_id = :req_id");
    $stmtCheckClaimStub->bindParam(':req_id', $reqId, PDO::PARAM_INT);
    $stmtCheckClaimStub->execute();
    $currentClaimStubStatus = $stmtCheckClaimStub->fetchColumn();

    if ($currentClaimStubStatus === false) {
        $response['message'] = 'Request not found.';
        $pdo->rollBack();
        echo json_encode($response);
        exit();
    }

    if ($currentClaimStubStatus == 1) {
        $response['message'] = 'Claim stub has already been released for this request.';
        $pdo->rollBack();
        echo json_encode($response);
        exit();
    }

    $stmtUpdate = $pdo->prepare("UPDATE clearance_request SET claim_stub = 1 WHERE req_id = :req_id");
    $stmtUpdate->bindParam(':req_id', $reqId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Claim stub successfully released.';

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error updating claim stub: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("General error updating claim stub: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>