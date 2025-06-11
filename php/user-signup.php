<?php
// php/user-signup.php
require_once 'database.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic Information
    $firstName = $_POST['firstName'] ?? '';
    $middleName = $_POST['middleName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $birthplace = $_POST['birthplace'] ?? '';
    $nationality = $_POST['nationality'] ?? '';

    // Address Information
    $houseNo = $_POST['houseNo'] ?? null; // Nullable
    $streetBrgy = $_POST['streetBrgy'] ?? '';
    $cityMun = $_POST['cityMun'] ?? '';
    $province = $_POST['province'] ?? '';
    $zipCode = $_POST['zipCode'] ?? null; // Nullable
    $mobileNum = $_POST['mobileNum'] ?? '';

    // Account Information
    $studentNo = $_POST['studentNo'] ?? '';
    $schoolEmail = $_POST['schoolEmail'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Input validation (basic - add more robust validation for production)
    if (empty($firstName) || empty($lastName) || empty($gender) || empty($birthdate) || empty($birthplace) || empty($nationality) ||
        empty($streetBrgy) || empty($cityMun) || empty($province) || empty($mobileNum) ||
        empty($studentNo) || empty($schoolEmail) || empty($password) || empty($confirmPassword)) {
        echo "Please fill in all required fields.";
        exit;
    }

    if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
        exit;
    }

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Insert into `users` table
        // Assumes default role for student is 'STUD'
        $stmt = $pdo->prepare("INSERT INTO users (role_code, firstname, lastname, middlename, email, user_password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['STUD', $firstName, $lastName, $middleName, $schoolEmail, $hashedPassword]);
        $user_id = $pdo->lastInsertId(); // Get the last inserted user_id from the users table

        // 2. Insert into `student_info` table
        // Note: `tel_num`, `course`, `start_yearsem`, `end_yearsem`, `grad_date` are not collected in the provided signup form.
        // They are set to NULL or empty string as per the table schema.
        $stmt = $pdo->prepare("INSERT INTO student_info (user_id, firstname, middlename, lastname, gender, nationality, birthdate, birthplace, tel_num, mobile_num, email_address, house_no, street, brgy, city_mun, province, student_no, course, start_yearsem, end_yearsem, grad_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Using streetBrgy for both street and brgy for simplicity, as only one input is provided
        $stmt->execute([
            $user_id,
            $firstName,
            $middleName,
            $lastName,
            $gender,
            $nationality,
            $birthdate,
            $birthplace,
            null, // tel_num - not collected in form
            $mobileNum,
            $schoolEmail,
            $houseNo,
            $streetBrgy,
            $streetBrgy, // brgy - using street/barangay input
            $cityMun,
            $province,
            $studentNo,
            null, // course - not collected in form
            null, // start_yearsem - not collected in form
            null, // end_yearsem - not collected in form
            null  // grad_date - not collected in form
        ]);

        $pdo->commit();
        echo "Registration successful! You can now log in.";
        // Optionally redirect to login page
        // header("Location: ../pages/login.html"); // Updated redirect path
        // exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Check for duplicate entry error (e.g., email or student ID unique constraint violation)
        if ($e->getCode() == '23000') {
            echo "Registration failed: Email or School ID already exists.";
        } else {
            echo "Registration failed: " . $e->getMessage();
        }
    }
}
?>