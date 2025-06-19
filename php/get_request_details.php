<?php
// get_request_details.php
require_once __DIR__ . '/database.php'; // Use require_once for critical includes like this

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['office_code'])) {
    echo json_encode(['error' => 'Unauthorized access or office not assigned.']);
    exit();
}

$office_code = $_SESSION['office_code'];
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id === 0) {
    echo json_encode(['error' => 'Invalid request ID.']);
    exit();
}

try {
    // Use $pdo which should be established in database.php
    $sql = "SELECT
                cr.req_id,
                cr.user_id,
                cr.req_date,
                cr.claim_stub,
                cr.enrollment_purpose,
                cr.student_remarks,
                CASE WHEN cr.consent_letter IS NOT NULL THEN TRUE ELSE FALSE END AS consent_letter_exists,
                u.firstname,
                u.lastname,
                u.email,
                si.student_no,
                si.course AS program, -- Assuming 'program' in select maps to 'course' in student_info
                cs.status_code,
                cs.office_remarks,
                GROUP_CONCAT(d.description SEPARATOR '; ') AS requested_documents -- 'description' from document table
            FROM
                clearance_request cr
            JOIN
                users u ON cr.user_id = u.user_id
            LEFT JOIN
                student_info si ON u.user_id = si.user_id
            LEFT JOIN
                clearance_status cs ON cr.req_id = cs.req_id AND cs.office_code = :office_code
            LEFT JOIN
                document_request dr ON cr.req_id = dr.req_id
            LEFT JOIN
                document d ON dr.doc_code = d.doc_code
            WHERE
                cr.req_id = :req_id
            GROUP BY
                cr.req_id, u.firstname, u.lastname, u.email, si.student_no, si.course, cs.status_code, cs.office_remarks";

    $stmt = $pdo->prepare($sql); // Use $pdo->prepare() for PDO

    if (!$stmt) { // PDO::prepare returns false on failure
        $errorInfo = $pdo->errorInfo();
        error_log("PDO Prepare failed: " . $errorInfo[2]); // Log detailed error
        echo json_encode(['error' => 'Database query preparation failed.']);
        exit();
    }

    // Bind parameters for PDO
    $stmt->bindParam(':office_code', $office_code, PDO::PARAM_STR);
    $stmt->bindParam(':req_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();

    $request_details = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result

    if ($request_details) { // If a row was fetched
        // Ensure consent_letter_exists is a boolean
        $request_details['consent_letter_exists'] = (bool)$request_details['consent_letter_exists'];
        echo json_encode($request_details);
    } else {
        echo json_encode(['error' => 'Request not found or not associated with this office.']);
    }

} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage()); // Log PDO exceptions
    echo json_encode(['error' => 'A database error occurred.']);
} finally {
    // No need to close PDO connection explicitly, it closes when script ends
    // If you're managing PDO as a global or singleton, you might not close here.
    // If $pdo is just a local variable, it will be garbage collected.
}
?>