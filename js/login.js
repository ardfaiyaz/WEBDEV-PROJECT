document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', function(event) {
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const accountType = document.getElementById('accountType');

        if (!emailInput.value || !passwordInput.value || !accountType.value) {
            alert('Please fill in all required fields.');
            event.preventDefault(); 
        }
    });
});



    