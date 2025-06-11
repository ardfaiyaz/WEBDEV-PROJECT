<?php
session_start();

require_once 'database.php'; // Adjust path if necessary

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {

    // 1. Sanitize and retrieve input data
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Student ID will now be the user_id, ensure it's an integer
    $studentId = (int)($_POST['studentId'] ?? 0); 
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // 2. Server-side Validation
    $errors = [];

    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Validate student ID as a positive integer (since it will be user_id)
    if ($studentId <= 0) { 
        $errors[] = "Student ID must be a positive number.";
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

    // 3. Check for existing email or student ID (now also the user_id)
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
            $stmt_check_user_id->execute([$studentId]);
            if ($stmt_check_user_id->fetchColumn() > 0) {
                 $errors[] = "Student ID is already registered as a user ID.";
            }

            // Also check if student ID already exists in student_info (as student_no)
            // This handles cases where student_info might have entries not yet linked to users,
            // or if a different user_id was previously used but had this student_no.
            $stmt_check_student_no = $pdo->prepare("SELECT COUNT(*) FROM student_info WHERE student_no = ?");
            $stmt_check_student_no->execute([$studentId]);
            if ($stmt_check_student_no->fetchColumn() > 0) {
                 $errors[] = "Student ID is already associated with another student profile.";
            }

        } catch (PDOException $e) {
            error_log("Database Error (signup check): " . $e->getMessage());
            $errors[] = "A database error occurred during validation. Please try again.";
        }
    }

    // 4. If no errors, proceed with insertion
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into 'users' table
            // user_id is explicitly set to studentId
            // role_code is 'STUD'
            // office_code is NULL
            $roleCode = 'STUD';
            $officeCode = null; // Set to null for students

            $stmt_insert_user = $pdo->prepare("
                INSERT INTO users (user_id, role_code, firstname, lastname, middlename, email, user_password, office_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert_user->execute([
                $studentId,       // This will be the user_id
                $roleCode,
                $firstName,
                $lastName,
                $middleName,
                $email,
                $hashedPassword,
                $officeCode       // This will be NULL
            ]);

            // No need for $pdo->lastInsertId() here, as user_id is manually set to $studentId

            // Insert student_no into student_info table
            // user_id in student_info will be the same as the user_id inserted into users table ($studentId)
            // student_no in student_info will also be $studentId
            $stmt_insert_student_info = $pdo->prepare("
                INSERT INTO student_info (user_id, student_no, firstname, lastname, middlename, email_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert_student_info->execute([
                $studentId,       // user_id for student_info
                $studentId,       // student_no for student_info
                $firstName,
                $lastName,
                $middleName,
                $email
            ]);

            $pdo->commit();

            $_SESSION['message'] = "Account created successfully! You can now log in using your Student ID and Password.";
            header("Location: ../pages/login.html");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database Error (signup insert): " . $e->getMessage());
            // More specific error message for duplicate user_id if needed
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation (e.g., duplicate PK)
                $_SESSION['error_message'] = "The Student ID is already registered. Please use a different one or contact support.";
            } else {
                $_SESSION['error_message'] = "Account creation failed due to a database error. Please try again. If the problem persists, contact support.";
            }
            header("Location: ../pages/user-signup.html");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Application Error (signup): " . $e->getMessage());
            $_SESSION['error_message'] = "An unexpected error occurred: " . $e->getMessage();
            header("Location: ../pages/user-signup.html");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Please correct the following issues:<br>" . implode("<br>", $errors);
        header("Location: ../pages/user-signup.html");
        exit;
    }

} else {
    header("Location: ../pages/user-signup.html"); // Assuming your signup HTML is user-signup.html
    exit;
}
?>