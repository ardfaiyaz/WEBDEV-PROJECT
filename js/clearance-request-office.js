// Function to show a custom message box
function showMessageBox(message, type = 'info', onOk = null) {
    const messageBox = document.createElement('div');
    messageBox.classList.add('custom-message-box');
    messageBox.classList.add(type); // Add type class for styling (e.g., 'success', 'error', 'info')
    messageBox.innerHTML = `<p>${message}</p><button class="custom-message-box-close">OK</button>`;
    document.body.appendChild(messageBox);

    messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
        messageBox.remove();
        if (onOk && typeof onOk === 'function') {
            onOk();
        }
    });
}


document.addEventListener('DOMContentLoaded', () => {
    // Logic for ALL/PENDING/ON-GOING/COMPLETED tabs
    const navItems = document.querySelectorAll('.Nav-item');
    const rows = document.querySelectorAll('.Status-row');
    const statusHeader = document.querySelector('.Status-header');

    // Function to update table headers based on selected status
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
        if (selectedStatus === 'ON-GOING' || selectedStatus === 'ISSUE FOUND') {
            statusColumnHeader.textContent = 'REMARK';
            dateSubmittedColumnHeader.style.display = 'block';
            dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
            if (actionsHeader) actionsHeader.style.display = 'block';
        } else if (selectedStatus === 'COMPLETED') {
            statusColumnHeader.textContent = 'DATE COMPLETED';
            dateSubmittedColumnHeader.style.display = 'block';
            dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
            if (actionsHeader) actionsHeader.style.display = 'none'; // Hide actions for completed
        } else { // ALL or PENDING
            statusColumnHeader.textContent = 'STATUS';
            dateSubmittedColumnHeader.style.display = 'block';
            dateSubmittedColumnHeader.textContent = 'DATE SUBMITTED';
            if (actionsHeader) actionsHeader.style.display = 'block';
        }
    }

    // Event listeners for status tabs
    navItems.forEach(tab => {
        tab.addEventListener('click', () => {
            navItems.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const selectedStatus = tab.dataset.status.toUpperCase();
            updateTableHeaders(selectedStatus);

            rows.forEach(row => {
                const rowStatus = row.dataset.status.toUpperCase(); // This is the status of the CURRENT OFFICE

                // Find the appropriate cell based on its class
                const statusCell = row.querySelector('.status-cell');
                const remarkColumn = row.querySelector('.remark-column');
                const dateCompletedColumn = row.querySelector('.date-completed-column');
                const actionsDiv = row.querySelector('.actions');
                const rowDateSubmitted = row.children[5]; // Date Submitted is always the 6th div

                // Hide all status/remark/date completed cells and actions by default for filtering
                if (statusCell) statusCell.style.display = 'none';
                if (remarkColumn) remarkColumn.style.display = 'none';
                if (dateCompletedColumn) dateCompletedColumn.style.display = 'none';
                if (actionsDiv) actionsDiv.style.display = 'none';
                if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Always show date submitted

                // Show row if it matches the selected status or if ALL is selected
                if (selectedStatus === 'ALL' || selectedStatus === rowStatus) {
                    row.style.display = 'grid'; // Display the row

                    // Re-display the correct cell and actions based on the row's actual status
                    if (rowStatus === 'PENDING') {
                        if (statusCell) statusCell.style.display = 'block'; // Show status cell
                        if (actionsDiv) actionsDiv.style.display = 'flex'; // Show actions
                    } else if (rowStatus === 'ON-GOING' || rowStatus === 'ISSUE FOUND') { // Adjusted for 'ISSUE FOUND'
                        if (remarkColumn) remarkColumn.style.display = 'block'; // Show remark column
                        if (actionsDiv) actionsDiv.style.display = 'flex'; // Show actions
                    } else if (rowStatus === 'COMPLETED') {
                        if (dateCompletedColumn) dateCompletedColumn.style.display = 'block'; // Show date completed column
                        if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Ensure date submitted is visible
                        // Actions div remains hidden for completed items, as per your previous logic
                    }
                } else {
                    row.style.display = 'none'; // Hide the row
                }
            });
        });
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

    // Handle remark selection (AJAX update)
    document.querySelectorAll('.dropdown-menu a').forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            let remarkText = this.dataset.remark; // Use 'let' because it might be changed for "Other"
            const reqId = this.dataset.reqId;
            const studentUserId = this.dataset.studentUserId;
            const row = this.closest('.Status-row');
            const officeCode = row.dataset.officeCode; // Get the office code from the row

            let newStatusCode = 'ON'; // Default to On-Going for most remarks
            if (remarkText === 'Other') {
                const customRemark = prompt("Please specify the remark:");
                if (customRemark) {
                    remarkText = customRemark;
                    newStatusCode = 'ISSUE'; // If a custom remark is provided, set status to 'ISSUE'
                } else {
                    // User cancelled the custom remark, do nothing.
                    this.closest('.dropdown-menu').classList.remove('show');
                    return;
                }
            } else if (remarkText === 'Unpaid Fees' || remarkText === 'Incomplete Documents' || remarkText === 'Missing Signatures') {
                 newStatusCode = 'ISSUE'; // These specific remarks imply an 'ISSUE'
            }


            // AJAX call to update status
            fetch('../php/update_clearance_status.php', { // Ensure this PHP file exists
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    req_id: reqId,
                    student_user_id: studentUserId,
                    office_code: officeCode,
                    status_code: newStatusCode, // Send the determined status code
                    office_remarks: remarkText // Use office_remarks as per your DB
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find the current status/remark/date completed cell
                    let statusOrRemarkCell = row.querySelector('.status-cell') || row.querySelector('.remark-column') || row.querySelector('.date-completed-column');

                    if (statusOrRemarkCell) {
                        statusOrRemarkCell.textContent = remarkText; // Update text
                        statusOrRemarkCell.classList.remove('status-cell', 'date-completed-column'); // Remove old class
                        statusOrRemarkCell.classList.add('remark-column'); // Add new class for remark styling
                    }
                    row.dataset.status = (newStatusCode === 'ON' ? 'ON-GOING' : 'ISSUE FOUND'); // Update row data status for filtering

                    showMessageBox(`Remark '${remarkText}' applied. Status updated to ${newStatusCode === 'ON' ? 'On-Going' : 'Issue Found'}.`, 'success', () => {
                         // Re-trigger active tab to update table view after message box closes
                        const activeTab = document.querySelector('.Nav-item.active');
                        if (activeTab) {
                            activeTab.click();
                        }
                    });

                } else {
                    showMessageBox(`Error updating status: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessageBox('An error occurred while updating the status.', 'error');
            });

            this.closest('.dropdown-menu').classList.remove('show'); // Hide dropdown
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
            // Store reqId and studentUserId for AJAX call
            confirmClearBtn.dataset.reqId = currentRowToClear.dataset.reqId;
            confirmClearBtn.dataset.studentUserId = currentRowToClear.dataset.studentUserId;
            confirmClearBtn.dataset.officeCode = currentRowToClear.dataset.officeCode;

            confirmationModal.classList.add('show-modal');
            document.body.classList.add('modal-open'); /* Add this class to prevent background scrolling */
        }
    });

    confirmClearBtn.addEventListener('click', function() {
        if (currentRowToClear) {
            const reqId = this.dataset.reqId;
            const studentUserId = this.dataset.studentUserId;
            const officeCode = this.dataset.officeCode;

            // AJAX call to update status to COMPLETED
            fetch('../php/update_clearance_status.php', { // Ensure this PHP file exists
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    req_id: reqId,
                    student_user_id: studentUserId,
                    office_code: officeCode,
                    status_code: 'COMP', // Completed
                    office_remarks: 'Cleared by ' + officeCode // Use office_remarks as per your DB
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessageBox('Request cleared successfully!', 'success', () => {
                        currentRowToClear.dataset.status = 'COMPLETED'; // Update row data status to COMPLETED
                        // Find the current status/remark/date completed cell
                        const statusCell = currentRowToClear.querySelector('.status-cell') || currentRowToClear.querySelector('.remark-column') || currentRowToClear.querySelector('.date-completed-column');
                        if (statusCell) {
                            statusCell.classList.remove('status-cell', 'remark-column'); // Remove old classes
                            statusCell.classList.add('date-completed-column'); // Add new class for completed styling

                            const today = new Date();
                            const dd = String(today.getDate()).padStart(2, '0');
                            const mm = String(today.getMonth() + 1).padStart(2, '0');
                            const yyyy = today.getFullYear();
                            statusCell.innerHTML = `<span>${mm}-${dd}-${yyyy}</span>`; // Update cell content to date completed
                        }
                        // Re-trigger active tab to update table view
                        const activeTab = document.querySelector('.Nav-item.active');
                        if (activeTab) {
                            activeTab.click();
                        }
                    });
                } else {
                    showMessageBox(`Error clearing request: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessageBox('An error occurred while clearing the request.', 'error');
            });

            confirmationModal.classList.remove('show-modal'); // Hide modal
            document.body.classList.remove('modal-open'); /* Remove background scrolling class */
            currentRowToClear = null; // Reset stored row
        }
    });

    cancelClearBtn.addEventListener('click', function() {
        showMessageBox('Clearance cancelled.', 'info', () => {
            confirmationModal.classList.remove('show-modal'); // Hide modal
            document.body.classList.remove('modal-open'); /* Remove background scrolling class */
            currentRowToClear = null; // Reset stored row
        });
    });

    // --- View Status Modal Logic ---
    const viewStatusModal = document.getElementById('viewStatusModal');
    const closeButton = viewStatusModal.querySelector('.close-button');
    const studentAvatarImg = viewStatusModal.querySelector('.student-avatar-img');
    const modalStudentName = viewStatusModal.querySelector('.modal-student-name');
    const modalStudentEmail = viewStatusModal.querySelector('.modal-student-email');
    const modalStudentId = viewStatusModal.querySelector('.modal-student-id');
    const modalRemarks = viewStatusModal.querySelector('.modal-remarks');
    const statusOfficesSection = viewStatusModal.querySelector('.status-offices-section');
    const requestedDocumentsSection = viewStatusModal.querySelector('.requested-documents-section');
    const viewConsentFileButton = viewStatusModal.querySelector('.view-consent-file-button'); // Get the button

    const openModal = (modalElement) => {
        modalElement.classList.add('show-modal');
        document.body.classList.add('modal-open');
    };

    const closeModal = (modalElement) => {
        modalElement.classList.remove('show-modal');
        document.body.classList.remove('modal-open');
        // Clear dynamically added content to prevent duplication on next open
        statusOfficesSection.querySelectorAll('.office-status-item').forEach(item => item.remove());
        requestedDocumentsSection.querySelectorAll('.document-item, .modal-placeholder-text').forEach(item => item.remove());
        // Reset the button's data attributes and display style
        viewConsentFileButton.dataset.reqId = '';
        viewConsentFileButton.dataset.studentUserId = '';
        viewConsentFileButton.style.display = 'none'; // Hide until determined if there's a file
    };

    // Event listener for "view status" links
    document.querySelectorAll('.view-status-link').forEach(link => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            const row = link.closest('.Status-row');
            if (row) {
                const reqId = row.dataset.reqId;
                const studentUserId = row.dataset.studentUserId;

                // Fetch student and all office statuses for this request via AJAX
                try {
                    const response = await fetch('../php/fetch_clearance_details.php', { // Ensure this PHP file exists
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            req_id: reqId,
                            student_user_id: studentUserId
                        })
                    });
                    const data = await response.json();

                    if (data.success) {
                        const studentDetails = data.student_details;
                        const officeStatuses = data.office_statuses;
                        const requestedDocuments = data.requested_documents;
                        const studentOverallRemarks = data.student_overall_remarks;
                        const hasConsentLetter = data.has_consent_letter; // New data point from PHP

                        // Populate student info
                        studentAvatarImg.src = studentDetails.avatar_url || `https://placehold.co/80x80/cccccc/333333?text=${studentDetails.firstname.charAt(0)}${studentDetails.lastname.charAt(0)}`;
                        modalStudentName.textContent = studentDetails.firstname + ' ' + studentDetails.lastname;
                        modalStudentEmail.textContent = studentDetails.email;
                        modalStudentId.textContent = studentDetails.student_no;
                        modalRemarks.textContent = studentOverallRemarks || 'No Remarks';

                        // Clear previous statuses and documents (important for re-opening modal)
                        statusOfficesSection.querySelectorAll('.office-status-item').forEach(item => item.remove());
                        requestedDocumentsSection.querySelectorAll('.document-item, .modal-placeholder-text').forEach(item => item.remove());


                        // Populate requested documents dynamically
                        if (requestedDocuments && requestedDocuments.length > 0) {
                            requestedDocuments.forEach(doc => {
                                const documentItem = document.createElement('div');
                                documentItem.classList.add('document-item');
                                documentItem.innerHTML = `<span class="document-name">${doc.description}:</span> <span class="document-copies">${doc.doc_copies}</span>`;
                                requestedDocumentsSection.appendChild(documentItem);
                            });
                        } else {
                            const noDocumentsText = document.createElement('p');
                            noDocumentsText.classList.add('modal-placeholder-text');
                            noDocumentsText.textContent = 'No documents requested.';
                            requestedDocumentsSection.appendChild(noDocumentsText);
                        }

                        // Populate office statuses dynamically
                        if (officeStatuses && officeStatuses.length > 0) {
                            officeStatuses.forEach(officeStatus => {
                                const officeItem = document.createElement('div');
                                officeItem.classList.add('office-status-item');
                                const officeNameSpan = document.createElement('span');
                                officeNameSpan.textContent = officeStatus.office_description + ':';
                                officeItem.appendChild(officeNameSpan);
                                const statusBadgeSpan = document.createElement('span');
                                statusBadgeSpan.classList.add('status-badge');
                                statusBadgeSpan.textContent = officeStatus.status_description;

                                // Apply specific status classes for styling
                                if (officeStatus.status_code === 'COMP') {
                                    statusBadgeSpan.classList.add('completed');
                                } else if (officeStatus.status_code === 'PEND') {
                                    statusBadgeSpan.classList.add('pending');
                                } else if (officeStatus.status_code === 'ISSUE') {
                                    statusBadgeSpan.classList.add('issue-found');
                                } else if (officeStatus.status_code === 'ON') {
                                    statusBadgeSpan.classList.add('on-going');
                                }
                                officeItem.appendChild(statusBadgeSpan);

                                if (officeStatus.office_remarks) {
                                    const remarksSpan = document.createElement('span');
                                    remarksSpan.classList.add('office-remarks');
                                    remarksSpan.textContent = ` (${officeStatus.office_remarks})`;
                                    officeItem.appendChild(remarksSpan);
                                }
                                statusOfficesSection.appendChild(officeItem);
                            });
                        } else {
                            const noStatusesText = document.createElement('p');
                            noStatusesText.classList.add('modal-placeholder-text');
                            noStatusesText.textContent = 'No status updates available.';
                            statusOfficesSection.appendChild(noStatusesText);
                        }

                        // Handle Consent Letter button visibility
                        if (hasConsentLetter) {
                            viewConsentFileButton.style.display = 'block';
                            viewConsentFileButton.dataset.reqId = reqId; // Store req_id
                            viewConsentFileButton.dataset.studentUserId = studentUserId; // Store student_user_id
                        } else {
                            viewConsentFileButton.style.display = 'none';
                        }


                        openModal(viewStatusModal);
                    } else {
                        showMessageBox(`Failed to fetch clearance details: ${data.message}`, 'error');
                    }
                } catch (error) {
                    console.error('Error fetching clearance details:', error);
                    showMessageBox('An error occurred while fetching clearance details.', 'error');
                }
            }
        });
    });

    closeButton.addEventListener('click', () => {
        closeModal(viewStatusModal);
    });

    window.addEventListener('click', (event) => {
        if (event.target === viewStatusModal) {
            closeModal(viewStatusModal);
        }
    });

    // Event listener for the "View Consent File" button
    viewConsentFileButton.addEventListener('click', () => {
        const reqId = viewConsentFileButton.dataset.reqId;
        const studentUserId = viewConsentFileButton.dataset.studentUserId;

        if (reqId && studentUserId) {
            // Open the consent letter in a new tab/window
            window.open(`../php/view_consent_letter.php?req_id=${reqId}&user_id=${studentUserId}`, '_blank');
        } else {
            showMessageBox('Consent file not available for this request.', 'info');
        }
    });

    // Initial load to show ALL requests
    // Simulate a click on the "ALL" tab to initialize the table display
    const initialTab = document.querySelector('.Nav-item.active');
    if (initialTab) {
        initialTab.click();
    }
});