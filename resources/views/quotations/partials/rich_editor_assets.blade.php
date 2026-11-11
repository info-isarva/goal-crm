<style>
    .qt-rich-wrap {
        border: 1px solid #ccc;
        border-radius: 0;
        background: #fff;
        overflow: visible;
    }
    .qt-rich-wrap .crm-quill-wrap,
    .qt-rich-wrap .crm-quill-wrap.qt-rich-quill {
        border: 0;
        border-radius: 0;
    }
    .qt-rich-label { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .qt-rich-hint { font-size: .78rem; color: #6c757d; }
    #projectDetailPanel { margin-top: .75rem; }
    #projectDetailPanel textarea#project_detail.qt-rich-editor {
        position: absolute;
        left: -10000px;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }
    #btnToggleProjectDetail.is-open .bi-plus-lg { display: none; }
    #btnToggleProjectDetail.is-open .bi-dash-lg { display: inline-block !important; }
    .qt-html-validate-ok { color: #198754; font-size: .8rem; }
    .qt-html-validate-err { color: #dc3545; font-size: .8rem; }
</style>
