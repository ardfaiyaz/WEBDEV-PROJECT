const API_BASE_URL = 'http://localhost/WEBDEV/WEBDEV-PROJECT/php/';
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('requestsTableBody')) {
        fetchClearanceRequests();
    }

    const requestDetailsModal = document.getElementById('requestDetailsModal');
    if (requestDetailsModal) {
        requestDetailsModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const requestId = button.getAttribute('data-bs-request-id');
            fetchRequestDetailsForModal(requestId);
        });

        const saveRemarksButton = requestDetailsModal.querySelector('#saveOfficeRemarksButton');
        if (saveRemarksButton) {
            saveRemarksButton.addEventListener('click', saveOfficeRemarks);
        }

        const approveButton = requestDetailsModal.querySelector('#approveRequestButton');
        const rejectButton = requestDetailsModal.querySelector('#rejectRequestButton');
        const pendingButton = requestDetailsModal.querySelector('#setPendingButton');

        if (approveButton) approveButton.addEventListener('click', () => updateRequestStatus('APPROVED'));
        if (rejectButton) rejectButton.addEventListener('click', () => updateRequestStatus('REJECTED'));
        if (pendingButton) pendingButton.addEventListener('click', () => updateRequestStatus('PENDING'));
    }

    fetchUserInfo();
});

async function fetchUserInfo() {
    try {
        const response = await fetch(`${API_BASE_URL}get_officeuser_info.php`);
        const userInfo = await response.json();

        if (userInfo.error) {
            console.error('Error fetching user info:', userInfo.error);
            return;
        }

        document.getElementById('userNameDisplay').textContent = `${userInfo.firstname} ${userInfo.lastname}`;
        document.getElementById('userEmailDisplay').textContent = userInfo.email;
        document.getElementById('officeNameDisplay').textContent = userInfo.office_name || 'N/A';
    } catch (error) {
        console.error('Failed to fetch user info:', error);
    }
}

async function fetchClearanceRequests() {
    try {
        const response = await fetch(`${API_BASE_URL}get_clearance_requests.php`);
        const requests = await response.json();
        const tableBody = document.getElementById('requestsTableBody');
        tableBody.innerHTML = '';

        if (requests.error) {
            console.error('Error fetching requests:', requests.error);
            const row = tableBody.insertRow();
            row.innerHTML = `<td colspan="8" class="text-center text-danger">${requests.error}</td>`;
            return;
        }

        if (requests.length === 0) {
            const row = tableBody.insertRow();
            row.innerHTML = `<td colspan="8" class="text-center">No clearance requests found.</td>`;
            return;
        }

        requests.forEach(request => {
            const row = tableBody.insertRow();
            row.innerHTML = `
                <td>${request.req_id}</td>
                <td>${request.firstname} ${request.lastname}</td>
                <td>${request.student_no || 'N/A'}</td>
                <td>${request.program || 'N/A'}</td>
                <td>${request.req_date}</td>
                <td>${request.status_code || 'PENDING'}</td>
                <td>${request.office_remarks || 'N/A'}</td>
                <td>
                    <button type="button" class="btn btn-info btn-sm view-details-btn" data-bs-toggle="modal" data-bs-target="#requestDetailsModal" data-bs-request-id="${request.req_id}">View Details</button>
                </td>
            `;
            if (request.status_code === 'APPROVED') {
                row.classList.add('table-success');
            } else if (request.status_code === 'REJECTED') {
                row.classList.add('table-danger');
            } else {
                row.classList.add('table-warning');
            }
        });
    } catch (error) {
        console.error('Failed to fetch clearance requests:', error);
        const tableBody = document.getElementById('requestsTableBody');
        const row = tableBody.insertRow();
        row.innerHTML = `<td colspan="8" class="text-center text-danger">Error loading requests. Please try again.</td>`;
    }
}

let currentRequestId = null;

