document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (sidebar && mainContent) {
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
    }

    // Script to disable select if checkbox is not checked
    const checkboxSelectGroups = document.querySelectorAll('.checkbox-select-group');

    checkboxSelectGroups.forEach(group => {
        const checkbox = group.querySelector('input[type="checkbox"]');
        const select = group.querySelector('select');

        if (checkbox && select) {
            // Initial state on page load (and after form submission with errors)
            // If the checkbox is checked from PHP (meaning its value was "on" in $_POST), enable the select.
            select.disabled = !checkbox.checked;

            // Add event listener to checkbox
            checkbox.addEventListener('change', () => {
                select.disabled = !checkbox.checked;
                // Optional: Reset select value when disabled
                if (select.disabled) {
                    select.value = ""; // Reset to the default disabled option
                }
            });
        }
    });

    // Handle temporary success/error messages (if any CSS for .alert is needed)
    // You might want to add CSS for .alert.success-alert and .alert.error-alert
    // Example: Hide alerts after a few seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                alert.remove();
            }, 500); // Remove after transition
        }, 5000); // 5 seconds
    });
});