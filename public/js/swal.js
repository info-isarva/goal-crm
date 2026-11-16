/**
 * Confirm before submitting a create/update form.
 * options: { title, text, confirmText, icon }
 */
function confirmFormSubmit(form, options) {
    if (!form) return;
    if (form._swalSubmitAttached) return;
    form._swalSubmitAttached = true;

    options = options || {};
    form.addEventListener('submit', function (e) {
        if (form.dataset.swalConfirmed === '1') {
            delete form.dataset.swalConfirmed;
            return;
        }
        e.preventDefault();

        var title = options.title || 'Confirm';
        var text = options.text || 'Do you want to save these changes?';
        var confirmText = options.confirmText || 'Yes, save';
        var icon = options.icon || 'question';

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.swalConfirmed = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        } else if (confirm(text)) {
            form.dataset.swalConfirmed = '1';
            form.submit();
        }
    });
}

/** Show success/error flash via global toastr (fallback: SweetAlert2) */
function showFlashSwal(type, message) {
    if (!message) return;
    var kind = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : (type === 'info' ? 'info' : 'success'));
    var text = String(message).replace(/<[^>]*>/g, '');
    if (window.toastr && typeof window.toastr[kind] === 'function') {
        window.toastr[kind](text);
        return;
    }
    if (window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: kind === 'error' ? 'error' : (kind === 'warning' ? 'warning' : (kind === 'info' ? 'info' : 'success')),
            title: text,
            showConfirmButton: false,
            timer: 4200,
            timerProgressBar: true
        });
    }
}

// SweetAlert2 delete confirmation for customer delete buttons
function attachDeleteHandlers(buttons, nameAttr) {
    // var buttons = document.querySelectorAll('.delete-customer-btn');
    buttons.forEach(function (btn) {
        // avoid attaching multiple listeners
        if (btn._swalAttached) return;
        btn._swalAttached = true;

        btn.addEventListener('click', function (e) {
            if (btn.classList.contains('disabled')) return;
            var form = btn.closest('form');
            if (!form) return;
            var name = btn.getAttribute(nameAttr) || '';
            var message = name ? 'Are you sure you want to delete "' + name + '"? This action cannot be undone.' : 'Are you sure you want to delete this record? This action cannot be undone.';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Delete confirmation',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                // Fallback to native confirm
                if (confirm(message)) {
                    form.submit();
                }
            }
        });
    });
}