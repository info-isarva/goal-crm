 (function(){
    const el = document.getElementById('company-success-alert');
    if (!el) return;
    // Hide after 2 seconds
    setTimeout(() => {
        try { el.style.transition = 'opacity 300ms ease'; el.style.opacity = '0'; setTimeout(()=>el.remove(), 350); } catch(e) {}
    }, 2000);
})();
 
 
 (function(){
    const countrySelect = document.getElementById('company-country');
    const codeInput = document.getElementById('currency_code');
    const symInput = document.getElementById('currency_symbol');
    if (!countrySelect) return;
    countrySelect.addEventListener('change', function(){
        const opt = this.options[this.selectedIndex];
        const code = opt.dataset.code || '';
        const symbol = opt.dataset.symbol || '';
        if (code) codeInput.value = code;
        if (symbol) symInput.value = symbol;
    });
})();
