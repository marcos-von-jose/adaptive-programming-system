import '../css/theme-toggle.css';

const themeRoot = document.documentElement;

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    const updateState = () => {
        button.setAttribute('aria-pressed', String(themeRoot.dataset.theme === 'dark'));
    };

    updateState();

    button.addEventListener('click', () => {
        const theme = themeRoot.dataset.theme === 'dark' ? 'light' : 'dark';
        themeRoot.dataset.theme = theme;

        try {
            localStorage.setItem('aps-theme', theme);
        } catch {
            // The selected theme still applies to the current page.
        }

        updateState();
    });
});
