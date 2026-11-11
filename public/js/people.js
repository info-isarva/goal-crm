(function() {
            try {
                var input = document.getElementById('peopleSearchInput');
                if (!input) return;
                var debounce = null;
                input.addEventListener('input', function() {
                    clearTimeout(debounce);
                    var q = (input.value || '').trim().toLowerCase();
                    debounce = setTimeout(function() {
                        var allRows = document.querySelectorAll('tbody tr');
                        allRows.forEach(function(row) {
                            if (row.classList.contains('d-md-none')) {
                                var text = ((row.textContent || row.innerText) || '').toLowerCase();
                                if (!q || text.indexOf(q) !== -1) {
                                    row.classList.remove('js-filter-hidden');
                                } else {
                                    row.classList.add('js-filter-hidden');
                                }
                            } else {
                                var nameCell = row.querySelector('td:nth-child(2)');
                                var emailCell = row.querySelector('td:nth-child(3)');
                                var phoneCell = row.querySelector('td:nth-child(4)');
                                var ownerCell = row.querySelector('td:nth-child(5)');
                                var name = (nameCell ? (nameCell.textContent || nameCell.innerText) : '').toLowerCase();
                                var email = (emailCell ? (emailCell.textContent || emailCell.innerText) : '').toLowerCase();
                                var phone = (phoneCell ? (phoneCell.textContent || phoneCell.innerText) : '').toLowerCase();
                                var owner = (ownerCell ? (ownerCell.textContent || ownerCell.innerText) : '').toLowerCase();
                                if (!q || name.indexOf(q) !== -1 || email.indexOf(q) !== -1 || phone.indexOf(q) !== -1 || owner.indexOf(q) !== -1) {
                                    row.classList.remove('js-filter-hidden');
                                } else {
                                    row.classList.add('js-filter-hidden');
                                }
                            }
                        });
                    }, 200);
                });
            } catch (err) {
                console.error('inline people search error', err);
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-person-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!confirm('Delete this person?')) return;
                    const form = this.closest('form');
                    const row = this.closest('tr');
                    fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: new URLSearchParams(new FormData(form))
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                row.remove();
                            } else {
                                alert(data.message || 'Error deleting person.');
                            }
                        })
                        .catch(() => alert('Error deleting person.'));
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('peopleSearchInput');
            console.log('people search script loaded');
            if (!input) {
                console.log('peopleSearchInput not found');
                return;
            }
            console.log('peopleSearchInput attached');
            var timeout = null;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                var q = (input.value || '').trim().toLowerCase();
                // debug
                // console.log('people search query:', q);
                timeout = setTimeout(function() {
                    var allRows = document.querySelectorAll('tbody tr');
                    var shown = 0,
                        hidden = 0;
                    allRows.forEach(function(row) {
                        // mobile card rows have class d-md-none
                        if (row.classList.contains('d-md-none')) {
                            var text = ((row.textContent || row.innerText) || '').toLowerCase();
                            if (!q || text.indexOf(q) !== -1) {
                                row.classList.remove('js-filter-hidden');
                                shown++;
                            } else {
                                row.classList.add('js-filter-hidden');
                                hidden++;
                            }
                        } else {
                            // desktop row
                            var nameCell = row.querySelector('td:nth-child(2)');
                            var emailCell = row.querySelector('td:nth-child(3)');
                            var phoneCell = row.querySelector('td:nth-child(4)');
                            var ownerCell = row.querySelector('td:nth-child(5)');
                            var name = (nameCell ? (nameCell.textContent || nameCell.innerText) : '').toLowerCase();
                            var email = (emailCell ? (emailCell.textContent || emailCell.innerText) : '').toLowerCase();
                            var phone = (phoneCell ? (phoneCell.textContent || phoneCell.innerText) : '').toLowerCase();
                            var owner = (ownerCell ? (ownerCell.textContent || ownerCell.innerText) : '').toLowerCase();
                            if (!q || name.indexOf(q) !== -1 || email.indexOf(q) !== -1 || phone.indexOf(q) !== -1 || owner.indexOf(q) !== -1) {
                                row.classList.remove('js-filter-hidden');
                                shown++;
                            } else {
                                row.classList.add('js-filter-hidden');
                                hidden++;
                            }
                        }
                    });
                    // console.log('people search results - shown:', shown, 'hidden:', hidden);
                }, 200);
            });
        });