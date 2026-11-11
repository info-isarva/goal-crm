 document.addEventListener('DOMContentLoaded', function() {
            const CSRF_TOKEN = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';
            // Autocomplete organization list
            const orgInput = document.getElementById('organization_id');
            const orgDropdown = document.getElementById('org-autocomplete-list');

            let orgListData = [];

            function renderOrgList(filter = '') {
                const scrollDiv = document.getElementById('org-autocomplete-scroll');
                const newDiv = document.getElementById('org-autocomplete-new');
                scrollDiv.innerHTML = '';
                (orgListData.filter(org => org.name.toLowerCase().includes(filter.toLowerCase()))).forEach(org => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = org.name;
                    item.onclick = function() {
                        orgInput.value = org.name;
                        orgDropdown.style.display = 'none';

                        // Fetch organization details (owner and primary contact) and autofill owner/contact fields
                        fetch('/organizations/details?name=' + encodeURIComponent(org.name))
                            .then(res => res.json())
                                .then(data => {
                                    // Helper to render a small selectable list below an input
                                    function renderRelatedList(containerId, items, defaultText, onSelect) {
                                        // remove existing container if present
                                        let existing = document.getElementById(containerId);
                                        if (existing) existing.remove();
                                        if (!items || items.length === 0) return;
                                        const container = document.createElement('div');
                                        container.id = containerId;
                                        container.style.marginTop = '6px';
                                        container.style.padding = '6px';
                                        container.style.border = '1px solid #e9ecef';
                                        container.style.background = '#fff';
                                        container.style.borderRadius = '4px';
                                        container.style.boxShadow = '0 1px 2px rgba(0,0,0,0.04)';
                                        const list = document.createElement('div');
                                        list.style.maxHeight = '160px';
                                        list.style.overflowY = 'auto';
                                        items.forEach((it, idx) => {
                                            const btn = document.createElement('button');
                                            btn.type = 'button';
                                            btn.className = 'btn btn-sm btn-light w-100 text-start mb-1';
                                            btn.style.whiteSpace = 'nowrap';
                                            btn.style.overflow = 'hidden';
                                            btn.style.textOverflow = 'ellipsis';
                                            btn.textContent = it.name || it.full_name || it;
                                            btn.onclick = function(e) {
                                                e.preventDefault();
                                                onSelect(it);
                                                container.remove();
                                            };
                                            list.appendChild(btn);
                                        });
                                        container.appendChild(list);
                                        // Insert after the input element
                                        const anchor = defaultText instanceof Element ? defaultText : document.getElementById(defaultText);
                                        if (anchor && anchor.parentNode) {
                                            anchor.parentNode.insertBefore(container, anchor.nextSibling);
                                        }
                                    }

                                    // Customers (company owners) list
                                    if (Array.isArray(data.customers) && data.customers.length > 0) {
                                        // Default: first customer
                                        const firstCust = data.customers[0];
                                        const custInputEl = document.getElementById('customer_id');
                                        if (custInputEl) custInputEl.value = firstCust.name;

                                        renderRelatedList('org-related-customers', data.customers, 'customer_id', function(sel) {
                                            const custInputEl2 = document.getElementById('customer_id');
                                            if (custInputEl2) custInputEl2.value = sel.name;
                                        });
                                    } else {
                                        const custInputEl = document.getElementById('customer_id');
                                        if (custInputEl) custInputEl.value = '';
                                        const existingCustList = document.getElementById('org-related-customers');
                                        if (existingCustList) existingCustList.remove();
                                    }

                                    // People list (contact persons)
                                    if (Array.isArray(data.people) && data.people.length > 0) {
                                        const firstPerson = data.people[0];
                                        const personInputEl = document.getElementById('people_id');
                                        if (personInputEl) personInputEl.value = firstPerson.full_name;

                                        // transform people array to objects with full_name property for rendering
                                        const peopleForRender = data.people.map(p => ({ full_name: p.full_name, id: p.id }));
                                        renderRelatedList('org-related-people', peopleForRender, 'people_id', function(sel) {
                                            const personInputEl2 = document.getElementById('people_id');
                                            if (personInputEl2) personInputEl2.value = sel.full_name;
                                        });
                                    } else {
                                        const personInputEl = document.getElementById('people_id');
                                        if (personInputEl) personInputEl.value = '';
                                        const existingPersonList = document.getElementById('org-related-people');
                                        if (existingPersonList) existingPersonList.remove();
                                    }

                                    // Owner (user_owner_id) selection if provided
                                    if (data.owner) {
                                        const ownerSelect = document.getElementById('user_owner_id');
                                        if (ownerSelect && data.owner.id) {
                                            try {
                                                ownerSelect.value = data.owner.id;
                                                if (typeof $ !== 'undefined') {
                                                    $(ownerSelect).val(data.owner.id).trigger('change');
                                                }
                                            } catch (e) {}
                                        }
                                    }
                                })
                            .catch(() => {
                                // On error, clear dependent fields
                                document.getElementById('customer_id').value = '';
                                document.getElementById('people_id').value = '';
                            });
                    };
                    scrollDiv.appendChild(item);
                });
                // Add "New Organization" option at the end, always visible and sticky
                newDiv.innerHTML = '';
                const newBtn = document.createElement('a');
                newBtn.href = "#";
                newBtn.className = 'dropdown-item text-primary';
                newBtn.textContent = 'New Company';
                newBtn.onclick = function(e) {
                    e.preventDefault();
                    orgDropdown.style.display = 'none';
                    setTimeout(function() {
                        var modal = new bootstrap.Modal(document.getElementById('orgNewModal'));
                        modal.show();
                    }, 100);
                };
                newDiv.appendChild(newBtn);
            }

            orgInput.addEventListener('focus', function() {
                fetch('/organizations/autocomplete?q=')
                    .then(res => res.json())
                    .then(data => {
                        orgListData = data;
                        renderOrgList();
                        orgDropdown.style.display = 'block';
                        document.getElementById('org-autocomplete-search').value = '';
                    });
            });

            document.getElementById('org-autocomplete-search').addEventListener('input', function(e) {
                renderOrgList(e.target.value);
            });



            // Click outside to close organization dropdown
            document.addEventListener('mousedown', function(e) {
                if (orgDropdown.style.display === 'block') {
                    if (!orgDropdown.contains(e.target) && e.target !== orgInput) {
                        orgDropdown.style.display = 'none';
                    }
                }
            });

            // New organization modal submit
            document.getElementById('orgNewForm').addEventListener('submit', function(e) {
                
                e.preventDefault();
                // Hide all error messages first
                ['org_name_error', 'org_address_error', 'org_phone_error', 'org_website_error'].forEach(
                    function(id) {
                        document.getElementById(id).style.display = 'none';
                        document.getElementById(id).textContent = '';
                    });
                const formData = new FormData(this);
                if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                fetch('/organizations/ajax-create', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            if (data.errors) {
                                Object.keys(data.errors).forEach(function(key) {
                                    const errorDiv = document.getElementById(key +
                                    '_error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[key][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            throw new Error('Validation error');
                        }
                        return res.json();
                    })
                    .then(org => {
                        orgInput.value = org.name;
                        var modal = bootstrap.Modal.getInstance(document.getElementById('orgNewModal'));
                        modal.hide();
                    })
                    .catch(() => {});
            });

            // Autocomplete customer list
            const custInput = document.getElementById('customer_id');
            const custDropdown = document.getElementById('customer-autocomplete-list');

            custInput.addEventListener('focus', function() {
                // Get selected organization name from orgInput
                const orgName = orgInput.value;
                let url = '/customers/autocomplete?q=' + '';
                if (orgName) {
                    url += '&organization=' + encodeURIComponent(orgName);
                }
                let customerListData = [];

                function renderCustomerList(filter = '') {
                    const scrollDiv = document.getElementById('customer-autocomplete-scroll');
                    const newDiv = document.getElementById('customer-autocomplete-new');
                    scrollDiv.innerHTML = '';
                    (customerListData.filter(cust => (cust.name || cust).toLowerCase().includes(filter
                        .toLowerCase()))).forEach(cust => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'dropdown-item';
                        item.textContent = cust.name || cust;
                        item.onclick = function() {
                            custInput.value = cust.name || cust;
                            custDropdown.style.display = 'none';
                        };
                        scrollDiv.appendChild(item);
                    });
                    // Add "New Customer" option at the end, always visible and sticky
                    newDiv.innerHTML = '';
                    const newBtn = document.createElement('a');
                    newBtn.href = "#";
                    newBtn.className = 'dropdown-item text-primary';
                    newBtn.textContent = 'New Company Owner';
                    newBtn.onclick = function(e) {
                        e.preventDefault();
                        custDropdown.style.display = 'none';
                        setTimeout(function() {
                            var modal = new bootstrap.Modal(document.getElementById(
                                'customerNewModal'));
                            modal.show();
                        }, 100);
                    };
                    newDiv.appendChild(newBtn);
                }
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        customerListData = data;
                        renderCustomerList();
                        custDropdown.style.display = 'block';
                        document.getElementById('customer-autocomplete-search').value = '';
                    });
                document.getElementById('customer-autocomplete-search').oninput = function(e) {
                    renderCustomerList(e.target.value);
                };
            });

            // Autocomplete contact person list
            const personInput = document.getElementById('people_id');
            const personDropdown = document.getElementById('person-autocomplete-list');

            personInput.addEventListener('focus', function() {
                // Get selected organization name from orgInput
                const orgName = orgInput.value;
                let url = '/people/autocomplete?q=' + '';
                if (orgName) {
                    url += '&organization=' + encodeURIComponent(orgName);
                }
                let personListData = [];

                function renderPersonList(filter = '') {
                    const scrollDiv = document.getElementById('person-autocomplete-scroll');
                    const newDiv = document.getElementById('person-autocomplete-new');
                    scrollDiv.innerHTML = '';
                    (personListData.filter(person => ((person.first_name ? person.first_name : '') + (person
                        .last_name ? ' ' + person.last_name : '')).toLowerCase().includes(filter
                        .toLowerCase()))).forEach(person => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'dropdown-item';
                        item.textContent = (person.first_name ? person.first_name : '') + (person
                            .last_name ? ' ' + person.last_name : '');
                        item.onclick = function() {
                            personInput.value = (person.first_name ? person.first_name : '') + (
                                person.last_name ? ' ' + person.last_name : '');
                            personDropdown.style.display = 'none';
                        };
                        scrollDiv.appendChild(item);
                    });
                    // Add "New Contact Person" option at the end, always visible and sticky
                    newDiv.innerHTML = '';
                    const newBtn = document.createElement('a');
                    newBtn.href = "#";
                    newBtn.className = 'dropdown-item text-primary';
                    newBtn.textContent = 'New Contact Person';
                    newBtn.onclick = function(e) {
                        e.preventDefault();
                        personDropdown.style.display = 'none';
                        setTimeout(function() {
                            var modal = new bootstrap.Modal(document.getElementById(
                                'personNewModal'));
                            modal.show();
                        }, 100);
                    };
                    newDiv.appendChild(newBtn);
                }
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        personListData = data;
                        renderPersonList();
                        personDropdown.style.display = 'block';
                        document.getElementById('person-autocomplete-search').value = '';
                    });
                document.getElementById('person-autocomplete-search').oninput = function(e) {
                    renderPersonList(e.target.value);
                };
            });

            // Click outside to close contact person dropdown
            document.addEventListener('mousedown', function(e) {
                if (personDropdown.style.display === 'block') {
                    if (!personDropdown.contains(e.target) && e.target !== personInput) {
                        personDropdown.style.display = 'none';
                    }
                }
            });

            // Click outside to close customer dropdown
            document.addEventListener('mousedown', function(e) {
                if (custDropdown.style.display === 'block') {
                    if (!custDropdown.contains(e.target) && e.target !== custInput) {
                        custDropdown.style.display = 'none';
                    }
                }
            });

            // Fill organization in customer modal when opened, make readonly if selected
            document.getElementById('customerNewModal').addEventListener('show.bs.modal', function() {
                const orgName = document.getElementById('organization_id').value;
                const orgSelect = document.getElementById('cust_org_id');
                let found = false;
                for (let i = 0; i < orgSelect.options.length; i++) {
                    if (orgSelect.options[i].text === orgName) {
                        orgSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (found && orgName !== '') {
                    orgSelect.setAttribute('readonly', 'readonly');
                } else {
                    orgSelect.removeAttribute('readonly');
                }
            });
            // New customer modal submit
            document.getElementById('customerNewForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // Hide all error messages first
                ['cust_name_error', 'cust_org_id_error'].forEach(function(id) {
                    document.getElementById(id).style.display = 'none';
                    document.getElementById(id).textContent = '';
                });
                const formData = new FormData(this);
                if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                fetch('/customers/ajax-create', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            if (data.errors) {
                                Object.keys(data.errors).forEach(function(key) {
                                    const errorDiv = document.getElementById(key +
                                    '_error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[key][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            throw new Error('Validation error');
                        }
                        return res.json();
                    })
                    .then(cust => {
                        if (cust && cust.duplicate) {
                            // show inline validation error and keep modal open
                            const errorDiv = document.getElementById('cust_name_error');
                            if (errorDiv) {
                                errorDiv.textContent = 'A company owner named "' + (cust.name || '') + '" already exists for the selected organization.';
                                errorDiv.style.display = 'block';
                            }
                            const nameInput = document.getElementById('cust_name');
                            if (nameInput) nameInput.focus();
                            return;
                        }
                        custInput.value = cust.name || ''
                        var modal = bootstrap.Modal.getInstance(document.getElementById(
                            'customerNewModal'));
                        modal.hide();
                    })
                    .catch(() => {});
            });

            // Fill organization in contact person modal when opened, make readonly if selected
            document.getElementById('personNewModal').addEventListener('show.bs.modal', function() {
                const orgName = document.getElementById('organization_id').value;
                const orgSelect = document.getElementById('person_org_id');
                let found = false;
                for (let i = 0; i < orgSelect.options.length; i++) {
                    if (orgSelect.options[i].text === orgName) {
                        orgSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (found && orgName !== '') {
                    orgSelect.setAttribute('readonly', 'readonly');
                } else {
                    orgSelect.removeAttribute('readonly');
                }
            });
            // New contact person modal submit with single word validation
            document.getElementById('personNewForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // Hide all error messages first
                ['person_first_name_error', 'person_last_name_error', 'person_org_id_error',
                    'person_email_error', 'person_phone_error'
                ].forEach(function(id) {
                    document.getElementById(id).style.display = 'none';
                    document.getElementById(id).textContent = '';
                });
                const firstName = document.getElementById('person_first_name').value.trim();
                const lastName = document.getElementById('person_last_name').value.trim();
                const singleWordRegex = /^\w+$/;
                let errorMsg = '';
                if (firstName && !singleWordRegex.test(firstName)) {
                    errorMsg += 'First name must be a single word.\n';
                }
                // if (lastName && !singleWordRegex.test(lastName)) {
                //     errorMsg += 'Last name must be a single word.';
                // }
                if (errorMsg) {
                    document.getElementById('person_first_name_error').textContent = errorMsg;
                    document.getElementById('person_first_name_error').style.display = 'block';
                    return;
                }
                const formEl = this;
                const orgSelect = document.getElementById('person_org_id');
                const orgName = orgSelect && orgSelect.options[orgSelect.selectedIndex] ? orgSelect.options[orgSelect.selectedIndex].text : '';
                const q = firstName + (lastName ? ' ' + lastName : '');

                if (firstName) {
                    const checkUrl = '/people/autocomplete?q=' + encodeURIComponent(q) + (orgName ? '&organization=' + encodeURIComponent(orgName) : '');
                    fetch(checkUrl)
                        .then(res => res.json())
                        .then(matches => {
                            const duplicate = (Array.isArray(matches) ? matches : []).find(p => (((p.first_name || '') + ' ' + (p.last_name || '')).trim().toLowerCase() === q.trim().toLowerCase()));
                            if (duplicate) {
                                const firstErr = document.getElementById('person_first_name_error');
                                const lastErr = document.getElementById('person_last_name_error');
                                const msg = 'A contact person named "' + q + '" already exists for "' + (orgName || 'the selected organization') + '".';
                                if (firstErr) { firstErr.textContent = msg; firstErr.style.display = 'block'; }
                                if (lastErr) { lastErr.textContent = msg; lastErr.style.display = 'block'; }
                                const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                                return;
                            }

                            // submit
                            const formData = new FormData(formEl);
                            if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                            fetch('/people/ajax-create', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                                    credentials: 'same-origin',
                                    body: formData
                                })
                                .then(async res => {
                                    if (res.status === 422) {
                                        const data = await res.json();
                                        if (data.errors) {
                                            Object.keys(data.errors).forEach(function(key) {
                                                const errorDiv = document.getElementById(key + '_error');
                                                if (errorDiv) {
                                                    errorDiv.textContent = data.errors[key][0];
                                                    errorDiv.style.display = 'block';
                                                }
                                            });
                                        }
                                        throw new Error('Validation error');
                                    }
                                    return res.json();
                                })
                                .then(person => {
                                    if (person && person.duplicate) {
                                        const firstErr = document.getElementById('person_first_name_error');
                                        const lastErr = document.getElementById('person_last_name_error');
                                        const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                                        if (firstErr) { firstErr.textContent = msg; firstErr.style.display = 'block'; }
                                        if (lastErr) { lastErr.textContent = msg; lastErr.style.display = 'block'; }
                                        const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                                        return;
                                    }
                                    personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                                    var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                                    modal.hide();
                                })
                                .catch(() => {});
                        })
                        .catch(() => {
                            // fallback submit
                            const formData = new FormData(formEl);
                            if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                            fetch('/people/ajax-create', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                                    credentials: 'same-origin',
                                    body: formData
                                })
                                .then(async res => {
                                    if (res.status === 422) {
                                        const data = await res.json();
                                        if (data.errors) {
                                            Object.keys(data.errors).forEach(function(key) {
                                                const errorDiv = document.getElementById(key + '_error');
                                                if (errorDiv) {
                                                    errorDiv.textContent = data.errors[key][0];
                                                    errorDiv.style.display = 'block';
                                                }
                                            });
                                        }
                                        throw new Error('Validation error');
                                    }
                                    return res.json();
                                })
                                .then(person => {
                                    if (person && person.duplicate) {
                                        const firstErr = document.getElementById('person_first_name_error');
                                        const lastErr = document.getElementById('person_last_name_error');
                                        const msg = 'A contact person named "' + ((person.first_name || '') + (person.last_name ? ' ' + person.last_name : '')).trim() + '" already exists for the selected organization.';
                                        if (firstErr) { firstErr.textContent = msg; firstErr.style.display = 'block'; }
                                        if (lastErr) { lastErr.textContent = msg; lastErr.style.display = 'block'; }
                                        const firstInput = document.getElementById('person_first_name'); if (firstInput) firstInput.focus();
                                        return;
                                    }
                                    personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                                    var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                                    modal.hide();
                                })
                                .catch(() => {});
                        });
                    return;
                }

                // fallback submit if firstName missing
                const formData = new FormData(this);
                if (CSRF_TOKEN) formData.append('_token', CSRF_TOKEN);
                fetch('/people/ajax-create', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: formData
                    })
                    .then(async res => {
                        if (res.status === 422) {
                            const data = await res.json();
                            if (data.errors) {
                                Object.keys(data.errors).forEach(function(key) {
                                    const errorDiv = document.getElementById(key + '_error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[key][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            throw new Error('Validation error');
                        }
                        return res.json();
                    })
                    .then(person => {
                        personInput.value = (person.first_name ? person.first_name : '') + (person.last_name ? ' ' + person.last_name : '');
                        var modal = bootstrap.Modal.getInstance(document.getElementById('personNewModal'));
                        modal.hide();
                    })
                    .catch(() => {});
            });

            // --- Fetch and update organization list for modals ---
            function loadLatestOrganizations(selectId) {
                fetch('/organizations/autocomplete?q=')
                    .then(res => res.json())
                    .then(data => {
                        const select = document.getElementById(selectId);
                        if (select) {
                            select.innerHTML = '<option value="">Select organization</option>';
                            data.forEach(item => {
                                // Use ID as value, name as text
                                const id = item.id || item.value || item;
                                const name = item.name || item.label || item;
                                const option = document.createElement('option');
                                option.value = id;
                                option.textContent = name;
                                select.appendChild(option);
                            });
                        }
                    });
            }

            // Before showing New Customer modal
            const customerNewModal = document.getElementById('customerNewModal');
            if (customerNewModal) {
                customerNewModal.addEventListener('show.bs.modal', function() {
                    loadLatestOrganizations('cust_org_id');
                });
            }

            // Before showing New Contact Person modal
            const personNewModal = document.getElementById('personNewModal');
            if (personNewModal) {
                personNewModal.addEventListener('show.bs.modal', function() {
                    loadLatestOrganizations('person_org_id');
                });
            }

            $('#user_owner_id').select2({
                placeholder: 'Select owner',
                allowClear: true,
                width: '100%'
            });
            $('#lead_source').select2({
                placeholder: 'Select industry',
                allowClear: true,
                width: '100%'
            });
            $('#status').select2({
                placeholder: 'Select organization type',
                allowClear: true,
                width: '100%'
            });
            $('#label').select2({
                placeholder: 'Select organization',
                allowClear: true,
                width: '100%'
            });
        });