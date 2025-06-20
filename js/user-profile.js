document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    if (sidebar && mainContent) {
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
    }


    const deleteProfileBtn = document.getElementById('deleteProfileBtn');
    const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
    const closeButton = document.querySelector('.close-button');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    if (!deleteProfileBtn || !deleteConfirmationModal || !closeButton || !confirmDeleteBtn || !cancelDeleteBtn) {
        console.error("One or more modal elements not found. Deletion functionality will not work.");
        return;
    }


    function showModal() {
        deleteConfirmationModal.style.display = 'flex';
        deleteConfirmationModal.offsetWidth;
        deleteConfirmationModal.classList.add('fade-in');
    }

    function hideModal() {
        deleteConfirmationModal.classList.remove('fade-in');
        deleteConfirmationModal.classList.add('fade-out');
        setTimeout(() => {
            deleteConfirmationModal.style.display = 'none';
            deleteConfirmationModal.classList.remove('fade-out');
        }, 300);
    }

    deleteProfileBtn.addEventListener('click', showModal);

    closeButton.addEventListener('click', hideModal);
    cancelDeleteBtn.addEventListener('click', hideModal);

    deleteConfirmationModal.addEventListener('click', (event) => {
        if (event.target === deleteConfirmationModal) {
            hideModal();
        }
    });

confirmDeleteBtn.addEventListener('click', () => {
    fetch('../php/delete_account.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'confirm_delete=true'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error('Network response was not ok: ' + response.status + ' ' + text); });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(data.message || "Account deleted successfully!");
            window.location.href = '../pages/login.php';
        } else {
            alert("Error deleting account: " + (data.message || "An unknown error occurred."));
            hideModal();
        }
    })
    .catch(error => {
        console.error('Error during account deletion:', error);
        alert("An error occurred during deletion. Please try again. Check console for details.");
        hideModal();
    });
});

    const formInputs = document.querySelectorAll('.form-row input, .form-row select');

    formInputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.classList.add('is-focused');
        });

        input.addEventListener('blur', () => {
            input.classList.remove('is-focused');
            if (input.value.trim() !== '') {
                input.classList.add('has-content');
            } else {
                input.classList.remove('has-content');
            }
        });

        if (input.value.trim() !== '') {
            input.classList.add('has-content');
        }
    });
});