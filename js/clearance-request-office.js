

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
                    const dateCompletedColumn = row.querySelector('.date-completed-column');
                    const actionsDiv = row.querySelector('.actions');
                    const actionsPlaceholder = row.querySelector('.actions-placeholder');
                    const rowDateSubmitted = row.children[5]; // Date Submitted is always the 6th div

                    // Hide all status/remark/date completed cells and actions by default
                    if (statusCell) statusCell.style.display = 'none';
                    if (remarkColumn) remarkColumn.style.display = 'none';
                    if (dateCompletedColumn) dateCompletedColumn.style.display = 'none';
                    if (actionsDiv) actionsDiv.style.display = 'none';
                    if (actionsPlaceholder) actionsPlaceholder.style.display = 'none';
                    if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Always show date submitted

                    // Show row if it matches the selected status or if ALL is selected
                    if (selectedStatus === 'ALL' || selectedStatus === rowStatus) {
                        row.style.display = 'grid'; // Display the row

                        if (rowStatus === 'PENDING') {
                            if (statusCell) statusCell.style.display = 'block'; // Show status cell
                            if (actionsDiv) actionsDiv.style.display = 'flex'; // Show actions
                        } else if (rowStatus === 'ON-GOING') {
                            if (remarkColumn) remarkColumn.style.display = 'block'; // Show remark column
                            if (actionsDiv) actionsDiv.style.display = 'flex'; // Show actions
                        } else if (rowStatus === 'COMPLETED') {
                            if (dateCompletedColumn) dateCompletedColumn.style.display = 'block'; // Show date completed column
                            if (rowDateSubmitted) rowDateSubmitted.style.display = 'block'; // Ensure date submitted is visible
                            // Actions div remains hidden for completed items
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

        // Handle remark selection
        document.querySelectorAll('.dropdown-menu a').forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault();
                const remarkText = this.dataset.remark;

                const row = this.closest('.Status-row');
                if (row) {
                    // Find the current status/remark cell
                    let statusOrRemarkCell = row.querySelector('.status-cell') || row.querySelector('.remark-column');

                    if (statusOrRemarkCell) {
                        statusOrRemarkCell.textContent = remarkText; // Update text
                        statusOrRemarkCell.classList.remove('status-cell'); // Remove old class
                        statusOrRemarkCell.classList.add('remark-column'); // Add new class for remark styling

                        row.dataset.status = 'ON-GOING'; // Update row data status

                        this.closest('.dropdown-menu').classList.remove('show'); // Hide dropdown

                        // Show custom message box (instead of alert)
                        const messageBox = document.createElement('div');
                        messageBox.classList.add('custom-message-box');
                        messageBox.innerHTML = `<p>Remark '${remarkText}' applied. Status updated to On-Going.</p><button class="custom-message-box-close">OK</button>`;
                        document.body.appendChild(messageBox);
                        messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
                            messageBox.remove();
                        });

                        // Re-trigger active tab to update table view
                        const activeTab = document.querySelector('.Nav-item.active');
                        if (activeTab) {
                            activeTab.click();
                        }
                    }
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
                    statusCell.classList.add('date-completed-column'); // Add new class for completed styling

                    const today = new Date();
                    const dd = String(today.getDate()).padStart(2, '0');
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const yyyy = today.getFullYear(); // Corrected variable name
                    statusCell.innerHTML = `<span>${mm}-${dd}-${yyyy}</span>`; // Update cell content to date completed
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

        // --- View Status Modal Logic (from the provided snippet) ---
        // These are the elements for the viewStatusModal
        const viewStatusModal = document.getElementById('viewStatusModal');
        const closeButton = viewStatusModal.querySelector('.close-button');
        const studentAvatarImg = viewStatusModal.querySelector('.student-avatar-img');
        const modalStudentName = viewStatusModal.querySelector('.modal-student-name');
        const modalStudentEmail = viewStatusModal.querySelector('.modal-student-email');
        const modalStudentId = viewStatusModal.querySelector('.modal-student-id');
        const modalRemarks = viewStatusModal.querySelector('.modal-remarks');
        const statusOfficesSection = viewStatusModal.querySelector('.status-offices-section');
        const requestedDocumentsSection = viewStatusModal.querySelector('.requested-documents-section');

        const openModal = (modalElement) => {
            modalElement.classList.add('show-modal');
            document.body.classList.add('modal-open');
        };

        const closeModal = (modalElement) => {
            modalElement.classList.remove('show-modal');
            document.body.classList.remove('modal-open');
        };

        // Event listener for "view status" links
        document.querySelectorAll('.view-status-link').forEach(link => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const row = link.closest('.Status-row'); 
                if (row) {
                    // Extract data from data attributes (from the table row)
                    const officeStatuses = JSON.parse(row.dataset.officeStatuses || '[]');
                    const requestedDocuments = JSON.parse(row.dataset.requestedDocuments || '[]');
                    const studentName = row.dataset.studentName || row.children[2].textContent;
                    const studentId = row.dataset.studentId || row.children[1].textContent;
                    const studentEmail = row.dataset.studentEmail || 'student@nu-dasma.edu.ph'; // Default if not present
                    const studentAvatar = row.dataset.studentAvatar || 'https://placehold.co/80x80/cccccc/333333?text=' + studentName.charAt(0) + (studentName.split(' ')[1] ? studentName.split(' ')[1].charAt(0) : '');
                    const remarks = row.dataset.remarks || 'No Remarks';


                    // Populate student info
                    studentAvatarImg.src = studentAvatar;
                    modalStudentName.textContent = studentName;
                    modalStudentEmail.textContent = studentEmail;
                    modalStudentId.textContent = studentId;
                    modalRemarks.textContent = remarks;

                    // Clear previous statuses and documents
                    statusOfficesSection.querySelectorAll('.office-status-item').forEach(item => item.remove());
                    requestedDocumentsSection.querySelectorAll('.document-item, .modal-placeholder-text').forEach(item => item.remove());

                    // Populate requested documents dynamically
                    if (requestedDocuments.length > 0) {
                        requestedDocuments.forEach(doc => {
                            const documentItem = document.createElement('div');
                            documentItem.classList.add('document-item');
                            const documentNameSpan = document.createElement('span');
                            documentNameSpan.classList.add('document-name');
                            documentNameSpan.textContent = doc.name + ':';
                            documentItem.appendChild(documentNameSpan);
                            const documentCopiesSpan = document.createElement('span');
                            documentCopiesSpan.classList.add('document-copies');
                            documentCopiesSpan.textContent = doc.copies;
                            documentItem.appendChild(documentCopiesSpan);
                            requestedDocumentsSection.appendChild(documentItem);
                        });
                    } else {
                        const noDocumentsText = document.createElement('p');
                        noDocumentsText.classList.add('modal-placeholder-text');
                        noDocumentsText.textContent = 'No documents requested.';
                        requestedDocumentsSection.appendChild(noDocumentsText);
                    }

                    // Populate office statuses dynamically
                    if (officeStatuses.length > 0) {
                        officeStatuses.forEach(officeStatus => {
                            const officeItem = document.createElement('div');
                            officeItem.classList.add('office-status-item');
                            const officeNameSpan = document.createElement('span');
                            officeNameSpan.textContent = officeStatus.office + ':';
                            officeItem.appendChild(officeNameSpan);
                            const statusBadgeSpan = document.createElement('span');
                            statusBadgeSpan.classList.add('status-badge');
                            statusBadgeSpan.textContent = officeStatus.status;

                            if (officeStatus.status === 'Completed') {
                                statusBadgeSpan.classList.add('completed');
                            } else if (officeStatus.status === 'Pending') {
                                statusBadgeSpan.classList.add('pending');
                            } else if (officeStatus.status === 'Issue Found') {
                                statusBadgeSpan.classList.add('issue-found');
                            } else if (officeStatus.status === 'On-going') {
                                statusBadgeSpan.classList.add('on-going');
                            }
                            officeItem.appendChild(statusBadgeSpan);
                            statusOfficesSection.appendChild(officeItem);
                        });
                    } else {
                        const noStatusesText = document.createElement('p');
                        noStatusesText.classList.add('modal-placeholder-text');
                        noStatusesText.textContent = 'No status updates available.';
                        statusOfficesSection.appendChild(noStatusesText);
                    }


                    openModal(viewStatusModal);
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

        // Initial load to show ALL requests
        document.addEventListener('DOMContentLoaded', () => {
            const initialTab = document.querySelector('.Nav-item.active');
            if (initialTab) {
                initialTab.click();
            }
        });
    </script>