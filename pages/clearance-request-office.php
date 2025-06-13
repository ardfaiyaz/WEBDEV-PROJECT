<?php
session_start(); // Start the session at the very beginning

// Include the database connection file
require_once __DIR__ . '/../php/database.php'; // Adjust path as needed

// Check if the user is logged in and is an office staff member (role_code 'SUB_ADMIN' or 'ADMIN' for Registrar)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_code']) || ($_SESSION['role_code'] !== 'SUB_ADMIN' && $_SESSION['role_code'] !== 'ADMIN')) {
    header("Location: ../login.php"); // Redirect to login if not authorized
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$displayFirstName = "Office Staff"; // Default fallback name
$loggedInUserOfficeCode = null;
$loggedInUserOfficeDescription = "Your Office";

// Fetch logged-in user's details and their assigned office
try {
    $stmtUser = $pdo->prepare("
        SELECT u.firstname, u.lastname, u.office_code, o.description AS office_description
        FROM users u
        LEFT JOIN office o ON u.office_code = o.office_code
        WHERE u.user_id = :user_id
    ");
    $stmtUser->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmtUser->execute();
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $displayFirstName = htmlspecialchars($userData['firstname'] ?? 'Office Staff');
        $loggedInUserOfficeCode = $userData['office_code'];
        $loggedInUserOfficeDescription = htmlspecialchars($userData['office_description'] ?? 'Your Office');
    } else {
        // If user data not found or no office assigned, log an error and redirect
        error_log("Office staff user_id " . $loggedInUserId . " not found or has no office_code.");
        header("Location: ../logout.php"); // Force logout for unconfigured users
        exit();
    }

} catch (PDOException $e) {
    error_log("Error fetching logged-in office user data: " . $e->getMessage());
    // In a real application, you might show a generic error or redirect
    echo "Error loading user data.";
    exit();
}

// --- Fetch Clearance Requests relevant to this office ---
$clearanceRequests = [];
$totalRequests = 0;
$pendingRequests = 0;
$ongoingRequests = 0;
$completedRequests = 0;

if ($loggedInUserOfficeCode) {
    try {
        // Fetch clearance requests where this office has an entry
        $stmtRequests = $pdo->prepare("
            SELECT
                cr.req_id,
                cr.req_date,
                cs.status_code,
                cs.office_remarks,
                u_student.user_id AS student_user_id,
                si.student_no,
                si.firstname AS student_firstname,
                si.lastname AS student_lastname,
                si.course AS student_program
            FROM
                clearance_request cr
            JOIN
                clearance_status cs ON cr.req_id = cs.req_id AND cr.user_id = cs.user_id
            JOIN
                users u_student ON cr.user_id = u_student.user_id
            LEFT JOIN
                student_info si ON u_student.user_id = si.user_id
            WHERE
                cs.office_code = :office_code
            ORDER BY
                cr.req_date DESC, cr.req_id DESC
        ");
        $stmtRequests->bindParam(':office_code', $loggedInUserOfficeCode, PDO::PARAM_STR);
        $stmtRequests->execute();
        $clearanceRequests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);

        // Calculate counts for status boxes
        foreach ($clearanceRequests as $request) {
            $totalRequests++;
            if ($request['status_code'] === 'PEND') {
                $pendingRequests++;
            } elseif ($request['status_code'] === 'ON' || $request['status_code'] === 'ISSUE') { // ISSUE is also on-going
                $ongoingRequests++;
            } elseif ($request['status_code'] === 'COMP') {
                $completedRequests++;
            }
        }

    } catch (PDOException $e) {
        error_log("Error fetching clearance requests for office: " . $e->getMessage());
        echo "Error fetching clearance requests.";
    }
}

