document.addEventListener('DOMContentLoaded', function() {
    const passwordToggles = document.querySelectorAll('.password-toggle-icon');
    const signupForm = document.querySelector('.signup-form');
    const header = document.querySelector('.header');
    const formItems = document.querySelectorAll('.form-item');
    const submitButton = document.getElementById('userSubmitButton');
    const notificationContainer = document.getElementById('notification-container');

    const phpData = window.phpData || {};

    function showNotification(message, type, heading = '', redirectToLogin = false) {
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

        void notification.offsetWidth;

        notification.classList.add('show');

        notification.querySelector('.close-btn').addEventListener('click', function() {
            hideNotification(notification);
        });

        if (type === 'success' && redirectToLogin) {
            setTimeout(() => {
                if (notification.parentNode) {
                    hideNotification(notification);
                }
                console.log("Auto-redirecting to login.php after timeout...");
                window.location.href = '../pages/login.php';
            }, 3000);
        } else if (type === 'success') {
            setTimeout(() => hideNotification(notification), 5000);
        }
    }

    function hideNotification(notificationElement) {
        notificationElement.classList.remove('show');
        notificationElement.classList.add('hide');
        notificationElement.addEventListener('animationend', () => {
            notificationElement.remove();
        }, { once: true });
    }

    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.closest('.password-input-container') ?
                                     this.closest('.password-input-container').querySelector('input') :
                                     this.previousElementSibling;

            if (passwordInput) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');

                this.classList.add('icon-pop');
                setTimeout(() => {
                    this.classList.remove('icon-pop');
                }, 200);
            }
        });
    });

    if (signupForm) {
        signupForm.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                event.preventDefault();
                showNotification('Your passwords do not match. Please try again.', 'danger', 'Password Mismatch');
            }
        });
    }

    if (header) {
        header.style.opacity = '0';
        setTimeout(() => {
            header.style.transition = 'opacity 1s ease-out';
            header.style.opacity = '1';
        }, 100);
    }

    formItems.forEach((item, index) => {
        item.style.opacity = 0;
        item.style.transform = 'translateY(20px)';
        setTimeout(() => {
            item.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            item.style.opacity = 1;
            item.style.transform = 'translateY(0)';
        }, 300 + (index * 100));
    });

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

    if (phpData.hasErrors) {
        showNotification(phpData.errorMessageHtml, 'danger', 'Signup Failed');
    } else if (phpData.successMessage) {
        const redirectToLogin = phpData.redirectToLogin || false;
        showNotification(phpData.successMessage, 'success', '', redirectToLogin);
    }
});