<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'api_config.php';
require_once 'database.php';

if (!isset($_GET['office_code'])) {
    echo json_encode(["success" => false, "message" => "Office code not provided."]);
    exit();
}

$officeCode = $_GET['office_code'];

$sql = "SELECT
            cs.req_id,
            si.student_no AS student_id,
            CONCAT(si.firstname, ' ', si.middlename, ' ', si.lastname) AS student_name,
            si.course AS program,
            cs.status_code,
            cr.req_date AS date_submitted,
            cs.office_remarks
        FROM
            clearance_status cs
        JOIN
            clearance_request cr ON cs.req_id = cr.req_id
        JOIN
            student_info si ON cs.user_id = si.user_id
        WHERE
            cs.office_code = :office_code";

$requests = [];

try {
    $stmt = $pdo->prepare($sql);

    $stmt->execute([':office_code' => $officeCode]);

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "requests" => $requests]);

} catch (PDOException $e) {
    error_log("Database error in get_clearance_requests.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database query error: " . $e->getMessage()]);
}
?>