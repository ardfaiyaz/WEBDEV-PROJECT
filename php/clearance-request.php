<?php
// WEBDEV/WEBDEV-PROJECT/php/clearance-request.php

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the PHP session only if not already active
}

// Include your existing PDO database connection
require_once __DIR__ . '/database.php'; // Ensure this path is correct

// --- Set CORS Headers ---
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, PUT, PATCH, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- AUTHENTICATION AND USER CONTEXT (Your existing logic adapted) ---
$loggedInUserId = null;
$loggedInOfficeCode = null;
$loggedInUserRole = null;
$displayFirstName = "User";

if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];

    try {
        // Fetch user's details including office_code and role_code
        $stmt = $pdo->prepare("SELECT u.firstname, u.office_code, u.role_code FROM users u WHERE u.user_id = ?");
        $stmt->execute([$loggedInUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $displayFirstName = $user['firstname'];
            $loggedInOfficeCode = $user['office_code'];
            $loggedInUserRole = $user['role_code'];
        } else {
            // User ID in session but not found in DB - invalidate session
            session_unset();
            session_destroy();
            $loggedInUserId = null; // Mark as not logged in
        }
    } catch (PDOException $e) {
        // Log the error but don't expose sensitive info to the client
        error_log("Database error fetching user info: " . $e->getMessage());
        // For API, just treat as not logged in or return a server error
        http_response_code(500);
        echo json_encode(["message" => "Server error during authentication."]);
        exit();
    }
}

// --- API access control based on login status and role ---
// This central check prevents unauthenticated access to most API routes
if (!$loggedInUserId && $action !== 'user') { // Allow 'user' endpoint for initial frontend checks if needed
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Unauthorized: Please log in to access this resource."]);
    exit();
}
// --- END AUTHENTICATION AND USER CONTEXT ---


$method = $_SERVER['REQUEST_METHOD'];

$requestId = null;
$action = null;

if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $path_segment = trim($_SERVER['PATH_INFO'], '/');
    if (ctype_digit($path_segment)) {
        $requestId = (int)$path_segment;
    } elseif ($path_segment === 'user') {
        $action = 'user'; 
    }
}


