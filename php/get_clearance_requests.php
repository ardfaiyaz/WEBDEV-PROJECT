<?php
// get_clearance_requests.php

session_start(); // Ensure session is started for session variables if you decide to use them here later

// --- TEMPORARY DEBUGGING LINES START ---
// KEEP these for now until everything is working, then REMOVE them in production!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- TEMPORARY DEBUGGING LINES END ---

require_once 'api_config.php';
require_once 'database.php'; // This file provides the $pdo object

// Check if office_code is provided in the GET request
if (!isset($_GET['office_code'])) {
    echo json_encode(["success" => false, "message" => "Office code not provided."]);
    // With PDO, you don't explicitly close the connection like $conn->close().
    // The connection will close automatically when the script ends.
    exit();
}

// Get the officeCode from the GET parameters.
// With PDO prepared statements, you do NOT need real_escape_string().
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
            cs.office_code = :office_code"; // Use a named placeholder for PDO

$requests = []; // Initialize an empty array to hold the fetched requests

try {
    // Prepare the SQL statement using the $pdo object
    $stmt = $pdo->prepare($sql);

    // Execute the prepared statement, binding the :office_code placeholder to the actual $officeCode value.
    $stmt->execute([':office_code' => $officeCode]);

    // Fetch all results as associative arrays.
    // fetchAll(PDO::FETCH_ASSOC) is typically more efficient than a while loop for multiple rows.
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send a successful JSON response with the fetched requests
    echo json_encode(["success" => true, "requests" => $requests]);

} catch (PDOException $e) {
    // Catch any PDO-specific exceptions (database errors)
    error_log("Database error in get_clearance_requests.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database query error: " . $e->getMessage()]); // Include message for debugging
}

// Remove $conn->close(); as it's for MySQLi and not needed for PDO.
// $conn->close(); // This line is removed
?>