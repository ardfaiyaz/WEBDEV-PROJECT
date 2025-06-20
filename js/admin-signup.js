document.addEventListener('DOMContentLoaded', function() {
    const passwordToggles = document.querySelectorAll('.password-toggle-icon');
    const signupForm = document.querySelector('.signup-form');
    const header = document.querySelector('.header');
    const formItems = document.querySelectorAll('.form-item');
    const submitButton = document.getElementById('adminSubmitButton');
    const notificationContainer = document.getElementById('notification-container');

    function showNotification(message, type, heading = '') {
        if (notificationContainer) {
            notificationContainer.innerHTML = '';
        }

        const notification = document.createElement('div');
        notification.classList.add('notification', `notification-${type}`);
        notification.innerHTML = `
            <div class="notification-header">
                ${type === 'danger' ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-check-circle"></i>'}
                <strong>${heading || (type === 'danger' ? 'Error!' : 'Success!')}</strong>
                <button class="close-btn">&times;</button>
            </div>
            <div class="notification-body">${message}</div>
        `;

        if (notificationContainer) {
            notificationContainer.appendChild(notification);
            void notification.offsetWidth;
            notification.classList.add('show');

            notification.querySelector('.close-btn').addEventListener('click', function() {
                hideNotification(notification);
            });

            setTimeout(() => {
                if (notification.parentNode) {
                    hideNotification(notification);
                }
            }, type === 'success' ? 3000 : 5000);
        } else {
            console.error("Notification container not found. Cannot display notification.");
            alert(`${heading || (type === 'danger' ? 'Error!' : 'Success!')}\n\n${message}`);
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
            const passwordInput = this.closest('.password-input-container').querySelector('input');

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
        setTimeout(() => {
            header.style.transition = 'opacity 1s ease-out';
            header.style.opacity = '1';
        }, 100);
    }

    formItems.forEach((item, index) => {
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

    if (notificationContainer) {
        const initialNotification = notificationContainer.querySelector('.notification.show');
        if (initialNotification) {
            initialNotification.querySelector('.close-btn').addEventListener('click', function() {
                hideNotification(initialNotification);
            });

            const isSuccess = initialNotification.classList.contains('notification-success');
            setTimeout(() => {
                if (initialNotification.parentNode) {
                    hideNotification(initialNotification);
                }
            }, isSuccess ? 3000 : 5000);
        }
    }

    formItems.forEach(item => {
        const input = item.querySelector('input, select');
        const label = item.querySelector('label');
        if (input && label) {
            if (input.tagName === 'INPUT' && input.value.length > 0) {
                label.style.top = '0px';
                label.style.fontSize = '12px';
                label.style.color = '#29227c';
                label.style.transform = 'translateY(-50%) scale(0.9)';
                label.style.backgroundColor = 'white';
                label.style.padding = '0 5px';
            }
            else if (input.tagName === 'SELECT' && input.value !== "") {
                label.style.top = '0px';
                label.style.fontSize = '12px';
                label.style.color = '#29227c';
                label.style.transform = 'translateY(-50%) scale(0.9)';
                label.style.backgroundColor = 'white';
                label.style.padding = '0 5px';
            }
        }
    });

});