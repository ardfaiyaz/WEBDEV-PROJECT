// Sidebar toggle logic
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
sidebar.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
});

// --- JavaScript for Delete Button and Modal ---
const deleteProfileBtn = document.getElementById('deleteProfileBtn');
const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
const closeButton = document.querySelector('.close-button');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

// Function to show modal with animation
function showModal() {
    deleteConfirmationModal.style.display = 'flex'; // Make it flex so justify/align work
    // Force reflow to ensure transition plays from the start
    // This is a common trick to re-trigger CSS transitions
    deleteConfirmationModal.offsetWidth;
    deleteConfirmationModal.classList.add('fade-in');
}

// Function to hide modal with animation
function hideModal() {
    deleteConfirmationModal.classList.remove('fade-in');
    deleteConfirmationModal.classList.add('fade-out');
    setTimeout(() => {
        deleteConfirmationModal.style.display = 'none'; // Hide it fully after animation
        deleteConfirmationModal.classList.remove('fade-out');
    }, 300); // Match CSS transition duration
}

// Show the modal when delete button is clicked
deleteProfileBtn.addEventListener('click', showModal);

// Hide the modal when close button or cancel button is clicked
closeButton.addEventListener('click', hideModal);
cancelDeleteBtn.addEventListener('click', hideModal);

// Hide modal if clicked outside of modal content
window.addEventListener('click', (event) => {
    if (event.target === deleteConfirmationModal) { // Use strict equality
        hideModal();
    }
});

// Handle the actual deletion (THIS IS WHERE YOU NEED PHP!)
confirmDeleteBtn.addEventListener('click', () => {
    // IMPORTANT:
    // This is a client-side only redirection for demonstration.
    // In a real application, you would send an AJAX request to a PHP script
    // that securely deletes the user's account from the database.
    //
    // Example (pseudocode - replace with your actual fetch call):
    /*
    fetch('../php/delete_account.php', { // Path to your PHP script
        method: 'POST', // Or 'DELETE' depending on your API design
        headers: {
            'Content-Type': 'application/json'
        },
        // body: JSON.stringify({ userId: <?php // echo $loggedInUserId; ?> }) // Pass user ID securely if not handled by session
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Account deleted successfully!");
            window.location.href = 'logout.php'; // Redirect to logout or login page
        } else {
            alert("Error deleting account: " + (data.message || "Unknown error."));
        }
    })
    .catch(error => {
        console.error('Error during deletion:', error);
        alert("An error occurred during deletion. Please try again.");
    });
    */

    // --- TEMPORARY DEMO BEHAVIOR ---
    alert("Account deletion initiated! (This is a client-side demo)");
    // After successful deletion (simulated), redirect the user
    window.location.href = 'logout.php'; // Or to login.php
});

// --- New JavaScript for Input Animations ---
document.addEventListener('DOMContentLoaded', () => {
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