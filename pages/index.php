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
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
  <title>Home</title>
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

  <div class="content-wrapper">
    <aside class="sidebar" id="sidebar">
      <ul class="icon-menu">
        <li><a href="index.php"><i class='bx bxs-home'></i><span class="label">Home</span></a></li>
        <li><a href="user-profile.php"><i class='bx bxs-user'></i><span class="label">Profile</span></a></li>
        <li><a href="track-clearance.php"><i class='bx bxs-file'></i><span class="label">My Clearances</span></a></li>
        <li><a href="login.php"><i class='bx bxs-log-out'></i><span class="label">Logout</span></a></li>
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <section>
        <h2>STUDENT CLEARANCE SYSTEM</h2>
        <p class="description">Effortlessly manage, track, and submit your student clearance requirements.</p>
        <div class="cards">
          <a href="track-clearance.php" class="card">
            <i class='bx bx-line-chart icon'></i>
            <h3>Track my Clearance</h3>
            <p>Visual tracker showing your current clearance status, pending steps, and completed stages.</p>
          </a>
          
          <a href="apply-clearance.php" class="card">
            <i class='bx bx-file icon'></i>
            <h3>Apply for Clearance</h3>
            <p>Start a new clearance application by filling out and submitting the official form.</p>
          </a>
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
  </script>

</body>
</html>
