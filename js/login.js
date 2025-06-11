// pages/js/login.js
// This file can be used for client-side validation or other dynamic functionalities
// For now, the form submission is handled directly by the HTML form's action attribute to the PHP file.

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function(event) {
        // You can add client-side validation here before submitting the form
        // For example:
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const accountType = document.getElementById('accountType');

        if (!emailInput.value || !passwordInput.value || !accountType.value) {
            alert('Please fill in all required fields.');
            event.preventDefault(); // Stop form submission if validation fails
        }
        // Additional validation can be added here (e.g., email format, password strength)
    });
});