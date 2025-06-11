document.addEventListener('DOMContentLoaded', () => {
            const navItems = document.querySelectorAll('.local-abroad-tabs .tab-item');
            const rows = document.querySelectorAll('.view-request-data-row');
            const viewStatusLinks = document.querySelectorAll('.view-status-link');
            const viewStatusModal = document.getElementById('viewStatusModal');
            const closeButton = viewStatusModal.querySelector('.close-button');

            const studentAvatarImg = viewStatusModal.querySelector('.student-avatar-img'); // Get the new img tag
            const modalStudentName = viewStatusModal.querySelector('.modal-student-name');
            const modalStudentEmail = viewStatusModal.querySelector('.modal-student-email');
            const modalStudentId = viewStatusModal.querySelector('.modal-student-id');
            const modalRemarks = viewStatusModal.querySelector('.modal-remarks');
            const statusOfficesSection = viewStatusModal.querySelector('.status-offices-section');
            const requestedDocumentsSection = viewStatusModal.querySelector('.requested-documents-section');

            navItems.forEach(tab => {
                tab.addEventListener('click', () => {
                    navItems.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    const selectedType = tab.dataset.type.toUpperCase();

                    rows.forEach(row => {
                        const rowType = row.dataset.type.toUpperCase();
                        if (selectedType === rowType) {
                            row.style.display = 'grid';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });

            const initialTab = document.querySelector('.local-abroad-tabs .tab-item.active');
            if (initialTab) {
                initialTab.click();
            }

            const openModal = (modalElement) => {
                modalElement.classList.add('show-modal');
                document.body.classList.add('modal-open');
            };

            const closeModal = (modalElement) => {
                modalElement.classList.remove('show-modal');
                document.body.classList.remove('modal-open');
            };

            viewStatusLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const row = link.closest('.view-request-data-row');
                    if (row) {
                        // Populate student info
                        studentAvatarImg.src = row.dataset.studentAvatar || 'https://placehold.co/80x80/e0e0e0/777777?text=NA'; // Set avatar source
                        modalStudentName.textContent = row.dataset.studentName || 'N/A';
                        modalStudentEmail.textContent = row.dataset.studentEmail || 'N/A';
                        modalStudentId.textContent = row.dataset.studentId || 'N/A';
                        modalRemarks.textContent = row.dataset.remarks || 'No Remarks'; // Assuming remarks might be added to data-attributes

                        // Clear previous statuses and documents
                        statusOfficesSection.querySelectorAll('.office-status-item').forEach(item => item.remove());
                        requestedDocumentsSection.querySelectorAll('.document-item, .modal-placeholder-text').forEach(item => item.remove()); // Clear previous documents and placeholder

                        // Populate requested documents dynamically
                        const requestedDocuments = JSON.parse(row.dataset.requestedDocuments || '[]');
                        if (requestedDocuments.length > 0) {
                            requestedDocuments.forEach(doc => {
                                const documentItem = document.createElement('div');
                                documentItem.classList.add('document-item'); // Add a class for styling

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
                            // Display "No documents requested" if the list is empty
                            const noDocumentsText = document.createElement('p');
                            noDocumentsText.classList.add('modal-placeholder-text'); // Reusing existing style if suitable
                            noDocumentsText.textContent = 'No documents requested.';
                            requestedDocumentsSection.appendChild(noDocumentsText);
                        }

                        // Populate office statuses dynamically
                        const officeStatuses = JSON.parse(row.dataset.officeStatuses || '[]');
                        officeStatuses.forEach(officeStatus => {
                            const officeItem = document.createElement('div');
                            officeItem.classList.add('office-status-item');

                            const officeNameSpan = document.createElement('span');
                            officeNameSpan.textContent = officeStatus.office + ':';
                            officeItem.appendChild(officeNameSpan);

                            const statusBadgeSpan = document.createElement('span');
                            statusBadgeSpan.classList.add('status-badge');
                            statusBadgeSpan.textContent = officeStatus.status;

                            // Apply color coding based on status
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

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('view-status-detail-button')) {
                    const messageBox = document.createElement('div');
                    messageBox.classList.add('custom-message-box');
                    messageBox.innerHTML = '<p>View Status Detail clicked for this request!</p><button class="custom-message-box-close">OK</button>';
                    document.body.appendChild(messageBox);

                    messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
                        messageBox.remove();
                    });
                }
                if (event.target.classList.contains('release-claim-stub-button')) {
                    const messageBox = document.createElement('div');
                    messageBox.classList.add('custom-message-box');
                    messageBox.innerHTML = '<p>Release Claim Stub clicked!</p><button class="custom-message-box-close">OK</button>';
                    document.body.appendChild(messageBox);

                    messageBox.querySelector('.custom-message-box-close').addEventListener('click', () => {
                        messageBox.remove();
                    });
                }
            });
        });