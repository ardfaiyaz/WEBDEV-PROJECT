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
            <a href="admin-index.php" class="logo-link">
                <img src="../assets/images/school-logo.png" alt="School Logo">
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
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by Student ID or Name...">
                <i class='bx bx-search icon-search'></i>
            </div>
            <div class="user-section">
                <span class="username">Hi, <span id="current-username"></span></span>
                <i class='bx bxs-user-circle'></i>
            </div>
        </div>
    </header>

    <div class="main-container">
        <main class="content-area">
            <div class="clearance-requests-header">
                <h2>CLEARANCE REQUESTS (<span id="user-office-code"></span>)</h2>
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
                    <li class="Nav-item" data-status-frontend="PENDING" data-status-db="ON">PENDING</li>
                    <li class="Nav-item" data-status-frontend="ON-GOING" data-status-db="ISSUE">ON-GOING</li>
                    <li class="Nav-item" data-status-frontend="COMPLETED" data-status-db="COMP">COMPLETED</li>
                </ul>

                <div class="Status-table">
                    <div class="Status-header">
                        <div class="reqid">REQUEST ID</div>
                        <div class="studid">STUDENT ID</div>
                        <div class="studname">STUDENT NAME</div>
                        <div class="program">PROGRAM</div>
                        <div id="statusOrRemarkHeader" class="status-cell">STATUS</div>
                        <div class="datesub">DATE SUBMITTED</div>
                        <div id="actionsHeader" class="actions">ACTIONS</div>
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

    <script>
        const API_BASE_URL = 'http://localhost/WEBDEV/WEBDEV-PROJECT/php/';
        let currentUserOfficeCode = '';
        let allRequests = [];

        const REMARKS_MAPPING = {
            "ACC": ["Balance Issues", "Unpaid Fees"],
            "DN_PC_PR": ["Grade/Subject Issues", "Incomplete Coursework"],
            "GO": ["Office Record", "Incorrect Information"],
            "ITSO": ["Damaged Equipment", "Software License Issues"],
            "LIB": ["Unreturned Book/Material", "Damaged Materials"],
            "REG": ["Incomplete Grade/Documents", "Enrollment Discrepancies"],
            "SDAO": ["Disciplinary Action", "Conduct Violation"],
            "SDO": ["Violation/Offense", "Academic Dishonesty"]
        };

        const navItems = document.querySelectorAll('.Nav-item');
        const requestsContainer = document.getElementById('requests-container');
        const statusHeader = document.querySelector('.Status-header');
        const statusOrRemarkHeader = document.getElementById('statusOrRemarkHeader');
        const actionsHeader = document.getElementById('actionsHeader');
        const confirmationModal = document.getElementById('confirmationModal');
        const confirmClearBtn = document.getElementById('confirmClearBtn');
        const cancelClearBtn = document.getElementById('cancelClearBtn');
        let currentRowToClear = null;

        const totalRequestsSpan = document.getElementById('total-requests');
        const pendingRequestsSpan = document.getElementById('pending-requests');
        const ongoingRequestsSpan = document.getElementById('ongoing-requests');
        const completedRequestsSpan = document.getElementById('completed-requests');
        const currentUsernameSpan = document.getElementById('current-username');
        const userOfficeCodeSpan = document.getElementById('user-office-code');

        const searchInput = document.getElementById('searchInput');

        function showMessageBox(message) {
            const messageBox = document.createElement('div');
            messageBox.classList.add('custom-message-box');
            messageBox.innerHTML = `<p>${message}</p><button class="custom-message-box-close">OK</button>`;
            document.body.appendChild(messageBox);
            messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
                messageBox.remove();
            });
        }

        async function fetchUserInfo() {
            try {
                const response = await fetch(`${API_BASE_URL}get_officeuser_info.php`);
                const data = await response.json();
                if (data.success) {
                    currentUsernameSpan.textContent = data.user.user_name;
                    userOfficeCodeSpan.textContent = data.user.office_code;
                    currentUserOfficeCode = data.user.office_code;
                    fetchClearanceRequests(currentUserOfficeCode);
                } else {
                    console.error("Failed to fetch user info:", data.message);
                    showMessageBox("Error: Could not fetch user information. " + data.message);
                }
            } catch (error) {
                console.error("Error fetching user info:", error);
                showMessageBox("Error connecting to server for user info.");
            }
        }

        async function fetchClearanceRequests(officeCode) {
            try {
                const response = await fetch(`${API_BASE_URL}get_clearance_requests.php?office_code=${officeCode}`);
                const data = await response.json();
                if (data.success) {
                    allRequests = data.requests;
                    updateStatusCounts();
                    filterAndRenderRequests();
                } else {
                    console.error("Failed to fetch requests:", data.message);
                    showMessageBox("Error: Could not fetch clearance requests.");
                }
            } catch (error) {
                console.error("Error fetching clearance requests:", error);
                showMessageBox("Error connecting to server for requests.");
            }
        }

        function updateStatusCounts() {
            const total = allRequests.length;
            const pending = allRequests.filter(req => req.status_code === 'ON').length;
            const ongoing = allRequests.filter(req => req.status_code === 'ISSUE').length;
            const completed = allRequests.filter(req => req.status_code === 'COMP').length;

            totalRequestsSpan.textContent = total;
            pendingRequestsSpan.textContent = pending;
            ongoingRequestsSpan.textContent = ongoing;
            completedRequestsSpan.textContent = completed;
        }

        function createRequestRow(request, filterStatusFrontend) {
            const row = document.createElement('div');
            row.classList.add('Status-row');
            row.dataset.statusFrontend = getFrontendStatus(request.status_code);
            row.dataset.statusDb = request.status_code;
            row.dataset.requestId = request.req_id;

            let statusDisplay = getFrontendStatus(request.status_code);
            let fifthColumnContent = '';
            let actionsContent = '';

            if (filterStatusFrontend === 'ON-GOING') {
                fifthColumnContent = `<div class="remark-column"><span class="remark-text">${request.office_remarks || 'N/A'}</span></div>`;
            } else {
                fifthColumnContent = `<div class="status-cell">${statusDisplay}</div>`;
            }

            if (filterStatusFrontend !== 'ALL') {
                if (request.status_code === 'ON') {
                    const customRemarks = REMARKS_MAPPING[currentUserOfficeCode] || [];
                    const selectOptions = customRemarks.map(remark =>
                        `<option value="${remark}">${remark}</option>`
                    ).join('');

                    actionsContent = `
                        <div class="custom-select-wrapper">
                            <span class="selected-remark-display">ADD REMARK</span> <select class="add-remark-select" data-request-id="${request.req_id}">
                                <option value="">Select Remark</option>
                                ${selectOptions}
                            </select>
                        </div>
                        <button class="clear-button">CLEAR</button>
                    `;
                } else if (request.status_code === 'ISSUE') {
                    actionsContent = `<button class="clear-button">CLEAR</button>`;
                } else if (request.status_code === 'COMP') {
                    actionsContent = `<div class="actions-placeholder"></div>`;
                }
            } else {
                actionsContent = `<div class="actions-placeholder"></div>`;
            }

            row.innerHTML = `
                <div class="reqid">${request.req_id}</div>
                <div class="studid">${request.student_id || 'N/A'}</div>
                <div class="studname">${request.student_name || 'N/A'}</div>
                <div class="program">${request.program || 'N/A'}</div>
                ${fifthColumnContent}
                <div class="datesub">${request.date_submitted || 'N/A'}</div>
                <div class="actions">${actionsContent}</div>`;

            return row;
        }

        function getFrontendStatus(dbStatusCode) {
            switch (dbStatusCode) {
                case 'ON': return 'PENDING';
                case 'ISSUE': return 'ON-GOING';
                case 'COMP': return 'COMPLETED';
                case 'PEND': return 'INITIAL PENDING (DB: PEND)';
                default: return dbStatusCode;
            }
        }

        function renderRequests(requestsToDisplay, filterStatusFrontend) {
            requestsContainer.innerHTML = '';

            if (requestsToDisplay.length === 0) {
                requestsContainer.innerHTML = '<div class="no-results">No requests found matching your criteria.</div>';
                return;
            }

            requestsToDisplay.forEach(request => {
                const row = createRequestRow(request, filterStatusFrontend);
                requestsContainer.appendChild(row);
            });

            attachEventListenersToRows();
        }

        function filterAndRenderRequests() {
            const activeTab = document.querySelector('.Nav-item.active');
            const selectedStatusFrontend = activeTab ? activeTab.dataset.statusFrontend.toUpperCase() : 'ALL';
            const selectedStatusDb = activeTab ? activeTab.dataset.statusDb.toUpperCase() : '';
            updateTableHeaders(selectedStatusFrontend);

            const searchTerm = searchInput.value.toLowerCase().trim();

            let filteredByTab = allRequests;
            if (selectedStatusFrontend !== 'ALL') {
                filteredByTab = allRequests.filter(req => req.status_code === selectedStatusDb);
            }

            const finalFilteredRequests = filteredByTab.filter(req => {
                const requestIdMatch = req.req_id.toString().toLowerCase().includes(searchTerm);
                const studentIdMatch = (req.student_id || '').toLowerCase().includes(searchTerm);
                const studentNameMatch = (req.student_name || '').toLowerCase().includes(searchTerm);
                const programMatch = (req.program || '').toLowerCase().includes(searchTerm);

                return requestIdMatch || studentIdMatch || studentNameMatch || programMatch;
            });

            renderRequests(finalFilteredRequests, selectedStatusFrontend);
        }

        function updateTableHeaders(selectedStatusFrontend) {
            statusOrRemarkHeader.textContent = 'STATUS';
            statusOrRemarkHeader.style.display = 'block';
            actionsHeader.style.display = 'block';

            if (selectedStatusFrontend === 'ON-GOING') {
                statusOrRemarkHeader.textContent = 'REMARK';
            } else if (selectedStatusFrontend === 'COMPLETED' || selectedStatusFrontend === 'ALL') {
                actionsHeader.style.display = 'none';
            }
        }

        async function handleRemarkSelectChange(event) {
            const selectElement = event.target;
            const remark = selectElement.value;
            const requestId = selectElement.dataset.requestId;

            if (remark) {
                try {
                    const response = await fetch(`${API_BASE_URL}update_office_remark.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: requestId, office_code: currentUserOfficeCode, remark: remark, status_code: 'ISSUE' })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showMessageBox("Remark added and status updated to ON-GOING!");
                        await fetchClearanceRequests(currentUserOfficeCode);
                    } else {
                        console.error("Failed to add remark:", data.message);
                        showMessageBox("Error: Could not add remark. " + data.message);
                    }
                } catch (error) {
                    console.error("Error adding remark:", error);
                    showMessageBox("Error connecting to server for remark update.");
                }
            }
            selectElement.value = "";
        }

        function showClearConfirmation(event) {
            currentRowToClear = this.closest('.Status-row').dataset.requestId;
            confirmationModal.classList.add('show-modal');
            document.body.classList.add('modal-open');
        }

        function attachEventListenersToRows() {
            document.querySelectorAll('.add-remark-select').forEach(select => {
                select.removeEventListener('change', handleRemarkSelectChange);
                select.addEventListener('change', handleRemarkSelectChange);
            });

            document.querySelectorAll('.clear-button').forEach(button => {
                button.removeEventListener('click', showClearConfirmation);
                button.addEventListener('click', showClearConfirmation);
            });
        }

        navItems.forEach(tab => {
            tab.addEventListener('click', () => {
                navItems.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                filterAndRenderRequests();
            });
        });

        confirmClearBtn.addEventListener('click', async function() {
            if (currentRowToClear) {
                try {
                    const response = await fetch(`${API_BASE_URL}update_request_status.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ request_id: currentRowToClear, office_code: currentUserOfficeCode, status_code: 'COMP' })
                    });
                    const data = await response.json();
                    if (data.success) {
                        showMessageBox("Request cleared successfully!");
                        await fetchClearanceRequests(currentUserOfficeCode);
                    } else {
                        console.error("Failed to clear request:", data.message);
                        showMessageBox("Error: Could not clear request.");
                    }
                } catch (error) {
                    console.error("Error clearing request:", error);
                    showMessageBox("Error connecting to server for clearance.");
                }
            }
            confirmationModal.classList.remove('show-modal');
            document.body.classList.remove('modal-open');
            currentRowToClear = null;
        });

        cancelClearBtn.addEventListener('click', function() {
            showMessageBox("Clearance cancelled.");
            confirmationModal.classList.remove('show-modal');
            document.body.classList.remove('modal-open');
            currentRowToClear = null;
        });

        searchInput.addEventListener('keyup', filterAndRenderRequests);

        document.addEventListener('DOMContentLoaded', () => {
            fetchUserInfo();
        });

    </script>
</body>
</html>