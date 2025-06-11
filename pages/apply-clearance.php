<?php
session_start(); // Start the session at the very beginning

// Include the database connection file
// Adjust this path based on where your 'database.php' is located relative to 'index.php'
require_once __DIR__ . '/../php/database.php';

// Check if the user is logged in
// If not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Adjust path if login.php is not in the same folder
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$displayFullName = "User"; // Default fallback name

// Fetch user details from the 'users' table using the session user_id
try {
    $stmt = $pdo->prepare("SELECT firstname, lastname, middlename, email FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $displayFirstName = htmlspecialchars($userData['firstname'] ?? 'User');
    }

} catch (PDOException $e) {
    error_log("Error fetching user data for homepage: " . $e->getMessage());
    // In a real application, you might show a generic error or redirect
    // For now, let's just use the default "User" name.
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
        <li><a href=""><i class='bx bxs-log-out'></i><span class="label">Logout</span></a></li>
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
          <form action="../php/apply_clearance.php" method="POST" enctype="multipart/form-data">
            
            <h3>Personal Information</h3>
            <div class="row1">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder=" Enter First Name" required>
              </div>
              <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" placeholder=" Enter Middle Name" required>
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder=" Enter Last Name" required>
              </div>
            </div>

            <div class="row1">
              <div class="form-group">
                <label>House No.</label>
                <input type="text" name="house_no" placeholder=" Enter House No." required>
              </div>
              <div class="form-group">
                <label>Street</label>
                <input type="text" name="street" placeholder="Enter Street" required>
              </div>
              <div class="form-group">
                <label>Baranggay</label>
                <input type="text" name="baranggay" placeholder="Enter Baranggay" required>
              </div>
            </div>

            <div class="row">
              <div class="form-group">
                <label>City/Municipality</label>
                <input type="text" name="city_mun" placeholder="Enter City/Municipality" required>
              </div>
              <div class="form-group">
                <label>Province</label>
                <input type="text" name="province" placeholder="Enter Province" required>
              </div>
            </div>
            
            <div class="row">
              <div class="form-group"><label>Tel No.</label><input type="text" name="tel_no" placeholder="Enter Tel No." required></div>
              <div class="form-group"><label>Mobile No.</label><input type="text" name="mobile_no" placeholder="Enter Mobile No." required></div>
              <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="Enter Here" required></div>
              <div class="form-group">
                <label>Sex</label>
                <select class="select1" name="sex" required>
                  <option value="" disabled selected hidden>Select Sex</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="form-group">
                <label>Birthdate</label>
                <input type="date" name="birthdate" required>
              </div>
              <div class="form-group"><label>Birthplace</label><input type="text" name="birthplace" placeholder="Enter Here" required></div>
              <div class="form-group"><label>Nationality</label><input type="text" name="nationality" placeholder="Enter Here" required></div>
            </div>


            
            <h3>Academic Information</h3>
            <div class="row">
              <div class="form-group"><label>Student No.</label><input type="text" name="student_no" placeholder="Enter Here"></div>

              <div class="form-group">
                <label>Course/Major</label>
                <div class="checkbox-select-group">
                  <select class="select1" name="course" required>
                    <option value="" disabled selected hidden>Select</option>
                    <option value="BSIT-MWA">BSIT-MWA</option>
                    <option value="BSCS-ML">BSCS-ML</option>
                    <option value="BSCPE">BSCPE</option>
                  </select>
                </div>
              </div>

              <div class="form-group"><label>Year/Semester Start</label><input type="text" name="sem_start" placeholder="Enter Here"></div>
              <div class="form-group"><label>Year/Semester End</label><input type="text" name="sem_end" placeholder="Enter Here"></div>
              <div class="form-group"><label>Date of Graduation</label><input type="text" name="graduation_date" placeholder="Enter Here"></div>
            </div>

          <h3>Type of Document</h3>
          <h2>PLEASE CHECK AND SELECT THE NO. OF COPIES</h2>
          <div class="row">
            <div class="form-group">
                <label>Authentication - Local</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="AUTH_LOC">
                <select class="select1" name="AUTH_LOC_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Authentication - Abroad</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="AUTH_ABR">
                <select class="select1" name="AUTH_ABR_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Certificate of Good Moral</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="GD_MOR">
                <select class="select1" name="GD_MOR_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Certification</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="CERT">
                <select class="select1" name="CERT_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Course Description</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="COR_DESC">
                <select class="select1" name="COR_DESC_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Diploma</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="DIPL">
                <select class="select1" name="DIPL_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Form 137</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="FORM137">
                <select class="select1" name="FORM137_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Honorable Dismissal</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="HON_DIS">
                <select class="select1" name="HON_DIS_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
            <div class="form-group">
                <label>Transcript of Records</label>
              <div class="checkbox-select-group">
                <input type="checkbox" name="TOR">
                <select class="select1" name="TOR_copies" required>
                  <option value="" disabled selected hidden>Select</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                </select>
              </div>
            </div>
          </div>

        <h2>FOR ENROLLMENT PURPOSES</h2>

        <div class="form-group">
          <label>Enrollment Purpose</label>
          <div class="checkbox-select-group" style="padding-bottom: 20px;">
              <select class="select1" name="Enrollment_Purpose" required>
                <option value="" disabled selected hidden>Select</option>
                <option value="Abroad">Abroad</option>
                <option value="Local">Local</option>
              </select>
        </div>  


          
          <div class="form-group">
            <label>Other Remarks</label>
            <textarea rows="4" name="REMARKS" placeholder="Enter Here"></textarea>
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

<script>
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    sidebar.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      mainContent.classList.toggle('shifted');
    });

    // Script to disable select if checkbox is not checked
    document.addEventListener('DOMContentLoaded', () => {
      const checkboxSelectGroups = document.querySelectorAll('.checkbox-select-group');

      checkboxSelectGroups.forEach(group => {
        const checkbox = group.querySelector('input[type="checkbox"]');
        const select = group.querySelector('select');

        if (checkbox && select) {
          // Initial state on page load
          select.disabled = !checkbox.checked;

          // Add event listener to checkbox
          checkbox.addEventListener('change', () => {
            select.disabled = !checkbox.checked;
            // Optional: Reset select value when disabled
            if (select.disabled) {
              select.value = ""; // Or set to the default disabled option
            }
          });
        }
      });
    });
  </script>
</body>
</html>
