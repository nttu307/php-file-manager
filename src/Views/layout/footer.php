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
