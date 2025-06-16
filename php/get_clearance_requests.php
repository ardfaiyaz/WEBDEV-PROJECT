<?php

require_once 'database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['office_code'])) {
    echo json_encode(['error' => 'Unauthorized access or office not assigned.']);
    exit();
}

$office_code = $_SESSION['office_code'];

// --- TEMPORARY DEBUGGING LINES START ---
error_log("Debugging get_clearance_requests.php (PDO version):");
error_log("Session office_code: " . $office_code);
// --- TEMPORARY DEBUGGING LINES END ---

$sql = "SELECT
            cr.req_id,
            cr.req_date,
            cr.claim_stub,
            u.firstname,
            u.lastname,
            si.student_no,
            si.program,
            cs.status_code,
            cs.office_remarks
        FROM
            clearance_request cr
        JOIN
            users u ON cr.user_id = u.user_id
        LEFT JOIN
            student_info si ON u.user_id = si.user_id
        LEFT JOIN
            clearance_status cs ON cr.req_id = cs.req_id AND cs.office_code = :office_code -- Use named placeholder
        ORDER BY
            cr.req_date DESC";

try {
    // Use $pdo for prepare
    $stmt = $pdo->prepare($sql);
    
    // Bind the parameter using PDO's bindParam
    $stmt->bindParam(':office_code', $office_code, PDO::PARAM_STR);
    
    $stmt->execute();
    
    // Fetch all results as an associative array
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- TEMPORARY DEBUGGING LINES START ---
    error_log("SQL executed. Number of rows found by PHP: " . count($requests));
    // --- TEMPORARY DEBUGGING LINES END ---

    echo json_encode($requests);

    // With PDO, you don't explicitly close statements or connections in this manner.
    // They are typically closed automatically when the script finishes or objects go out of scope.

} catch (PDOException $e) {
    // Catch PDO-specific exceptions for database errors
    error_log("Database error in get_clearance_requests.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
    exit();
}
?>