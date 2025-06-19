<?php
session_start();

require_once '../php/database.php';

// Define the office mappings with associated office_code and role_code
$officeMappings = [
    'registrar-office'             => ['office_code' => 'REG',       'role_code' => 'ADMIN'],
    'dean-program-chair-principal' => ['office_code' => 'DN_PC_PR', 'role_code' => 'SUB_ADMIN'],
    'student-discipline-office'    => ['office_code' => 'SDO',       'role_code' => 'SUB_ADMIN'],
    'student-affairs-office'       => ['office_code' => 'SDAO',      'role_code' => 'SUB_ADMIN'],
    'guidance-office'              => ['office_code' => 'GO',        'role_code' => 'SUB_ADMIN'],
    'IT-Support'                         => ['office_code' => 'ITSO',      'role_code' => 'SUB_ADMIN'],
    'library-office'               => ['office_code' => 'LIB',       'role_code' => 'SUB_ADMIN'],
    'accounting-office'            => ['office_code' => 'ACC',       'role_code' => 'SUB_ADMIN'],
];

// Initialize variables to store submitted data and errors
$firstName = '';
$middleName = '';
$lastName = '';
$email = '';
$employeeId = '';
$office = '';      // Variable for admin signup (holds the key from $officeMappings)
$errors = [];
$generalErrorHeading = '';

// Variable for success message (will be displayed inline)
$successMessage = '';
$redirectNow = false;

// Define the required email domain for admins
$requiredAdminEmailDomain = '@admin.nu-dasma.edu.ph';

