// jQuery for Select2
if (!window.jQuery) {
    var jqScript = document.createElement('script');
    jqScript.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
    document.head.appendChild(jqScript);
}
setTimeout(function () {
    var el = document.getElementById('orgSuccessMsg');
    if (el) {
        el.classList.remove('show');
        setTimeout(function () {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 500);
    }
}, 2000);


document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('orgCreateForm').addEventListener('submit', function (e) {
        let valid = true;
        // Clear previous errors
        document.querySelectorAll('.client-error').forEach(function (el) {
            el.remove();
        });

        // City: only letters, spaces, dots, hyphens, min 2 chars
        const city = document.getElementById('city');
        if (city && city.value && !/^([A-Za-z .'-]{2,})$/.test(city.value)) {
            showError(city, 'Enter a valid city name.');
            valid = false;
        }

        // State: only letters, spaces, dots, hyphens, min 2 chars
        const state = document.getElementById('state');
        if (state && state.value && !/^([A-Za-z .'-]{2,})$/.test(state.value)) {
            showError(state, 'Enter a valid state name.');
            valid = false;
        }

        // Pincode: 4-10 digits (India: 6 digits, but allow more for other countries)
        const pincode = document.getElementById('pincode');
        if (pincode && pincode.value && !/^\d{4,10}$/.test(pincode.value)) {
            showError(pincode, 'Enter a valid pincode (4-10 digits).');
            valid = false;
        }

        // Phone: +, digits, spaces, hyphens, 7-20 chars
        const phone = document.getElementById('phone');
        if (phone && phone.value && !/^\+?[0-9\-\s]{7,20}$/.test(phone.value)) {
            showError(phone, 'Enter a valid phone number.');
            valid = false;
        }

        // Email: basic email regex
        const email = document.getElementById('email');
        if (email && email.value && !/^([a-zA-Z0-9_\.-]+)@([a-zA-Z0-9\.-]+)\.([a-zA-Z]{2,})$/.test(
            email.value)) {
            showError(email, 'Enter a valid email address.');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        }

        function showError(input, msg) {
            const err = document.createElement('div');
            err.className = 'text-danger small mt-1 client-error';
            err.textContent = msg;
            input.parentNode.appendChild(err);
        }
    });
});

// Select2 for industry and type dropdowns
document.addEventListener('DOMContentLoaded', function () {
    function initSelect2() {
        $('#industry_type').select2({
            placeholder: 'Select industry',
            allowClear: true,
            width: '100%'
        });
        $('#organization_type').select2({
            placeholder: 'Select type',
            allowClear: true,
            width: '100%'
        });
        $('#user_owner_id').select2({
            placeholder: 'Select owner',
            allowClear: true,
            width: '100%'
        });
    }
    if (window.$ && $.fn.select2) {
        initSelect2();
    } else {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
        document.head.appendChild(link);
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = initSelect2;
        document.body.appendChild(script);
    }
});