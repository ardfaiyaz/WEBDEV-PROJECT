document.addEventListener('DOMContentLoaded', function() {
    const passwordToggles = document.querySelectorAll('.password-toggle-icon');

    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            // Toggle the eye icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });
});



    if (message) {
        console.log("Message is not empty, attempting to show notification.");
        notificationTitle.textContent = "Success!";
        notificationMessage.textContent = message;

        notification.style.display = 'flex';

        notificationOkButton.onclick = function() {
            notification.style.display = 'none';
            if (redirectToLogin) {
                console.log("Redirecting to login.html...");
                window.location.href = '../pages/login.html';
            }
        };

        if (redirectToLogin) {
            setTimeout(() => {
                if (notification.style.display === 'flex') {
                    console.log("Auto-redirecting to login.html after timeout...");
                    window.location.href = '../pages/login.html';
                }
            }, 3000);
        }

    } else {
        console.log("Message is empty, notification will remain hidden.");
        notification.style.display = 'none';
    }

