 document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('leadViewSearchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                var filter = searchInput.value.toLowerCase();
                var dropdown = searchInput.closest('ul');
                var items = dropdown.querySelectorAll('a.dropdown-item');
                items.forEach(function(item) {
                    var text = item.textContent || item.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        item.parentElement.style.display = '';
                    } else {
                        item.parentElement.style.display = 'none';
                    }
                });
            });
        }
    });
