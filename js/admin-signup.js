document.addEventListener('DOMContentLoaded', function() {
    // --- Select all necessary elements once ---
    const passwordToggles = document.querySelectorAll('.password-toggle-icon');
    const signupForm = document.querySelector('.signup-form');
    const header = document.querySelector('.header');
    const formItems = document.querySelectorAll('.form-item');
    const submitButton = document.getElementById('adminSubmitButton');
    const notificationContainer = document.getElementById('notification-container'); // Ensure this ID exists in your HTML

    // --- Function to display notification ---
    function showNotification(message, type, heading = '') {
        // Clear any existing notifications before showing a new one
        if (notificationContainer) {
            notificationContainer.innerHTML = ''; //
        }

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

        if (notificationContainer) {
            notificationContainer.appendChild(notification); //

            // Force reflow to ensure animation plays
            void notification.offsetWidth; //

            notification.classList.add('show'); // Trigger slide-in animation

            // Close button functionality
            notification.querySelector('.close-btn').addEventListener('click', function() {
                hideNotification(notification); //
            });

            // Auto-hide logic
            setTimeout(() => {
                if (notification.parentNode) { // Check if notification is still in the DOM
                    hideNotification(notification); //
                }
            }, type === 'success' ? 3000 : 5000); // 3 seconds for success, 5 for errors
        } else {
            console.error("Notification container not found. Cannot display notification.");
            // Fallback to alert if container is missing (less ideal, but better than nothing)
            alert(`${heading || (type === 'danger' ? 'Error!' : 'Success!')}\n\n${message}`);
        }
    }

    // --- Function to hide notification ---
    function hideNotification(notificationElement) {
        notificationElement.classList.remove('show'); //
        notificationElement.classList.add('hide'); // Trigger slide-out animation
        notificationElement.addEventListener('animationend', () => {
            notificationElement.remove(); //
        }, { once: true }); // Remove after animation ends
    }

    // --- Password Toggle Animation ---
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            // Find the input element within the same password-input-container
            const passwordInput = this.closest('.password-input-container').querySelector('input');

            if (passwordInput) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the eye icon
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');

                // Add pop animation
                this.classList.add('icon-pop');
                setTimeout(() => {
                    this.classList.remove('icon-pop');
                }, 200); // Matches CSS transition duration
            }
        });
    });

    // --- Client-Side Form Submission Validation (Password Match) ---
    if (signupForm) {
        signupForm.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                // Prevent form submission if passwords don't match
                event.preventDefault();
                // Display a specific notification for this client-side error
                showNotification('Your passwords do not match. Please try again.', 'danger', 'Password Mismatch');
            }
            // If passwords match, the form will submit normally to the PHP script.
            // The PHP script will then handle server-side validation and display
            // success/error messages via the meta refresh or the PHP block in HTML.
        });
    }

    // --- Header & Logo Fade-In ---
    if (header) {
        setTimeout(() => {
            header.style.transition = 'opacity 1s ease-out';
            header.style.opacity = '1';
        }, 100);
    }

    // --- Form Fields Slide & Fade-In (Staggered) ---
    formItems.forEach((item, index) => {
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

    // Initial check for PHP-generated notifications on page load
    // The PHP code already adds the 'show' class, so the CSS animation will trigger.
    // We just need to attach the close button listener and auto-hide.
    if (notificationContainer) {
        const initialNotification = notificationContainer.querySelector('.notification.show');
        if (initialNotification) {
            // Attach close button listener
            initialNotification.querySelector('.close-btn').addEventListener('click', function() {
                hideNotification(initialNotification);
            });

            // Determine type for auto-hide duration
            const isSuccess = initialNotification.classList.contains('notification-success');
            setTimeout(() => {
                if (initialNotification.parentNode) {
                    hideNotification(initialNotification);
                }
            }, isSuccess ? 3000 : 5000);
        }
    }
});