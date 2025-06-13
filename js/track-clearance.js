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

    // Handle temporary success messages
    // You'll want to ensure you have CSS for .alert.success-alert in track-clearance.css
    const successAlert = document.querySelector('.alert.success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                successAlert.remove();
            }, 500); // Remove after transition
        }, 5000); // 5 seconds
    }
});