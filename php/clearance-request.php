<?php
// WEBDEV/WEBDEV-PROJECT/php/clearance-request.php

session_start(); // Start the PHP session at the very beginning


// Include your existing PDO database connection
require_once __DIR__ . '/database.php';

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


// --- SIMULATED LOGIN (FOR DEMONSTRATION ONLY) ---
// In a real application, this part would be handled by your actual login process.
// It would fetch user details from your database after successful authentication.
if (!isset($_SESSION['firstname'])) {
    $_SESSION['firstname'] = 'John'; // Default first name for demonstration
    $_SESSION['user_id'] = 1;      // Default user ID for demonstration (if needed later)
}
// --- END SIMULATED LOGIN ---


// --- API Logic ---
$method = $_SERVER['REQUEST_METHOD'];

$requestId = null;
$action = null; // New variable to determine specific action (e.g., 'user' for user info)

// Parse URL to get request ID or specific action
// Examples:
//   http://localhost/WEBDEV/WEBDEV-PROJECT/php/clearance-request.php/123 (for request ID)
//   http://localhost/WEBDEV/WEBDEV-PROJECT/php/clearance-request.php/user (for user info)
if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
    $path_segment = trim($_SERVER['PATH_INFO'], '/');
    if (ctype_digit($path_segment)) {
        $requestId = (int)$path_segment;
    } elseif ($path_segment === 'user') {
        $action = 'user'; // This indicates a request for user info
    }
}


switch ($method) {
    case 'GET':
        // --- Handle GET request for user info ---
        if ($action === 'user') {
            echo json_encode([
                'firstname' => $_SESSION['firstname'] ?? 'User'
            ]);
            exit(); // IMPORTANT: Stop script execution after sending user info
        }

        // --- Original GET request logic for clearance requests ---
        try {
            // SQL to fetch main request data, student info
            // Removed joins for clearance_status and document_request as per your earlier optimization
            $sql = "
                SELECT
                    cr.req_id,
                    si.student_no AS studId,
                    CONCAT(si.firstname, ' ', IFNULL(si.middlename, ''), ' ', si.lastname) AS studName,
                    si.course AS program,
                    DATE_FORMAT(cr.req_date, '%m-%d-%Y') AS dateSubmitted,
                    si.email_address AS studentEmail,
                    cr.student_remarks AS remark,
                    cr.claim_stub
                FROM
                    clearance_request cr
                JOIN
                    student_info si ON cr.user_id = si.user_id
                ORDER BY
                    cr.req_id DESC
            ";

            $stmt = $pdo->query($sql);
            $requests = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reqId = $row['req_id'];

                // Simplified Overall Status Determination based on claim_stub and remark
                $overallStatus = 'PENDING'; // Default
                if ($row['claim_stub'] == 1) {
                    $overallStatus = 'COMPLETED';
                } elseif (!empty($row['remark'])) {
                    $overallStatus = 'ON-GOING';
                }

                // Construct the final request object
                $requests[] = [
                    'reqId' => (string)$row['req_id'],
                    'studId' => $row['studId'],
                    'studName' => $row['studName'],
                    'program' => $row['program'],
                    'status' => $overallStatus,
                    'dateSubmitted' => $row['dateSubmitted'],
                    'studentEmail' => $row['studentEmail'],
                    'studentAvatar' => 'https://placehold.co/80x80/cccccc/333333?text=' . substr($row['studName'], 0, 1),
                    'remark' => $row['remark'],
                    'dateCompleted' => ($overallStatus === 'COMPLETED' && $row['claim_stub'] == 1) ? $row['dateSubmitted'] : null
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
        if (!$requestId) {
            http_response_code(400);
            echo json_encode(["message" => "Request ID is required for update."]);
            error_log("PUT/PATCH: Request ID is missing or invalid.");
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $updates = [];
        $params = [];

        try {
            if (isset($data['remark'])) {
                $updates[] = "student_remarks = ?";
                $params[] = $data['remark'];

                if (!isset($data['status']) || $data['status'] !== 'COMPLETED') {
                    $updates[] = "claim_stub = ?";
                    $params[] = 0;
                }
            }

            if (isset($data['status']) && $data['status'] === 'COMPLETED') {
                $updates[] = "claim_stub = ?";
                $params[] = 1;
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(["message" => "No valid fields provided for update."]);
                error_log("PUT/PATCH: No valid fields for update were provided for Request ID: " . $requestId);
                exit();
            }

            $sql = "UPDATE clearance_request SET " . implode(", ", $updates) . " WHERE req_id = ?";
            $params[] = $requestId;

            $stmt = $pdo->prepare($sql);
            if ($stmt === false) {
                 http_response_code(500);
                 echo json_encode(["message" => "Prepare failed: " . implode(" ", $pdo->errorInfo())]);
                 error_log("PUT/PATCH Prepare failed for Request ID: " . $requestId . " - " . implode(" ", $pdo->errorInfo()));
                 exit();
            }

            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Request updated successfully."]);
            } else {
                http_response_code(200); // Changed from 404
                echo json_encode(["message" => "Request found, but no new changes were applied (data was already up-to-date)."]);
                error_log("PUT/PATCH: No rows affected (data already up-to-date or ID did not exist for Request ID: " . $requestId . ").");
            }

        } catch (PDOException $e) {
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