async function fetchRequestDetailsForModal(requestId) {
    currentRequestId = requestId;
    try {
        const response = await fetch(`${API_BASE_URL}get_request_details.php?request_id=${requestId}`);
        const details = await response.json();

        if (details.error) {
            console.error('Error fetching request details:', details.error);
            alert('Error fetching request details: ' + details.error);
            return;
        }

        document.getElementById('modalRequestId').textContent = details.req_id;
        document.getElementById('modalStudentName').textContent = `${details.firstname} ${details.lastname}`;
        document.getElementById('modalStudentNo').textContent = details.student_no || 'N/A';
        document.getElementById('modalStudentProgram').textContent = details.program || 'N/A';
        document.getElementById('modalRequestDate').textContent = details.req_date;
        document.getElementById('modalEnrollmentPurpose').textContent = details.enrollment_purpose || 'N/A';
        document.getElementById('modalClaimStub').textContent = details.claim_stub ? 'Yes' : 'No';
        document.getElementById('modalStudentRemarks').textContent = details.student_remarks || 'No remarks provided.';
        document.getElementById('modalRequestedDocuments').textContent = details.requested_documents || 'No documents requested.';
        
        const officeRemarksInput = document.getElementById('modalOfficeRemarks');
        officeRemarksInput.value = details.office_remarks || '';

        const viewConsentFileButton = document.getElementById('viewConsentFileButton');
        if (details.consent_letter_exists) {
            viewConsentFileButton.style.display = 'inline-block';
            viewConsentFileButton.onclick = () => window.open(`${API_BASE_URL}get_consent_file.php?request_id=${requestId}`, '_blank');
        } else {
            viewConsentFileButton.style.display = 'none';
        }

        const currentStatusDisplay = document.getElementById('modalCurrentStatus');
        currentStatusDisplay.textContent = details.status_code || 'PENDING';
        currentStatusDisplay.className = '';
        if (details.status_code === 'APPROVED') {
            currentStatusDisplay.classList.add('text-success', 'fw-bold');
        } else if (details.status_code === 'REJECTED') {
            currentStatusDisplay.classList.add('text-danger', 'fw-bold');
        } else {
            currentStatusDisplay.classList.add('text-warning', 'fw-bold');
        }

    } catch (error) {
        console.error('Failed to fetch request details:', error);
        alert('Error loading request details. Please try again.');
    }
}

async function saveOfficeRemarks() {
    if (!currentRequestId) {
        alert('No request selected.');
        return;
    }

    const officeRemarks = document.getElementById('modalOfficeRemarks').value;

    try {
        const response = await fetch(`${API_BASE_URL}update_office_remark.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                req_id: currentRequestId,
                office_remarks: officeRemarks
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Office remarks saved successfully!');
            fetchClearanceRequests();
            fetchRequestDetailsForModal(currentRequestId);
        } else {
            alert('Failed to save office remarks: ' + result.error);
        }
    } catch (error) {
        console.error('Error saving office remarks:', error);
        alert('An error occurred while saving remarks. Please try again.');
    }
}

async function updateRequestStatus(statusCode) {
    if (!currentRequestId) {
        alert('No request selected.');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}update_request_status.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                req_id: currentRequestId,
                status_code: statusCode
            })
        });

        const result = await response.json();

        if (result.success) {
            alert(`Request status updated to ${statusCode}!`);
            fetchClearanceRequests();
            fetchRequestDetailsForModal(currentRequestId);
        } else {
            alert('Failed to update request status: ' + result.error);
        }
    } catch (error) {
        console.error('Error updating request status:', error);
        alert('An error occurred while updating status. Please try again.');
    }
}

function logout() {
    fetch(`${API_BASE_URL}logout.php`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'login.html';
            } else {
                alert('Logout failed: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error during logout:', error);
            alert('An error occurred during logout.');
        });
}

const logoutButton = document.getElementById('logoutButton');
if (logoutButton) {
    logoutButton.addEventListener('click', logout);
}