switch ($method) {
    case 'GET':
        if ($action === 'user') {
            echo json_encode([
                'firstname' => $displayFirstName,
                'officeCode' => $loggedInOfficeCode,
                'roleCode' => $loggedInUserRole
            ]);
            exit();
        }

        try {
            $sql = "
                SELECT
                    cr.req_id,
                    si.student_no AS studId,
                    CONCAT(si.firstname, ' ', IFNULL(si.middlename, ''), ' ', si.lastname) AS studName,
                    si.course AS program,
                    DATE_FORMAT(cr.req_date, '%m-%d-%Y') AS dateSubmitted,
                    si.email_address AS studentEmail,
                    cr.student_remarks AS remark,
                    cr.claim_stub,
                    -- Logic to determine Overall Status based on associated office statuses
                    CASE
                        WHEN MAX(CASE WHEN rs.status_code = 'ISSUE' THEN 1 ELSE 0 END) = 1 THEN 'ISSUE' -- If any office has an issue
                        WHEN MIN(CASE WHEN rs.status_code = 'COMP' THEN 1 ELSE 0 END) = 1 THEN 'COMPLETED' -- If ALL offices are completed
                        WHEN MAX(CASE WHEN rs.status_code = 'ON' THEN 1 ELSE 0 END) = 1 THEN 'ON-GOING' -- If any office is on-going (and no issues)
                        ELSE 'PENDING' -- Otherwise, it's pending (or a mix of pending/on-going)
                    END AS overallStatus
                FROM
                    clearance_request cr
                JOIN
                    student_info si ON cr.user_id = si.user_id
                LEFT JOIN -- Use LEFT JOIN to ensure requests without any status entries are still included
                    clearance_status cs ON cr.req_id = cs.req_id
                LEFT JOIN -- Join to get the status description
                    request_status rs ON cs.status_code = rs.status_code
                GROUP BY
                    cr.req_id, si.student_no, si.firstname, si.middlename, si.lastname, si.course,
                    cr.req_date, si.email_address, cr.student_remarks, cr.claim_stub
                ORDER BY
                    cr.req_id DESC
            ";

            $stmt = $pdo->query($sql);
            $requests = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $requests[] = [
                    'reqId' => (string)$row['req_id'],
                    'studId' => $row['studId'],
                    'studName' => $row['studName'],
                    'program' => $row['program'],
                    'status' => $row['overallStatus'], 
                    'dateSubmitted' => $row['dateSubmitted'],
                    'studentEmail' => $row['studentEmail'],
                    'studentAvatar' => 'https://placehold.co/80x80/cccccc/333333?text=' . substr($row['studName'], 0, 1),
                    'remark' => $row['remark'],
                    'claimStub' => $row['claim_stub'], 
                    'dateCompleted' => ($row['overallStatus'] === 'COMPLETED' && $row['claim_stub'] == 1) ? $row['dateSubmitted'] : null
                ];
            }
            echo json_encode($requests);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
            error_log("GET Request Error: " . $e->getMessage());
        }
        break;

    case 'PUT':
    case 'PATCH':
        if ($loggedInUserRole !== 'ADMIN' && $loggedInUserRole !== 'SUB_ADMIN') {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden: You do not have permission to update requests."]);
            exit();
        }

        if (!$requestId) {
            http_response_code(400);
            echo json_encode(["message" => "Request ID is required for update."]);
            error_log("PUT/PATCH: Request ID is missing or invalid.");
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $validStatusCodes = ['PEND', 'ON', 'COMP', 'ISSUE'];

        $newStudentRemark = $data['studentRemark'] ?? null;
        $newOfficeRemark = $data['officeRemark'] ?? null;
        $newStatusCode = $data['statusCode'] ?? null;

        $officeCode = $loggedInOfficeCode;

        if (!$officeCode) {
            http_response_code(403);
            echo json_encode(["message" => "Office information not associated with your account. Cannot perform update."]);
            error_log("PUT/PATCH: Logged in user " . $loggedInUserId . " has no associated office_code.");
            exit();
        }

        try {
            $pdo->beginTransaction();

            $updatesCount = 0;

            if ($newStudentRemark !== null) {
                $sql_student_remark = "UPDATE clearance_request SET student_remarks = ? WHERE req_id = ?";
                $stmt_student_remark = $pdo->prepare($sql_student_remark);
                $stmt_student_remark->execute([$newStudentRemark, $requestId]);
                $updatesCount += $stmt_student_remark->rowCount();
            }

            if ($newStatusCode !== null || $newOfficeRemark !== null) {
                if ($newStatusCode !== null && !in_array($newStatusCode, $validStatusCodes)) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(["message" => "Invalid status code provided: " . htmlspecialchars($newStatusCode)]);
                    error_log("PUT/PATCH: Invalid status code provided for Request ID: " . $requestId . " - " . $newStatusCode);
                    exit();
                }

                $check_sql = "SELECT COUNT(*) FROM clearance_status WHERE req_id = ? AND office_code = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$requestId, $officeCode]);
                $exists = $check_stmt->fetchColumn();

                if ($exists > 0) {
                    $status_updates = [];
                    $status_params = [];

                    if ($newStatusCode !== null) {
                        $status_updates[] = "status_code = ?";
                        $status_params[] = $newStatusCode;
                    }
                    if ($newOfficeRemark !== null) {
                        $status_updates[] = "office_remarks = ?";
                        $status_params[] = $newOfficeRemark;
                    }

                    if (!empty($status_updates)) {
                        $sql_clearance_status = "UPDATE clearance_status SET " . implode(", ", $status_updates) . " WHERE req_id = ? AND office_code = ?";
                        $status_params[] = $requestId;
                        $status_params[] = $officeCode;

                        $stmt_clearance_status = $pdo->prepare($sql_clearance_status);
                        $stmt_clearance_status->execute($status_params);
                        $updatesCount += $stmt_clearance_status->rowCount();
                    }
                } else {
                    $get_student_id_sql = "SELECT user_id FROM clearance_request WHERE req_id = ?";
                    $get_student_id_stmt = $pdo->prepare($get_student_id_sql);
                    $get_student_id_stmt->execute([$requestId]);
                    $student_userId = $get_student_id_stmt->fetchColumn();

                    if (!$student_userId) {
                         $pdo->rollBack();
                         http_response_code(404);
                         echo json_encode(["message" => "Request not found for office update (could not determine student user_id)."]);
                         error_log("PUT/PATCH: Request ID " . $requestId . " not found for creating clearance_status entry.");
                         exit();
                    }

                    $sql_insert_status = "INSERT INTO clearance_status (req_id, user_id, office_code, status_code, office_remarks) VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert_status = $pdo->prepare($sql_insert_status);
                    $stmt_insert_status->execute([
                        $requestId,
                        $student_userId,
                        $officeCode,
                        $newStatusCode ?? 'PEND', 
                        $newOfficeRemark
                    ]);
                    $updatesCount += $stmt_insert_status->rowCount();
                }
            }

            $pdo->commit();

            if ($updatesCount > 0) {
                http_response_code(200);
                echo json_encode(["message" => "Request and/or office status updated successfully for Request ID: " . $requestId . "."]);
            } else {
                http_response_code(200);
                echo json_encode(["message" => "Request found, but no new changes were applied (data was already up-to-date or no relevant fields provided)."]);
                error_log("PUT/PATCH: No rows affected for Request ID: " . $requestId . " (data already up-to-date or no relevant fields provided).");
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Failed to update request: " . $e->getMessage()]);
            error_log("PUT/PATCH Request Error for Request ID: " . $requestId . " - " . $e->getMessage());
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed."]);
        break;
}
?>