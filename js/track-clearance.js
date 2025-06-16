document.addEventListener('DOMContentLoaded', () => {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (sidebar && mainContent) {
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
    }

    // Handle temporary success messages
    const successAlert = document.querySelector('.alert.success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                successAlert.remove();
            }, 500); // Remove after transition
        }, 5000); // 5 seconds
    }

    // Pop up functionality (Modal)
    const statusModal = document.getElementById('statusModal');
    const closeButton = document.querySelector('.close-button');
    const officeCardButtons = document.querySelectorAll('.office-card-button');
    const modalOfficeName = document.getElementById('modalOfficeName');
    const modalStatus = document.getElementById('modalStatus');
    const modalRemarksDisplay = document.getElementById('modalRemarksDisplay'); // New ID for displayed remarks
    const modalActionDisplay = document.getElementById('modalActionDisplay'); // New ID for displayed action

    officeCardButtons.forEach(button => {
        button.addEventListener('click', () => {
            const officeName = button.dataset.officeName;
            const statusCode = button.dataset.statusCode;
            // Get the remarks and action that were prepared in PHP
            const remarksToDisplay = button.dataset.remarksDisplay;
            const actionToDisplay = button.dataset.actionDisplay;

            // Display modal for all clickable statuses
            // You can adjust this condition if you only want 'ISSUE' to be clickable
            if (statusCode === 'PEND' || statusCode === 'ON' || statusCode === 'ISSUE' || statusCode === 'COMP') {
                modalOfficeName.textContent = officeName;
                modalStatus.textContent = getStatusDescription(statusCode);
                modalRemarksDisplay.textContent = remarksToDisplay;
                modalActionDisplay.textContent = actionToDisplay;
                statusModal.style.display = 'flex'; // Use 'flex' for centering with CSS
            } else {
                console.log(`Status for ${officeName} is ${statusCode}. Modal not shown.`);
            }
        });
    });

    // Close the modal when the close button is clicked
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            statusModal.style.display = 'none';
        });
    }

    // Close the modal if the user clicks outside of the modal content
    window.addEventListener('click', (event) => {
        if (event.target === statusModal) {
            statusModal.style.display = 'none';
        }
    });

    // Helper function to get descriptive status from code (for modal display)
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