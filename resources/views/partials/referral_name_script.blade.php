<script>
(function () {
    var REFERRAL_SOURCE_NAMES = [
        'client referral',
        'employee referral',
        'partner referral',
        'white-label referral',
        'white label referral',
        'whitelabel referral'
    ];

    function normalizeSourceName(name) {
        return String(name || '')
            .toLowerCase()
            .replace(/[_–—]/g, '-')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function isReferralSourceName(name) {
        return REFERRAL_SOURCE_NAMES.indexOf(normalizeSourceName(name)) !== -1;
    }

    function selectedLeadSourceName() {
        var sel = document.getElementById('lead_source');
        if (!sel) return '';
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return '';
        return opt.getAttribute('data-name') || opt.textContent || '';
    }

    function toggleReferralNameField(clearWhenHidden) {
        var wrap = document.getElementById('referral_name_wrap');
        var input = document.getElementById('referral_name');
        if (!wrap || !input) return;

        var show = isReferralSourceName(selectedLeadSourceName());
        wrap.style.display = show ? '' : 'none';

        if (show) {
            input.setAttribute('required', 'required');
        } else {
            input.removeAttribute('required');
            if (clearWhenHidden) {
                input.value = '';
            }
        }
    }

    window.toggleReferralNameField = toggleReferralNameField;

    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('lead_source');
        if (!sel) return;

        toggleReferralNameField(false);

        sel.addEventListener('change', function () {
            toggleReferralNameField(true);
        });

        if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
            $(sel).on('change select2:select select2:clear', function () {
                toggleReferralNameField(true);
            });
        }
    });
})();
</script>
