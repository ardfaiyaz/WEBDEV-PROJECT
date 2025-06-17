<?php
session_start(); // IMPORTANT: Start the session at the very beginning of the file

// Check if the user is logged in. If not, redirect to the login page.
// We use 'user_id' from the session variables set in login.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
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
  <link rel="stylesheet" href="../assets/css/admin-style.css"/>
  <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
  <title>Home</title>
</head>
<body>
  <header class="topbar">
    <a href="index.html" class="logo-link">
      <div class="logo-section">
        <img src="../assets/images/school-logo.png" alt="Logo">
        <span class="school-name">NATIONAL<br/>UNIVERSITY</span>
      </div>
    </a>
    <div class="user-section">
      <i class='bx bxs-bell'></i>
      <span class="username">Hi, Admin</span>
      <i class='bx bxs-user-circle'></i>
    </div>
  </header>

  <div class="content-wrapper">
    <aside class="sidebar" id="sidebar">
      <ul class="icon-menu">
        <li><a href="#"><i class='bx bxs-home'></i><span class="label">Home</span></a></li>
        <li><a href="#"><i class='bx bxs-user'></i><span class="label">Profile</span></a></li>
        <li><a href="#"><i class='bx bxs-bell'></i><span class="label">Announcements</span></a></li>
        <li><a href="#"><i class='bx bxs-log-out'></i><span class="label">Logout</span></a></li>
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <section>
        <h2>STUDENT CLEARANCE SYSTEM</h2>
        <p class="description">Effortlessly manage and track your student clearance requests.</p>
        <div class="cards">

          <a href="clearance-request.php" class="card">
            <i class='bx bx-list-ul icon'></i> <!-- Changed icon class here -->
            <h3>View Requests</h3>
            <p>Browse all incoming student requests for documents, letters, and clearance applications.</p>
          </a>


          <a href="admin-request-stubs.php" class="card">
            <i class='bx bx-task icon'></i> <!-- Changed icon class here -->
            <h3>Process Requests</h3>
            <p>Approve, decline, or forward student requests with notes and file attachments.</p>
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
