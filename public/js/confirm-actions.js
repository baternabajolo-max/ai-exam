document.addEventListener('DOMContentLoaded', function () {
    function showConfirmation(element) {
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 is required for confirmation actions.');

            return Promise.resolve(false);
        }

        return Swal.fire({
            icon: element.dataset.confirmIcon || 'warning',
            title: element.dataset.confirmTitle || 'Are you sure?',
            text: element.dataset.confirmText || '',
            showCancelButton: true,
            confirmButtonText: element.dataset.confirmButton || 'Continue',
            cancelButtonText: element.dataset.cancelButton || 'Cancel',
            confirmButtonColor: '#9f1239',
            cancelButtonColor: '#64748b',
            focusCancel: true,
            reverseButtons: true,
        }).then(result => result.isConfirmed);
    }

    document.addEventListener('submit', function (event) {
        const form = event.target instanceof Element
            ? event.target.closest('form[data-confirm-title]')
            : null;

        if (!form || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        showConfirmation(form).then(function (isConfirmed) {
            if (!isConfirmed) {
                return;
            }

            form.dataset.confirmed = 'true';
            if (event.submitter) {
                form.requestSubmit(event.submitter);
            } else {
                form.requestSubmit();
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        const link = event.target instanceof Element
            ? event.target.closest('a[data-confirm-title]')
            : null;
        if (
            !link
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey
        ) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        showConfirmation(link).then(function (isConfirmed) {
            if (isConfirmed) {
                window.location.assign(link.href);
            }
        });
    }, true);
});
