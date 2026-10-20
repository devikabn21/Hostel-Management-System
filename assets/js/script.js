document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuButton && sidebar) {
        menuButton.addEventListener('click', () => {
            const isOpen = sidebar.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 760 && sidebar.classList.contains('open')
                && !sidebar.contains(event.target) && !menuButton.contains(event.target)) {
                sidebar.classList.remove('open');
                menuButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('.notice-close').forEach((button) => {
        button.addEventListener('click', () => button.closest('.notice').remove());
    });
});