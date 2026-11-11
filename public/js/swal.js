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