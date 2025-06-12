<?php
session_start(); // Start the session to access $_SESSION variables

// Include your database connection file
require_once 'database.php'; // Adjust path if db_connect.php is in a different directory

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or show an error
    header("Location: login.php"); // Assuming you have a login page
    exit;
}

$user_id = $_SESSION['user_id']; // Get the user_id from the session

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['applyclr_submitbtn'])) {

    // --- 1. Sanitize Personal Information ---
    // All fields now treated as strings due to VARCHAR database types
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    
    $houseNo = trim($_POST['house_no'] ?? ''); 
    $street = trim($_POST['street'] ?? '');
    $baranggay = trim($_POST['baranggay'] ?? '');
    $cityMun = trim($_POST['city_mun'] ?? '');
    $province = trim($_POST['province'] ?? '');
    
    $telNo = trim($_POST['tel_no'] ?? '');    
    $mobileNo = trim($_POST['mobile_no'] ?? '');
    
    $email = trim($_POST['email'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $birthplace = trim($_POST['birthplace'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');

    // --- 2. Sanitize Academic Information ---
    $studentNo = trim($_POST['student_no'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semStart = trim($_POST['sem_start'] ?? '');
    $semEnd = trim($_POST['sem_end'] ?? '');
    $graduationDate = trim($_POST['graduation_date'] ?? '');

    // --- 3. Other Request Details ---
    $enrollmentPurpose = trim($_POST['Enrollment_Purpose'] ?? '');
    $studentRemarks = trim($_POST['REMARKS'] ?? '');


    // Check for at least one document requested
    $documentRequestedCount = 0;
    $documentCopyErrors = [];
    $documentsMap = [
        "AUTH_LOC" => "Authentication - Local",
        "AUTH_ABR" => "Authentication - Abroad",
        "GD_MOR" => "Certificate of Good Moral",
        "CERT" => "Certification",
        "COR_DESC" => "Course Description",
        "DIPL" => "Diploma",
        "FORM137" => "Form 137",
        "HON_DIS" => "Honorable Dismissal",
        "TOR" => "Transcript of Records"
    ];
    
    foreach ($documentsMap as $doc_code => $doc_label) {
        if (isset($_POST[$doc_code]) && $_POST[$doc_code] === 'on') {
            $copies = (int)($_POST[$doc_code . "_copies"] ?? 0);
            if ($copies <= 0) {
                $documentCopyErrors[] = "Please select number of copies for " . htmlspecialchars($doc_label) . ".";
            } else {
                $documentRequestedCount++;
            }
        }
    }

    // Merge document-specific errors with general errors
    if (!empty($documentCopyErrors)) {
        $errors = array_merge($errors, $documentCopyErrors);
    }
    
    if (empty($errors)) {
        try {
            // Start a transaction for atomicity
            $pdo->beginTransaction();

            // --- Check and Insert into student_info table (if not exists) ---
            $stmt_check_student_info = $pdo->prepare("SELECT user_id FROM student_info WHERE user_id = ?");
            $stmt_check_student_info->execute([$user_id]);
            $studentInfoExists = $stmt_check_student_info->fetchColumn();

            if (!$studentInfoExists) {
                $stmt_insert_student_info = $pdo->prepare("
                    INSERT INTO student_info (
                        user_id, firstname, middlename, lastname, gender, nationality,
                        birthdate, birthplace, tel_num, mobile_num, email_address,
                        house_no, street, brgy, city_mun, province,
                        student_no, course, start_yearsem, end_yearsem, grad_date
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                $stmt_insert_student_info->execute([
                    $user_id, $firstName, $middleName, $lastName, $sex, $nationality,
                    $birthdate, $birthplace, $telNo, $mobileNo, $email, 
                    $houseNo, $street, $baranggay, $cityMun, $province, 
                    $studentNo, $course, $semStart, $semEnd, $graduationDate
                ]);
            }

            // --- Insert into clearance_request table ---
            $consent_letter_blob = null;
            if (isset($_FILES['CONSENT_FILE']) && $_FILES['CONSENT_FILE']['error'] === UPLOAD_ERR_OK) {
                $consent_letter_blob = file_get_contents($_FILES['CONSENT_FILE']['tmp_name']);
            } else if (isset($_FILES['CONSENT_FILE']) && $_FILES['CONSENT_FILE']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
                ];
                $error_msg = $upload_errors[$_FILES['CONSENT_FILE']['error']] ?? 'Unknown upload error.';
                throw new Exception("Consent file upload error: " . $error_msg);
            } else {
                throw new Exception("Consent file is required.");
            }

            $claimStubStatus = 0; // Set to 0 (false) initially, meaning not yet issued.

            $stmt_clearance_request = $pdo->prepare("
                INSERT INTO clearance_request (user_id, req_date, claim_stub, enrollment_purpose, student_remarks, consent_letter)
                VALUES (?, CURDATE(), ?, ?, ?, ?)
            ");
            $stmt_clearance_request->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt_clearance_request->bindParam(2, $claimStubStatus, PDO::PARAM_INT);
            $stmt_clearance_request->bindParam(3, $enrollmentPurpose);
            $stmt_clearance_request->bindParam(4, $studentRemarks);
            $stmt_clearance_request->bindParam(5, $consent_letter_blob, PDO::PARAM_LOB);
            $stmt_clearance_request->execute();

            $req_id = $pdo->lastInsertId(); // Get the ID of the newly inserted clearance request

            // --- Insert into document_request table ---
            // Reusing the documentsMap from validation
            $stmt_document_request = $pdo->prepare("
                INSERT INTO document_request (req_id, doc_code, doc_copies)
                VALUES (?, ?, ?)
            ");

            foreach ($documentsMap as $doc_code => $doc_label) {
                if (isset($_POST[$doc_code]) && $_POST[$doc_code] === 'on') {
                    $copies = (int)($_POST[$doc_code . "_copies"] ?? 0);
                    if ($copies > 0) {
                        $stmt_document_request->execute([$req_id, $doc_code, $copies]);
                    }
                }
            }
            
            // --- Insert into clearance_status for all relevant offices (ADMIN and SUB_ADMIN) ---
            // First, fetch all office codes that are involved in clearance (Admin and Sub-Admin roles)
            $stmt_get_offices = $pdo->prepare("SELECT office_code FROM office WHERE office_role IN ('ADMIN', 'SUB_ADMIN')");
            $stmt_get_offices->execute();
            $offices = $stmt_get_offices->fetchAll(PDO::FETCH_COLUMN); // Fetch just the office_codes

            $stmt_initial_status = $pdo->prepare("
                INSERT INTO clearance_status (req_id, user_id, office_code, status_code, office_remarks)
                VALUES (?, ?, ?, ?, ?)
            ");
            $initial_status_code = 'PEND'; // Always 'PEND' for initial status
            $initial_remarks = 'Request submitted for initial review.';

            foreach ($offices as $office_code) {
                $stmt_initial_status->execute([$req_id, $user_id, $office_code, $initial_status_code, $initial_remarks]);
            }


            // If all operations are successful, commit the transaction
            $pdo->commit();

            // Success! Redirect to a confirmation page or track clearance page
            $_SESSION['message'] = "Your clearance request has been submitted successfully!";
            header("Location: track-clearance.html");
            exit;

        } catch (PDOException $e) {
            // Rollback the transaction on database error
            $pdo->rollBack();
            error_log("Database Error in apply_clearance.php: " . $e->getMessage()); // Log the error for debugging
            $_SESSION['error_message'] = "A database error occurred while submitting your request. Please try again. If the problem persists, contact support.";
            header("Location: apply-clearance.html");
            exit;
        } catch (Exception $e) {
            // Catch other application-level exceptions (e.g., validation, file upload)
            $pdo->rollBack();
            error_log("Application Error in apply_clearance.php: " . $e->getMessage()); // Log the error
            $_SESSION['error_message'] = "An unexpected error occurred: " . $e->getMessage();
            header("Location: apply-clearance.html");
            exit;
        }
    } else {
        // If there are initial validation errors (before database interaction)
        $_SESSION['error_message'] = "Please correct the following issues:<br>" . implode("<br>", $errors);
        header("Location: apply-clearance.html");
        exit;
    }
} else {
    // If not a POST request, redirect to the form or homepage
    header("Location: index.html");
    exit;
}
?>