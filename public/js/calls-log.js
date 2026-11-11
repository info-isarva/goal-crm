document.addEventListener('DOMContentLoaded', function() {
            let entryIndex = 1;
            document.getElementById('add-entry').onclick = function() {
                const container = document.getElementById('calllog-entries');
                const first = container.querySelector('.calllog-entry');
                const clone = first.cloneNode(true);
                clone.querySelectorAll('input, textarea, select').forEach(function(el) {
                    const name = el.getAttribute('name');
                    if (name) {
                        el.setAttribute('name', name.replace(/logs\[\d+\]/, 'logs['+entryIndex+']'));
                        el.value = '';
                    }
                });
                clone.querySelector('.remove-entry').style.display = 'inline-block';
                clone.querySelector('.remove-entry').onclick = function() {
                    clone.remove();
                };
                container.appendChild(clone);
                entryIndex++;
            };
            document.querySelector('.remove-entry').onclick = function() {
                this.closest('.calllog-entry').remove();
            };
        });
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.calllog-sidebar-action').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var sidebarBtn = document.querySelector('button[aria-controls="sidebarOffcanvas"]');
            if (sidebarBtn) {
                if (!document.querySelector('.mobile-sidebar.open')) {
                    sidebarBtn.click();
                }
            }
            setTimeout(function() {
                var actionsMenu = document.querySelector('.nav-link[href*="calllogs.create"]');
                if (actionsMenu) {
                    actionsMenu.classList.add('active', 'bg-warning', 'text-dark');
                    actionsMenu.scrollIntoView({behavior: 'smooth', block: 'center'});
                    setTimeout(function() {
                        actionsMenu.classList.remove('bg-warning', 'text-dark');
                    }, 2000);
                }
            }, 350);
        });
    });

    // Edit modal logic
    var editModalEl = document.getElementById('editCallLogModal');
    var editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;
    document.querySelectorAll('.edit-calllog-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var call = JSON.parse(this.getAttribute('data-calllog'));
            document.getElementById('editCallLogId').value = call.id;
            document.getElementById('editName').value = call.name || '';
            document.getElementById('editCompanyName').value = call.company_name || '';
            document.getElementById('editMobileNumber').value = call.mobile_number || '';
            document.getElementById('editRequirement').value = call.requirement || '';
            document.getElementById('editCallStatus').value = call.call_status || '';
            document.getElementById('editLeadStatus').value = call.lead_status || '';
            if (editModal) {
                editModal.show();
            }
            document.getElementById('editCallLogForm').action = '/calllogs/' + call.id;
        });
    });

    // Fix modal backdrop issue on Cancel
    var cancelBtn = document.querySelector('#editCallLogModal .btn-secondary');
    if (cancelBtn && editModal) {
        cancelBtn.addEventListener('click', function() {
            editModal.hide();
            setTimeout(function() {
                document.body.classList.remove('modal-open');
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(bd) { bd.remove(); });
            }, 300);
        });
    }
});