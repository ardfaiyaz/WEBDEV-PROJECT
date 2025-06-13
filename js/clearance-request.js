// --- Configuration ---
const API_ENDPOINT = 'http://localhost/WEBDEV/WEBDEV-PROJECT/php/clearance-request.php';

// --- Global Data Store (will be populated from API) ---
let requestsData = [];

// --- Element Selectors ---
const navItems = document.querySelectorAll('.Nav-item');
const statusTable = document.querySelector('.Status-table');
const statusHeader = document.querySelector('.Status-header');

// Elements for the Confirmation Modal
const confirmationModal = document.getElementById('confirmationModal');
const confirmClearBtn = document.getElementById('confirmClearBtn');
const cancelClearBtn = document.getElementById('cancelClearBtn');
let requestIdToClear = null; // To store the ID of the request being cleared

// --- Utility Functions for Modals and Messages ---

// Function to open modals
const openModal = (modalElement) => {
    modalElement.classList.add('show-modal');
    document.body.classList.add('modal-open');
};

// Function to close modals
const closeModal = (modalElement) => {
    modalElement.classList.remove('show-modal');
    document.body.classList.remove('modal-open');
};

// Custom message box function
function showCustomMessageBox(message, type = 'info') {
    const messageBox = document.createElement('div');
    messageBox.classList.add('custom-message-box', type);
    messageBox.innerHTML = `<p>${message}</p><button class="custom-message-box-close">OK</button>`;
    document.body.appendChild(messageBox);
    messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
        messageBox.remove();
    });
    setTimeout(() => {
        if (messageBox.parentNode) {
            messageBox.remove();
        }
    }, 3000); // Auto-remove after 3 seconds
}

// --- Data Fetching and Updating Functions ---

async function fetchRequestsData() {
    try {
        const response = await fetch(API_ENDPOINT);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        requestsData = data;
    } catch (error) {
        console.error("Error fetching requests data:", error);
        showCustomMessageBox('Failed to load requests. Please try again.', 'error');
        requestsData = [];
    }
}

async function updateRequestOnServer(requestId, updates) {
    try {
        const response = await fetch(`${API_ENDPOINT}/${requestId}`, { // Append ID to the endpoint for PUT/PATCH
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(updates)
        });

        if (!response.ok) {
            const errorText = await response.text();
            let errorMessage = `Failed to update request on server: ${response.status} - ${errorText}`;
            try {
                const errorJson = JSON.parse(errorText);
                if (errorJson.message) {
                    errorMessage = errorJson.message;
                }
            } catch (e) {
                // Not a JSON error, use plain text
            }
            throw new Error(errorMessage);
        }
        return true;
    } catch (error) {
        console.error("Error updating request on server:", error);
        showCustomMessageBox(`Failed to update request ${requestId}. ${error.message}`, 'error');
        return false;
    }
}

// --- Table Rendering Logic ---

async function renderTable(selectedStatus) {
    // Always fetch fresh data before rendering to ensure accuracy
    await fetchRequestsData();

    const existingRows = statusTable.querySelectorAll('.Status-row');
    existingRows.forEach(row => row.remove());

    updateTableHeaders(selectedStatus);

    requestsData.forEach(request => {
        const rowStatus = request.status.toUpperCase();

        if (selectedStatus === 'ALL' || selectedStatus === rowStatus) {
            const row = document.createElement('div');
            row.classList.add('Status-row');
            row.dataset.status = request.status;
            row.dataset.requestId = request.reqId;

            let statusCellContent = '';
            let actionsCellContent = '';

            if (selectedStatus === 'ALL') {
                statusCellContent = `<div class="status-cell">${request.status}</div>`;
                if (request.status === 'COMPLETED') {
                    actionsCellContent = `<div class="actions"><span class="status-completed-text">Claim Stub Released</span></div>`;
                } else {
                    actionsCellContent = `<div class="actions-placeholder"></div>`;
                }
            } else if (selectedStatus === 'PENDING') {
                statusCellContent = `<div class="status-cell">${request.status}</div>`;
                actionsCellContent = `
                    <div class="actions">
                        <div class="dropdown-wrapper">
                            <button class="add-remark-button dropdown-toggle">ADD REMARK <i class='bx bx-chevron-down'></i></button>
                            <div class="dropdown-menu">
                                <a href="#" data-remark="Unpaid Fees">Unpaid Fees</a>
                                <a href="#" data-remark="Incomplete Documents">Incomplete Documents</a>
                                <a href="#" data-remark="Missing Signatures">Missing Signatures</a>
                                <a href="#" data-remark="Other">Other (Specify)</a>
                            </div>
                        </div>
                        <button class="clear-button">CLEAR</button>
                    </div>`;
            } else if (selectedStatus === 'ON-GOING') {
                statusCellContent = `<div class="remark-column"><span class="remark-text">${request.remark || 'No remark'}</span></div>`;
                actionsCellContent = `
                    <div class="actions">
                        <div class="dropdown-wrapper">
                            <button class="add-remark-button dropdown-toggle">ADD REMARK <i class='bx bx-chevron-down'></i></button>
                            <div class="dropdown-menu">
                                <a href="#" data-remark="Unpaid Fees">Unpaid Fees</a>
                                <a href="#" data-remark="Incomplete Documents">Incomplete Documents</a>
                                <a href="#" data-remark="Missing Signatures">Missing Signatures</a>
                                <a href="#" data-remark="Other">Other (Specify)</a>
                            </div>
                        </div>
                        <button class="clear-button">CLEAR</button>
                    </div>`;
            } else if (selectedStatus === 'COMPLETED') {
                statusCellContent = `<div class="date-completed-column"><span>${request.dateCompleted || 'N/A'}</span></div>`;
                actionsCellContent = `<div class="actions"><span class="status-completed-text">Claim Stub Released</span></div>`;
            }

            row.innerHTML = `
                <div class="reqid">${request.reqId}</div>
                <div class="studid">${request.studId}</div>
                <div class="studname">${request.studName}</div>
                <div class="program">${request.program}</div>
                ${statusCellContent}
                <div class="datesub">${request.dateSubmitted}</div>
                ${actionsCellContent}
            `;
            statusTable.appendChild(row);
        }
    });

    attachEventListenersToRows();
}

