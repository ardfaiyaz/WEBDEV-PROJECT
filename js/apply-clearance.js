document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    if (sidebar && mainContent) {
        sidebar.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('shifted');
        });
    }

    const checkboxSelectGroups = document.querySelectorAll('.checkbox-select-group');

    checkboxSelectGroups.forEach(group => {
        const checkbox = group.querySelector('input[type="checkbox"]');
        const select = group.querySelector('select');

        if (checkbox && select) {
            select.disabled = !checkbox.checked;

            checkbox.addEventListener('change', () => {
                select.disabled = !checkbox.checked;
                if (select.disabled) {
                    select.value = "";
                }
            });
        }
    });

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});