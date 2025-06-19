<?php
session_start();
require_once '../php/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $accountType = $_POST['accountType'];

    $roleCode = '';
    if ($accountType === 'student') {
        $roleCode = 'STUD';
    } elseif ($accountType === 'admin') {
        $roleCode = 'ADMIN';
    } elseif ($accountType === 'sub_admin') {
        $roleCode = 'SUB_ADMIN';
    } else {
        $_SESSION['login_error'] = "Invalid account type selected.";
        header("Location: ../pages/login.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, user_password, role_code, firstname, office_code FROM users WHERE email = :email AND role_code = :role_code");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role_code', $roleCode, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['user_password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['account_type'] = $user['role_code'];
                $_SESSION['firstname'] = $user['firstname'];

                if ($user['role_code'] === 'ADMIN' || $user['role_code'] === 'SUB_ADMIN') {
                    $_SESSION['office_code'] = $user['office_code'];
                } else {
                    unset($_SESSION['office_code']);
                }

                session_regenerate_id(true);

                if ($user['role_code'] === 'STUD') {
                    header("Location: index.php");
                } elseif ($user['role_code'] === 'ADMIN') {
                    header("Location: admin-index.php");
                } elseif ($user['role_code'] === 'SUB_ADMIN') {
                    header("Location: clearance-request.php");
                }

                exit();
            } else {
                $_SESSION['login_error'] = "Invalid email or password.";
                header("Location: ../pages/login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: ../pages/login.php");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login. Please try again later.";
        header("Location: ../pages/login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
</head>
<body>
    <div class="container">

        <div class="left-panel">
            <img src="../assets/icons/NU_shield.svg.png" alt="NU Logo" class="logo">
            <h1>WELCOME TO <br> NU STUDENT CLEARANCE <br> SYSTEM</h1>
            <p>The Office of the University Registrar maintains the <br> centralized student clearance system.</p>
        </div>

        <div class="right-panel">
            <form id="loginForm" action="../pages/login.php" method="POST"> <div class="login-header">
                    <h2>Log in</h2>
                </div>

                <div class="input-fields">
                    <h4>Select Account Type</h4>
                    <div class="input-group">
                        <span class="iconType"><img src="../assets/icons/user-icon.png" alt="Human Icon"></span>
                        <select id="accountType" name="accountType" required>
                            <option value="" disabled selected>Choose Type</option>
                            <option value="student">Student</option>
                            <option value="admin">Admin</option>
                            <option value="sub_admin">Sub-Admin</option>
                        </select>
                    </div>
                    <h4>Log In Credentials</h4>
                    <div class="input-group">
                        <span class="icon"><img src="../assets/icons/at-sign.png" alt="Your Icon"></span>
                        <input type="email" id="emailInput" name="email" placeholder="Enter your email here" required>
                    </div>

                    <div class="input-group">
                        <span class="iconlock"><img src="../assets/icons/lock-sign.png" alt="icon lock"></span>
                        <input type="password" id="passwordInput" name="password" placeholder="Enter your password here" required>
                    </div>
                </div>

                <div class="login-button-container">
                    <button type="submit" class="login-button">Log in with NUSCS</button>
                </div>

                <div class="divider">or Sign Up as</div>

                <div class="signup-links-container">
                    <a href="user-signup.php" class="signup-link">Student</a>
                    <span class="pipe-divider">|</span>
                    <a href="admin-signup.php" class="signup-link">Admin</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/login.js"></script> </body>
</html>