<?php
session_start(); // Start the session at the very beginning

// Include the database connection file
require_once '../php/database.php'; // Adjust path if 'database.php' is in a different directory

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); // Trim whitespace
    $password = $_POST['password'];
    $accountType = $_POST['accountType']; // 'student' or 'admin' from your HTML form

    // Map the HTML form's accountType to the database's role_code
    $roleCode = '';
    if ($accountType === 'student') {
    $roleCode = 'STUD'; // Matches the database role_code
    } elseif ($accountType === 'admin') {
        $roleCode = 'ADMIN'; // Matches the database role_code
    } else {
        // Invalid account type selected
        $_SESSION['login_error'] = "Invalid account type selected.";
        header("Location: ../login.php"); // Redirect back to login page
        exit();
    }

    try {
        // 3. Prepare and Execute SQL Query using PDO
        $stmt = $pdo->prepare("SELECT user_id, user_password, role_code FROM users WHERE email = :email AND role_code = :role_code");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role_code', $roleCode, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // 4. Verify Password
            if (password_verify($password, $user['user_password'])) {
                // Password is correct, start a session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['account_type'] = $user['role_code']; // Store 'STUD' or 'ADMIN'

                // Redirect based on role code
                if ($user['role_code'] === 'STUD') {
                    // *** THIS IS THE CHANGE ***
                    header("Location: ../user-profile.html"); // Redirect students to user-profile.html
                } elseif ($user['role_code'] === 'ADMIN') {
                    header("Location: ../admin_dashboard.php"); // Keep admin redirect as is
                }
                exit(); // Always exit after a header redirect
            } else {
                // Incorrect password
                $_SESSION['login_error'] = "Invalid email or password.";
                header("Location: ../login.php"); // Redirect back to login page
                exit();
            }
        } else {
            // User not found for the given email and account type combination
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: ../login.php"); // Redirect back to login page
            exit();
        }

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login. Please try again later.";
        header("Location: ../login.php");
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
      <form id="loginForm"> <div class="login-header">
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
          <a href="admin-signup.html" class="signup-link">Admin</a>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/login.js"></script> </body>
</html>