<?php
session_start();

require_once '../php/database.php'; // Ensure this path is correct for your admin structure

// Define the office mappings with associated office_code and role_code
$officeMappings = [
    'registrar-office'             => ['office_code' => 'REG',       'role_code' => 'ADMIN'],
    'dean-program-chair-principal' => ['office_code' => 'DN_PC_PR', 'role_code' => 'SUB_ADMIN'],
    'student-discipline-office'    => ['office_code' => 'SDO',       'role_code' => 'SUB_ADMIN'],
    'student-affairs-office'       => ['office_code' => 'SDAO',      'role_code' => 'SUB_ADMIN'],
    'guidance-office'              => ['office_code' => 'GO',        'role_code' => 'SUB_ADMIN'],
    'itso'                         => ['office_code' => 'ITSO',      'role_code' => 'SUB_ADMIN'],
    'library-office'               => ['office_code' => 'LIB',       'role_code' => 'SUB_ADMIN'],
    'accounting-office'            => ['office_code' => 'ACC',       'role_code' => 'SUB_ADMIN'],
];

// Initialize variables to store submitted data and errors
$firstName = '';
$middleName = '';
$lastName = '';
$email = '';
$employeeId = '';
$office = '';      // New variable for admin signup (holds the key from $officeMappings)
$errors = [];      // For validation errors that keep the user on the same page
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
    $employeeId = trim($_POST['employeeId'] ?? '');
    $office = trim($_POST['office'] ?? ''); // This will be the key from $officeMappings
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

    // Validate Employee ID as a positive integer
    if (empty($employeeId)) {
        $errors[] = "Employee ID is required.";
    } elseif (!ctype_digit($employeeId) || (int)$employeeId <= 0) {
        $errors[] = "Employee ID must be a positive number (digits only).";
    }

    // Validate Office selection using the mappings
    if (empty($office)) {
        $errors[] = "Office selection is required.";
    } elseif (!array_key_exists($office, $officeMappings)) {
        // Ensure the selected office is one of the valid keys in our mapping
        $errors[] = "Invalid office selected.";
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

    // 3. Check for existing email or employee ID in database (if no immediate errors)
    if (empty($errors)) {
        try {
            // Check if email already exists in users table
            $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetchColumn() > 0) {
                $errors[] = "Email address is already registered.";
            }

            // Check if employee ID (which will be user_id) already exists in users table
            $stmt_check_user_id = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
            $stmt_check_user_id->execute([(int)$employeeId]);
            if ($stmt_check_user_id->fetchColumn() > 0) {
                $errors[] = "Employee ID is already registered as a user ID.";
            }

            // OPTIONAL: Check if employee ID is associated with a student_info record
            // This prevents an employee ID from being the same as a student ID
            $stmt_check_student_no = $pdo->prepare("SELECT COUNT(*) FROM student_info WHERE student_no = ?");
            $stmt_check_student_no->execute([(int)$employeeId]);
            if ($stmt_check_student_no->fetchColumn() > 0) {
                $errors[] = "Employee ID is already associated with an existing student profile. Please use a different ID.";
            }

        } catch (PDOException $e) {
            error_log("Database Error (admin signup check): " . $e->getMessage());
            $errors[] = "A database error occurred during validation. Please try again.";
        }
    }

    // 4. If still no errors, proceed with insertion
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Get the office_code and role_code from the mapping
            $selectedOfficeData = $officeMappings[$office];
            $roleCode = $selectedOfficeData['role_code'];
            $officeCode = $selectedOfficeData['office_code'];

            $stmt_insert_user = $pdo->prepare("
                INSERT INTO users (user_id, role_code, firstname, lastname, middlename, email, user_password, office_code)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_insert_user->execute([
                (int)$employeeId, // Cast to int for insertion
                $roleCode,        // Dynamically set based on office selection
                $firstName,
                $lastName,
                $middleName,
                $email,
                $hashedPassword,
                $officeCode       // Dynamically set based on office selection
            ]);

            $pdo->commit();

            // Set success message to be displayed on this page
            $successMessage = "Admin account created successfully! Redirecting to login page...";
            $redirectNow = true; // Set flag to trigger redirection

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database Error (admin signup insert): " . $e->getMessage());
            if ($e->getCode() == 23000) { // Duplicate entry error
                $errors[] = "The Employee ID or Email is already registered. Please use a different one or contact support.";
            } else {
                $errors[] = "Admin account creation failed due to a database error. Please try again. If the problem persists, contact support.";
            }
            $generalErrorHeading = "Signup Failed:";
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Application Error (admin signup): " . $e->getMessage());
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
    <title>Admin Signup</title>
    <link rel="stylesheet" href="../assets/css/admin-signup.css" />
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

                <?php
                // Display validation errors or success message using the new notification classes
                if (!empty($errors)) {
                    // Start the notification container outside the notification div
                    echo '<div id="notification-container">';
                    echo '<div class="notification notification-danger show">'; // Ensure 'show' class is present to trigger animation
                    echo '<div class="notification-header">';
                    echo '<i class="fas fa-times-circle"></i>'; // Error icon
                    echo '<strong>' . htmlspecialchars($generalErrorHeading) . '</strong>';
                    echo '<button class="close-btn">&times;</button>';
                    echo '</div>';
                    echo '<div class="notification-body">';
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>'; // Close the notification container
                } elseif (!empty($successMessage)) { // Display success message
                    // Start the notification container outside the notification div
                    echo '<div id="notification-container">';
                    echo '<div class="notification notification-success show">'; // Ensure 'show' class is present to trigger animation
                    echo '<div class="notification-header">';
                    echo '<i class="fas fa-check-circle"></i>'; // Success icon
                    echo '<strong>Success!</strong>';
                    echo '<button class="close-btn">&times;</button>';
                    echo '</div>';
                    echo '<div class="notification-body">';
                    echo '<p>' . htmlspecialchars($successMessage) . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>'; // Close the notification container
                }
                ?>

                <div class="form-wrapper">
                    <form class="signup-form" action="admin-signup.php" method="POST">
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
                                <div class="form-item">
                                    <input type="text" id="lastName" name="lastName" placeholder=" " required
                                        value="<?php echo htmlspecialchars($lastName); ?>" />
                                    <label for="lastName">Last Name:</label>
                                </div>
                            </div>

                            <div class="form-item">
                                <input type="email" id="email" name="email" placeholder=" " required
                                        value="<?php echo htmlspecialchars($email); ?>" />
                                <label for="email">School Email:</label>
                            </div>
                            <div class="form-item">
                                <input type="number" id="employeeId" name="employeeId" placeholder=" " required
                                        value="<?php echo htmlspecialchars($employeeId); ?>" />
                                <label for="employeeId">Employee ID:</label>
                            </div>

                            <div class="form-item">
                                <label for="office">Office:</label>
                                <select id="office" name="office" required>
                                    <option value="" disabled selected>Select Office</option>
                                    <?php
                                    // Dynamically populate options from the $officeMappings array
                                    foreach ($officeMappings as $key => $values) {
                                        // $key is 'registrar-office', 'dean-program-chair-principal', etc.
                                        // $values['office_code'] is 'REG', 'DN_PC_PR', etc.
                                        // $values['role_code'] is 'ADMIN', 'SUB_ADMIN', etc.

                                        // Convert the key to a more readable format for the display text
                                        $displayOfficeName = ucwords(str_replace('-', ' ', $key));

                                        echo '<option value="' . htmlspecialchars($key) . '"';
                                        // Retain selected option on form submission if validation fails
                                        if ($office === $key) {
                                            echo ' selected';
                                        }
                                        echo '>' . htmlspecialchars($displayOfficeName) . '</option>';
                                    }
                                    ?>
                                </select>
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
                            <button type="submit" class="submit-btn" id="adminSubmitButton" name="signup_submit">Sign Up Admin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/admin-signup.js"></script>
    <style>
        #notification-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            left: auto;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        }

        .notification {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 15px 20px;
            color: #333;
            font-size: 14px;
            line-height: 1.4;
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.5s ease-out, opacity 0.5s ease-out;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification.hide {
            opacity: 0;
            transform: translateX(100%);
        }

        .notification-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            padding-right: 25px;
        }

        .notification-header i {
            margin-right: 8px;
            font-size: 18px;
        }

        .notification-body ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
            list-style-type: disc;
        }

        .notification-body ul li {
            margin-bottom: 2px;
        }

        .notification-danger {
            border-left: 5px solid #dc3545;
            color: #721c24;
            background-color: #f8d7da;
        }
        .notification-danger .notification-header i {
            color: #dc3545;
        }
        .notification-danger strong {
            color: #dc3545;
        }

        .notification-success {
            border-left: 5px solid #28a745;
            color: #155724;
            background-color: #d4edda;
        }
        .notification-success .notification-header i {
            color: #28a745;
        }
        .notification-success strong {
            color: #28a745;
        }

        .notification .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            line-height: 1;
            color: #aaa;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .notification .close-btn:hover {
            color: #666;
        }
    </style>
</body>
</html>