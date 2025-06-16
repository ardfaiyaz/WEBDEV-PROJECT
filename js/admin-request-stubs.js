document.addEventListener('DOMContentLoaded', () => {
    const viewStatusDetailButtons = document.querySelectorAll('.view-status-detail-button');
    const viewStatusModal = document.getElementById('viewStatusModal');
    const closeButton = viewStatusModal.querySelector('.close-button');

    // Get modal content elements
    const modalStudentAvatar = viewStatusModal.querySelector('.student-avatar-img');
    const modalStudentName = viewStatusModal.querySelector('.modal-student-name');
    const modalStudentId = viewStatusModal.querySelector('.modal-student-id');
    const modalStudentEmail = viewStatusModal.querySelector('.modal-student-email');
    const statusOfficesSection = viewStatusModal.querySelector('.status-offices-section');
    const requestedDocumentsSection = viewStatusModal.querySelector('.requested-documents-section');
    const modalRemarks = viewStatusModal.querySelector('.modal-remarks');

    // Function to open the modal
    function openModal() {
        viewStatusModal.style.display = 'flex'; // Use flex to center the modal
        document.body.classList.add('modal-open'); // Prevent scrolling
    }

    // Function to close the modal
    function closeModal() {
        viewStatusModal.style.display = 'none';
        document.body.classList.remove('modal-open'); // Re-enable scrolling
    }

    // Event listeners for opening the modal
    viewStatusDetailButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior if applicable

            const dataRow = this.closest('.data-row'); // Get the parent data-row

            // Retrieve data from data attributes
            const studentName = dataRow.dataset.studentName;
            const studentId = dataRow.dataset.studentId;
            const studentEmail = dataRow.dataset.studentEmail;
            const studentAvatar = dataRow.dataset.studentAvatar;
            const officeStatuses = JSON.parse(dataRow.dataset.officeStatuses);
            const requestedDocuments = JSON.parse(dataRow.dataset.requestedDocuments);

            // Populate the modal with data
            modalStudentAvatar.src = studentAvatar;
            modalStudentName.textContent = studentName;
            modalStudentId.textContent = `Student ID: ${studentId}`;
            modalStudentEmail.textContent = `Email: ${studentEmail}`;

            // Clear previous content
            // Select by class within the specific section to avoid clearing other potential elements
            statusOfficesSection.querySelectorAll('.office-status-item').forEach(item => item.remove());
            requestedDocumentsSection.querySelectorAll('.document-item').forEach(item => item.remove());


            // Populate office statuses
            const officeStatusList = document.createElement('ul');
            officeStatusList.classList.add('office-status-list');
            officeStatuses.forEach(office => {
                const statusItem = document.createElement('li');
                statusItem.classList.add('office-status-item');
                statusItem.innerHTML = `
                    <div class="office-name">${office.office}:</div>
                    <div class="status-badge status-${office.status.toLowerCase().replace(' ', '-')}">${office.status}</div>
                `;
                officeStatusList.appendChild(statusItem);
            });
            // Clear previous status list and append new one
            const oldStatusList = statusOfficesSection.querySelector('.office-status-list');
            if (oldStatusList) {
                oldStatusList.remove();
            }
            statusOfficesSection.appendChild(officeStatusList);


            // Populate requested documents
            const documentList = document.createElement('ul');
            documentList.classList.add('document-list');
            if (requestedDocuments && requestedDocuments.length > 0) {
                requestedDocuments.forEach(doc => {
                    const documentItem = document.createElement('li');
                    documentItem.classList.add('document-item');
                    documentItem.innerHTML = `
                        <div class="document-name">${doc.name}</div>
                        <div class="document-copies">${doc.copies}</div>
                    `;
                    documentList.appendChild(documentItem);
                });
            } else {
                const noDocuments = document.createElement('p');
                noDocuments.textContent = "No documents requested.";
                documentList.appendChild(noDocuments); // Append to list for consistency
            }
            // Clear previous document list and append new one
            const oldDocumentList = requestedDocumentsSection.querySelector('.document-list');
            if (oldDocumentList) {
                oldDocumentList.remove();
            }
            requestedDocumentsSection.appendChild(documentList);


            // Set remarks (you'll need to decide how to derive a general remark,
            // or if you want to show remarks per office - current HTML assumes one general remark)
            let generalRemark = "No Remarks";
            const issueFoundOffice = officeStatuses.find(office => office.status === "Issue Found" && office.remarks);
            if (issueFoundOffice) {
                generalRemark = issueFoundOffice.remarks;
            } else {
                const pendingOffice = officeStatuses.find(office => office.status === "Pending" && office.remarks);
                if (pendingOffice) {
                    generalRemark = pendingOffice.remarks;
                }
            }
            modalRemarks.textContent = generalRemark;

            openModal();
        });
    });

    // Event listener for closing the modal
    closeButton.addEventListener('click', closeModal);

    // Close modal if clicking outside the modal content
    window.addEventListener('click', (event) => {
        if (event.target === viewStatusModal) {
            closeModal();
        }
    });

    // Handle tab switching (Local/Abroad)
    const tabItems = document.querySelectorAll('.tab-item');
    tabItems.forEach(tab => {
        tab.addEventListener('click', () => {
            const type = tab.dataset.type;

            // Remove active class from all tabs and add to clicked tab
            tabItems.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');

            // Show/hide data rows based on type
            const dataRows = document.querySelectorAll('.view-request-data-row');
            dataRows.forEach(row => {
                if (row.dataset.type === type) {
                    row.style.display = 'grid'; // Or 'table-row-group' if using tbody
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Initial display for LOCAL tab
    document.querySelector('.tab-item[data-type="LOCAL"]').click();


    // New Confirmation Pop-up Logic
    const releaseStubButtons = document.querySelectorAll('.release-claim-stub-button');
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmYesButton = confirmationModal.querySelector('.confirm-yes');
    const confirmNoButton = confirmationModal.querySelector('.confirm-no');
    let currentButton = null; // To store the button that was clicked

    releaseStubButtons.forEach(button => {
        button.addEventListener('click', () => {
            currentButton = button; // Store the clicked button
            confirmationModal.classList.add('show-modal'); // Show the confirmation modal
            document.body.classList.add('modal-open'); // Prevent scrolling
        });
    });

    confirmYesButton.addEventListener('click', () => {
        if (currentButton) {
            currentButton.textContent = 'Claim Stub Released';
            currentButton.classList.add('claim-stub-released');
            currentButton.classList.remove('primary-button');
            currentButton.disabled = true; // Disable the button after click
        }
        confirmationModal.classList.remove('show-modal'); // Hide the confirmation modal
        document.body.classList.remove('modal-open'); // Re-enable scrolling
    });

    confirmNoButton.addEventListener('click', () => {
        confirmationModal.classList.remove('show-modal'); // Hide the confirmation modal
        document.body.classList.remove('modal-open'); // Re-enable scrolling
        currentButton = null; // Clear the stored button
    });

    // Close confirmation modal if clicking outside (optional, based on modal-overlay behavior)
    confirmationModal.addEventListener('click', (event) => {
        if (event.target === confirmationModal) {
            confirmationModal.classList.remove('show-modal');
            document.body.classList.remove('modal-open');
            currentButton = null;
        }
    });
});