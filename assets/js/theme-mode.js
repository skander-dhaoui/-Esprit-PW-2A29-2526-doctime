(function () {
    const STORAGE_KEY = 'valorys_theme_mode';
    const root = document.documentElement;

    function getTheme() {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === 'dark' || stored === 'light' ? stored : 'light';
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        root.style.colorScheme = theme;
        updateButtons(theme);
    }

    function updateButtons(theme) {
        const isDark = theme === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-label', isDark ? 'Passer au mode normal' : 'Passer au mode sombre');
            button.setAttribute('title', isDark ? 'Mode normal' : 'Mode sombre');
            button.innerHTML = isDark
                ? '<i class="fas fa-sun"></i><span>Normal</span>'
                : '<i class="fas fa-moon"></i><span>Sombre</span>';
        });
    }

    function toggleTheme() {
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem(STORAGE_KEY, next);
        applyTheme(next);
    }

    function createButton(extraClass = '') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = ('theme-toggle ' + extraClass).trim();
        button.setAttribute('data-theme-toggle', '');
        return button;
    }

    function ensureToggle() {
        if (document.querySelector('[data-theme-toggle]')) {
            updateButtons(root.getAttribute('data-theme') || getTheme());
            return;
        }

        const pageHeader = document.querySelector('.page-header');
        if (pageHeader) {
            const button = createButton('theme-toggle-nav');
            const rightSide = pageHeader.querySelector('.admin-avatar, .navbar-user, div:last-child');
            if (rightSide && rightSide.parentElement === pageHeader) {
                pageHeader.insertBefore(button, rightSide);
            } else {
                pageHeader.appendChild(button);
            }
            updateButtons(root.getAttribute('data-theme') || getTheme());
            return;
        }

        const navList = document.querySelector('.navbar .navbar-nav.ms-auto, .navbar .navbar-nav:last-child');
        if (navList) {
            const item = document.createElement('li');
            item.className = 'nav-item d-flex align-items-center';
            item.appendChild(createButton('theme-toggle-nav'));
            navList.insertBefore(item, navList.firstChild);
            updateButtons(root.getAttribute('data-theme') || getTheme());
            return;
        }

        document.body.appendChild(createButton('theme-toggle-fixed'));
        updateButtons(root.getAttribute('data-theme') || getTheme());
    }

    applyTheme(getTheme());

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-theme-toggle]');
        if (!button) return;
        event.preventDefault();
        toggleTheme();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureToggle);
    } else {
        ensureToggle();
    }
})();
