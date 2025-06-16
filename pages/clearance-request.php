<?php
session_start();
// Include database connection (adjust path as needed, assuming database.php is in ../php)
require_once __DIR__ . '/../php/database.php';

// Check if the user is logged in and is an admin/office user
// You might want more specific role checks here, e.g., $_SESSION['account_type'] == 'ADMIN' || $_SESSION['account_type'] == 'SUB_ADMIN'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Fetch current user's name and office code from session if available,
// otherwise, the JS will fetch it via AJAX. This provides initial load data.
$currentUsername = htmlspecialchars($_SESSION['firstname'] ?? '');
$currentUserOfficeCode = htmlspecialchars($_SESSION['office_code'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Requests</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/clearance-request.css">
    <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
</head>
<body>
    <header class="topbar">
        <div class="logo-section">
            <a href="index.html" class="logo-link">
                <img src="../assets/images/school-logo.png" alt="School Logo">
                <span class="school-name">NATIONAL<br/>UNIVERSITY</span>
            </a>
        </div>

        <nav class="top-navbar">
            <ul class="navbar-menu">
                <li class="menu-item"><a href="UserPOV.html"><i class='bx bxs-home icon-sidebar'></i> Home</a></li>
                <li class="menu-item"><a href="#"><i class='bx bxs-user icon-sidebar'></i> Profile</a></li>
                <li class="menu-item"><a href="About-us.html"><i class='bx bxs-file icon-sidebar'></i> About Us</a></li>
                <li class="menu-item"><a href="../php/logout.php"><i class='bx bxs-log-out icon-sidebar'></i> Logout</a></li> </ul>
        </nav>

        <div class="header-right-section">
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <i class='bx bx-search icon-search'></i>
            </div>
            <div class="user-section">
                <i class='bx bxs-bell'></i>
                <span class="username">Hi, <span id="current-username"><?php echo $currentUsername; ?></span></span>
                <i class='bx bxs-user-circle'></i>
            </div>
        </div>
    </header>

    <div class="main-container">
        <main class="content-area">
            <div class="clearance-requests-header">
                <h2>CLEARANCE REQUESTS (<span id="user-office-code"><?php echo $currentUserOfficeCode; ?></span>)</h2>
            </div>
            <div class="Status-Request">
                <div class="status-box">
                    <h6>Total Requests</h6>
                    <p id="total-requests">0</p>
                </div>
                <div class="status-box">
                    <h6>Pending</h6>
                    <p id="pending-requests">0</p>
                </div>
                <div class="status-box">
                    <h6>On-Going</h6>
                    <p id="ongoing-requests">0</p>
                </div>
                <div class="status-box">
                    <h6>Completed</h6>
                    <p id="completed-requests">0</p>
                </div>
            </div>

            <div class="Status">
                <ul class="navtabs">
                    <li class="Nav-item active" data-status-frontend="ALL" data-status-db="">ALL</li>
                    <li class="Nav-item" data-status-frontend="PENDING" data-status-db="PEND">PENDING</li>
                    <li class="Nav-item" data-status-frontend="ON-GOING" data-status-db="ON">ON-GOING</li>
                    <li class="Nav-item" data-status-frontend="COMPLETED" data-status-db="COMP">COMPLETED</li>
                </ul>

                <div class="Status-table">
                    <div class="Status-header">
                        <div>REQUEST ID</div>
                        <div>STUDENT ID</div>
                        <div>STUDENT NAME</div>
                        <div>PROGRAM</div>
                        <div id="statusOrRemarkHeader">STATUS</div>
                        <div>DATE SUBMITTED</div>
                        <div id="actionsHeader">ACTIONS</div>
                    </div>
                    <div id="requests-container"></div>
                </div>
            </div>
        </main>
    </div>

    <div id="viewStatusModal" class="modal-overlay">
        <div class="modal-content view-status-modal-content">
            <span class="close-button">&times;</span>
            <div class="view-status-modal-inner">
                <div class="student-info-section">
                    <img src="" alt="Student Avatar" class="student-avatar-img">
                    <div class="student-text-info">
                        <h3 class="modal-student-name"></h3>
                        <p class="modal-student-id"></p>
                        <p class="modal-student-email"></p>
                    </div>
                </div>

                <div class="status-offices-section">
                    <h4>STATUS</h4>
                    </div>

                <div class="requested-documents-section">
                    <h4>REQUESTED DOCUMENTS</h4>
                    </div>

                <div class="remarks-section">
                    <h4>OTHER REMARKS</h4>
                    <div class="remarks-box">
                        <p class="modal-remarks">No Remarks</p>
                    </div>
                    <button class="view-consent-file-button">View Consent File</button>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal-overlay">
        <div class="modal-content">
            <i class='bx bx-question-mark modal-icon'></i>
            <h2>Confirm Clearance</h2>
            <p>Are you sure you want to clear this request?</p>
            <div class="modal-buttons">
                <button id="confirmClearBtn" class="modal-button confirm">Yes, Clear</button>
                <button id="cancelClearBtn" class="modal-button cancel">Cancel</button>
            </div>
        </div>
    </div>

    <div id="customMessageBoxContainer"></div>

    <script src="../js/clearance-requests.js"></script>
</body>
</html>