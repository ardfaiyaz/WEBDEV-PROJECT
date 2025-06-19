<?php
session_start();

require_once '../php/database.php';

$firstName = '';
$middleName = '';
$lastName = '';
$email = '';
$studentId = '';
$errors = [];
$generalErrorHeading = '';

$successMessage = '';
$redirectNow = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {

    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $studentId = trim($_POST['studentId'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    if (empty($email)) {
        $errors[] = "School Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid school email format.";
    } elseif (!str_ends_with($email, "@students.nu-dasma.edu.ph")) {
        $errors[] = "School Email must end with '@students.nu-dasma.edu.ph'.";
    }

    if (empty($studentId)) {
        $errors[] = "Student ID is required.";
    } elseif (!ctype_digit($studentId) || (int)$studentId <= 0) {
        $errors[] = "Student ID must be a positive number (digits only).";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if (empty($errors)) {
        try {
            $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetchColumn() > 0) {
                $errors[] = "Email address is already registered.";
            }

            $stmt_check_user_id = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
            $stmt_check_user_id->execute([(int)$studentId]);
            if ($stmt_check_user_id->fetchColumn() > 0) {
                $errors[] = "Student ID is already registered as a user ID.";
            }

            $stmt_check_student_no = $pdo->prepare("SELECT COUNT(*) FROM student_info WHERE student_no = ?");
            $stmt_check_student_no->execute([(int)$studentId]);
            if ($stmt_check_student_no->fetchColumn() > 0) {
                $errors[] = "Student ID is already associated with another student profile.";
            }

        } catch (PDOException $e) {
            error_log("Database Error (signup check): " . $e->getMessage());
            $errors[] = "A database error occurred during validation. Please try again.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $roleCode = 'STUD';
            $officeCode = null;

            $stmt_insert_user = $pdo->prepare("
                INSERT INTO users (user_id, role_code, firstname, lastname, middlename, email, user_password, office_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert_user->execute([
                (int)$studentId,
                $roleCode,
                $firstName,
                $lastName,
                $middleName,
                $email,
                $hashedPassword,
                $officeCode
            ]);

            $pdo->commit();

            $successMessage = "Account created successfully! Redirecting to login page...";
            $redirectNow = true;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database Error (signup insert): " . $e->getMessage());
            if ($e->getCode() == 23000) {
                $errors[] = "The Student ID or Email is already registered. Please use a different one or contact support.";
            } else {
                $errors[] = "Account creation failed due to a database error. Please try again. If the problem persists, contact support.";
            }
            $generalErrorHeading = "Signup Failed:";
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Application Error (signup): " . $e->getMessage());
            $errors[] = "An unexpected error occurred: " . $e->getMessage();
            $generalErrorHeading = "Signup Failed:";
        }
    } else {
        $generalErrorHeading = "Please correct the following issues:";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Student Signup</title>
    <link rel="stylesheet" href="../assets/css/user-signup.css" />
    <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <?php if ($redirectNow): ?>
    <meta http-equiv="refresh" content="3;url=../pages/login.php">
    <?php endif; ?>

</head>
<body>
    <div class="signup-wrapper">
        <div class="signup-container">
            <div class="signup-box">
                <div class="header">
                    <img src="../assets/icons/NU_shield.svg.png" alt="NU Logo" class="logo">
                    <h1>NU STUDENT CLEARANCE SYSTEM</h1>
                </div>

                <div class="form-wrapper">
                    <form class="signup-form" action="user-signup.php" method="POST">
                        <div class="form-grid">
                            <div class="form-row-grid">
                                <div class="form-item">
                                    <input type="text" id="firstName" name="firstName" placeholder=" " required
                                                 value="<?php echo htmlspecialchars($firstName); ?>" />
                                    <label for="firstName">First Name:</label>
                                </div>
                                <div class="form-item">
                                    <input type="text" id="middleName" name="middleName" placeholder=" "
                                                 value="<?php echo htmlspecialchars($middleName); ?>" />
                                    <label for="middleName">Middle Name:</label>
                                </div>
                            </div>

                            <div class="form-item">
                                <input type="text" id="lastName" name="lastName" placeholder=" " required
                                                value="<?php echo htmlspecialchars($lastName); ?>" />
                                <label for="lastName">Last Name:</label>
                            </div>

                            <div class="form-item">
                                <input type="email" id="email" name="email" placeholder=" " required
                                                 value="<?php echo htmlspecialchars($email); ?>" />
                                <label for="email">School Email:</label>
                            </div>
                            <div class="form-item">
                                <input type="number" id="studentId" name="studentId" placeholder=" " required
                                                 value="<?php echo htmlspecialchars($studentId); ?>" />
                                <label for="studentId">Student ID:</label>
                            </div>

                            <div class="form-row-grid">
                                <div class="form-item">
                                    <div class="password-input-container">
                                        <input type="password" id="password" name="password" placeholder=" " required />
                                        <label for="password">Password:</label>
                                        <span class="password-toggle-icon"><i class="fas fa-eye"></i></span>
                                    </div>
                                </div>
                                <div class="form-item">
                                    <div class="password-input-container">
                                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder=" " required />
                                        <label for="confirmPassword">Confirm Password:</label>
                                        <span class="password-toggle-icon"><i class="fas fa-eye"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="submit-wrapper">
                            <button type="submit" class="submit-btn" id="userSubmitButton" name="signup_submit">Sign Up</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="notification-container">
        </div>

    <script>
        window.phpData = {
            hasErrors: <?php echo json_encode(!empty($errors)); ?>,
            errorMessageHtml: <?php echo json_encode(!empty($errors) ? (
                (!empty($generalErrorHeading) ? '<strong>' . htmlspecialchars($generalErrorHeading) . '</strong>' : '') .
                '<ul>' . implode('', array_map(function($e) { return '<li>' . htmlspecialchars($e) . '</li>'; }, $errors)) . '</ul>'
            ) : null); ?>,
            successMessage: <?php echo json_encode($successMessage); ?>,
            redirectNow: <?php echo json_encode($redirectNow); ?>
        };
    </script>
    <script src="../js/user-signup.js"></script>
</body>
</html>