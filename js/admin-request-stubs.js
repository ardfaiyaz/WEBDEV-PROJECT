// In your admin-request-stubs.js file

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
    }

    // Function to close the modal
    function closeModal() {
        viewStatusModal.style.display = 'none';
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
            statusOfficesSection.querySelectorAll('.status-item').forEach(item => item.remove());
            requestedDocumentsSection.querySelectorAll('.document-item').forEach(item => item.remove());

            // Populate office statuses
            officeStatuses.forEach(office => {
                const statusItem = document.createElement('div');
                statusItem.classList.add('status-item');
                statusItem.innerHTML = `
                    <div class="office-name">${office.office}:</div>
                    <div class="office-status status-${office.status.toLowerCase().replace(' ', '-')}">${office.status}</div>
                `;
                statusOfficesSection.appendChild(statusItem);
            });

            // Populate requested documents
            if (requestedDocuments && requestedDocuments.length > 0) {
                requestedDocuments.forEach(doc => {
                    const documentItem = document.createElement('div');
                    documentItem.classList.add('document-item');
                    documentItem.textContent = `${doc.name} (${doc.copies})`;
                    requestedDocumentsSection.appendChild(documentItem);
                });
            } else {
                const noDocuments = document.createElement('p');
                noDocuments.textContent = "No documents requested.";
                requestedDocumentsSection.appendChild(noDocuments);
            }

            // Set remarks (you'll need to decide how to derive a general remark,
            // or if you want to show remarks per office - current HTML assumes one general remark)
            // For now, let's just pick the first 'issue found' remark or default
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
});