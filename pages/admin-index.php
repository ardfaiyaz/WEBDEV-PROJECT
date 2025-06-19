<?php
session_start(); // IMPORTANT: Start the session at the very beginning of the file

$loggedInAdminName = $_SESSION['username'] ?? 'Admin';
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
        <div class="logo-section">
            <a href="admin-index.php" class="logo-link">
                <img src="../assets/icons/NU_shield.svg.png" alt="School Logo">
                <span class="school-name">NATIONAL<br/>UNIVERSITY</span>
            </a>
        </div>

        <nav class="top-navbar">
            <ul class="navbar-menu">
                <li class="menu-item"><a href="admin-index.php"><i class='bx bxs-home icon-sidebar'></i> Home</a></li>
                <li class="menu-item"><a href="clearance-request.php"><i class='bx bxs-file-export icon-sidebar'></i> Requests</a></li>
                <li class="menu-item"><a href="about-us.html"><i class='bx bxs-file icon-sidebar'></i> About Us</a></li>
                <li class="menu-item"><a href="../php/logout.php"><i class='bx bxs-log-out icon-sidebar'></i> Logout</a></li>
            </ul>
        </nav>

        <div class="header-right-section">
            <div class="user-section">
                <span class="username">Hi, <?php echo htmlspecialchars($loggedInAdminName); ?></span>
                <i class='bx bxs-user-circle'></i>
            </div>
        </div>
    </header>

  <div class="content-wrapper">
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