// Function to format status codes for display
function formatStatusCode($statusCode) {
    switch ($statusCode) {
        case 'PEND': return 'PENDING';
        case 'ON': return 'ON-GOING';
        case 'ISSUE': return 'ISSUE FOUND';
        case 'COMP': return 'COMPLETED';
        default: return 'UNKNOWN';
    }
}
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
                <li class="menu-item"><a href="#"><i class='bx bxs-log-out icon-sidebar'></i> Logout</a></li>
            </ul>
        </nav>

        <div class="header-right-section">
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <i class='bx bx-search icon-search'></i>
            </div>
            <div class="user-section">
                <i class='bx bxs-bell'></i>
                <span class="username">Hi, User</span>
                <i class='bx bxs-user-circle'></i>
            </div>
        </div>
    </header>

    <div class="main-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="office-dashboard.php"><i class='bx bxs-home icon-sidebar'></i> Home</a></li>
                <li class="menu-item"><a href="office-profile.php"><i class='bx bxs-user icon-sidebar'></i> Profile</a></li>
                <li class="menu-item"><a href="clearance-requests-office.php"><i class='bx bxs-file icon-sidebar'></i> Clearance Requests</a></li>
                <li class="menu-item"><a href="../php/logout.php"><i class='bx bxs-log-out icon-sidebar'></i> Logout</a></li>
            </ul>
        </aside>

        <main class="content-area">
            <div class="clearance-requests-header">
                <h2>CLEARANCE REQUESTS (<?php echo $loggedInUserOfficeDescription; ?>)</h2>
            </div>
            <div class="Status-Request">
                <div class="status-box">
                    <h6>Total Requests</h6>
                    <p><?php echo $totalRequests; ?></p>
                </div>
                <div class="status-box">
                    <h6>Pending</h6>
                    <p><?php echo $pendingRequests; ?></p>
                </div>
                <div class="status-box">
                    <h6>On-Going</h6>
                    <p><?php echo $ongoingRequests; ?></p>
                </div>
                <div class="status-box">
                    <h6>Completed</h6>
                    <p><?php echo $completedRequests; ?></p>
                </div>
            </div>

            <div class="Status">
                <ul class="navtabs">
                    <li class="Nav-item active" data-status="ALL">ALL</li>
                    <li class="Nav-item" data-status="PENDING">PENDING</li>
                    <li class="Nav-item" data-status="ON-GOING">ON-GOING</li>
                    <li class="Nav-item" data-status="COMPLETED">COMPLETED</li>
                </ul>

                <div class="Status-table">
                    <div class="Status-header">
                        <div>REQUEST ID</div>
                        <div>STUDENT ID</div>
                        <div>STUDENT NAME</div>
                        <div>PROGRAM</div>
                        <div>STATUS</div>
                        <div>DATE SUBMITTED</div>
                        <div class="actions-header">ACTIONS</div>
                    </div>

                    <?php if (!empty($clearanceRequests)): ?>
                        <?php foreach ($clearanceRequests as $request):
                            $studentFullName = htmlspecialchars($request['student_firstname'] . ' ' . $request['student_lastname']);
                            $studentProgram = htmlspecialchars($request['student_program'] ?? 'N/A');
                            $displayStatus = formatStatusCode($request['status_code']);
                            $displayRemarks = htmlspecialchars($request['office_remarks'] ?? '');
                            $reqDate = new DateTime($request['req_date']);
                            $formattedReqDate = $reqDate->format('m-d-Y');

                            // Determine what content to show in the status/remark/date completed cell
                            $statusOrRemarkContent = '';
                            $actionsContent = '';
                            $isCompleted = false;

                            if ($request['status_code'] === 'COMP') {
                                $isCompleted = true;
                                $statusOrRemarkContent = '<span>' . $formattedReqDate . '</span>';
                                $actionsContent = '<a href="#" class="view-status-link" data-modal-target="#viewStatusModal" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">View Status Detail</a><span class="status-completed-text">Claim Stub Released</span>';
                            } elseif ($request['status_code'] === 'ON' || $request['status_code'] === 'ISSUE') {
                                $statusOrRemarkContent = '<span class="remark-text">' . ($displayRemarks ?: 'On-going') . '</span>';
                                $actionsContent = '
                                    <div class="dropdown-wrapper">
                                        <button class="add-remark-button dropdown-toggle">ADD REMARK <i class="bx bx-chevron-down"></i></button>
                                        <div class="dropdown-menu">
                                            <a href="#" data-remark="Unpaid Fees" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Unpaid Fees</a>
                                            <a href="#" data-remark="Incomplete Documents" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Incomplete Documents</a>
                                            <a href="#" data-remark="Missing Signatures" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Missing Signatures</a>
                                            <a href="#" data-remark="Other" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Other (Specify)</a>
                                        </div>
                                    </div>
                                    <button class="clear-button" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">CLEAR</button>
                                    <a href="#" class="view-status-link" data-modal-target="#viewStatusModal" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">View Status Detail</a>
                                ';
                            } else { // PENDING
                                $statusOrRemarkContent = '<div class="status-cell">' . $displayStatus . '</div>';
                                $actionsContent = '
                                    <div class="dropdown-wrapper">
                                        <button class="add-remark-button dropdown-toggle">ADD REMARK <i class="bx bx-chevron-down"></i></button>
                                        <div class="dropdown-menu">
                                            <a href="#" data-remark="Unpaid Fees" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Unpaid Fees</a>
                                            <a href="#" data-remark="Incomplete Documents" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Incomplete Documents</a>
                                            <a href="#" data-remark="Missing Signatures" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Missing Signatures</a>
                                            <a href="#" data-remark="Other" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">Other (Specify)</a>
                                        </div>
                                    </div>
                                    <button class="clear-button" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">CLEAR</button>
                                    <a href="#" class="view-status-link" data-modal-target="#viewStatusModal" data-req-id="' . $request['req_id'] . '" data-student-user-id="' . $request['student_user_id'] . '">View Status Detail</a>
                                ';
                            }
                        ?>
                            <div class="Status-row" data-status="<?php echo $displayStatus; ?>"
                                data-req-id="<?php echo $request['req_id']; ?>"
                                data-student-user-id="<?php echo $request['student_user_id']; ?>"
                                data-office-code="<?php echo $loggedInUserOfficeCode; ?>">
                                <div><?php echo htmlspecialchars($request['req_id']); ?></div>
                                <div><?php echo htmlspecialchars($request['student_no'] ?? 'N/A'); ?></div>
                                <div><?php echo $studentFullName; ?></div>
                                <div><?php echo $studentProgram; ?></div>
                                <div class="<?php echo ($request['status_code'] === 'COMP' ? 'date-completed-column' : (($request['status_code'] === 'ON' || $request['status_code'] === 'ISSUE') ? 'remark-column' : 'status-cell')); ?>">
                                    <?php echo $statusOrRemarkContent; ?>
                                </div>
                                <div><?php echo $formattedReqDate; ?></div>
                                <div class="actions">
                                    <?php echo $actionsContent; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-requests-message">No clearance requests found for <?php echo $loggedInUserOfficeDescription; ?>.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="viewStatusModal" class="modal-overlay">
        <div class="modal-content view-status-modal-content">
            <span class="close-button">&times;</span>
            <div class="view-status-modal-inner">
                <div class="student-info-section">
                    <img src="https://placehold.co/80x80/cccccc/333333?text=S" alt="Student Avatar" class="student-avatar-img">
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
                    <button class="view-consent-file-button" style="display: none;">View Consent File</button>
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

    <script src="../js/clearance-requests-office.js"></script> </body>
</html>