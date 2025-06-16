   // Logic for ALL/PENDING/ON-GOING/COMPLETED tabs
const navItems = document.querySelectorAll('.Nav-item');
const rows = document.querySelectorAll('.Status-row');
const statusHeader = document.querySelector('.Status-header');

function updateTableHeaders(selectedStatus) {
    statusHeader.innerHTML = `
        <div>REQUEST ID</div>
        <div>STUDENT ID</div>
        <div>STUDENT NAME</div>
        <div>PROGRAM</div>
        <div>STATUS</div>
        <div>DATE SUBMITTED</div>
        <div class="actions-header">ACTIONS</div>
    `;

    const statusColumnHeader = statusHeader.children[4];
    const dateSubmittedColumnHeader = statusHeader.children[5];
    const actionsHeader = statusHeader.querySelector('.actions-header');

    // Adjust headers based on selected status
    if (selectedStatus === 'ON-GOING') {
        statusColumnHeader.textContent = 'REMARK';
        dateSubmittedColumnHeader.style.display = 'block';
        dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
        if (actionsHeader) actionsHeader.style.display = 'block'; // Actions visible for ON-GOING
    } else if (selectedStatus === 'COMPLETED') {
        dateSubmittedColumnHeader.style.display = 'block';
        dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
        if (actionsHeader) actionsHeader.style.display = 'none'; // Hide actions for COMPLETED
    } else if (selectedStatus === 'ALL') {
        dateSubmittedColumnHeader.style.display = 'block';
        dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
        if (actionsHeader) actionsHeader.style.display = 'none'; // Hide actions for ALL
    } else { // PENDING
        statusColumnHeader.textContent = 'STATUS';
        dateSubmittedColumnHeader.style.display = 'block';
        dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
        if (actionsHeader) actionsHeader.style.display = 'block'; // Actions visible for PENDING
    }
}

navItems.forEach(tab => {
    tab.addEventListener('click', () => {
        navItems.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const selectedStatus = tab.dataset.status.toUpperCase();
        updateTableHeaders(selectedStatus);

        rows.forEach(row => {
            const rowStatus = row.dataset.status.toUpperCase();

            const statusCell = row.querySelector('.status-cell');
            const remarkColumn = row.querySelector('.remark-column');
            const actionsDiv = row.querySelector('.actions');
            const actionsPlaceholder = row.querySelector('.actions-placeholder');
            const rowDateSubmitted = row.children[5]; // Date Submitted is always the 6th div

            // Hide all status/remark/ cells and actions by default for this row's logic
            if (statusCell) statusCell.style.display = 'none';
            if (remarkColumn) remarkColumn.style.display = 'none';
            if (actionsDiv) actionsDiv.style.display = 'none';
            if (actionsPlaceholder) actionsPlaceholder.style.display = 'none';
            if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Always show date submitted

            // Show row if it matches the selected status or if ALL is selected
            if (selectedStatus === 'ALL') {
                row.style.display = 'grid'; // Display the row

                if (rowStatus === 'PENDING') {
                    if (statusCell) statusCell.style.display = 'block'; // Show status cell
                    // Actions div remains hidden for ALL view, as per the header
                } else if (rowStatus === 'ON-GOING') {
                    if (remarkColumn) remarkColumn.style.display = 'block'; // Show remark column
                    // Actions div remains hidden for ALL view, as per the header
                } else if (rowStatus === 'COMPLETED') {
                    if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Ensure date submitted is visible
                    // Actions div remains hidden for COMPLETED and ALL view
                }
            } else if (selectedStatus === rowStatus) {
                row.style.display = 'grid'; // Display the row

                if (rowStatus === 'PENDING') {
                    if (statusCell) statusCell.style.display = 'block'; // Show status cell
                    if (actionsDiv) actionsDiv.style.display = 'flex'; // Show actions for PENDING
                } else if (rowStatus === 'ON-GOING') {
                    if (remarkColumn) remarkColumn.style.display = 'block'; // Show remark column
                    if (actionsDiv) actionsDiv.style.display = 'flex'; // Show actions for ON-GOING
                } else if (rowStatus === 'COMPLETED') {
                    if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Ensure date submitted is visible
                    // Actions div remains hidden for COMPLETED
                }
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });
    });
});

// Simulate a click on the active tab on page load to set initial state
document.addEventListener('DOMContentLoaded', () => {
    const activeTabOnLoad = document.querySelector('.Nav-item.active');
    if (activeTabOnLoad) {
        activeTabOnLoad.click();
    }
});

// Dropdown for Add Remark button
document.querySelectorAll('.dropdown-toggle').forEach(button => {
    button.addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent document click from closing immediately
        const dropdownMenu = this.nextElementSibling;
        dropdownMenu.classList.toggle('show');
    });
});

// Close dropdown when clicking outside
window.addEventListener('click', function(event) {
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
        if (!menu.contains(event.target) && !event.target.classList.contains('dropdown-toggle')) {
            menu.classList.remove('show');
        }
    });
});


// --- Custom Confirmation Modal Logic for Clearing ---
const confirmationModal = document.getElementById('confirmationModal');
const confirmClearBtn = document.getElementById('confirmClearBtn');
const cancelClearBtn = document.getElementById('cancelClearBtn');
let currentRowToClear = null; // To store the row element that triggered the modal

document.addEventListener('click', function(event) {
    if (event.target.classList.contains('clear-button')) {
        currentRowToClear = event.target.closest('.Status-row');
        confirmationModal.classList.add('show-modal');
        document.body.classList.add('modal-open'); /* Add this class to prevent background scrolling */
    }
});

confirmClearBtn.addEventListener('click', function() {
    if (currentRowToClear) {
        // Show custom message box (instead of alert)
        const messageBox = document.createElement('div');
        messageBox.classList.add('custom-message-box');
        messageBox.innerHTML = '<p>Request cleared successfully!</p><button class="custom-message-box-close">OK</button>';
        document.body.appendChild(messageBox);
        messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
            messageBox.remove();
        });

        currentRowToClear.dataset.status = 'COMPLETED'; // Update row data status to COMPLETED
        const statusCell = currentRowToClear.querySelector('.status-cell') || currentRowToClear.querySelector('.remark-column');
        if (statusCell) {
            statusCell.classList.remove('status-cell', 'remark-column'); // Remove old classes
        }

        // Re-trigger active tab to update table view
        const activeTab = document.querySelector('.Nav-item.active');
        if (activeTab) {
            activeTab.click();
        }
    }
    confirmationModal.classList.remove('show-modal'); // Hide modal
    document.body.classList.remove('modal-open'); /* Remove background scrolling class */
    currentRowToClear = null; // Reset stored row
});

cancelClearBtn.addEventListener('click', function() {
    // Show custom message box (instead of alert)
    const messageBox = document.createElement('div');
    messageBox.classList.add('custom-message-box');
    messageBox.innerHTML = '<p>Clearance cancelled.</p><button class="custom-message-box-close">OK</button>';
    document.body.appendChild(messageBox);
    messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
        messageBox.remove();
    });

    confirmationModal.classList.remove('show-modal'); // Hide modal
    document.body.classList.remove('modal-open'); /* Remove background scrolling class */
    currentRowToClear = null; // Reset stored row
});
