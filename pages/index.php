<?php
session_start();

require_once __DIR__ . '/../php/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$displayFirstName = $_SESSION['firstname'] ?? 'User';

$showClaimNotification = false;
$claimNotificationData = [
    'req_id' => '',
    'documents' => []
];

// New variable to track if any request has been submitted by the user
$noRequestSubmitted = false;

try {
    // 1. Find the latest clearance request for the user
    $stmtLatestReq = $pdo->prepare("
        SELECT req_id, claim_stub
        FROM clearance_request
        WHERE user_id = :user_id
        ORDER BY req_date DESC, req_id DESC
        LIMIT 1
    ");
    $stmtLatestReq->bindParam(':user_id', $loggedInUserId, PDO::PARAM_INT);
    $stmtLatestReq->execute();
    $latestRequest = $stmtLatestReq->fetch(PDO::FETCH_ASSOC);

    // Check if any request was found for the user
    if (!$latestRequest) {
        $noRequestSubmitted = true;
    } else {
        // If a request is found, proceed to check claim_stub
        if ($latestRequest['claim_stub'] == 1) {
            $showClaimNotification = true;
            $claimNotificationData['req_id'] = $latestRequest['req_id'];

            // 2. If claim stub is released, fetch the requested documents
            $stmtDocuments = $pdo->prepare("
                SELECT dr.doc_copies, d.description AS document_name
                FROM document_request dr
                JOIN document d ON dr.doc_code = d.doc_code
                WHERE dr.req_id = :req_id
            ");
            $stmtDocuments->bindParam(':req_id', $latestRequest['req_id'], PDO::PARAM_INT);
            $stmtDocuments->execute();
            $claimNotificationData['documents'] = $stmtDocuments->fetchAll(PDO::FETCH_ASSOC);
        }
        // If $latestRequest exists but claim_stub is not 1,
        // then $showClaimNotification remains false and $noRequestSubmitted is false.
        // This means there's an ongoing request, but not ready for claim.
    }

} catch (PDOException $e) {
    error_log("Database Error fetching claim notification data: " . $e->getMessage());
    $showClaimNotification = false; // Hide notification on error
    $noRequestSubmitted = false; // Assume some error, not necessarily no request.
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css"/>
    <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
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
            <i class='bx bxs-bell notification-bell-icon <?php echo ($showClaimNotification || $noRequestSubmitted) ? "has-notification" : ""; ?>' id="notification-bell" style="color: white;"></i>
            <span class="username">Hi, <?= htmlspecialchars($displayFirstName); ?> </span>
            <i class='bx bxs-user-circle'></i>
        </div>
    </header>

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

        <div id="claim-notification" class="claim-notification" style="display: <?php echo ($showClaimNotification || $noRequestSubmitted) ? 'block' : 'none'; ?>">
            <?php if ($showClaimNotification): ?>
                <i class='bx bxs-check-circle icon'></i>
                <h2><b>CLAIM STUB UPDATE</b> </h2>
                <p>Hello, <?= htmlspecialchars($displayFirstName); ?>! Your request is ready.</p>
                <p>Request No. : <strong>#<?= htmlspecialchars($claimNotificationData['req_id']); ?></strong></p>
                <?php if (!empty($claimNotificationData['documents'])): ?>
                    <?php foreach ($claimNotificationData['documents'] as $doc): ?>
                        <p>
                            <strong><?= htmlspecialchars($doc['document_name']); ?></strong>
                            (<?= htmlspecialchars($doc['doc_copies']); ?>
                            <?php
                                // Determine if "copy" or "copies" should be displayed
                                if ($doc['doc_copies'] > 1) {
                                    echo 'copies';
                                } else {
                                    echo 'copy';
                                }
                            ?>)
                        </p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No specific documents listed for this request.</p>
                <?php endif; ?>
                <a href="track-clearance.php">You may now claim your documents at the Registrar's Office.</a>
                <div class="yellow-button">
                    <button id="close-notif-btn" class="notifbtn" style="font-family: 'Montserrat', sans-serif;">I UNDERSTOOD</button>
                </div>
            <?php elseif ($noRequestSubmitted): ?>
                <i class='bx bxs-info-circle icon' style="color: #FFC107;"></i> <h2><b>NO REQUEST SUBMITTED</b> </h2>
                <p>Hello, <?= htmlspecialchars($displayFirstName); ?>!</p>
                <p>It looks like you haven't submitted any clearance requests yet.</p>
                <p>Click on "Apply for Clearance" to start your first request.</p>
                <a href="apply-clearance.php">Apply for Clearance</a>
                <div class="yellow-button">
                    <button id="close-notif-btn" class="notifbtn" style="font-family: 'Montserrat', sans-serif;">GOT IT</button>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const notificationBell = document.getElementById('notification-bell');
        const claimNotification = document.getElementById('claim-notification');
        const closeNotifBtn = document.getElementById('close-notif-btn');

        // Function to toggle sidebar (already existing)
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });

        // Event listener for the notification bell
        notificationBell.addEventListener('click', function() {
            // Toggle visibility of the claim notification popup
            if (claimNotification.style.display === 'none' || claimNotification.style.display === '') {
                claimNotification.style.display = 'block';
                // You might want to remove the 'has-notification' class only if it's a new, unread notification
                // For simplicity, we remove it on click, assuming the user acknowledges it.
                notificationBell.classList.remove('has-notification');
            } else {
                claimNotification.style.display = 'none';
            }
        });

        // Event listener for the "I UNDERSTOOD" or "GOT IT" button
        closeNotifBtn.addEventListener('click', function () {
            claimNotification.style.display = 'none';
            // Optional: You can add AJAX here to mark the notification as read in the database
            // so it doesn't reappear on subsequent page loads until a new claim stub is released.
            // Example:
            // fetch('../php/mark_notification_read.php', { method: 'POST', body: JSON.stringify({ req_id: '<?php echo $claimNotificationData['req_id']; ?>', type: 'claim' }) });
        });
    </script>

</body>
</html>