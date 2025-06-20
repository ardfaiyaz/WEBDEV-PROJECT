document.addEventListener('DOMContentLoaded', () => {
    // Consolidated variable declarations
    const viewStatusDetailButtons = document.querySelectorAll('.view-status-detail-button');
    const tabItems = document.querySelectorAll('.tab-item');
    const dataRows = document.querySelectorAll('.data-row'); // All request rows

    const viewStatusModal = document.getElementById('viewStatusModal');
    const closeViewModalButton = viewStatusModal.querySelector('.close-button');

    const confirmationModal = document.getElementById('confirmationModal');
    const confirmYesButton = confirmationModal.querySelector('.confirm-yes');
    const confirmNoButton = confirmationModal.querySelector('.confirm-no');
    const releaseClaimStubButtons = document.querySelectorAll('.release-claim-stub-button');

    // Get view modal content elements
    const modalStudentAvatar = viewStatusModal.querySelector('.student-avatar-img');
    const modalStudentName = viewStatusModal.querySelector('.modal-student-name');
    const modalStudentId = viewStatusModal.querySelector('.modal-student-id');
    const modalStudentEmail = viewStatusModal.querySelector('.modal-student-email');
    const officeStatusesListContainer = viewStatusModal.querySelector('.office-statuses-list'); // Direct container for office statuses
    const requestedDocumentsListContainer = viewStatusModal.querySelector('.requested-documents-list'); // Direct container for requested documents
    const modalRemarks = viewStatusModal.querySelector('.modal-remarks'); // Element for "OTHER REMARKS"
    const viewConsentFileButton = viewStatusModal.querySelector('.view-consent-file-button');

    let currentRequestId = null; // To store the ID of the request being processed for claim stub

    // --- Add a unique identifier to this JS file for verification ---
    console.log("admin-request-stubs.js version: 202506180930"); // Updated version number


    // --- Modal Functions (for View Status Modal) ---
    function openViewModal() {
        viewStatusModal.style.display = 'flex'; // Use flex to center the modal
        document.body.classList.add('modal-open'); // Prevent scrolling
    }

    function closeViewModal() {
        viewStatusModal.style.display = 'none';
        document.body.classList.remove('modal-open'); // Re-enable scrolling
    }

    // --- Modal Functions (for Confirmation Modal) ---
    function openConfirmationModal() {
        confirmationModal.style.display = 'flex'; // Use flex to center the modal
        document.body.classList.add('modal-open'); // Prevent scrolling
    }

    function closeConfirmationModal() {
        confirmationModal.style.display = 'none';
        document.body.classList.remove('modal-open'); // Re-enable scrolling
        // currentRequestId is now reset in the fetch's finally block
    }


    // --- Tab Switching Logic ---
    tabItems.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove 'active' class from all tabs
            tabItems.forEach(item => item.classList.remove('active'));
            // Add 'active' class to the clicked tab
            tab.classList.add('active');

            const selectedType = tab.dataset.type;

            // Show/hide rows based on the selected tab type
            dataRows.forEach(row => {
                const rowType = row.dataset.type;
                if (rowType === selectedType) {
                    row.style.display = 'grid'; // Ensure it displays as grid as per CSS
                } else {
                    row.style.display = 'none'; // Hide row
                }
            });
        });
    });

    // Initial display for LOCAL tab (trigger its click to show content on load)
    // Ensures only 'LOCAL' requests are shown initially.
    document.querySelector('.tab-item[data-type="LOCAL"]').click();


    // --- View Status Detail Modal Logic ---
    viewStatusDetailButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior if applicable

            const dataRow = this.closest('.data-row'); // Get the parent data-row
            console.log("Clicked View Status Detail button. Data Row:", dataRow); // DEBUG: Log the clicked row

            // Retrieve data from data attributes
            const studentName = dataRow.dataset.studentName;
            const studentId = dataRow.dataset.studentId;
            const studentEmail = dataRow.dataset.studentEmail;
            const studentAvatar = dataRow.dataset.studentAvatar;
            let officeStatuses = [];
            try {
                officeStatuses = JSON.parse(dataRow.dataset.officeStatuses);
            } catch (e) {
                console.error("Error parsing officeStatuses JSON:", e, dataRow.dataset.officeStatuses);
                officeStatuses = []; // Fallback if parsing fails
            }

            let requestedDocuments = [];
            try {
                requestedDocuments = JSON.parse(dataRow.dataset.requestedDocuments);
            } catch (e) {
                console.error("Error parsing requestedDocuments JSON:", e, dataRow.dataset.requestedDocuments);
                requestedDocuments = []; // Fallback if parsing fails
            }

            const otherRemarks = dataRow.dataset.otherRemarks; // Retrieve otherRemarks
            const consentFileUrl = dataRow.dataset.consentFileUrl;
            const hasConsentFile = dataRow.dataset.hasConsentFile === 'true';

            console.log("Parsed officeStatuses:", officeStatuses); // DEBUG: Log parsed data
            console.log("Parsed requestedDocuments:", requestedDocuments); // DEBUG: Log parsed data
            console.log("Other Remarks:", otherRemarks); // DEBUG: Log other remarks


            // Populate the modal with data
            modalStudentAvatar.src = studentAvatar;
            modalStudentName.textContent = studentName;
            modalStudentId.textContent = `Student ID: ${studentId}`;
            modalStudentEmail.textContent = `Email: ${studentEmail}`;


            // Populate office statuses
            // Clear previous content by clearing the innerHTML of the dedicated container
            officeStatusesListContainer.innerHTML = '';

            if (officeStatuses.length > 0) {
                officeStatuses.forEach(office => {
                    const statusItem = document.createElement('div'); // Using div for each item
                    statusItem.classList.add('office-status-item');
                    statusItem.innerHTML = `
                        <strong>${office.office}:</strong>
                        <span class="status-badge status-${office.status.toLowerCase().replace(' ', '-')}">${office.status}</span>
                        <!-- Individual office remarks are removed as requested -->
                    `;
                    officeStatusesListContainer.appendChild(statusItem);
                });
            } else {
                officeStatusesListContainer.innerHTML = '<p>No office statuses found.</p>';
            }


            // Populate requested documents
            // Clear previous content by clearing the innerHTML of the dedicated container
            requestedDocumentsListContainer.innerHTML = '';

            if (requestedDocuments && requestedDocuments.length > 0) {
                requestedDocuments.forEach(doc => {
                    const documentItem = document.createElement('li');
                    documentItem.classList.add('document-item');
                    documentItem.innerHTML = `
                        <div class="document-name">${doc.name}</div>
                        <div class="document-copies">${doc.copies}</div>
                    `;
                    requestedDocumentsListContainer.appendChild(documentItem);
                });
            } else {
                const noDocuments = document.createElement('p');
                noDocuments.textContent = "No documents requested.";
                requestedDocumentsListContainer.appendChild(noDocuments);
            }


            // Set "OTHER REMARKS" at the bottom
            modalRemarks.textContent = otherRemarks || 'No other remarks.';

            // Consent file button
            if (hasConsentFile) {
                viewConsentFileButton.href = consentFileUrl;
                viewConsentFileButton.style.display = ''; // Show button
                viewConsentFileButton.classList.remove('disabled');
                viewConsentFileButton.removeAttribute('disabled');
                // Open in a new tab when clicked
                viewConsentFileButton.onclick = (e) => {
                    e.preventDefault(); // Prevent default link behavior if href is used
                    console.log('Attempting to open consent file:', consentFileUrl); // DEBUG: Log the URL
                    window.open(consentFileUrl, '_blank');
                };
            } else {
                viewConsentFileButton.href = '#';
                viewConsentFileButton.style.display = 'none'; // Hide button if no file
                viewConsentFileButton.classList.add('disabled');
                viewConsentFileButton.setAttribute('disabled', 'true');
                viewConsentFileButton.onclick = null; // Remove click handler if disabled
            }

            openViewModal();
            console.log("openViewModal() called."); // DEBUG: Confirm modal function call
        });
    });

    // Event listener for closing the view modal
    closeViewModalButton.addEventListener('click', closeViewModal);

    // Close modals if clicking outside the modal content or overlay
    window.addEventListener('click', (event) => {
        if (event.target === viewStatusModal) {
            closeViewModal();
        }
        if (event.target === confirmationModal) {
            closeConfirmationModal();
        }
    });


    // --- Release Claim Stub Logic (Integrated with AJAX) ---
    releaseClaimStubButtons.forEach(button => {
        // Initial state is set by PHP through `disabled` and `data-released` attributes
        // Add disabled class if the button is initially disabled by PHP
        if (button.hasAttribute('disabled')) {
            button.classList.add('disabled');
        }

        button.addEventListener('click', (event) => {
            const clickedButton = event.target;
            const row = clickedButton.closest('.data-row');
            currentRequestId = row.dataset.reqId; // Store for confirmation

            // Change: Use getAttribute instead of dataset for these two
            const canRelease = clickedButton.getAttribute('data-can-release-claim-stub') === 'true';
            const isReleased = clickedButton.getAttribute('data-released') === 'true';

            console.log(`Release Claim Stub clicked for Req ID: ${currentRequestId}`); // DEBUG
            console.log(`  data-can-release-claim-stub (raw - getAttribute): ${clickedButton.getAttribute('data-can-release-claim-stub')}`); // DEBUG
            console.log(`  canRelease (JS boolean): ${canRelease}`); // DEBUG
            console.log(`  data-released (raw - getAttribute): ${clickedButton.getAttribute('data-released')}`); // DEBUG
            console.log(`  isReleased (JS boolean): ${isReleased}`); // DEBUG


            if (!canRelease && !isReleased) {
                // This state should ideally be prevented by PHP's disabled attribute.
                // If it's not releasable and not already released, simply alert.
                alert('Claim stub cannot be released unless all offices have completed the clearance and it has not been released yet.');
                return;
            }

            if (isReleased) {
                alert('Claim stub has already been released for this request.');
                return;
            }

            // If we reach here, it means canRelease is true and isReleased is false
            openConfirmationModal(); // Show confirmation modal
        });
    });

    // --- Confirmation for Release Claim Stub ---
    confirmYesButton.addEventListener('click', async () => {
        closeConfirmationModal(); // Hide confirmation modal immediately

        if (currentRequestId) {
            console.log(`Attempting to release claim stub for Req ID: ${currentRequestId}`); // DEBUG
            try {
                // Add a cache-busting timestamp to the fetch URL
                const cacheBuster = new Date().getTime();
                const fetchUrl = `../php/update_claim_stub.php?_=${cacheBuster}`;
                console.log(`Fetching URL: ${fetchUrl}`); // DEBUG

                // Send AJAX request to update the database
                const response = await fetch(fetchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ req_id: currentRequestId })
                });

                console.log(`Fetch response received. Status: ${response.status}, OK: ${response.ok}`); // DEBUG

                // Check if the response is actually JSON before parsing
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    const result = await response.json(); // Attempt to parse JSON
                    console.log('Server response (JSON):', result); // DEBUG

                    if (result.success) {
                        // Update the UI: change button text and disable it
                        const releasedButton = document.querySelector(`.data-row[data-req-id="${currentRequestId}"] .release-claim-stub-button`);
                        if (releasedButton) {
                            releasedButton.textContent = 'Claim Stub Released';
                            releasedButton.setAttribute('disabled', 'true');
                            releasedButton.classList.add('disabled');
                            releasedButton.dataset.released = 'true'; // Mark as released in dataset
                            releasedButton.dataset.canReleaseClaimStub = 'false'; // Prevent further clicks
                        }
                        alert('Claim stub successfully released!');
                    } else {
                        alert(`Failed to release claim stub: ${result.message || 'Unknown error'}`);
                    }
                } else {
                    const textResponse = await response.text();
                    console.error('Server response was not JSON:', textResponse);
                    alert('Server returned an unexpected response. Please check server logs.');
                }
            } catch (error) {
                console.error('Error in AJAX request or parsing JSON:', error); // More specific error logging
                alert('An error occurred while communicating with the server or processing its response.');
            } finally {
                // Ensure currentRequestId is reset ONLY after the fetch process completes
                currentRequestId = null;
            }
        } else {
            console.warn("currentRequestId was null when confirmYesButton was clicked. This should not happen.");
        }
    });

    // Event listener for closing the confirmation modal (No button)
    confirmNoButton.addEventListener('click', closeConfirmationModal);


    // Helper function to show alerts (using native alert for now)
    function alert(message) {
        console.log("Alert:", message);
        window.alert(message);
    }
});


