document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));

    if (!input) {
        return;
    }

    button.addEventListener('click', () => {
        const showing = input.type === 'password';
        const fieldName = input.id === 'password_confirmation' ? 'confirm password' : 'password';

        input.type = showing ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(showing));
        button.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} ${fieldName}`);
        button.title = `${showing ? 'Hide' : 'Show'} ${fieldName}`;
    });
});

document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('[data-toggle-password]').forEach((button) => {
            const input = document.getElementById(button.getAttribute('aria-controls'));

            if (input) {
                input.type = 'password';
            }
        });
    });
});
