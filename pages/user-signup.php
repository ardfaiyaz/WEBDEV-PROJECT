<?php
session_start();

require_once '../php/database.php';

// Initialize variables to store submitted data and errors
$firstName = '';
$middleName = '';
$lastName = '';
$email = '';
$studentId = '';
$errors = []; // For validation errors that keep the user on the same page
$generalErrorHeading = ''; // For the heading of the validation errors

// Variable for success message (will be displayed inline)
$successMessage = ''; 
$redirectNow = false; // Flag to trigger immediate redirect after success

// --- HANDLE FORM SUBMISSION (PHP Processing Logic) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {

    // 1. Sanitize and retrieve input data from POST
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $studentId = trim($_POST['studentId'] ?? ''); 
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // 2. Server-side Validation
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
    }
    
    // Validate student ID as a positive integer
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

    // 3. Check for existing email or student ID in database (if no immediate errors)
    if (empty($errors)) {
        try {
            // Check if email already exists in users table
            $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetchColumn() > 0) {
                $errors[] = "Email address is already registered.";
            }

            // Check if student ID (which will be user_id) already exists in users table
            $stmt_check_user_id = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
            $stmt_check_user_id->execute([(int)$studentId]); 
            if ($stmt_check_user_id->fetchColumn() > 0) {
                $errors[] = "Student ID is already registered as a user ID.";
            }
            
            // Check if student ID is associated with another student_info record (if applicable)
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

    // 4. If still no errors, proceed with insertion
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $roleCode = 'STUD';
            $officeCode = null; // Set to null for students

            $stmt_insert_user = $pdo->prepare("
                INSERT INTO users (user_id, role_code, firstname, lastname, middlename, email, user_password, office_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert_user->execute([
                (int)$studentId, // Cast to int for insertion
                $roleCode,
                $firstName,
                $lastName,
                $middleName,
                $email,
                $hashedPassword,
                $officeCode
            ]);

            $pdo->commit();

            // Set success message to be displayed on this page
            $successMessage = "Account created successfully! Redirecting to login page..."; 
            $redirectNow = true; // Set flag to trigger redirection

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
    <meta http-equiv="refresh" content="3;url=../pages/login.html"> 
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

                <?php
                // Display validation errors or success message
                if (!empty($errors)) { 
                    echo '<div class="alert alert-danger">';
                    if (!empty($generalErrorHeading)) {
                        echo '<strong>' . htmlspecialchars($generalErrorHeading) . '</strong>';
                    }
                    echo '<ul>';
                    foreach ($errors as $error) { 
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                } elseif (!empty($successMessage)) { // Display success message
                    echo '<div class="alert alert-success">'; // Use a success class for green background
                    echo '<strong>Success!</strong> ' . htmlspecialchars($successMessage);
                    echo '</div>';
                }
                ?>

                <div class="form-wrapper">
                    <form class="signup-form" action="user-signup.php" method="POST">
                        <div class="form-grid">
                            <div class="form-row-grid">
                                <div class="form-item">
                                    <label for="firstName">First Name:</label>
                                    <input type="text" id="firstName" name="firstName" placeholder="Enter First Name" required 
                                            value="<?php echo htmlspecialchars($firstName); ?>" />
                                </div>
                                <div class="form-item">
                                    <label for="middleName">Middle Name:</label>
                                    <input type="text" id="middleName" name="middleName" placeholder="Enter Middle Name" 
                                            value="<?php echo htmlspecialchars($middleName); ?>" />
                                </div>
                                <div class="form-item">
                                    <label for="lastName">Last Name:</label>
                                    <input type="text" id="lastName" name="lastName" placeholder="Enter Last Name" required 
                                            value="<?php echo htmlspecialchars($lastName); ?>" />
                                </div>
                            </div>

                            <div class="form-item">
                                <label for="email">School Email:</label>
                                <input type="email" id="email" name="email" placeholder="Enter School Email" required 
                                            value="<?php echo htmlspecialchars($email); ?>" />
                            </div>
                            <div class="form-item">
                                <label for="studentId">Student ID:</label>
                                <input type="number" id="studentId" name="studentId" placeholder="Enter Student ID (Ex.: 2023123456)" required 
                                            value="<?php echo htmlspecialchars($studentId); ?>" />
                            </div>

                            <div class="form-row-grid">
                                <div class="form-item">
                                    <label for="password">Password:</label>
                                    <div class="password-input-container">
                                        <input type="password" id="password" name="password" placeholder="Enter Password" required />
                                        <span class="password-toggle-icon"><i class="fas fa-eye"></i></span>
                                    </div>
                                </div>
                                <div class="form-item">
                                    <label for="confirmPassword">Confirm Password:</label>
                                    <div class="password-input-container">
                                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required />
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

    <script src="../assets/js/user-signup.js"></script>
    </body>
</html>