// --- HANDLE FORM SUBMISSION (PHP Processing Logic) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {

    // 1. Sanitize and retrieve input data from POST
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? ''); // This will be the full email including the domain
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
        $errors[] = "Invalid school email format. It should look like 'username" . htmlspecialchars($requiredAdminEmailDomain) . "'.";
    } elseif (!str_ends_with($email, $requiredAdminEmailDomain)) {
        $errors[] = "School Email must end with " . htmlspecialchars($requiredAdminEmailDomain) . ".";
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
                    echo '<div id="notification-container">';
                    echo '<div class="notification notification-danger show">';
                    echo '<div class="notification-header">';
                    echo '<i class="fas fa-times-circle"></i>';
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
                    echo '</div>';
                } elseif (!empty($successMessage)) {
                    echo '<div id="notification-container">';
                    echo '<div class="notification notification-success show">';
                    echo '<div class="notification-header">';
                    echo '<i class="fas fa-check-circle"></i>';
                    echo '<strong>Success!</strong>';
                    echo '<button class="close-btn">&times;</button>';
                    echo '</div>';
                    echo '<div class="notification-body">';
                    echo '<p>' . htmlspecialchars($successMessage) . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>

                <div class="form-wrapper">
                    <form class="signup-form" action="admin-signup.php" method="POST">
                        <div class="form-grid">
                            <div class="form-row-grid">
                                <div class="form-item">
                                    <input type="text" id="firstName" name="firstName" placeholder=" " required
                                        value="<?php echo htmlspecialchars($firstName); ?>" />
                                    <label for="firstName">First Name</label>
                                </div>
                                <div class="form-item">
                                    <input type="text" id="middleName" name="middleName" placeholder=" "
                                        value="<?php echo htmlspecialchars($middleName); ?>" />
                                    <label for="middleName">Middle Name</label>
                                </div>
                                <div class="form-item">
                                    <input type="text" id="lastName" name="lastName" placeholder=" " required
                                        value="<?php echo htmlspecialchars($lastName); ?>" />
                                    <label for="lastName">Last Name</label>
                                </div>
                            </div>

                            <div class="form-item">
                                <input type="email" id="email" name="email"
                                       placeholder="username@admin.nu-dasma.edu.ph" required
                                       value="<?php
                                           echo empty($email) ? '' : htmlspecialchars($email); // Changed to empty string for proper placeholder behavior
                                       ?>" />
                                <label for="email">School Email</label>
                            </div>
                            <div class="form-item">
                                <input type="number" id="employeeId" name="employeeId" placeholder=" " required value="<?php echo htmlspecialchars($employeeId); ?>" />
                                <label for="employeeId">Employee ID</label>
                            </div>

                            <div class="form-item">
                                <select id="office" name="office" required>
                                    <option value="" disabled <?php echo empty($office) ? 'selected' : ''; ?>>Select Office</option>
                                    <?php
                                    foreach ($officeMappings as $key => $values) {
                                        $displayOfficeName = ucwords(str_replace('-', ' ', $key));

                                        echo '<option value="' . htmlspecialchars($key) . '"';
                                        if ($office === $key) {
                                            echo ' selected';
                                        }
                                        echo '>' . htmlspecialchars($displayOfficeName) . '</option>';
                                    }
                                    ?>
                                </select>
                                <label for="office">Office</label>
                            </div>

                            <div class="form-row-grid">
                                <div class="form-item">
                                    <div class="password-input-container">
                                        <input type="password" id="password" name="password" placeholder=" " required />
                                        <label for="password">Password</label>
                                        <span class="password-toggle-icon"><i class="fas fa-eye"></i></span>
                                    </div>
                                </div>
                                <div class="form-item">
                                    <div class="password-input-container">
                                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder=" " required />
                                        <label for="confirmPassword">Confirm Password</label>
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

    <div id="notification-container">
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Select all necessary elements once ---
            const passwordToggles = document.querySelectorAll('.password-toggle-icon');
            const signupForm = document.querySelector('.signup-form');
            const header = document.querySelector('.header');
            const formItems = document.querySelectorAll('.form-item');
            const submitButton = document.getElementById('adminSubmitButton');
            const notificationContainer = document.getElementById('notification-container');

            // --- Function to display notification ---
            function showNotification(message, type, heading = '') {
                if (notificationContainer) {
                    notificationContainer.innerHTML = ''; // Clear existing notifications
                }

                const notification = document.createElement('div');
                notification.classList.add('notification', `notification-${type}`);
                notification.innerHTML = `
                    <div class="notification-header">
                        ${type === 'danger' ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-check-circle"></i>'}
                        <strong>${heading || (type === 'danger' ? 'Error!' : 'Success!')}</strong>
                        <button class="close-btn">&times;</button>
                    </div>
                    <div class="notification-body">${message}</div>
                `;

                if (notificationContainer) {
                    notificationContainer.appendChild(notification);
                    void notification.offsetWidth; // Force reflow
                    notification.classList.add('show');

                    notification.querySelector('.close-btn').addEventListener('click', function() {
                        hideNotification(notification);
                    });

                    setTimeout(() => {
                        if (notification.parentNode) {
                            hideNotification(notification);
                        }
                    }, type === 'success' ? 3000 : 5000);
                } else {
                    console.error("Notification container not found. Cannot display notification.");
                    alert(`${heading || (type === 'danger' ? 'Error!' : 'Success!')}\n\n${message}`);
                }
            }

            // --- Function to hide notification ---
            function hideNotification(notificationElement) {
                notificationElement.classList.remove('show');
                notificationElement.classList.add('hide');
                notificationElement.addEventListener('animationend', () => {
                    notificationElement.remove();
                }, { once: true });
            }

            // --- Password Toggle Animation ---
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const passwordInput = this.closest('.password-input-container').querySelector('input');

                    if (passwordInput) {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);

                        this.querySelector('i').classList.toggle('fa-eye');
                        this.querySelector('i').classList.toggle('fa-eye-slash');

                        this.classList.add('icon-pop');
                        setTimeout(() => {
                            this.classList.remove('icon-pop');
                        }, 200);
                    }
                });
            });

            // --- Client-Side Form Submission Validation (Password Match) ---
            if (signupForm) {
                signupForm.addEventListener('submit', function(event) {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;

                    if (password !== confirmPassword) {
                        event.preventDefault();
                        showNotification('Your passwords do not match. Please try again.', 'danger', 'Password Mismatch');
                    }
                });
            }

            // --- Header & Logo Fade-In ---
            if (header) {
                setTimeout(() => {
                    header.style.transition = 'opacity 1s ease-out';
                    header.style.opacity = '1';
                }, 100);
            }

            // --- Form Fields Slide & Fade-In (Staggered) ---
            formItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                    item.style.opacity = 1;
                    item.style.transform = 'translateY(0)';
                }, 300 + (index * 100));
            });

            // --- Submit Button Click Effect ---
            if (submitButton) {
                submitButton.addEventListener('mousedown', () => {
                    submitButton.classList.add('button-pressed');
                });
                submitButton.addEventListener('mouseup', () => {
                    submitButton.classList.remove('button-pressed');
                });
                submitButton.addEventListener('mouseleave', () => {
                    submitButton.classList.remove('button-pressed');
                });
            }

            // Initial check for PHP-generated notifications on page load
            if (notificationContainer) {
                const initialNotification = notificationContainer.querySelector('.notification.show');
                if (initialNotification) {
                    initialNotification.querySelector('.close-btn').addEventListener('click', function() {
                        hideNotification(initialNotification);
                    });

                    const isSuccess = initialNotification.classList.contains('notification-success');
                    setTimeout(() => {
                        if (initialNotification.parentNode) {
                            hideNotification(initialNotification);
                        }
                    }, isSuccess ? 3000 : 5000);
                }
            }
            
            // Handle initial state of floating labels for pre-filled values (from PHP)
            formItems.forEach(item => {
                const input = item.querySelector('input, select');
                const label = item.querySelector('label');
                if (input && label) {
                    // For text inputs and email inputs
                    if (input.tagName === 'INPUT' && input.value.length > 0) {
                        label.style.top = '0px';
                        label.style.fontSize = '12px';
                        label.style.color = '#29227c';
                        label.style.transform = 'translateY(-50%) scale(0.9)';
                        label.style.backgroundColor = 'white';
                        label.style.padding = '0 5px';
                    }
                    // For select elements
                    else if (input.tagName === 'SELECT' && input.value !== "") {
                        label.style.top = '0px';
                        label.style.fontSize = '12px';
                        label.style.color = '#29227c';
                        label.style.transform = 'translateY(-50%) scale(0.9)';
                        label.style.backgroundColor = 'white';
                        label.style.padding = '0 5px';
                    }
                }
            });

        });

        // Define an object to hold our PHP data for client-side JS
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
</body>
</html>