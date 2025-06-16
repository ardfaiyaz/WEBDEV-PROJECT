<?php
include 'database.php';

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
            si.program,
            cs.status_code,
            cs.office_remarks,
            GROUP_CONCAT(d.document_name SEPARATOR '; ') AS requested_documents
        FROM
            clearance_request cr
        JOIN
            users u ON cr.user_id = u.user_id
        LEFT JOIN
            student_info si ON u.user_id = si.user_id
        LEFT JOIN
            clearance_status cs ON cr.req_id = cs.req_id AND cs.office_code = ?
        LEFT JOIN
            document_request dr ON cr.req_id = dr.req_id
        LEFT JOIN
            document d ON dr.doc_code = d.doc_code
        WHERE
            cr.req_id = ?
        GROUP BY
            cr.req_id, u.firstname, u.lastname, u.email, si.student_no, si.program, cs.status_code, cs.office_remarks";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("si", $office_code, $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $request_details = $result->fetch_assoc();
    // Ensure consent_letter_exists is a boolean
    $request_details['consent_letter_exists'] = (bool)$request_details['consent_letter_exists'];
    echo json_encode($request_details);
} else {
    echo json_encode(['error' => 'Request not found or not associated with this office.']);
}

$stmt->close();
$conn->close();
?>