document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const accountTypeSelect = document.getElementById('accountType');
    const emailInput = document.getElementById('emailInput');
    const passwordInput = document.getElementById('passwordInput');

    loginForm.addEventListener('submit', function(event) {
        // Prevent the default form submission
        event.preventDefault();

        // Check if account type is selected
        if (accountTypeSelect.value === "") {
            alert('Please select an Account Type.');
            accountTypeSelect.focus(); // Focus on the select element
            return; // Stop the function here
        }

        // Check if email is empty
        if (emailInput.value.trim() === '') {
            alert('Please enter your email.');
            emailInput.focus(); // Focus on the email input
            return; // Stop the function here
        }

        // Check if password is empty
        if (passwordInput.value.trim() === '') {
            alert('Please enter your password.');
            passwordInput.focus(); // Focus on the password input
            return; // Stop the function here
        }

        // If all fields are filled, you can proceed with form submission
        // For demonstration, we'll just log a success message.
        // In a real application, you'd send data to the server here.
        alert('Login successful! (This is a demo, form would submit now)');
        
        // Uncomment the line below to allow the form to actually submit
        // loginForm.submit(); 
    });
});