function updateTableHeaders(selectedStatus) {
    statusHeader.innerHTML = `
        <div>REQUEST ID</div>
        <div>STUDENT ID</div>
        <div>STUDENT NAME</div>
        <div>PROGRAM</div>
        <div></div> <div>DATE SUBMITTED</div>
        <div class="actions-header"></div> `;

    const dynamicHeaderCell = statusHeader.children[4];
    const actionsHeaderCell = statusHeader.children[6];

    if (selectedStatus === 'ON-GOING') {
        dynamicHeaderCell.textContent = 'REMARK';
        actionsHeaderCell.textContent = 'ACTIONS';
        actionsHeaderCell.style.display = 'block';
    } else if (selectedStatus === 'COMPLETED') {
        dynamicHeaderCell.textContent = 'DATE COMPLETED';
        actionsHeaderCell.textContent = ''; // No actions for completed
        actionsHeaderCell.style.display = 'block';
    } else if (selectedStatus === 'ALL') {
        dynamicHeaderCell.textContent = 'STATUS';
        actionsHeaderCell.textContent = ''; // No generic actions for 'ALL' view
        actionsHeaderCell.style.display = 'block';
    } else { // PENDING
        dynamicHeaderCell.textContent = 'STATUS';
        actionsHeaderCell.textContent = 'ACTIONS';
        actionsHeaderCell.style.display = 'block';
    }
}

function attachEventListenersToRows() {
    document.querySelectorAll('.dropdown-toggle').forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation();
            const dropdownMenu = this.nextElementSibling;
            dropdownMenu.classList.toggle('show');
        });
    });

    document.querySelectorAll('.dropdown-menu a').forEach(item => {
        item.addEventListener('click', async function(event) {
            event.preventDefault();
            const remarkText = this.dataset.remark;
            const row = this.closest('.Status-row');
            const requestId = row.dataset.requestId;

            const updates = { remark: remarkText }; // Only send remark
            const success = await updateRequestOnServer(requestId, updates);

            if (success) {
                showCustomMessageBox(`Remark '${remarkText}' applied.`);
                // Re-render the table for the currently active tab
                const activeTab = document.querySelector('.Nav-item.active');
                if (activeTab) {
                    const selectedStatus = activeTab.dataset.status.toUpperCase();
                    renderTable(selectedStatus); // Re-render table based on active tab
                }
            }
            this.closest('.dropdown-menu').classList.remove('show');
        });
    });

    document.querySelectorAll('.clear-button').forEach(button => {
        button.addEventListener('click', function(event) {
            requestIdToClear = event.target.closest('.Status-row').dataset.requestId;
            openModal(confirmationModal);
        });
    });
}

// --- Modal Close Listeners ---

window.addEventListener('click', function(event) {
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
        if (!menu.contains(event.target) && !event.target.classList.contains('dropdown-toggle')) {
            menu.classList.remove('show');
        }
    });
});

confirmClearBtn.addEventListener('click', async function() {
    // No need to fetch the request from requestsData here, as updateRequestOnServer will handle it
    if (requestIdToClear) {
        // Send status: 'COMPLETED' to the backend
        const updates = { status: 'COMPLETED' };
        const success = await updateRequestOnServer(requestIdToClear, updates);

        if (success) {
            showCustomMessageBox('Request cleared successfully!');
            // Re-render the table for the currently active tab
            const activeTab = document.querySelector('.Nav-item.active');
            if (activeTab) {
                const selectedStatus = activeTab.dataset.status.toUpperCase();
                renderTable(selectedStatus);
            }
        }
    }
    closeModal(confirmationModal);
    requestIdToClear = null;
});

cancelClearBtn.addEventListener('click', function() {
    showCustomMessageBox('Clearance cancelled.');
    closeModal(confirmationModal);
    requestIdToClear = null;
});


// Close modals when clicking outside
window.addEventListener('click', (event) => {
    if (event.target === confirmationModal) {
        closeModal(confirmationModal);
    }
});

// --- Initial Page Load ---

document.addEventListener('DOMContentLoaded', async () => {
    // Initial fetch happens inside renderTable now to ensure fresh data
    navItems.forEach(tab => {
        tab.addEventListener('click', () => {
            navItems.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const selectedStatus = tab.dataset.status.toUpperCase();
            renderTable(selectedStatus);
        });
    });

    const initialTab = document.querySelector('.Nav-item.active');
    if (initialTab) {
        initialTab.click();
    } else {
        const allTab = document.querySelector('[data-status="ALL"]');
        if (allTab) {
            allTab.click();
        } else {
            console.error("No 'ALL' tab found. Please ensure one of your Nav-items has data-status='ALL'.");
        }
    }
});