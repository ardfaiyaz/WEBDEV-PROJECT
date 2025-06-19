<?php
session_start();

require_once __DIR__ . '/../php/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'STUD') {
    header("Location: login.php");
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserEmail = $_SESSION['user_email']; // Assuming this is set during login

$userProfileData = null;
$studentInfoData = null;

try {
    // Fetch user data from 'users' table
    $stmtUsers = $pdo->prepare("
        SELECT
            user_id,
            email,
            firstname,
            lastname,
            middlename
        FROM
            users
        WHERE
            user_id = :user_id
    ");
    $stmtUsers->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmtUsers->execute();
    $userProfileData = $stmtUsers->fetch(PDO::FETCH_ASSOC);

    // Fetch comprehensive student data from 'student_info' table
    $stmtStudentInfo = $pdo->prepare("
        SELECT
            si.student_no,
            si.course,
            si.birthdate,
            -- si.civil_status,        -- Removed as per user's request
            si.gender,
            -- si.religion,            -- Removed as per user's request
            si.mobile_num,
            si.tel_num,
            si.email_address,
            si.nationality,
            si.birthplace,
            si.province,
            si.city_mun,
            -- si.postal_code,         -- Removed as per user's request
            si.brgy,
            si.house_no,
            si.street,
            si.start_yearsem,
            si.end_yearsem,
            si.grad_date
        FROM
            student_info si
        WHERE
            si.user_id = :user_id
    ");
    $stmtStudentInfo->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmtStudentInfo->execute();
    $studentInfoData = $stmtStudentInfo->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching user profile data: " . $e->getMessage());
    die("Could not load user profile. Please try again later.");
}

// Initialize display variables with fetched data or empty strings
// User data (from 'users' table)
$displayFullName = "User";
$displayFirstName = "";
$displayLastName = "";
$displayMiddleName = "";
$displaySchoolEmail = $loggedInUserEmail; // Default to session email

// Student data (from 'student_info' table)
$displayStudentId = "";
$displayCourse = "";
$displayBirthDate = "";
// $displayCivilStatus = ""; // Removed
$displayGender = "";
// $displayReligion = ""; // Removed
$displayMobileNo = "";
$displayTelephoneNo = "";
$displayPersonalEmail = "";
$displayNationality = "";
$displayBirthPlace = "";
$displayProvince = "";
$displayCityMunicipality = "";
$displayPostalCode = ""; // This variable will remain empty string if column is not fetched
$displayBarangay = "";
$displayHouseNo = "";
$displayStreet = "";
$displayStartYearSem = "";
$displayEndYearSem = "";
$displayGradDate = "";


if ($userProfileData) {
    $displayFirstName = htmlspecialchars($userProfileData['firstname'] ?? '');
    $displayLastName = htmlspecialchars($userProfileData['lastname'] ?? '');
    $displayMiddleName = htmlspecialchars($userProfileData['middlename'] ?? '');
    $displaySchoolEmail = htmlspecialchars($userProfileData['email'] ?? $loggedInUserEmail);

    $displayFullName = trim($displayFirstName . ' ' . ($displayMiddleName ? $displayMiddleName . ' ' : '') . $displayLastName);
    if (empty($displayFullName)) {
        $displayFullName = "User";
    }
}

if ($studentInfoData) {
    $displayStudentId = htmlspecialchars($studentInfoData['student_no'] ?? '');
    $displayCourse = htmlspecialchars($studentInfoData['course'] ?? '');
    $displayBirthDate = htmlspecialchars($studentInfoData['birthdate'] ?? '');
    // $displayCivilStatus = htmlspecialchars($studentInfoData['civil_status'] ?? ''); // Removed
    $displayGender = htmlspecialchars($studentInfoData['gender'] ?? '');
    // $displayReligion = htmlspecialchars($studentInfoData['religion'] ?? ''); // Removed
    $displayMobileNo = htmlspecialchars($studentInfoData['mobile_num'] ?? '');
    $displayTelephoneNo = htmlspecialchars($studentInfoData['tel_num'] ?? '');
    $displayPersonalEmail = htmlspecialchars($studentInfoData['email_address'] ?? '');
    $displayNationality = htmlspecialchars($studentInfoData['nationality'] ?? '');
    $displayBirthPlace = htmlspecialchars($studentInfoData['birthplace'] ?? '');
    $displayProvince = htmlspecialchars($studentInfoData['province'] ?? '');
    $displayCityMunicipality = htmlspecialchars($studentInfoData['city_mun'] ?? '');
    // postal_code is intentionally not fetched, so it will remain its initial value (empty string)
    $displayBarangay = htmlspecialchars($studentInfoData['brgy'] ?? '');
    $displayHouseNo = htmlspecialchars($studentInfoData['house_no'] ?? '');
    $displayStreet = htmlspecialchars($studentInfoData['street'] ?? '');
    $displayStartYearSem = htmlspecialchars($studentInfoData['start_yearsem'] ?? '');
    $displayEndYearSem = htmlspecialchars($studentInfoData['end_yearsem'] ?? '');
    $displayGradDate = htmlspecialchars($studentInfoData['grad_date'] ?? '');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/user-profile.css" />
    <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
    <title>My Profile</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
        
        .delete-profile-btn {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .delete-profile-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .delete-profile-btn i {
            font-size: 20px;
        }

        .modal {
            position: fixed;
            z-index: 200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease-out;
        }

        .modal.fade-in {
            opacity: 1;
            display: flex;
        }

        .modal.fade-out {
            opacity: 0;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            position: relative;
            text-align: center;
            transform: scale(0.9);
            opacity: 0;
            transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }

        .modal.fade-in .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal.fade-out .modal-content {
            transform: scale(0.9);
            opacity: 0;
        }

        .modal-content h2 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .modal-content p {
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
            color: #555;
        }

        .close-button {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-button:hover,
        .close-button:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .confirm-delete-btn,
        .cancel-delete-btn {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .confirm-delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        .confirm-delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .cancel-delete-btn {
            background-color: #6c757d;
            color: white;
        }

        .cancel-delete-btn:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
    </style>

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
            <span class="username">Hi, <?php echo $displayFirstName; ?></span>
            <i class='bx bxs-user-circle'></i>
        </div>
    </header>

    <div class="yellow-wrap">
        <div class="yellow">My Profile</div>
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
            <div class="profile-body">
                <div class="profile-image">
                    <img src="../assets/images/default-profile.jpg" alt="Profile Photo">
                </div>
                <div class="profile-details">
                    <p><strong>Name:</strong> <?php echo $displayFirstName . ' ' . $displayMiddleName . ' ' . $displayLastName; ?></p>
                    <p><strong>ID:</strong> <?php echo $displayStudentId; ?></p>
                    <p><strong>Course:</strong> <?php echo $displayCourse; ?></p>
                    <p><strong>School Email:</strong> <?php echo $displaySchoolEmail; ?></p>
                    <button class="delete-profile-btn" id="deleteProfileBtn">
                        <i class='bx bxs-trash'></i> Delete Account
                    </button>
                </div>
            </div>

            <div class="about">
                <h2>About Me</h2>

                <h3><i class='bx bx-info-circle'></i> Basic Information</h3>
                <div class="form-row">
                    <label>Last Name: <input type="text" value="<?php echo $displayLastName; ?>" readonly /></label>
                    <label>Gender:
                        <select class="select1" id="genderSelect" required disabled>
                            <option value="" disabled selected hidden>Select</option>
                            <option value="Female" <?php echo ($displayGender == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Male" <?php echo ($displayGender == 'Male') ? 'selected' : ''; ?>>Male</option>
                        </select>
                    </label>
                </div>
                <div class="form-row">
                    <label>First Name: <input type="text" value="<?php echo $displayFirstName; ?>" readonly /></label>
                    <label>Nationality: <input type="text" value="<?php echo $displayNationality; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Middle Name: <input type="text" value="<?php echo $displayMiddleName; ?>" readonly /></label>
                    <label>Mobile No: <input type="text" value="<?php echo $displayMobileNo; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Birth Date: <input type="date" value="<?php echo $displayBirthDate; ?>" readonly /></label>
                    <label>Telephone No: <input type="text" value="<?php echo $displayTelephoneNo; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Birth Place: <input type="text" value="<?php echo $displayBirthPlace; ?>" readonly /></label>
                    <label>Personal Email: <input type="email" value="<?php echo $displayPersonalEmail; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Start Year/Sem: <input type="text" value="<?php echo $displayStartYearSem; ?>" readonly /></label>
                    <label>End Year/Sem: <input type="text" value="<?php echo $displayEndYearSem; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Graduation Date: <input type="text" value="<?php echo $displayGradDate; ?>" readonly /></label>
                </div>


                <h3><i class='bx bx-home'></i> Address</h3>
                <div class="form-row">
                    <label>House No.: <input type="text" value="<?php echo $displayHouseNo; ?>" readonly /></label>
                    <label>Street: <input type="text" value="<?php echo $displayStreet; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Barangay: <input type="text" value="<?php echo $displayBarangay; ?>" readonly /></label>
                    <label>City/Municipality: <input type="text" value="<?php echo $displayCityMunicipality; ?>" readonly /></label>
                </div>
                <div class="form-row">
                    <label>Province: <input type="text" value="<?php echo $displayProvince; ?>" readonly /></label>
                </div>
            </div>
        </main>
    </div>

    <div id="deleteConfirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Confirm Account Deletion</h2>
            <p>Are you sure you want to delete your account? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button id="confirmDeleteBtn" class="confirm-delete-btn">Delete Account</button>
                <button id="cancelDeleteBtn" class="cancel-delete-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../js/user-profile.js"></script>
</body>
</html>
