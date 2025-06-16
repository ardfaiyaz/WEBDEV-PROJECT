document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle logic (ensure these elements exist in user-profile.php)
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    // Check if elements exist before adding event listener
    if (sidebar && mainContent) {
        // Assuming there's a button or another mechanism to toggle this.
        // If sidebar itself is clicked to toggle, this works:
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
        // If you have a specific button for sidebar toggle, bind to that instead.
    }


    // --- JavaScript for Delete Button and Modal ---
    const deleteProfileBtn = document.getElementById('deleteProfileBtn');
    const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
    const closeButton = document.querySelector('.close-button'); // Corrected selector for inner close button
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    // Ensure all necessary elements for the modal exist before trying to bind events
    if (!deleteProfileBtn || !deleteConfirmationModal || !closeButton || !confirmDeleteBtn || !cancelDeleteBtn) {
        console.error("One or more modal elements not found. Deletion functionality will not work.");
        return; // Exit if essential elements are missing
    }


    // Function to show modal with animation
    function showModal() {
        deleteConfirmationModal.style.display = 'flex'; // Make it flex so justify/align work
        // Force reflow to ensure transition plays from the start
        deleteConfirmationModal.offsetWidth; // Trigger reflow
        deleteConfirmationModal.classList.add('fade-in');
    }

    // Function to hide modal with animation
    function hideModal() {
        deleteConfirmationModal.classList.remove('fade-in');
        deleteConfirmationModal.classList.add('fade-out');
        setTimeout(() => {
            deleteConfirmationModal.style.display = 'none'; // Hide it fully after animation
            deleteConfirmationModal.classList.remove('fade-out'); // Clean up class
        }, 300); // Match CSS transition duration
    }

    // Show the modal when delete button is clicked
    deleteProfileBtn.addEventListener('click', showModal);

    // Hide the modal when close button or cancel button is clicked
    closeButton.addEventListener('click', hideModal);
    cancelDeleteBtn.addEventListener('click', hideModal);

    // Hide modal if clicked outside of modal content
    deleteConfirmationModal.addEventListener('click', (event) => { // Bind directly to the modal overlay
        if (event.target === deleteConfirmationModal) { // Use strict equality to check if click was on the overlay itself
            hideModal();
        }
    });

    // Handle the actual deletion via AJAX
    // Handle the actual deletion via AJAX
confirmDeleteBtn.addEventListener('click', () => {
    // Send an AJAX request to the PHP script that securely deletes the user's account from the database.
    fetch('../php/delete_account.php', { // Path to your PHP script
        method: 'POST', // Use POST method for sensitive actions
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded' // Or 'application/json' if sending JSON in body
        },
        body: 'confirm_delete=true' // A simple way to tell PHP this is a confirmed delete request
    })
    .then(response => {
        // Check if the network response was successful (e.g., status 200-299)
        if (!response.ok) {
            // If response is not OK, throw an error to be caught by .catch()
            // This is crucial for catching 404s, 500s from delete_account.php
            return response.text().then(text => { throw new Error('Network response was not ok: ' + response.status + ' ' + text); });
        }
        return response.json(); // Parse the JSON response from PHP
    })
    .then(data => {
        if (data.success) {
            // Account deleted successfully
            alert(data.message || "Account deleted successfully!");
            // Redirect to the login page on success
            window.location.href = '../pages/login.php'; // Assuming login.php is in the same 'pages' directory
        } else {
            // Error deleting account (message from PHP)
            alert("Error deleting account: " + (data.message || "An unknown error occurred."));
            hideModal(); // Hide the modal, but keep the user on the profile page
        }
    })
    .catch(error => {
        // Catch network errors (like 404 for delete_account.php) or errors thrown in the .then() block
        console.error('Error during account deletion:', error);
        alert("An error occurred during deletion. Please try again. Check console for details.");
        hideModal();
    });

    // --- REMOVE OR COMMENT OUT THIS TEMPORARY DEMO BEHAVIOR ---
    // alert("Account deletion initiated! (This is a client-side demo)");
    // window.location.href = 'logout.php'; // Or to login.php
    // --- END TEMPORARY DEMO BEHAVIOR ---
});

    // --- New JavaScript for Input Animations ---
    // Ensure this runs after DOM is loaded. It's already inside DOMContentLoaded.
    const formInputs = document.querySelectorAll('.form-row input, .form-row select');

    formInputs.forEach(input => {
        // Add a class when the input is focused
        input.addEventListener('focus', () => {
            input.classList.add('is-focused');
        });

        // Remove the class when the input loses focus,
        // and add 'has-content' if it has a value
        input.addEventListener('blur', () => {
            input.classList.remove('is-focused');
            if (input.value.trim() !== '') {
                input.classList.add('has-content');
            } else {
                input.classList.remove('has-content');
            }
        });

        // Initial check on page load if inputs already have values (e.g., from PHP echo)
        if (input.value.trim() !== '') {
            input.classList.add('has-content');
        }
    });
});