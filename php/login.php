<?php
// php/login.php
session_start();
require_once 'database.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $accountType = $_POST['accountType'] ?? '';

    if (empty($email) || empty($password) || empty($accountType)) {
        echo "Please fill in all required fields.";
        exit;
    }

    try {
        if ($accountType === 'student') {
            // Authenticate student
            // Joins users and role tables to verify role is 'STUD'
            $stmt = $pdo->prepare("SELECT u.user_id, u.email, u.user_password, r.description AS role_description FROM users u JOIN role r ON u.role_code = r.role_code WHERE u.email = ? AND r.role_code = 'STUD'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['user_password'])) {
                // Login successful for student
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role_description']; // 'Student'
                header("Location: ../pages/student-dashboard.html"); // Redirect to student dashboard (updated path)
                exit;
            } else {
                echo "Invalid student credentials.";
            }
        } elseif ($accountType === 'admin') {
            // Authenticate admin (including sub-admins)
            // Joins users and role tables to verify role is 'ADMIN' or 'SUB_ADMIN'
            $stmt = $pdo->prepare("SELECT u.user_id, u.email, u.user_password, r.description AS role_description FROM users u JOIN role r ON u.role_code = r.role_code WHERE u.email = ? AND (r.role_code = 'ADMIN' OR r.role_code = 'SUB_ADMIN')");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['user_password'])) {
                // Login successful for admin/sub-admin
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role_description']; // 'Administrator' or 'Sub Administrator'
                header("Location: ../pages/admin-dashboard.html"); // Redirect to admin dashboard (updated path)
                exit;
            } else {
                echo "Invalid admin credentials.";
            }
        } else {
            echo "Invalid account type selected.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>