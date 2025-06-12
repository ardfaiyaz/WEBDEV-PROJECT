<?php
session_start(); // IMPORTANT: Start the session at the very beginning of the file

// Check if the user is logged in. If not, redirect to the login page.
// We use 'user_id' from the session variables set in login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Adjust path if your login page is elsewhere
    exit();
}


$displayFirstName = $_SESSION['firstname'] ?? 'User'; // Default to 'User' if not set
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
    <a href="index.php" class="logo-link"> <div class="logo-section">
        <img src="../assets/images/school-logo.png" alt="Logo">
        <span class="school-name">NATIONAL<br/>UNIVERSITY</span>
      </div>
    </a>
    <div class="user-section">
      <i class='bx bxs-bell'></i>
      <span class="username">Hi, <?= htmlspecialchars($displayFirstName); ?> </span> 
      <i class='bx bxs-user-circle'></i>
    </div>
  </header>

  <div class="content-wrapper">
    <aside class="sidebar" id="sidebar">
      <ul class="icon-menu">
        <li><a href="index.php"><i class='bx bxs-home'></i><span class="label">Home</span></a></li> <li><a href="user-profile.html"><i class='bx bxs-user'></i><span class="label">Profile</span></a></li>
        <li><a href="track-clearance.html"><i class='bx bxs-file'></i><span class="label">My Clearances</span></a></li>
        <li><a href="../php/logout.php"><i class='bx bxs-log-out'></i><span class="label">Logout</span></a></li> </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <section>
        <h2>STUDENT CLEARANCE SYSTEM</h2>
        <p class="description">Effortlessly manage, track, and submit your student clearance requirements.</p>
        <div class="cards">
          <a href="track-clearance.html" class="card">
            <i class='bx bx-line-chart icon'></i>
            <h3>Track my Clearance</h3>
            <p>Visual tracker showing your current clearance status, pending steps, and completed stages.</p>
          </a>
          
          <a href="apply-clearance.html" class="card">
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