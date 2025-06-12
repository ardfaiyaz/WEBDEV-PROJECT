<<<<<<< HEAD:pages/track-clearance.html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/track-clearance.css" />
  <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
  <title>Track My Clearance</title>
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
      <span class="username">Hi, User</span>
      <i class='bx bxs-user-circle'></i>
    </div>
  </header>

  <div class="yellow-wrap">
    <div class="yellow">Track your Clearance</div>
  </div>

  <div class="content-wrapper">
    <aside class="sidebar" id="sidebar">
      <ul class="icon-menu">
        <li><a href="index.html"><i class='bx bxs-home'></i><span class="label">Home</span></a></li>
        <li><a href="user-profile.html"><i class='bx bxs-user'></i><span class="label">Profile</span></a></li>
        <li><a href="track-clearance.html"><i class='bx bxs-file'></i><span class="label">My Clearances</span></a></li>
        <li><a href="#"><i class='bx bxs-log-out'></i><span class="label">Logout</span></a></li>
      </ul>
    </aside>

    <main class="main-content" id="mainContent">
      <section>
        <h2 class="section-header">SECURE CLEARANCE / SIGNATURES FROM THE OFFICERS INDICATED</h2>

        <div class="cards">
          <button><div class="card pending">DEAN/ PROGRAM CHAIR/ PRINCIPAL<br/><span>5th Floor / Faculty Room</span></div></button>
          <button><div class="card pending">LIBRARY<br/><span>5th Floor</span></div></button>
          <button><div class="card pending">STUDENT DISCIPLINE OFFICE<br/><span>4th Floor</span></div></button>
          <button><div class="card pending">ITSO<br/><span>4th Floor</span></div></button>
          <button><div class="card pending">STUDENT AFFAIRS OFFICE<br/><span>4th Floor</span></div></button>
          <button><div class="card pending">ACCOUNTING OFFICE<br/><span>4th Floor</span></div></button>
          <button><div class="card pending">GUIDANCE OFFICE<br/><span>4th Floor beside Chapel</span></div></button>
          <button><div class="card pending">REGISTRAR OFFICE<br/><span>4th Floor</span></div></button>
        </div>

        <div class="legend">
          <span class="legend-item pending">- Pending</span>
          <span class="legend-item ongoing">- On-Going</span>
          <span class="legend-item issuefound">- Issue Found</span>
          <span class="legend-item completed">- Completed</span>
          
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
=======
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
  <link rel="stylesheet" href="../assets/css/track-clearance.css" />
  <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
  <title>Track My Clearance</title>
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
    <div class="yellow">Track your Clearance</div>
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
        <h2 class="section-header">SECURE CLEARANCE / SIGNATURES FROM THE OFFICERS INDICATED</h2>

        <div class="cards">
          <button><div class="card pending">DEAN/ PROGRAM CHAIR/ PRINCIPAL<br/><span>5th Floor / Faculty Room</span></div></button>
          <button><div class="card completed">LIBRARY<br/><span>5th Floor</span></div></button>
          <button><div class="card ongoing">STUDENT DISCIPLINE OFFICE<br/><span>4th Floor</span></div></button>
          <button><div class="card completed">ITSO<br/><span>4th Floor</span></div></button>
          <button><div class="card ongoing">STUDENT AFFAIRS OFFICE<br/><span>4th Floor</span></div></button>
          <button><div class="card issuefound">ACCOUNTING OFFICE<br/><span>4th Floor</span></div></button>
          <button><div class="card ongoing">GUIDANCE OFFICE<br/><span>4th Floor beside Chapel</span></div></button>
          <button><div class="card pending">REGISTRAR OFFICE<br/><span>4th Floor</span></div></button>
        </div>

        <div class="legend">
          <span class="legend-item pending">- Pending</span>
          <span class="legend-item ongoing">- On-Going</span>
          <span class="legend-item issuefound">- Issue Found</span>
          <span class="legend-item completed">- Completed</span>
          
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
>>>>>>> a78c22506c1d428ead20adc2b65444a086a29dfc:pages/track-clearance.php
