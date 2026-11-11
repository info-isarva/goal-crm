<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-sa-copy]');
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('data-sa-copy'));
    if (!input) return;
    var done = function () {
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied';
        setTimeout(function () { btn.innerHTML = original; }, 1600);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(done);
    } else {
        input.select();
        document.execCommand('copy');
        done();
    }
});
if (window.jQuery && jQuery.fn.select2) {
    jQuery('.sa-select2').each(function () {
        jQuery(this).select2({ width: '100%', placeholder: jQuery(this).data('placeholder') || 'Select', closeOnSelect: false });
    });
}
</script>
