document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.querySelector('.signup-form');
    const passwordToggleIcons = document.querySelectorAll('.password-toggle-icon');
    const header = document.querySelector('.header');
    const formItems = document.querySelectorAll('.form-item');
    const submitButton = document.getElementById('userSubmitButton');
    const notificationContainer = document.getElementById('notification-container');

    // Access PHP data from the global window.phpData object
    const phpData = window.phpData || {}; // Ensure it exists, default to empty object

    // --- Function to display notification ---
    function showNotification(message, type, heading = '') {
        const notification = document.createElement('div');
        notification.classList.add('notification', `notification-${type}`);
        notification.innerHTML = `
            <div class="notification-header">
                ${type === 'danger' ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-check-circle"></i>'}
                <strong>${heading || (type === 'danger' ? 'Error!' : 'Success!')}</strong>
                <button class="close-btn">&times;</button>
            </div>
            <div class="notification-body">${message}</div>
        `;
        notificationContainer.appendChild(notification);

        // Force reflow to ensure animation plays
        void notification.offsetWidth;

        notification.classList.add('show'); // Trigger slide-in animation

        // Close button functionality
        notification.querySelector('.close-btn').addEventListener('click', function() {
            hideNotification(notification);
        });

        // Auto-hide after a delay for success messages
        if (type === 'success') {
            setTimeout(() => hideNotification(notification), 5000); // 5 seconds
        }
    }

    // --- Function to hide notification ---
    function hideNotification(notificationElement) {
        notificationElement.classList.remove('show');
        notificationElement.classList.add('hide'); // Trigger slide-out animation
        notificationElement.addEventListener('animationend', () => {
            notificationElement.remove();
        }, { once: true }); // Remove after animation ends
    }

    // --- Client-Side Form Submission Validation (Password Match) ---
    signupForm.addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            // Prevent form submission if passwords don't match
            event.preventDefault();
            // Display a specific notification for this client-side error
            showNotification('Your passwords do not match. Please try again.', 'danger', 'Password Mismatch');
        }
    });

    // --- Password Toggle Animation ---
    passwordToggleIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const passwordInput = this.closest('.password-input-container').querySelector('input');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');

            this.classList.add('icon-pop');
            setTimeout(() => {
                this.classList.remove('icon-pop');
            }, 200);
        });
    });

    // --- Header & Logo Fade-In ---
    header.style.opacity = '0';
    setTimeout(() => {
        header.style.transition = 'opacity 1s ease-out';
        header.style.opacity = '1';
    }, 100);

    // --- Form Fields Slide & Fade-In (Staggered) ---
    formItems.forEach((item, index) => {
        item.style.opacity = 0;
        item.style.transform = 'translateY(20px)';
        setTimeout(() => {
            item.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            item.style.opacity = 1;
            item.style.transform = 'translateY(0)';
        }, 300 + (index * 100));
    });

    // --- Submit Button Click Effect ---
    if (submitButton) {
        submitButton.addEventListener('mousedown', () => {
            submitButton.classList.add('button-pressed');
        });
        submitButton.addEventListener('mouseup', () => {
            submitButton.classList.remove('button-pressed');
        });
        submitButton.addEventListener('mouseleave', () => {
            submitButton.classList.remove('button-pressed');
        });
    }

    // --- Display PHP messages as pop-ups using the phpData object ---
    if (phpData.hasErrors) {
        showNotification(phpData.errorMessageHtml, 'danger', 'Signup Failed');
    } else if (phpData.successMessage) {
        showNotification(phpData.successMessage, 'success');
    }

    // No need for explicit redirect logic here if meta refresh handles it.
    // The previous `if (successMessageDiv)` check and removal of auto-hide
    // for success messages on redirect are not needed with the new notification system
    // and meta refresh.
});