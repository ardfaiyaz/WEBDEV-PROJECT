<?php
session_start();
require_once 'api_config.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in."]);
    exit();
}

$userId = $_SESSION['user_id'];

$sql = "SELECT u.firstname, u.middlename, u.lastname, u.office_code
        FROM users u
        WHERE u.user_id = :user_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_name = trim($user['firstname']);

        echo json_encode(["success" => true, "user" => ["user_name" => $user_name, "office_code" => $user['office_code']]]);
    } else {
        error_log("Session user_id " . $userId . " not found in database.");
        echo json_encode(["success" => false, "message" => "Logged in user data not found."]);
    }
} catch (PDOException $e) {
    error_log("Database error in get_useroffice_info.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database query error."]);
}
?>