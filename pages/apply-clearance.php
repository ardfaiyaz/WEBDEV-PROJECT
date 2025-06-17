<?php
session_start(); // Start the session at the very beginning

// Include the database connection file
// Adjust this path based on where your 'database.php' is located relative to this file
require_once __DIR__ . '/../php/database.php';

// Check if the user is logged in
// If not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Adjust path if login.php is not in the same folder
    exit();
}

$user_id = $_SESSION['user_id']; // Get the user_id from the session

// Initialize variables for displaying user info
$displayFirstName = "User"; // Default fallback name

// Initialize variables to store submitted data or pre-fill from student_info
// These will hold the values for the form fields
$firstName = '';
$middleName = '';
$lastName = '';
$houseNo = '';
$street = '';
$baranggay = '';
$cityMun = '';
$province = '';
$telNo = '';
$mobileNo = '';
$email = '';
$sex = '';
$birthdate = '';
$birthplace = '';
$nationality = '';
$studentNo = '';
$course = '';
$semStart = '';
$semEnd = '';
$graduationDate = '';
$enrollmentPurpose = '';
$studentRemarks = '';

// Initialize arrays for errors and messages
$errors = []; // For validation errors
$successMessage = ''; // For success message that persists after redirect
$generalErrorHeading = ''; // To display a heading for errors

$formSubmitted = false; // Flag to indicate if the form was submitted

// Mapping HTML form field names for documents to their display labels and default states
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

// Initialize document checkbox and copies values
foreach ($documentsMap as $doc_code => $doc_label) {
    ${$doc_code . '_checked'} = false; // Boolean for checkbox
    ${$doc_code . '_copies_value'} = ''; // Value for select
}

// --- Check for and display any session-based messages (from a previous redirect) ---
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}
if (isset($_SESSION['error_message'])) {
    // If there's a general error message from a redirect, add it to errors array
    // This allows the error display logic below to handle it.
    $errors[] = $_SESSION['error_message'];
    $generalErrorHeading = "Submission Failed:";
    unset($_SESSION['error_message']); // Clear the message
}


