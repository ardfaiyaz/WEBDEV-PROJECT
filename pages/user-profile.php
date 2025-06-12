<?php
session_start(); // Start the session at the very beginning

require_once __DIR__ . '/../php/database.php'; // Adjust path if needed

// Check if the user is logged in and if they are a student
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'STUD') {
    header("Location: login.php"); // Adjust path if login.php is not in the same folder
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserEmail = $_SESSION['user_email']; // Email from session

$userProfileData = null; // This will hold all data fetched from the users table

try {
    // Fetch user details from the 'users' table
    // Using the exact column names: `firstname`, `lastname`, `middlename`
    $stmt = $pdo->prepare("SELECT
        email,
        firstname,
        lastname,
        middlename
        FROM users WHERE user_id = :user_id");

    $stmt->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt->execute();
    $userProfileData = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error for debugging, don't show sensitive info to the user in production
    error_log("Error fetching user profile from users table: " . $e->getMessage());
    die("Could not load user profile. Please try again later.");
}

// Initialize display variables with defaults/empty strings
$displayFullName = "User";
$displayFirstName = "";
$displayLastName = "";
$displayMiddleName = ""; // Use 'middlename' from the DB
$displaySchoolEmail = $loggedInUserEmail; // Default from session

// Initialize variables for fields NOT in the users table (as per your provided schema)
// These will remain blank or 'N/A' as they are not fetched from the users table
$displayStudentId = $loggedInUserId;
$displayCourse = "N/A";
$displayDepartment = "N/A";
$displayBirthDate = "";
$displayCivilStatus = "";
$displayGender = "";
$displayReligion = "";
$displayMobileNo = "";
$displayTelephoneNo = "";
$displayPersonalEmail = ""; // If your 'email' column is the main one, this might be redundant
$displayCountryOfBirth = "";
$displayBirthPlace = "";
$displayProvince = "";
$displayCityMunicipality = "";
$displayPostalCode = "";
$displayBarangay = "";
$displayHouseNoSt = "";


// Populate display variables if data is found
if ($userProfileData) {
    $displayFirstName = htmlspecialchars($userProfileData['firstname'] ?? '');
    $displayLastName = htmlspecialchars($userProfileData['lastname'] ?? '');
    $displayMiddleName = htmlspecialchars($userProfileData['middlename'] ?? '');
    $displaySchoolEmail = htmlspecialchars($userProfileData['email'] ?? $loggedInUserEmail);

    // Construct full name
    $displayFullName = trim($displayFirstName . ' ' . ($displayMiddleName ? $displayMiddleName . ' ' : '') . $displayLastName);
    if (empty($displayFullName)) {
        $displayFullName = "User"; // Fallback if name components are empty
    }

    // Since these are not in the users table, they will remain 'N/A' or blank
    // unless you retrieve them from student_info or add them to users table.
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
          <p><strong>Department:</strong> <?php echo $displayDepartment; ?></p>
          <p><strong>School Email:</strong> <?php echo $displaySchoolEmail; ?></p>
        </div>
      </div>

      <div class="about">
        <h2>About Me</h2>

        <h3><i class='bx bx-info-circle'></i> Basic Information</h3>
        <div class="form-row">
          <label>Last Name: <input type="text" /></label>
          <label>Civil Status: 
            <select class="select1" required>
              <option value="" disabled selected hidden>Select</option>
              <option>Single</option>
              <option>Married</option>
            </select>
          </label>
        </div>
        <div class="form-row">
          <label>First Name: <input type="text" /></label>
          <label>Gender: 
            <select class="select1" required>
              <option value="" disabled selected hidden>Select</option>
              <option value="Female">Female</option>
              <option value="Male">Male</option>
            </select>
          </label>
        </div>
        <div class="form-row">
          <label>Middle Name: <input type="text" /></label>
          <label>Religion: <input type="text" /></label>
        </div>
        <div class="form-row">
          <label>Middle Initial: <input type="text" value="L" /></label>
          <label>Mobile No: <input type="text" /></label>
        </div>
        <div class="form-row">
          <label>Birth Date: <input type="date" /></label>
          <label>Telephone No: <input type="text" /></label>
        </div>
        <div class="form-row">
          <label>Country of Birth: <input type="text" /></label>
          <label>Email Address: <input type="email" /></label>
        </div>
        <div class="form-row">
          <label>Birth Place: <input type="text" /></label>
        </div>

        <h3><i class='bx bx-home'></i> Address</h3>
        <div class="form-row">
          <label>Province: <input type="text" /></label>
          <label>City/Municipality: <input type="text" /></label>
        </div>
        <div class="form-row">
          <label>Postal Code: <input type="text" /></label>
          <label>Barangay: <input type="text" /></label>
        </div>
        <div class="form-row">
          <label>House No./St.:<input type="text" /></label>
        </div>
      </div>
    </main>
  </div>

  <script>
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    sidebar.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      mainContent.classList.toggle('shifted');
    });
  </script>
</body>
</html>
