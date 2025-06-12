<?php
session_start(); // Start the session at the very beginning

// Include the database connection file
require_once __DIR__ . '/../php/database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$displayFirstName = "User"; // Default fallback name
$successMessage = ''; // Initialize success message variable

// Fetch user details from the 'users' table using the session user_id
try {
    $stmt = $pdo->prepare("SELECT firstname, lastname FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $displayFirstName = htmlspecialchars($userData['firstname'] ?? 'User');
    }

} catch (PDOException $e) {
    error_log("Error fetching user data for track-clearance page: " . $e->getMessage());
}

// Check for a success message from the previous page
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying it
}

// --- Fetch Clearance Status for the Logged-in User ---
$clearanceStatuses = []; // Array to store fetched statuses for each office
$latestReqId = null;

try {
    // 1. Find the latest clearance request for the user
    $stmtLatestReq = $pdo->prepare("
        SELECT req_id
        FROM clearance_request
        WHERE user_id = :user_id
        ORDER BY req_date DESC, req_id DESC
        LIMIT 1
    ");
    $stmtLatestReq->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmtLatestReq->execute();
    $latestReqId = $stmtLatestReq->fetchColumn();

    if ($latestReqId) {
        // 2. Fetch all clearance statuses for this latest request and user, along with office descriptions
        $stmtStatuses = $pdo->prepare("
            SELECT cs.office_code, o.description AS office_description, cs.status_code, rs.description AS status_description
            FROM clearance_status cs
            JOIN office o ON cs.office_code = o.office_code
            JOIN request_status rs ON cs.status_code = rs.status_code
            WHERE cs.req_id = :req_id AND cs.user_id = :user_id
        ");
        $stmtStatuses->bindParam(':req_id', $latestReqId, PDO::PARAM_INT);
        $stmtStatuses->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
        $stmtStatuses->execute();
        $results = $stmtStatuses->fetchAll(PDO::FETCH_ASSOC);

        // Map the results for easy access by office_code
        foreach ($results as $row) {
            $clearanceStatuses[$row['office_code']] = [
                'status_code' => $row['status_code'],
                'status_description' => $row['status_description'],
                'office_description' => $row['office_description']
            ];
        }
    }

} catch (PDOException $e) {
    error_log("Error fetching clearance statuses: " . $e->getMessage());
    // You might want to display a user-friendly error message on the page here
}

// Define the order and mapping of offices to display on the page
// The key is the office_code from your `office` table
// The value is an array containing the default display name and location
$officeDisplayMap = [
    'DN_PC_PR' => ['DEAN/ PROGRAM CHAIR/ PRINCIPAL', '5th Floor / Faculty Room'],
    'LIB' => ['LIBRARY', '5th Floor'],
    'SDO' => ['STUDENT DISCIPLINE OFFICE', '4th Floor'],
    'ITSO' => ['ITSO', '4th Floor'],
    'SDAO' => ['STUDENT AFFAIRS OFFICE', '4th Floor'],
    'ACC' => ['ACCOUNTING OFFICE', '4th Floor'],
    'GO' => ['GUIDANCE OFFICE', '4th Floor beside Chapel'],
    'REG' => ['REGISTRAR OFFICE', '4th Floor']
];

// Function to get the status class based on status_code
function getStatusClass($statusCode) {
    switch ($statusCode) {
        case 'PEND': return 'pending';
        case 'ON': return 'ongoing';
        case 'ISSUE': return 'issuefound';
        case 'COMP': return 'completed';
        default: return 'pending'; // Default to pending if status is unknown
    }
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
        <?php
        // Display success message if available
        if (!empty($successMessage)) {
            echo '<div class="alert success-alert">';
            echo '<strong>Success!</strong> ' . htmlspecialchars($successMessage);
            echo '</div>';
        }

        if (empty($latestReqId)) {
            echo '<div class="no-request-message">You have no pending clearance requests. Apply for a new clearance to start tracking!</div>';
        }
        ?>
        <h2 class="section-header">SECURE CLEARANCE / SIGNATURES FROM THE OFFICERS INDICATED</h2>

        <div class="cards">
            <?php foreach ($officeDisplayMap as $officeCode => $officeInfo):
                $officeName = $officeInfo[0];
                $officeLocation = $officeInfo[1];
                $currentStatusCode = $clearanceStatuses[$officeCode]['status_code'] ?? 'PEND'; // Default to PEND if not found
                $statusClass = getStatusClass($currentStatusCode);
            ?>
                <button>
                    <div class="card <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($officeName); ?><br/>
                        <span><?php echo htmlspecialchars($officeLocation); ?></span>
                    </div>
                </button>
            <?php endforeach; ?>
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

<script src="../assets/js/track-clearance.js"></script>

</body>
</html>