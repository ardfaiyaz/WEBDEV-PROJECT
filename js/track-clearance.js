document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (sidebar && mainContent) {
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
    }

    const successAlert = document.querySelector('.alert.success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                successAlert.remove();
            }, 500);
        }, 5000);
    }

    const statusModal = document.getElementById('statusModal');
    const closeButton = document.querySelector('.close-button');
    const officeCardButtons = document.querySelectorAll('.office-card-button');
    const modalOfficeName = document.getElementById('modalOfficeName');
    const modalStatus = document.getElementById('modalStatus');
    const modalRemarksDisplay = document.getElementById('modalRemarksDisplay');
    const modalActionDisplay = document.getElementById('modalActionDisplay');

    officeCardButtons.forEach(button => {
        button.addEventListener('click', () => {
            const officeName = button.dataset.officeName;
            const statusCode = button.dataset.statusCode;
            const remarksToDisplay = button.dataset.remarksDisplay;
            const actionToDisplay = button.dataset.actionDisplay;

            if (statusCode === 'PEND' || statusCode === 'ON' || statusCode === 'ISSUE' || statusCode === 'COMP') {
                modalOfficeName.textContent = officeName;
                modalStatus.textContent = getStatusDescription(statusCode);
                modalRemarksDisplay.textContent = remarksToDisplay;
                modalActionDisplay.textContent = actionToDisplay;
                statusModal.style.display = 'flex';
            } else {
                console.log(`Status for ${officeName} is ${statusCode}. Modal not shown.`);
            }
        });
    });

    if (closeButton) {
        closeButton.addEventListener('click', () => {
            statusModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === statusModal) {
            statusModal.style.display = 'none';
        }
    });

    function getStatusDescription(statusCode) {
        switch (statusCode) {
            case 'PEND': return 'Pending';
            case 'ON': return 'On-Going';
            case 'ISSUE': return 'Issue Found';
            case 'COMP': return 'Completed';
            default: return 'Unknown';
        }
    }
});