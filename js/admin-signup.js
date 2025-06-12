document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.querySelector('.signup-form');
    const passwordToggleIcons = document.querySelectorAll('.password-toggle-icon');
    const header = document.querySelector('.header');
    const formItems = document.querySelectorAll('.form-item');
    const submitButton = document.getElementById('adminSubmitButton');

    // --- Client-Side Form Submission Validation (Password Match) ---
    signupForm.addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            event.preventDefault(); // Prevent form submission
            alert("Passwords do not match. Please try again."); // Using alert as requested
            return;
        }

        // If validation passes, allow form submission or redirection
        // In a real application, you'd typically send data to a server here via fetch/XHR
        // If the form has an 'action' attribute, it will submit normally after this point.
        // If you intended to handle submission purely via JS, you'd add:
        // event.preventDefault();
        // And then your fetch/XHR logic here.
        // For now, it will proceed with the default form submission to the action defined (if any)
        // or redirect as per your original HTML's logic (though no action is defined in the provided HTML).
        // If you want to redirect after success, you can put it here:
        // window.location.href = 'index.html'; // Example redirect
    });


    // --- Password Toggle Animation ---
    passwordToggleIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const passwordInput = this.closest('.password-input-container').querySelector('input');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');

            // Add a small bounce/scale animation
            this.classList.add('icon-pop');
            setTimeout(() => {
                this.classList.remove('icon-pop');
            }, 200); // Duration matches CSS transition
        });
    });

    // --- Header & Logo Fade-In ---
    // Ensure header starts hidden via CSS or inline style if JS might run late
    // In admin-signup.css, we've set opacity: 0; for .header
    setTimeout(() => {
        header.style.transition = 'opacity 1s ease-out';
        header.style.opacity = '1';
    }, 100);

    // --- Form Fields Slide & Fade-In (Staggered) ---
    formItems.forEach((item, index) => {
        // Ensure items start hidden via CSS or inline style if JS might run late
        // In admin-signup.css, we've set opacity: 0; and transform: translateY(20px); for .form-item
        setTimeout(() => {
            item.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            item.style.opacity = 1;
            item.style.transform = 'translateY(0)';
        }, 300 + (index * 100)); // Stagger after header fades in
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
});