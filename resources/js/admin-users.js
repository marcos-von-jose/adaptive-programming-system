import Swal from 'sweetalert2';

const dialogColors = () => {
    const darkMode = document.documentElement.dataset.theme === 'dark';

    return {
        confirmButtonColor: '#172033',
        cancelButtonColor: darkMode ? '#405169' : '#718096',
        background: darkMode ? '#1b293b' : '#fff',
        color: darkMode ? '#eff4fb' : '#172033',
    };
};

document.querySelectorAll('[data-view-user]').forEach((button) => {
    button.addEventListener('click', () => {
        const details = document.createElement('dl');
        details.className = 'user-details';

        const fields = [
            ['User ID', button.dataset.userId],
            ['Name', button.dataset.userName],
            ['Email', button.dataset.userEmail],
            ['Role', button.dataset.userRole],
            ['Email status', button.dataset.userEmailStatus],
            ['Two-factor authentication', button.dataset.userTwoFactor],
            ['Joined', button.dataset.userJoined],
        ];

        fields.forEach(([label, value]) => {
            const row = document.createElement('div');
            const term = document.createElement('dt');
            const description = document.createElement('dd');
            term.textContent = label;
            description.textContent = value || '—';
            row.append(term, description);
            details.append(row);
        });

        if (button.dataset.userPhoto) {
            const row = document.createElement('div');
            const term = document.createElement('dt');
            const description = document.createElement('dd');
            const photo = document.createElement('img');
            term.textContent = 'Profile photo';
            photo.className = 'user-details-photo';
            photo.src = button.dataset.userPhoto;
            photo.alt = `Profile photo of ${button.dataset.userName}`;
            description.append(photo);
            row.append(term, description);
            details.append(row);
        }

        Swal.fire({
            title: 'User details',
            html: details,
            width: 'min(560px, 95vw)',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: { htmlContainer: 'user-modal-content' },
            ...dialogColors(),
        });
    });
});

document.querySelectorAll('[data-user-modal]').forEach((link) => {
    link.addEventListener('click', async (event) => {
        const template = document.getElementById(link.dataset.userModal);
        if (!template) {
            return;
        }

        event.preventDefault();

        const content = document.createElement('div');
        content.appendChild(template.content.cloneNode(true));
        const form = content.querySelector('form');
        form.addEventListener('submit', (submitEvent) => {
            submitEvent.preventDefault();
            Swal.clickConfirm();
        });

        const result = await Swal.fire({
            title: link.dataset.userModalTitle,
            html: content,
            width: 'min(680px, 95vw)',
            customClass: { htmlContainer: 'user-modal-content' },
            showCancelButton: true,
            confirmButtonText: link.dataset.userModalConfirm,
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            focusConfirm: false,
            allowOutsideClick: () => !Swal.isLoading(),
            ...dialogColors(),
            didOpen: () => form.querySelector('[name="name"]')?.focus(),
            preConfirm: async () => {
                if (!form.reportValidity()) {
                    return false;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.headers.get('content-type')?.includes('application/json')) {
                        Swal.showValidationMessage('Your session may have expired. Reload the page and try again.');
                        return false;
                    }

                    if (!response.ok) {
                        const message = Object.values(data.errors ?? {}).flat()[0]
                            ?? data.message
                            ?? 'The user could not be saved. Please try again.';
                        Swal.showValidationMessage(message);
                        return false;
                    }

                    return data.message ?? 'User saved successfully.';
                } catch {
                    Swal.showValidationMessage('The request could not be completed. Please try again.');
                    return false;
                }
            },
        });

        if (result.isConfirmed) {
            await Swal.fire({
                title: result.value,
                icon: 'success',
                confirmButtonText: 'OK',
                ...dialogColors(),
            });
            window.location.reload();
        }
    });
});

document.querySelectorAll('[data-confirm-delete-user]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();

        const result = await Swal.fire({
            title: 'Delete user?',
            text: `Delete ${form.dataset.userName}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            ...dialogColors(),
            confirmButtonColor: '#b42318',
        });

        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }
    });
});
