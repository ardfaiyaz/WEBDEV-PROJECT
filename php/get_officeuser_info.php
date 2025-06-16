<?php
require_once 'database.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT u.firstname, u.lastname, u.email, u.office_code, o.description AS office_name -- Changed to o.description
        FROM users u
        LEFT JOIN office o ON u.office_code = o.office_code
        WHERE u.user_id = :user_id"; // Use named placeholder for PDO

try {
    // Use $pdo for prepare
    $stmt = $pdo->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(['error' => 'Failed to prepare statement.']);
        exit();
    }

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Bind as integer

    $stmt->execute();
    
    // PDO fetches results directly from the statement
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_info) {
        echo json_encode($user_info);
    } else {
        echo json_encode(['error' => 'User not found']);
    }

} catch (PDOException $e) {
    // Catch PDO-specific exceptions
    error_log("Database error in get_officeuser_info.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred.']);
    exit();
}
