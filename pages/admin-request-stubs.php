<?php
session_start();

require_once __DIR__ . '/../php/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type'])) {
    header("Location: login.php");
    exit();
}

// Correctly catch and display the first name of the logged-in user
$displayFirstName = $_SESSION['firstname'] ?? 'Admin';

$requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            cr.req_id,
            cr.req_date,
            cr.claim_stub,
            cr.enrollment_purpose,
            cr.student_remarks AS other_remarks,
            cr.consent_letter,
            u.user_id,
            si.firstname,
            si.lastname,
            u.email,
            si.student_no,
            si.course AS program
        FROM
            clearance_request cr
        JOIN
            student_info si ON cr.user_id = si.user_id
        JOIN
            users u ON si.user_id = u.user_id
        ORDER BY
            req_id ASC
    ");
    $stmt->execute();
    $rawRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawRequests as $request) {
        $reqId = $request['req_id'];

        $officeStatuses = [];
        $stmtStatus = $pdo->prepare("
            SELECT
                o.description AS office_name,
                rs.description AS status,
                cs.office_remarks
            FROM
                clearance_status cs
            JOIN
                office o ON cs.office_code = o.office_code
            JOIN
                request_status rs ON cs.status_code = rs.status_code
            WHERE
                cs.req_id = :req_id
            ORDER BY o.description
        ");
        $stmtStatus->bindParam(':req_id', $reqId, PDO::PARAM_INT);
        $stmtStatus->execute();
        $officeStatusesData = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

        $allOfficesCompleted = true;
        foreach ($officeStatusesData as $officeStatus) {
            $officeStatuses[] = [
                'office' => htmlspecialchars($officeStatus['office_name']),
                'status' => htmlspecialchars($officeStatus['status']),
                'remarks' => htmlspecialchars($officeStatus['office_remarks'] ?? 'No remarks.')
            ];
            if (strtoupper($officeStatus['status']) !== 'COMPLETED') {
                $allOfficesCompleted = false;
            }
        }


        $requestedDocuments = [];
        $stmtDocs = $pdo->prepare("
            SELECT
                d.description AS name
            FROM
                document_request dr
            JOIN
                document d ON dr.doc_code = d.doc_code
            WHERE
                dr.req_id = :req_id
        ");
        $stmtDocs->bindParam(':req_id', $reqId, PDO::PARAM_INT);
        $stmtDocs->execute();
        $requestedDocumentsData = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        foreach ($requestedDocumentsData as $doc) {
            $requestedDocuments[] = [
                'name' => htmlspecialchars($doc['name']),
                'copies' => '1 copy'
            ];
        }


        $request['office_statuses_json'] = json_encode($officeStatuses);
        $request['requested_documents_json'] = json_encode($requestedDocuments);

        $request['formatted_date'] = (new DateTime($request['req_date']))->format('m-d-Y');
        $request['full_name'] = htmlspecialchars($request['firstname'] . ' ' . $request['lastname']);

        $derivedOverallStatus = 'COMPLETED';
        $hasPending = false;
        $hasOngoing = false;
        foreach ($officeStatuses as $os) {
            if ($os['status'] === 'Issue Found') {
                $derivedOverallStatus = 'ISSUE FOUND';
                break;
            } elseif ($os['status'] === 'Pending') {
                $hasPending = true;
            } elseif ($os['status'] === 'On-Going') {
                $hasOngoing = true;
            }
        }
        if ($derivedOverallStatus !== 'ISSUE FOUND') {
            if ($hasPending) {
                $derivedOverallStatus = 'PENDING';
            } elseif ($hasOngoing) {
                $derivedOverallStatus = 'ONGOING';
            }
        }
        $request['overall_status'] = $derivedOverallStatus;
        $request['overall_status_display'] = htmlspecialchars(str_replace('_', ' ', $derivedOverallStatus));


        $request['avatar_url_display'] = 'https://placehold.co/80x80/cccccc/333333?text=User';


        $request['consent_file_url'] = !empty($request['consent_letter']) ?
            '../php/get_consent_file.php?request_id=' . $request['req_id'] :
            '#';

        $request['clearance_type_display'] = htmlspecialchars(strtoupper($request['enrollment_purpose']));

        $request['can_release_claim_stub'] = $allOfficesCompleted && ($request['claim_stub'] == 0);


        $requests[] = $request;
    }
} catch (PDOException $e) {
    error_log("Error fetching clearance requests for admin: " . $e->getMessage());
    die("A database error occurred. Please try again later or contact support. Error: " . $e->getMessage());
}

function getOverallStatusClass($overallStatus)
{
    switch (strtoupper($overallStatus)) {
        case 'PENDING':
            return 'status-pending';
        case 'ONGOING':
            return 'status-ongoing';
        case 'ISSUE FOUND':
            return 'status-issue-found';
        case 'COMPLETED':
            return 'status-completed';
        default:
            return '';
    }
}

$js_version = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Requests</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-request-stubs.css">
    <link rel="icon" type="image/png" href="../assets/images/school-logo.png" />
    <style>
        .release-claim-stub-button {
            background-color: #2c2273;
            color: white;
            cursor: pointer;
        }

        .release-claim-stub-button[disabled] {
            background-color: #ccc;
            color: #666;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="logo-section">
            <a href="index.html" class="logo-link">
                <img src="../assets/icons/NU_shield.svg.png" alt="School Logo">
                <span class="school-name">NATIONAL<br />UNIVERSITY</span>
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
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <i class='bx bx-search icon-search'></i>
            </div>
            <div class="user-section">
                <span class="username">Hi, <span id="current-username"><?php echo htmlspecialchars($displayFirstName); ?></span></span>
                <i class='bx bxs-user-circle'></i>
            </div>
        </div>
    </header>

    <div class="main-container">
        <main class="content-area">
            <div class="page-header-controls view-request-header">
                <div class="page-title-container">
                    <h2>Process Requests</h2>
                </div>
                <div class="header-actions">
                    <a href="admin-index.php" class="back-link">Back to Dashboard</a>
                </div>
            </div>

            <section class="section-card">
                <div class="tab-navs local-abroad-tabs">
                    <ul class="navtabs">
                        <li class="tab-item active" data-type="LOCAL">LOCAL</li>
                        <li class="tab-item" data-type="ABROAD">ABROAD</li>
                    </ul>
                </div>

                <div class="status-table-container">
                    <div class="table-header-row data-table-header view-request-table-header">
                        <div>ID</div>
                        <div>DATE</div>
                        <div>STUDENT ID</div>
                        <div>NAME</div>
                        <div>PROGRAM</div>
                        <div>STATUS</div>
                        <div>ACTION</div>
                    </div>

                    <?php if (empty($requests)) : ?>
                        <div class="no-requests-message">No clearance requests found.</div>
                    <?php else : ?>
                        <?php foreach ($requests as $request) : ?>
                            <div class="data-row view-request-data-row" data-req-id="<?php echo htmlspecialchars($request['req_id']); ?>" data-type="<?php echo htmlspecialchars($request['clearance_type_display']); ?>" data-office-statuses='<?php echo htmlspecialchars($request['office_statuses_json'], ENT_QUOTES, 'UTF-8'); ?>' data-student-name="<?php echo htmlspecialchars($request['full_name']); ?>" data-student-email="<?php echo htmlspecialchars($request['email'] ?? 'N/A'); ?>" data-student-id="<?php echo htmlspecialchars($request['student_no'] ?? 'N/A'); ?>" data-requested-documents='<?php echo htmlspecialchars($request['requested_documents_json'], ENT_QUOTES, 'UTF-8'); ?>' data-student-avatar="<?php echo htmlspecialchars($request['avatar_url_display']); ?>" data-other-remarks="<?php echo htmlspecialchars($request['other_remarks'] ?? 'No other remarks.'); ?>" data-consent-file-url="<?php echo htmlspecialchars($request['consent_file_url']); ?>" data-has-consent-file="<?php echo !empty($request['consent_letter']) ? 'true' : 'false'; ?>">
                                <div><?php echo htmlspecialchars($request['req_id']); ?></div>
                                <div><?php echo htmlspecialchars($request['formatted_date']); ?></div>
                                <div><?php echo htmlspecialchars($request['student_no'] ?? 'N/A'); ?></div>
                                <div><?php echo htmlspecialchars($request['full_name']); ?></div>
                                <div><?php echo htmlspecialchars($request['program'] ?? 'N/A'); ?></div>
                                <div> <span class="<?php echo getOverallStatusClass($request['overall_status']); ?>"><?php echo htmlspecialchars($request['overall_status_display']); ?></span> </div>
                                <div class="action-cell">
                                    <button class="action-button secondary-button view-status-detail-button">View Status Detail</button>
                                    <button class="action-button primary-button release-claim-stub-button" <?php echo ($request['can_release_claim_stub'] ? '' : 'disabled'); ?> data-can-release-claim-stub="<?php echo $request['can_release_claim_stub'] ? 'true' : 'false'; ?>" data-released="<?php echo ($request['claim_stub'] == 1 ? 'true' : 'false'); ?>">
                                        <?php echo ($request['claim_stub'] == 1 ? 'Claim Stub Released' : 'Release Claim Stub'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </section>
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
                    <h4>OFFICE STATUSES</h4>
                    <div class="office-statuses-list">
                    </div>
                </div>

                <div class="requested-documents-section">
                    <h4>REQUESTED DOCUMENTS</h4>
                    <ul class="requested-documents-list">
                    </ul>
                </div>

                <div class="remarks-section">
                    <h4>OTHER REMARKS</h4>
                    <div class="remarks-box">
                        <p class="modal-remarks">No Remarks</p>
                    </div>
                    <button class="view-consent-file-button" target="_blank">View Consent File</button>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal-overlay">
        <div class="modal-content confirmation-modal-content">
            <h4 class="confirmation-modal-title">Confirm Action</h4>
            <p class="confirmation-modal-message">Are you sure you want to release the claim stub?</p>
            <div class="confirmation-buttons">
                <button class="action-button confirm-yes">Yes</button>
                <button class="action-button secondary-button confirm-no">No</button>
            </div>
        </div>
    </div>

    <script src="../js/admin-request-stubs.js?v=<?php echo $js_version; ?>"></script>
</body>
</html>