(function () {
    var cfg = {!! $configJson !!};
    var rootId = 'crm-webform-' + cfg.token;
    var currentScript = document.currentScript;

    function findScript() {
        if (currentScript) return currentScript;
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if ((scripts[i].src || '').indexOf('/webforms/' + cfg.token + '/embed.js') !== -1) return scripts[i];
        }
        return null;
    }

    function el(tag, attrs, text) {
        var node = document.createElement(tag);
        if (attrs) {
            for (var k in attrs) {
                if (Object.prototype.hasOwnProperty.call(attrs, k) && attrs[k] !== null && attrs[k] !== undefined && attrs[k] !== false) {
                    node.setAttribute(k, attrs[k] === true ? '' : attrs[k]);
                }
            }
        }
        if (text) node.textContent = text;
        return node;
    }

    function injectStyles(color) {
        var styleId = 'crm-wf-style-' + cfg.token;
        if (document.getElementById(styleId)) return;
        var s = '#R{font-family:inherit;max-width:640px;box-sizing:border-box;color:#1f2937;}'
            + '#R *{box-sizing:border-box;}'
            + '#R .cwf-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 4px 18px rgba(15,23,42,.06);}'
            + '#R .cwf-heading{margin:0 0 6px;font-size:22px;font-weight:700;line-height:1.3;color:#111827;}'
            + '#R .cwf-intro{margin:0 0 18px;font-size:14px;color:#6b7280;line-height:1.5;}'
            + '#R .cwf-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px;}'
            + '#R .cwf-field{display:flex;flex-direction:column;min-width:0;}'
            + '#R .cwf-full{grid-column:1 / -1;}'
            + '#R label{font-size:13px;font-weight:600;margin:0 0 6px;color:#374151;}'
            + '#R .cwf-req{color:#dc2626;margin-left:2px;}'
            + '#R input,#R select,#R textarea{width:100%;font:inherit;font-size:14px;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#111827;outline:none;transition:border-color .15s,box-shadow .15s;margin:0;}'
            + '#R textarea{min-height:110px;resize:vertical;}'
            + '#R input:focus,#R select:focus,#R textarea:focus{border-color:C;box-shadow:0 0 0 3px CA;}'
            + '#R .cwf-invalid{border-color:#dc2626 !important;}'
            + '#R .cwf-error{color:#dc2626;font-size:12px;margin-top:4px;}'
            + '#R .cwf-hp{position:absolute !important;left:-9999px !important;width:1px;height:1px;overflow:hidden;}'
            + '#R .cwf-btn{margin-top:18px;background:C;color:#fff;border:0;border-radius:8px;padding:11px 26px;font:inherit;font-size:15px;font-weight:600;cursor:pointer;transition:opacity .15s;}'
            + '#R .cwf-btn:hover{opacity:.9;}'
            + '#R .cwf-btn[disabled]{opacity:.6;cursor:not-allowed;}'
            + '#R .cwf-alert{padding:12px 14px;border-radius:8px;font-size:14px;margin-bottom:14px;line-height:1.5;}'
            + '#R .cwf-alert-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}'
            + '#R .cwf-success{text-align:center;padding:28px 12px;}'
            + '#R .cwf-success-icon{width:54px;height:54px;border-radius:50%;background:C;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:12px;}'
            + '#R .cwf-success p{margin:0;font-size:15px;color:#374151;line-height:1.6;}'
            + '@' + 'media (max-width:560px){#R .cwf-grid{grid-template-columns:1fr;}#R .cwf-card{padding:18px;}}';
        var css = s.split('#R').join('#' + rootId).split('CA').join(hexToRgba(color, 0.18)).split(':C;').join(':' + color + ';');
        var style = el('style', { id: styleId });
        style.appendChild(document.createTextNode(css));
        (document.head || document.documentElement).appendChild(style);
    }

    function hexToRgba(hex, alpha) {
        var m = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(hex || '');
        if (!m) return 'rgba(91,60,196,' + alpha + ')';
        return 'rgba(' + parseInt(m[1], 16) + ',' + parseInt(m[2], 16) + ',' + parseInt(m[3], 16) + ',' + alpha + ')';
    }

    function getRoot() {
        var root = document.getElementById(rootId);
        if (!root) {
            root = el('div', { id: rootId });
            var script = findScript();
            if (script && script.parentNode) {
                script.parentNode.insertBefore(root, script);
            } else {
                document.body.appendChild(root);
            }
        }
        return root;
    }

    function render() {
        var root = getRoot();
        root.innerHTML = '';
        injectStyles(cfg.color || '#5b3cc4');

        var card = el('div', { 'class': 'cwf-card' });
        root.appendChild(card);

        if (!cfg.active) {
            card.appendChild(el('p', { 'class': 'cwf-intro', style: 'margin:0' }, 'This form is currently unavailable.'));
            return;
        }

        if (cfg.heading) card.appendChild(el('h3', { 'class': 'cwf-heading' }, cfg.heading));
        if (cfg.intro) card.appendChild(el('p', { 'class': 'cwf-intro' }, cfg.intro));

        var alertBox = el('div', { 'class': 'cwf-alert cwf-alert-err', role: 'alert', style: 'display:none' });
        card.appendChild(alertBox);

        var form = el('form', { novalidate: true, action: cfg.submitUrl, method: 'POST' });
        var grid = el('div', { 'class': 'cwf-grid' });
        form.appendChild(grid);

        var halfCount = 0;
        (cfg.fields || []).forEach(function (f, idx) {
            var id = rootId + '-' + f.key;
            var full = f.type === 'textarea' || f.type === 'select';
            if (!full) halfCount++;
            var wrap = el('div', { 'class': 'cwf-field' + (full ? ' cwf-full' : ''), 'data-key': f.key });
            var label = el('label', { 'for': id }, f.label);
            if (f.required) label.appendChild(el('span', { 'class': 'cwf-req', 'aria-hidden': 'true' }, '*'));
            wrap.appendChild(label);

            var input;
            var common = { id: id, name: f.key, required: !!f.required, 'aria-required': f.required ? 'true' : null };
            if (f.type === 'textarea') {
                common.rows = 4;
                common.maxlength = f.max;
                common.placeholder = f.placeholder || null;
                input = el('textarea', common);
            } else if (f.type === 'select') {
                input = el('select', common);
                input.appendChild(el('option', { value: '' }, f.placeholder || 'Select'));
                (f.options || []).forEach(function (o) {
                    input.appendChild(el('option', { value: o.value }, o.label));
                });
            } else {
                common.type = f.type;
                common.maxlength = f.max;
                common.placeholder = f.placeholder || null;
                if (f.type === 'email') common.autocomplete = 'email';
                if (f.type === 'tel') common.autocomplete = 'tel';
                if (f.key === 'first_name') common.autocomplete = 'given-name';
                if (f.key === 'last_name') common.autocomplete = 'family-name';
                if (f.key === 'company') common.autocomplete = 'organization';
                input = el('input', common);
            }
            wrap.appendChild(input);
            wrap.appendChild(el('div', { 'class': 'cwf-error', style: 'display:none' }));
            grid.appendChild(wrap);
        });

        // A lone half-width field would leave an empty cell; stretch the last one.
        if (halfCount % 2 === 1) {
            var halves = grid.querySelectorAll('.cwf-field:not(.cwf-full)');
            if (halves.length) halves[halves.length - 1].className += ' cwf-full';
        }

        var hp = el('div', { 'class': 'cwf-hp', 'aria-hidden': 'true' });
        hp.appendChild(el('input', { type: 'text', name: '_hp_website', tabindex: '-1', autocomplete: 'off' }));
        form.appendChild(hp);

        var btn = el('button', { type: 'submit', 'class': 'cwf-btn' }, cfg.button || 'Submit');
        form.appendChild(btn);
        card.appendChild(form);

        function setError(key, msg) {
            var wrap = form.querySelector('[data-key="' + key + '"]');
            if (!wrap) return;
            var input = wrap.querySelector('input,select,textarea');
            var box = wrap.querySelector('.cwf-error');
            if (msg) {
                input.className = 'cwf-invalid';
                input.setAttribute('aria-invalid', 'true');
                box.textContent = msg;
                box.style.display = 'block';
            } else {
                input.className = '';
                input.removeAttribute('aria-invalid');
                box.textContent = '';
                box.style.display = 'none';
            }
        }

        function clientValidate() {
            var ok = true;
            (cfg.fields || []).forEach(function (f) {
                var input = form.elements[f.key];
                var val = (input.value || '').trim();
                var msg = '';
                if (f.required && !val) msg = f.label + ' is required.';
                else if (val && f.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) msg = 'Please enter a valid email address.';
                else if (val && f.type === 'tel' && !/^[0-9+()\-\s.]{6,20}$/.test(val)) msg = 'Please enter a valid phone number.';
                setError(f.key, msg);
                if (msg) ok = false;
            });
            return ok;
        }

        function showAlert(msg) {
            alertBox.textContent = msg;
            alertBox.style.display = msg ? 'block' : 'none';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            showAlert('');
            if (!clientValidate()) {
                var firstBad = form.querySelector('.cwf-invalid');
                if (firstBad) firstBad.focus();
                return;
            }

            var data = new FormData(form);
            data.append('_ajax', '1');
            data.append('_page', window.location.href);

            var originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Submitting...';

            fetch(cfg.submitUrl, { method: 'POST', body: data, mode: 'cors', credentials: 'omit' })
                .then(function (res) {
                    return res.json().catch(function () { return { ok: false, message: 'Something went wrong. Please try again.' }; });
                })
                .then(function (json) {
                    if (json && json.ok) {
                        if (json.redirect) {
                            window.location.href = json.redirect;
                            return;
                        }
                        card.innerHTML = '';
                        var done = el('div', { 'class': 'cwf-success', role: 'status' });
                        done.appendChild(el('div', { 'class': 'cwf-success-icon', 'aria-hidden': 'true' }, '\u2713'));
                        done.appendChild(el('p', null, json.message || cfg.success || 'Thank you!'));
                        card.appendChild(done);
                        return;
                    }
                    if (json && json.errors) {
                        Object.keys(json.errors).forEach(function (k) { setError(k, json.errors[k][0]); });
                    }
                    showAlert((json && json.message) || 'Something went wrong. Please try again.');
                })
                .catch(function () {
                    showAlert('Could not submit the form. Please check your connection and try again.');
                })
                .then(function () {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', render);
    } else {
        render();
    }
})();
