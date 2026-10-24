import './bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import './theme';
import './admin-users';
import './student-activity-controls';
import './student-fullscreen';
import './responsive-tables';

// Browsers may restore a protected page from the back-forward cache after
// logout. Reload it so Laravel can redirect the now-guest session to login.
window.addEventListener('pageshow', (event) => {
    if (event.persisted && document.body.hasAttribute('data-authenticated-page')) {
        window.location.reload();
    }
});

const showRequiredTwoFactorAlert = async (message, setupUrl) => {
    const darkMode = document.documentElement.dataset.theme === 'dark';
    const result = await Swal.fire({
        title: 'Two-factor authentication required',
        text: message,
        icon: 'warning',
        confirmButtonText: 'Set up now',
        confirmButtonColor: '#b45309',
        background: darkMode ? '#1b293b' : '#fff',
        color: darkMode ? '#eff4fb' : '#172033',
        allowOutsideClick: false,
        allowEscapeKey: false,
    });

    if (result.isConfirmed && setupUrl) {
        window.location.assign(setupUrl);
    }
};

const requiredTwoFactorAlert = document.querySelector('[data-two-factor-required-alert]');
if (requiredTwoFactorAlert) {
    showRequiredTwoFactorAlert(requiredTwoFactorAlert.dataset.message, requiredTwoFactorAlert.dataset.setupUrl);
}

document.querySelectorAll('[data-two-factor-sidebar-link]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        showRequiredTwoFactorAlert(
            'Complete authenticator setup before using the system. Continue to the setup page to unlock the sidebar.',
            link.dataset.setupUrl,
        );
    });
});

const loadCodeEditor = () => {
    if (!document.querySelector('[data-code-editor]')) return;
    import('./student-code-editor').then(({ initStudentCodeEditor }) => initStudentCodeEditor()).catch(() => {
        // The textarea remains available if the editor cannot load.
    });
};
loadCodeEditor();
document.addEventListener('student-activity-ready', loadCodeEditor);

const reviewStudent = document.querySelector('[data-review-student-select]');
const reviewSubmission = document.querySelector('[data-review-submission-select]');
const reviewLines = [...document.querySelectorAll('[data-review-line]')];
const selectedLineCount = document.querySelector('[data-selected-line-count]');

reviewStudent?.addEventListener('change', () => {
    const url = new URL(window.location.href);
    if (reviewStudent.value) url.searchParams.set('student_id', reviewStudent.value);
    else url.searchParams.delete('student_id');
    url.searchParams.delete('code_submission_id');
    window.location.assign(url);
});

reviewSubmission?.addEventListener('change', () => {
    const url = new URL(window.location.href);
    if (reviewSubmission.value) url.searchParams.set('code_submission_id', reviewSubmission.value);
    else url.searchParams.delete('code_submission_id');
    window.location.assign(url);
});

const updateSelectedLines = () => {
    const count = reviewLines.filter((input) => input.checked).length;
    if (selectedLineCount) selectedLineCount.textContent = count ? `${count} line${count === 1 ? '' : 's'} selected.` : 'No lines selected.';
    reviewLines.forEach((input) => input.closest('li')?.classList.toggle('is-selected', input.checked));
};

reviewLines.forEach((input) => {
    input.addEventListener('change', () => {
        const outcome = document.querySelector('#review_outcome');
        if (outcome && input.checked) outcome.value = 'incorrect';
        updateSelectedLines();
    });
});
updateSelectedLines();

const logoutForm = document.querySelector('[data-confirm-logout]');
const accountMenu = document.querySelector('[data-account-menu]');

if (accountMenu) {
    document.addEventListener('click', (event) => {
        if (accountMenu.open && !accountMenu.contains(event.target)) accountMenu.open = false;
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && accountMenu.open) {
            accountMenu.open = false;
            accountMenu.querySelector('summary')?.focus();
        }
    });
}

if (logoutForm) {
    logoutForm.addEventListener('submit', async (event) => {
        if (logoutForm.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();

        const darkMode = document.documentElement.dataset.theme === 'dark';
        const result = await Swal.fire({
            title: 'Log out?',
            text: 'Are you sure you want to log out?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, log out',
            cancelButtonText: 'Stay signed in',
            confirmButtonColor: '#b42318',
            cancelButtonColor: darkMode ? '#405169' : '#172033',
            background: darkMode ? '#1b293b' : '#fff',
            color: darkMode ? '#eff4fb' : '#172033',
            reverseButtons: true,
            focusCancel: true,
        });

        if (result.isConfirmed) {
            logoutForm.dataset.confirmed = 'true';
            logoutForm.requestSubmit();
        }
    });
}

document.querySelectorAll('[data-confirm-clear-logs]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();

        const darkMode = document.documentElement.dataset.theme === 'dark';
        const result = await Swal.fire({
            title: 'Clear all system logs?',
            text: `This will permanently remove all ${form.dataset.logCount} system logs. This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear all logs',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#b42318',
            cancelButtonColor: darkMode ? '#405169' : '#172033',
            background: darkMode ? '#1b293b' : '#fff',
            color: darkMode ? '#eff4fb' : '#172033',
            reverseButtons: true,
            focusCancel: true,
        });

        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    });
});
