<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/database.php'; // Adjust path as needed

$response = ['success' => false, 'message' => '', 'student_details' => null, 'office_statuses' => [], 'requested_documents' => [], 'student_overall_remarks' => '', 'has_consent_letter' => false];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_code']) || ($_SESSION['role_code'] !== 'SUB_ADMIN' && $_SESSION['role_code'] !== 'ADMIN')) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqId = $_POST['req_id'] ?? null;
    $studentUserId = $_POST['student_user_id'] ?? null;

    if (!$reqId || !$studentUserId) {
        $response['message'] = 'Missing request ID or student user ID.';
        echo json_encode($response);
        exit();
    }

    try {
        // Fetch Student Details, student_remarks, and Consent Letter existence
        $stmtStudent = $pdo->prepare("
            SELECT
                u.firstname, u.lastname, u.email,
                si.student_no,
                si.course,
                cr.student_remarks, -- Get student remarks from clearance_request
                cr.consent_letter IS NOT NULL AS has_consent_letter -- Check if consent_letter exists
            FROM
                users u
            LEFT JOIN
                student_info si ON u.user_id = si.user_id
            LEFT JOIN
                clearance_request cr ON u.user_id = cr.user_id AND cr.req_id = :req_id -- Join to get student remarks and consent_letter
            WHERE
                u.user_id = :student_user_id
        ");
        $stmtStudent->bindParam(':student_user_id', $studentUserId, PDO::PARAM_INT);
        $stmtStudent->bindParam(':req_id', $reqId, PDO::PARAM_INT); // Bind req_id for the join
        $stmtStudent->execute();
        $studentData = $stmtStudent->fetch(PDO::FETCH_ASSOC);

        if (!$studentData) {
            $response['message'] = 'Student details or request not found.';
            echo json_encode($response);
            exit();
        }

        $studentDetails = [
            'firstname' => $studentData['firstname'],
            'lastname' => $studentData['lastname'],
            'email' => $studentData['email'],
            'student_no' => $studentData['student_no'],
            'course' => $studentData['course'],
        ];
        $studentDetails['avatar_url'] = "https://placehold.co/80x80/cccccc/333333?text=" . strtoupper($studentDetails['firstname'][0] . ($studentDetails['lastname'][0] ?? 'S'));
        $response['student_details'] = $studentDetails;
        $response['student_overall_remarks'] = $studentData['student_remarks'] ?: 'No specific remarks from student.';
        $response['has_consent_letter'] = (bool)$studentData['has_consent_letter'];

        // Fetch All Office Statuses for this request
        $stmtOfficeStatuses = $pdo->prepare("
            SELECT
                cs.office_code,
                o.description AS office_description,
                cs.status_code,
                rs.description AS status_description,
                cs.office_remarks
            FROM
                clearance_status cs
            JOIN
                office o ON cs.office_code = o.office_code
            JOIN
                request_status rs ON cs.status_code = rs.status_code
            WHERE
                cs.req_id = :req_id AND cs.user_id = :student_user_id
            ORDER BY o.description
        ");
        $stmtOfficeStatuses->bindParam(':req_id', $reqId, PDO::PARAM_INT);
        $stmtOfficeStatuses->bindParam(':student_user_id', $studentUserId, PDO::PARAM_INT);
        $stmtOfficeStatuses->execute();
        $response['office_statuses'] = $stmtOfficeStatuses->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Requested Documents
        $stmtDocuments = $pdo->prepare("
            SELECT dr.doc_copies, d.description
            FROM document_request dr
            JOIN document d ON dr.doc_code = d.doc_code
            WHERE dr.req_id = :req_id
        ");
        $stmtDocuments->bindParam(':req_id', $reqId, PDO::PARAM_INT);
        $stmtDocuments->execute();
        $response['requested_documents'] = $stmtDocuments->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;

    } catch (PDOException $e) {
        error_log("Database error fetching clearance details: " . $e->getMessage());
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>