</main>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalMessage">Are you sure you want to perform this action?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalSubmit">Confirm</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.toast').forEach((toast) => {
    bootstrap.Toast.getOrCreateInstance(toast).show();
});

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
    bootstrap.Tooltip.getOrCreateInstance(element);
});

function showClientToast(message, type = 'success') {
    const container = document.querySelector('.toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('data-bs-delay', '2200');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    toast.querySelector('.toast-body').textContent = message;
    container.appendChild(toast);
    const instance = bootstrap.Toast.getOrCreateInstance(toast);
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
    instance.show();
}

function updateVisibilityUi(form, visibility, nextVisibility) {
    const row = form.closest('tr');
    const badge = row?.querySelector('[data-visibility-badge]');
    const hiddenInput = form.querySelector('input[name="visibility"]');
    const button = form.querySelector('button[type="submit"]');
    const badgeIcon = badge?.querySelector('i');
    const badgeText = badge?.querySelector('span');
    const buttonIcon = button?.querySelector('i');

    const isPublic = visibility === 'public';
    badge?.classList.toggle('text-bg-success', isPublic);
    badge?.classList.toggle('text-bg-secondary', !isPublic);
    badgeIcon?.classList.toggle('bi-globe2', isPublic);
    badgeIcon?.classList.toggle('bi-lock-fill', !isPublic);
    if (badgeText) {
        badgeText.textContent = visibility;
    }

    if (hiddenInput) {
        hiddenInput.value = nextVisibility;
    }

    button?.classList.toggle('visibility-public', isPublic);
    button?.classList.toggle('visibility-private', !isPublic);
    buttonIcon?.classList.toggle('bi-globe2', isPublic);
    buttonIcon?.classList.toggle('bi-lock-fill', !isPublic);

    if (button) {
        const tooltipText = isPublic ? 'Public - click to make private' : 'Private - click to make public';
        button.setAttribute('data-bs-title', tooltipText);
        button.setAttribute('data-bs-original-title', tooltipText);
        bootstrap.Tooltip.getInstance(button)?.dispose();
        bootstrap.Tooltip.getOrCreateInstance(button);
    }
}

document.querySelectorAll('form[data-visibility-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button?.setAttribute('disabled', 'disabled');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Could not update visibility.');
            }

            updateVisibilityUi(form, payload.visibility, payload.next_visibility);
            showClientToast(payload.message || 'Visibility updated.');
        } catch (error) {
            showClientToast(error.message || 'Could not update visibility.', 'danger');
        } finally {
            button?.removeAttribute('disabled');
        }
    });
});

document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.querySelector(button.dataset.copy);
        if (!target) return;
        await navigator.clipboard.writeText(target.value);
        button.textContent = 'Copied';
        setTimeout(() => button.textContent = 'Copy', 1200);
    });
});
document.querySelectorAll('[data-copy-text]').forEach((button) => {
    const originalHtml = button.innerHTML;
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.copyText);
        button.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => button.innerHTML = originalHtml, 1200);
    });
});

let pendingConfirmForm = null;
const confirmModalElement = document.getElementById('confirmModal');
const confirmModal = confirmModalElement ? new bootstrap.Modal(confirmModalElement) : null;
const confirmMessage = document.getElementById('confirmModalMessage');
const confirmSubmit = document.getElementById('confirmModalSubmit');

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();
        pendingConfirmForm = form;
        if (confirmMessage) {
            confirmMessage.textContent = form.dataset.confirm;
        }
        confirmModal?.show();
    });
});

document.querySelectorAll('button[data-confirm-form]').forEach((button) => {
    button.addEventListener('click', () => {
        pendingConfirmForm = document.getElementById(button.dataset.confirmForm);
        if (!pendingConfirmForm) {
            return;
        }
        if (confirmMessage) {
            confirmMessage.textContent = button.dataset.confirm || 'Are you sure you want to perform this action?';
        }
        confirmModal?.show();
    });
});

confirmSubmit?.addEventListener('click', () => {
    if (!pendingConfirmForm) {
        return;
    }
    pendingConfirmForm.dataset.confirmed = '1';
    pendingConfirmForm.requestSubmit();
});
</script>
</body>
</html>