// --- HANDLE FORM SUBMISSION (PHP Processing Logic) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['applyclr_submitbtn'])) {
    $formSubmitted = true; // Set flag as form was submitted

    // --- 1. Sanitize and retrieve input data from POST ---
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

    // Academic Information
    $studentNo = trim($_POST['student_no'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semStart = trim($_POST['sem_start'] ?? '');
    $semEnd = trim($_POST['sem_end'] ?? '');
    $graduationDate = trim($_POST['graduation_date'] ?? '');

    // Other Request Details
    $enrollmentPurpose = trim($_POST['Enrollment_Purpose'] ?? '');
    $studentRemarks = trim($_POST['REMARKS'] ?? '');

    // --- 2. Server-side Validation ---
    if (empty($firstName)) $errors[] = "First name is required.";
    if (empty($lastName)) $errors[] = "Last name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if (empty($mobileNo)) $errors[] = "Mobile number is required.";
    if (empty($birthdate)) $errors[] = "Birthdate is required.";
    if (empty($houseNo)) $errors[] = "House number is required.";
    if (empty($street)) $errors[] = "Street is required.";
    if (empty($baranggay)) $errors[] = "Baranggay is required.";
    if (empty($cityMun)) $errors[] = "City/Municipality is required.";
    if (empty($province)) $errors[] = "Province is required.";
    if (empty($sex)) $errors[] = "Sex is required.";
    if (empty($birthplace)) $errors[] = "Birthplace is required.";
    if (empty($nationality)) $errors[] = "Nationality is required.";
    if (empty($course)) $errors[] = "Course/Major is required.";
    if (empty($semStart)) $errors[] = "Year/Semester Start is required.";
    if (empty($semEnd)) $errors[] = "Year/Semester End is required.";
    if (empty($graduationDate)) $errors[] = "Date of Graduation is required.";
    if (empty($enrollmentPurpose)) $errors[] = "Enrollment Purpose is required.";

    // Check for at least one document requested and its copies
    $documentRequestedCount = 0;
    $documentCopyErrors = [];

    foreach ($documentsMap as $doc_code => $doc_label) {
        // If the checkbox for a document is checked
        if (isset($_POST[$doc_code]) && $_POST[$doc_code] === 'on') {
            ${$doc_code . '_checked'} = true; // Mark checkbox as checked for form re-display
            $copies = (int)($_POST[$doc_code . "_copies"] ?? 0);
            ${$doc_code . '_copies_value'} = $copies; // Keep selected copies value for form re-display

            if ($copies <= 0) {
                $documentCopyErrors[] = "Please select number of copies for " . htmlspecialchars($doc_label) . ".";
            } else {
                $documentRequestedCount++;
            }
        } else {
            // Ensure variables are defined even if not checked, to avoid PHP notices later
            ${$doc_code . '_checked'} = false;
            ${$doc_code . '_copies_value'} = '';
        }
    }

    // Ensure at least one document type is selected
    if ($documentRequestedCount === 0) {
        $errors[] = "Please select at least one document type to request.";
    }

    // Merge document-specific errors with general errors
    if (!empty($documentCopyErrors)) {
        $errors = array_merge($errors, $documentCopyErrors);
    }

    // Consent file validation
    // Only proceed with file validation if no general file upload error occurred or if a file was selected
    if (isset($_FILES['CONSENT_FILE']) && $_FILES['CONSENT_FILE']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['CONSENT_FILE']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the form.',
                UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
            ];
            $error_msg = $upload_errors[$_FILES['CONSENT_FILE']['error']] ?? 'Unknown upload error.';
            $errors[] = "Consent file upload error: " . $error_msg;
        } else {
            // Check file type (MIME type)
            $allowed_mime_types = ['application/pdf', 'image/jpeg', 'image/png'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($_FILES['CONSENT_FILE']['tmp_name']);

            if (!in_array($mime_type, $allowed_mime_types)) {
                $errors[] = "Invalid consent file type. Only PDF, JPG, and PNG are allowed.";
            }
        }
    } else if (!isset($_FILES['CONSENT_FILE']) || $_FILES['CONSENT_FILE']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Consent file is required.";
    }


    // --- 3. If no errors, proceed with database insertion ---
    if (empty($errors)) {
        try {
            // Start a transaction for atomicity
            $pdo->beginTransaction();

            // --- Check and Insert/Update student_info table ---
            // Fetch current student info
            $stmt_check_student_info = $pdo->prepare("SELECT user_id FROM student_info WHERE user_id = ?");
            $stmt_check_student_info->execute([$user_id]);
            $studentInfoExists = $stmt_check_student_info->fetchColumn();

            if (!$studentInfoExists) {
                // Insert if student info doesn't exist
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
            } else {
                // Update if student info exists
                $stmt_update_student_info = $pdo->prepare("
                    UPDATE student_info SET
                        firstname = ?, middlename = ?, lastname = ?, gender = ?, nationality = ?,
                        birthdate = ?, birthplace = ?, tel_num = ?, mobile_num = ?, email_address = ?,
                        house_no = ?, street = ?, brgy = ?, city_mun = ?, province = ?,
                        student_no = ?, course = ?, start_yearsem = ?, end_yearsem = ?, grad_date = ?
                    WHERE user_id = ?
                ");
                $stmt_update_student_info->execute([
                    $firstName, $middleName, $lastName, $sex, $nationality,
                    $birthdate, $birthplace, $telNo, $mobileNo, $email,
                    $houseNo, $street, $baranggay, $cityMun, $province,
                    $studentNo, $course, $semStart, $semEnd, $graduationDate,
                    $user_id
                ]);
            }

            // --- Insert into clearance_request table ---
            $consent_letter_blob = file_get_contents($_FILES['CONSENT_FILE']['tmp_name']);
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
            $stmt_document_request = $pdo->prepare("
                INSERT INTO document_request (req_id, doc_code, doc_copies)
                VALUES (?, ?, ?)
            ");

            foreach ($documentsMap as $doc_code => $doc_label) {
                if (isset($_POST[$doc_code]) && $_POST[$doc_code] === 'on') {
                    $copies = (int)($_POST[$doc_code . "_copies"] ?? 0);
                    if ($copies > 0) { // Only insert if checkbox is checked AND copies > 0
                        $stmt_document_request->execute([$req_id, $doc_code, $copies]);
                    }
                }
            }

            // --- Insert into clearance_status for all relevant offices (ADMIN and SUB_ADMIN) ---
            $stmt_get_offices = $pdo->prepare("SELECT office_code FROM office WHERE office_role IN ('ADMIN', 'SUB_ADMIN')");
            $stmt_get_offices->execute();
            $offices = $stmt_get_offices->fetchAll(PDO::FETCH_COLUMN); // Fetch just the office_codes

            $stmt_initial_status = $pdo->prepare("
                INSERT INTO clearance_status (req_id, user_id, office_code, status_code, office_remarks)
                VALUES (?, ?, ?, ?, ?)
            ");
            $initial_status_code = 'ON'; // Set initial status to 'ON' (Ongoing)
            $initial_remarks = 'Request submitted. Currently under initial review by office.';

            foreach ($offices as $office_code) {
                $stmt_initial_status->execute([$req_id, $user_id, $office_code, $initial_status_code, $initial_remarks]);
            }

            // If all operations are successful, commit the transaction
            $pdo->commit();

            // Set success message for display on the track-clearance page and redirect
            $_SESSION['success_message'] = "Your clearance request has been submitted successfully and is now ON-GOING!";
            header("Location: track-clearance.php"); // Redirect to tracking page
            exit;

        } catch (PDOException $e) {
            // Rollback the transaction on database error
            $pdo->rollBack();
            error_log("Database Error in apply-clearance.php: " . $e->getMessage()); // Log the error for debugging
            $errors[] = "A database error occurred while submitting your request. Please try again. If the problem persists, contact support.";
            $generalErrorHeading = "Submission Failed:";
        } catch (Exception $e) {
            // Catch other application-level exceptions (e.g., validation, file upload)
            $pdo->rollBack();
            error_log("Application Error in apply-clearance.php: " . $e->getMessage()); // Log the error
            $errors[] = "An unexpected error occurred: " . $e->getMessage();
            $generalErrorHeading = "Submission Failed:";
        }
    }
}

// --- INITIAL DATA LOADING FOR FORM (if not submitted or on error) ---
// If the form was NOT submitted (first load) OR if it was submitted but had errors,
// attempt to pre-fill from student_info or user data.
if (!$formSubmitted || !empty($errors)) {
    try {
        // Fetch user data for general display (like "Hi, User")
        $stmt_user = $pdo->prepare("SELECT firstname, lastname, middlename, email FROM users WHERE user_id = :user_id");
        $stmt_user->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_user->execute();
        $userData = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $displayFirstName = htmlspecialchars($userData['firstname'] ?? 'User');
            // If the form wasn't submitted (first load) or if the email field was empty, pre-fill it.
            // This prevents overwriting user input if they typed something invalid.
            if (!$formSubmitted || empty($email)) {
                $email = htmlspecialchars($userData['email'] ?? '');
            }
        }

        // Fetch existing student_info to pre-fill the form fields on initial load
        // Or if the form was submitted with errors, but certain fields weren't touched.
        $stmt_student_info = $pdo->prepare("SELECT * FROM student_info WHERE user_id = :user_id");
        $stmt_student_info->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_student_info->execute();
        $studentInfoData = $stmt_student_info->fetch(PDO::FETCH_ASSOC);

        if ($studentInfoData) {
            // Only pre-fill if the form hasn't been submitted OR if the field was empty in the *current* submission
            $firstName = $formSubmitted && !empty($firstName) ? $firstName : htmlspecialchars($studentInfoData['firstname'] ?? '');
            $middleName = $formSubmitted && !empty($middleName) ? $middleName : htmlspecialchars($studentInfoData['middlename'] ?? '');
            $lastName = $formSubmitted && !empty($lastName) ? $lastName : htmlspecialchars($studentInfoData['lastname'] ?? '');

            $houseNo = $formSubmitted && !empty($houseNo) ? $houseNo : htmlspecialchars($studentInfoData['house_no'] ?? '');
            $street = $formSubmitted && !empty($street) ? $street : htmlspecialchars($studentInfoData['street'] ?? '');
            $baranggay = $formSubmitted && !empty($baranggay) ? $baranggay : htmlspecialchars($studentInfoData['brgy'] ?? '');
            $cityMun = $formSubmitted && !empty($cityMun) ? $cityMun : htmlspecialchars($studentInfoData['city_mun'] ?? '');
            $province = $formSubmitted && !empty($province) ? $province : htmlspecialchars($studentInfoData['province'] ?? '');

            $telNo = $formSubmitted && !empty($telNo) ? $telNo : htmlspecialchars($studentInfoData['tel_num'] ?? '');
            $mobileNo = $formSubmitted && !empty($mobileNo) ? $mobileNo : htmlspecialchars($studentInfoData['mobile_num'] ?? '');
            // Email is handled above
            $sex = $formSubmitted && !empty($sex) ? $sex : htmlspecialchars($studentInfoData['gender'] ?? '');
            $birthdate = $formSubmitted && !empty($birthdate) ? $birthdate : htmlspecialchars($studentInfoData['birthdate'] ?? '');
            $birthplace = $formSubmitted && !empty($birthplace) ? $birthplace : htmlspecialchars($studentInfoData['birthplace'] ?? '');
            $nationality = $formSubmitted && !empty($nationality) ? $nationality : htmlspecialchars($studentInfoData['nationality'] ?? '');

            $studentNo = $formSubmitted && !empty($studentNo) ? $studentNo : htmlspecialchars($studentInfoData['student_no'] ?? '');
            $course = $formSubmitted && !empty($course) ? $course : htmlspecialchars($studentInfoData['course'] ?? '');
            $semStart = $formSubmitted && !empty($semStart) ? $semStart : htmlspecialchars($studentInfoData['start_yearsem'] ?? '');
            $semEnd = $formSubmitted && !empty($semEnd) ? $semEnd : htmlspecialchars($studentInfoData['end_yearsem'] ?? '');
            $graduationDate = $formSubmitted && !empty($graduationDate) ? $graduationDate : htmlspecialchars($studentInfoData['grad_date'] ?? '');
        }
    } catch (PDOException $e) {
        error_log("Error fetching user or student_info data: " . $e->getMessage());
        $errors[] = "Error loading your profile data. Please try again later.";
        $generalErrorHeading = "Data Load Error:";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/apply-clearance.css" />
  <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
  <title>Apply for Clearance</title>
</head>
<body>
  <header class="topbar">
    <a href="index.php" class="logo-link">
      <div class="logo-section">
        <img src="../assets/images/school-logo.png" alt="Logo">
        <span class="school-name">NATIONAL<br/>UNIVERSITY</span>
      </div>
    </a>
    <div class="user-section">
      <i class='bx bxs-bell'></i>
      <span class="username">Hi, <?php echo $displayFirstName; ?> </span>
      <i class='bx bxs-user-circle'></i>
    </div>
  </header>

  <div class="yellow-wrap">
    <div class="yellow">Apply for Clearance</div>
  </div>

  <div class="content-wrapper">
    <aside class="sidebar" id="sidebar">
      <ul class="icon-menu">
        <li><a href="index.php"><i class='bx bxs-home'></i><span class="label">Home</span></a></li>
        <li><a href="user-profile.php"><i class='bx bxs-user'></i><span class="label">Profile</span></a></li>
        <li><a href="track-clearance.php"><i class='bx bxs-file'></i><span class="label">My Clearances</span></a></li>
        <li><a href="../php/logout.php"><i class='bx bxs-log-out'></i><span class="label">Logout</span></a></li>
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <section>
        <p class="notice-text">
          NOTICE: <span>When you click the "Submit Request" button, your request will be immediately sent to the assigned teachers and office staff for review.
            They will check, process, and approve your request based on the provided information. Please make sure all details are complete and accurate before submitting.</span>
        </p>

        <div class="reminder-section">
          <div class="reminder-header">REMINDERS</div>
          <ol>
            <li>Under existing laws, only the owner of the records is allowed to request for documents in connection with his/her school records and claim the requested documents.</li>
            <li>Requests and claiming of documents by representative require authorization letter from the owner. The representative must present his/her two (2) valid ID's and one (1) of the owner. </li>
            <li>Please return this form to the Office of the Registrar after payment at the Accounting Office. Without this form, the request cannot be processed. </li>
            <li>Claim stub will be needed in order to release the document requested.</li>
            <li>Initial payment for Transcript of records are two (2) sheets. Additional sheets if needed are to be paid upon the release date.</li>
            <li>The University policy states that once a student has obtained transfer credentials, they cannot be readmitted. I have read and understood all the conditions and reminders related to this request and agree to comply with them.</li>
          </ol>
        </div>

        <p class="note-text">NOTE: <span>CORRECTLY FILL THE DATA LEGIBLY IN CORRECT LETTERS.</span></p>

        <div class="form-section">
          <form action="apply-clearance.php" method="POST" enctype="multipart/form-data">
            <?php
            // Display success message
            if (!empty($successMessage)) {
                echo '<div class="alert success-alert">';
                echo '<strong>Success!</strong> ' . htmlspecialchars($successMessage);
                echo '</div>';
            }
            // Display validation errors
            if (!empty($errors)) {
                echo '<div class="alert error-alert">'; // Use a more descriptive class
                if (!empty($generalErrorHeading)) {
                    echo '<strong>' . htmlspecialchars($generalErrorHeading) . '</strong>';
                }
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            ?>

            <h3>Personal Information</h3>
            <div class="row1">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder=" Enter First Name" required
                       value="<?php echo htmlspecialchars($firstName); ?>">
              </div>
              <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" placeholder=" Enter Middle Name"
                       value="<?php echo htmlspecialchars($middleName); ?>">
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder=" Enter Last Name" required
                       value="<?php echo htmlspecialchars($lastName); ?>">
              </div>
            </div>

            <div class="row1">
              <div class="form-group">
                <label>House No.</label>
                <input type="text" name="house_no" placeholder=" Enter House No." required
                       value="<?php echo htmlspecialchars($houseNo); ?>">
              </div>
              <div class="form-group">
                <label>Street</label>
                <input type="text" name="street" placeholder="Enter Street" required
                       value="<?php echo htmlspecialchars($street); ?>">
              </div>
              <div class="form-group">
                <label>Baranggay</label>
                <input type="text" name="baranggay" placeholder="Enter Baranggay" required
                       value="<?php echo htmlspecialchars($baranggay); ?>">
              </div>
            </div>

            <div class="row">
              <div class="form-group">
                <label>City/Municipality</label>
                <input type="text" name="city_mun" placeholder="Enter City/Municipality" required
                       value="<?php echo htmlspecialchars($cityMun); ?>">
              </div>
              <div class="form-group">
                <label>Province</label>
                <input type="text" name="province" placeholder="Enter Province" required
                       value="<?php echo htmlspecialchars($province); ?>">
              </div>
            </div>

            <div class="row">
              <div class="form-group"><label>Tel No.</label><input type="text" name="tel_no" placeholder="Enter Tel No."
                      value="<?php echo htmlspecialchars($telNo); ?>"></div>
              <div class="form-group"><label>Mobile No.</label><input type="text" name="mobile_no" placeholder="Enter Mobile No." required
                      value="<?php echo htmlspecialchars($mobileNo); ?>"></div>
              <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="Enter Here" required
                      value="<?php echo htmlspecialchars($email); ?>"></div>
              <div class="form-group">
                <label>Sex</label>
                <select class="select1" name="sex" required>
                  <option value="" disabled <?php echo ($sex == '') ? 'selected' : ''; ?> hidden>Select Sex</option>
                  <option value="Male" <?php echo ($sex == 'Male') ? 'selected' : ''; ?>>Male</option>
                  <option value="Female" <?php echo ($sex == 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
              </div>
              <div class="form-group">
                <label>Birthdate</label>
                <input type="date" name="birthdate" required
                        value="<?php echo htmlspecialchars($birthdate); ?>">
              </div>
              <div class="form-group"><label>Birthplace</label><input type="text" name="birthplace" placeholder="Enter Here" required
                      value="<?php echo htmlspecialchars($birthplace); ?>"></div>
              <div class="form-group"><label>Nationality</label><input type="text" name="nationality" placeholder="Enter Here" required
                      value="<?php echo htmlspecialchars($nationality); ?>"></div>
            </div>


            <h3>Academic Information</h3>
            <div class="row">
              <div class="form-group"><label>Student No.</label><input type="text" name="student_no" placeholder="Enter Here"
                      value="<?php echo htmlspecialchars($studentNo); ?>"></div>

              <div class="form-group">
                <label>Course/Major</label>
                <div class="checkbox-select-group">
                  <select class="select1" name="course" required>
                    <option value="" disabled <?php echo ($course == '') ? 'selected' : ''; ?> hidden>Select</option>
                    <option value="BSIT-MWA" <?php echo ($course == 'BSIT-MWA') ? 'selected' : ''; ?>>BSIT-MWA</option>
                    <option value="BSCS-ML" <?php echo ($course == 'BSCS-ML') ? 'selected' : ''; ?>>BSCS-ML</option>
                    <option value="BSCPE" <?php echo ($course == 'BSCPE') ? 'selected' : ''; ?>>BSCPE</option>
                  </select>
                </div>
              </div>

              <div class="form-group"><label>Year/Semester Start</label><input type="text" name="sem_start" placeholder="Enter Here"
                      value="<?php echo htmlspecialchars($semStart); ?>"></div>
              <div class="form-group"><label>Year/Semester End</label><input type="text" name="sem_end" placeholder="Enter Here"
                      value="<?php echo htmlspecialchars($semEnd); ?>"></div>
              <div class="form-group"><label>Date of Graduation</label><input type="text" name="graduation_date" placeholder="Enter Here"
                      value="<?php echo htmlspecialchars($graduationDate); ?>"></div>
            </div>

            <h3>Type of Document</h3>
            <h2>PLEASE CHECK AND SELECT THE NO. OF COPIES</h2>
            <div class="row">
              <?php foreach ($documentsMap as $doc_code => $doc_label):
                $isChecked = ${$doc_code . '_checked'} ?? false; // Use initial value or what was posted
                $copiesValue = ${$doc_code . '_copies_value'} ?? ''; // Use initial value or what was posted
              ?>
              <div class="form-group">
                  <label><?php echo htmlspecialchars($doc_label); ?></label>
                  <div class="checkbox-select-group">
                      <input type="checkbox" name="<?php echo $doc_code; ?>"
                             <?php echo $isChecked ? 'checked' : ''; ?>>
                      <select class="select1" name="<?php echo $doc_code; ?>_copies" required <?php echo !$isChecked ? 'disabled' : ''; ?>>
                          <option value="" disabled <?php echo ($copiesValue == '') ? 'selected' : ''; ?> hidden>Select</option>
                          <option value="1" <?php echo ($copiesValue == '1') ? 'selected' : ''; ?>>1</option>
                          <option value="2" <?php echo ($copiesValue == '2') ? 'selected' : ''; ?>>2</option>
                          <option value="3" <?php echo ($copiesValue == '3') ? 'selected' : ''; ?>>3</option>
                      </select>
                  </div>
              </div>
              <?php endforeach; ?>
            </div>

            <h2>FOR ENROLLMENT PURPOSES</h2>

            <div class="form-group">
              <label>Enrollment Purpose</label>
              <div class="checkbox-select-group" style="padding-bottom: 20px;">
                  <select class="select1" name="Enrollment_Purpose" required>
                    <option value="" disabled <?php echo ($enrollmentPurpose == '') ? 'selected' : ''; ?> hidden>Select</option>
                    <option value="Abroad" <?php echo ($enrollmentPurpose == 'Abroad') ? 'selected' : ''; ?>>Abroad</option>
                    <option value="Local" <?php echo ($enrollmentPurpose == 'Local') ? 'selected' : ''; ?>>Local</option>
                  </select>
              </div>
            </div>

            <div class="form-group">
              <label>Other Remarks</label>
              <textarea rows="4" name="REMARKS" placeholder="Enter Here"><?php echo htmlspecialchars($studentRemarks); ?></textarea>
            </div>

            <div class="form-group">
              <label>Upload Your Consent Here</label>
              <input type="file" name="CONSENT_FILE" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>

            <button class="submit-btn" type="submit" name="applyclr_submitbtn">SUBMIT</button>
          </form>
        </div>
      </section>
    </main>
  </div>

<script src="../js/apply-clearance.js"></script>
</body>
</html>