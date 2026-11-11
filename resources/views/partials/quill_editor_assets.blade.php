{{-- Shared Quill + quill-table-better (full table editing on all CRM editors) --}}
@once
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill-table-better@1.1.6/dist/quill-table-better.css" rel="stylesheet">
<style>
.crm-quill-wrap {
    border: 1px solid #ccc;
    border-radius: 0;
    background: #fff;
    overflow: visible;
    position: relative;
    box-shadow: none;
}
/* TinyMCE-like menubar (Quill only — same File…Help layout) */
.crm-quill-menubar {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    align-items: center;
    background: #fff;
    border: 0;
    border-bottom: 1px solid #eee;
    padding: 0 8px;
    min-height: 39px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 14px;
    user-select: none;
}
.crm-quill-menubar .crm-quill-menu { position: relative; }
.crm-quill-menubar .crm-quill-menu > button {
    align-items: center;
    background: transparent;
    border: 0;
    border-radius: 3px;
    color: #222f3e;
    cursor: pointer;
    display: flex;
    font-family: inherit;
    font-size: 14px;
    font-weight: 400;
    height: 28px;
    line-height: 1;
    margin: 5px 0 6px;
    padding: 0 5px;
}
.crm-quill-menubar .crm-quill-menu > button:hover {
    background: #dee0e2;
    color: #222f3e;
}
.crm-quill-menubar .crm-quill-menu.is-open > button {
    background: #cce2fa;
    color: #222f3e;
}
.crm-quill-menubar .crm-quill-menu-panel {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 60;
    min-width: 220px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    padding: 4px 0;
    margin-top: 1px;
}
.crm-quill-menubar .crm-quill-menu.is-open .crm-quill-menu-panel { display: block; }
.crm-quill-menubar .crm-quill-menu-panel button {
    display: flex;
    width: 100%;
    text-align: left;
    border: 0;
    background: transparent;
    border-radius: 0;
    padding: 6px 12px;
    color: #222f3e;
    font-family: inherit;
    font-size: 14px;
    cursor: pointer;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    white-space: nowrap;
    line-height: 1.3;
}
.crm-quill-menubar .crm-quill-menu-panel button:hover {
    background: #2a6edb;
    color: #fff;
}
.crm-quill-menubar .crm-quill-menu-panel button:hover .crm-menu-shortcut {
    color: #dbeafe;
}
.crm-quill-menubar .crm-quill-menu-panel button .crm-menu-shortcut {
    color: #776d6d;
    font-size: 12px;
    margin-left: auto;
}
.crm-quill-menubar .crm-quill-menu-panel button.is-check::after {
    content: '✓';
    color: #2a6edb;
    font-weight: 700;
    margin-left: 8px;
}
.crm-quill-menubar .crm-quill-menu-panel button:hover.is-check::after {
    color: #fff;
}
.crm-quill-menubar .crm-quill-menu-sep {
    height: 1px;
    background: #ccc;
    margin: 4px 0;
}
.crm-quill-menubar .crm-quill-submenu {
    position: relative;
}
.crm-quill-menubar .crm-quill-submenu > button::after {
    content: '›';
    font-size: 16px;
    color: inherit;
    margin-left: auto;
    padding-left: 16px;
}
.crm-quill-menubar .crm-quill-submenu-panel {
    display: none;
    position: absolute;
    left: calc(100% - 4px);
    top: -4px;
    min-width: 180px;
    max-height: 280px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    padding: 4px 0;
    z-index: 70;
}
.crm-quill-menubar .crm-quill-submenu:hover > .crm-quill-submenu-panel,
.crm-quill-menubar .crm-quill-submenu.is-open > .crm-quill-submenu-panel {
    display: block;
}
.crm-quill-menubar .crm-quill-submenu-panel button:hover {
    background: #2a6edb;
    color: #fff;
}
/* TinyMCE-like toolbar strip under menubar */
.crm-quill-wrap .ql-toolbar.ql-snow {
    border: 0;
    border-bottom: 1px solid #eee;
    background: #fff;
    padding: 4px 6px;
    flex-wrap: wrap;
}
.crm-quill-wrap.is-show-blocks .ql-editor > * {
    outline: 1px dashed #93c5fd;
    outline-offset: 1px;
}
.crm-quill-wrap.is-show-invisibles .ql-editor p::after,
.crm-quill-wrap.is-show-invisibles .ql-editor li::after {
    content: '¶';
    color: #94a3b8;
    margin-left: 2px;
    font-size: 0.75em;
}
.crm-quill-wrap.is-visual-aids .ql-editor table td,
.crm-quill-wrap.is-visual-aids .ql-editor table th {
    outline: 1px dotted #cbd5e1;
}
.crm-quill-char-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 0.25rem;
    padding: 0.5rem;
    max-width: 320px;
}
.crm-quill-char-grid button {
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 0.25rem;
    padding: 0.35rem;
    cursor: pointer;
    font-size: 1rem;
}
.crm-quill-char-grid button:hover { background: #eef2ff; }
.crm-quill-find-box {
    display: none;
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 80;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
    padding: 0.65rem;
    width: 280px;
}
.crm-quill-find-box.is-open { display: block; }
.crm-quill-find-box input {
    width: 100%;
    margin-bottom: 0.35rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.3rem;
    padding: 0.35rem 0.5rem;
    font-size: 0.82rem;
}
.crm-quill-find-box .crm-find-actions {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
}
.crm-quill-find-box .crm-find-actions button {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    border-radius: 0.3rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    cursor: pointer;
}
/* Font family + size picker labels (style attributors) */
.crm-quill-wrap .ql-snow .ql-picker.ql-font { width: 155px; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size { width: 72px; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-options {
    max-height: 280px;
    overflow-y: auto;
}
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item::before {
    content: 'Sans Serif';
}
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="sans-serif"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="sans-serif"]::before { content: 'Sans Serif'; font-family: sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="serif"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="serif"]::before { content: 'Serif'; font-family: serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="monospace"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="monospace"]::before { content: 'Monospace'; font-family: monospace; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Arial"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Arial"]::before { content: 'Arial'; font-family: Arial, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Arial Black"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Arial Black"]::before { content: 'Arial Black'; font-family: 'Arial Black', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Helvetica"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Helvetica"]::before { content: 'Helvetica'; font-family: Helvetica, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Verdana"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Verdana"]::before { content: 'Verdana'; font-family: Verdana, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Tahoma"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Tahoma"]::before { content: 'Tahoma'; font-family: Tahoma, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Trebuchet MS"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Trebuchet MS"]::before { content: 'Trebuchet MS'; font-family: 'Trebuchet MS', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Times New Roman"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Times New Roman"]::before { content: 'Times New Roman'; font-family: 'Times New Roman', serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Georgia"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Georgia"]::before { content: 'Georgia'; font-family: Georgia, serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Garamond"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Garamond"]::before { content: 'Garamond'; font-family: Garamond, serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Palatino Linotype"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Palatino Linotype"]::before { content: 'Palatino'; font-family: 'Palatino Linotype', Palatino, serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Courier New"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Courier New"]::before { content: 'Courier New'; font-family: 'Courier New', monospace; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Lucida Console"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Lucida Console"]::before { content: 'Lucida Console'; font-family: 'Lucida Console', monospace; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Consolas"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Consolas"]::before { content: 'Consolas'; font-family: Consolas, monospace; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Calibri"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Calibri"]::before { content: 'Calibri'; font-family: Calibri, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Cambria"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Cambria"]::before { content: 'Cambria'; font-family: Cambria, serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Candara"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Candara"]::before { content: 'Candara'; font-family: Candara, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Segoe UI"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Segoe UI"]::before { content: 'Segoe UI'; font-family: 'Segoe UI', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Comic Sans MS"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Comic Sans MS"]::before { content: 'Comic Sans MS'; font-family: 'Comic Sans MS', cursive; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Impact"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Impact"]::before { content: 'Impact'; font-family: Impact, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Lucida Sans Unicode"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Lucida Sans Unicode"]::before { content: 'Lucida Sans'; font-family: 'Lucida Sans Unicode', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Roboto"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Roboto"]::before { content: 'Roboto'; font-family: Roboto, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Open Sans"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Open Sans"]::before { content: 'Open Sans'; font-family: 'Open Sans', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Lato"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Lato"]::before { content: 'Lato'; font-family: Lato, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Montserrat"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Montserrat"]::before { content: 'Montserrat'; font-family: Montserrat, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Poppins"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Poppins"]::before { content: 'Poppins'; font-family: Poppins, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Nunito"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Nunito"]::before { content: 'Nunito'; font-family: Nunito, sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="DejaVu Sans"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="DejaVu Sans"]::before { content: 'DejaVu Sans'; font-family: 'DejaVu Sans', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Noto Sans"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Noto Sans"]::before { content: 'Noto Sans'; font-family: 'Noto Sans', sans-serif; }
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="Source Sans Pro"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="Source Sans Pro"]::before { content: 'Source Sans Pro'; font-family: 'Source Sans Pro', sans-serif; }

.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item::before { content: '14px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="10px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="10px"]::before { content: '10px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="12px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="12px"]::before { content: '12px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="14px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="14px"]::before { content: '14px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="16px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="16px"]::before { content: '16px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="18px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="18px"]::before { content: '18px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="20px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="20px"]::before { content: '20px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="24px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="24px"]::before { content: '24px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="28px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="28px"]::before { content: '28px'; }
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="32px"]::before,
.crm-quill-wrap .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="32px"]::before { content: '32px'; }
.crm-quill-wrap .ql-container.ql-snow {
    border: 0;
    font-size: 0.95rem;
}
.crm-quill-wrap .ql-editor {
    min-height: 220px;
    line-height: 1.55;
    color: #202124;
}
/* Match Word-style numbered sections: bold title + indented body */
.crm-quill-wrap .ql-editor p.ql-indent-1,
.crm-quill-wrap .ql-editor .ql-indent-1 {
    padding-left: 1.5em;
}
.crm-quill-wrap .ql-editor p.ql-indent-2,
.crm-quill-wrap .ql-editor .ql-indent-2 {
    padding-left: 3em;
}
.crm-quill-wrap .ql-editor p.ql-indent-3,
.crm-quill-wrap .ql-editor .ql-indent-3 {
    padding-left: 4.5em;
}
.crm-quill-wrap .ql-editor ol {
    padding-left: 1.5em;
    margin: 0 0 0.75rem;
}
.crm-quill-wrap .ql-editor ol > li {
    margin-bottom: 0.65rem;
    padding-left: 0.35em;
}
.crm-quill-wrap .ql-editor ol > li > p {
    margin: 0.25rem 0;
}
.crm-quill-wrap .ql-editor p {
    margin: 0 0 0.4rem;
}
.crm-quill-wrap.crm-quill-tall .ql-editor { min-height: 420px; }
.crm-quill-wrap.crm-quill-medium .ql-editor { min-height: 280px; }
.crm-quill-wrap.crm-quill-short .ql-editor { min-height: 200px; }
.crm-quill-wrap .ql-editor.ql-blank::before {
    color: #94a3b8;
    font-style: normal;
}
/* Email layout: Quill host is hidden; editable canvas keeps designed HTML visible */
.crm-quill-wrap.qet-email-editor .ql-editor.ql-blank::before {
    display: none !important;
}
.crm-quill-wrap.qet-email-editor .ql-editor.crm-quill-email-host {
    display: none !important;
}
.crm-quill-wrap.qet-email-editor .crm-email-canvas {
    white-space: normal;
    min-height: 280px;
    outline: none;
    overflow-y: auto;
    background: #fff;
    color: #202124;
}
.crm-quill-wrap.qet-email-editor.crm-quill-tall .crm-email-canvas { min-height: 420px; }
.crm-quill-wrap.qet-email-editor.crm-quill-medium .crm-email-canvas { min-height: 280px; }
.crm-quill-wrap.qet-email-editor .crm-email-canvas img {
    max-width: 100%;
    height: auto;
}
.crm-quill-wrap.qet-email-editor .crm-email-canvas table {
    width: 100% !important;
    table-layout: auto !important;
    border-collapse: collapse;
}
.crm-quill-wrap.qet-email-editor .crm-email-canvas td,
.crm-quill-wrap.qet-email-editor .crm-email-canvas th {
    white-space: normal !important;
    word-break: normal !important;
    min-width: 80px;
    vertical-align: top;
}
.crm-quill-wrap.qet-email-editor .ql-editor {
    white-space: normal;
}
.crm-quill-wrap.qet-email-editor .ql-editor img {
    max-width: 100%;
    height: auto;
}
.crm-quill-wrap .ql-editor span[style],
.crm-quill-wrap .ql-editor p[style],
.crm-quill-wrap .ql-editor div[style],
.crm-quill-wrap .ql-editor li[style],
.crm-quill-wrap .ql-editor td[style],
.crm-quill-wrap .ql-editor th[style] {
    line-height: inherit;
}
.crm-quill-wrap.is-source .ql-container { display: none; }
.crm-quill-wrap.is-source .crm-email-canvas { display: none !important; }
.crm-quill-source {
    display: none;
    width: 100%;
    min-height: 220px;
    border: 0;
    outline: 0;
    resize: vertical;
    padding: 0.85rem 1rem;
    font-family: Consolas, "Courier New", monospace;
    font-size: 0.85rem;
    line-height: 1.5;
    background: #0b1220;
    color: #e2e8f0;
    box-sizing: border-box;
}
.crm-quill-wrap.is-source .crm-quill-source { display: block; }
.crm-quill-wrap.crm-quill-tall .crm-quill-source { min-height: 420px; }
.crm-quill-wrap.crm-quill-medium .crm-quill-source { min-height: 280px; }
.crm-quill-wrap.crm-quill-short .crm-quill-source { min-height: 200px; }
.crm-quill-wrap .ql-toolbar .ql-source,
.crm-quill-wrap .ql-toolbar .ql-preview,
.crm-quill-wrap .ql-toolbar .ql-fullscreen,
.crm-quill-wrap .ql-toolbar .ql-help,
.crm-quill-wrap .ql-toolbar .ql-undo,
.crm-quill-wrap .ql-toolbar .ql-redo {
    width: auto !important;
    min-width: 28px;
    padding: 0 6px !important;
}
.crm-quill-wrap .ql-toolbar button.ql-preview,
.crm-quill-wrap .ql-toolbar button.ql-fullscreen,
.crm-quill-wrap .ql-toolbar button.ql-undo,
.crm-quill-wrap .ql-toolbar button.ql-redo {
    min-width: 42px !important;
}
.crm-quill-wrap .ql-toolbar button.ql-source svg,
.crm-quill-wrap .ql-toolbar button.ql-preview svg,
.crm-quill-wrap .ql-toolbar button.ql-fullscreen svg,
.crm-quill-wrap .ql-toolbar button.ql-help svg,
.crm-quill-wrap .ql-toolbar button.ql-undo svg,
.crm-quill-wrap .ql-toolbar button.ql-redo svg {
    display: none !important;
}
.crm-quill-wrap .ql-toolbar .ql-source::before {
    content: '</>';
    font-family: Consolas, monospace;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
}
.crm-quill-wrap .ql-toolbar .ql-preview::before { content: 'Preview'; font-size: 10px; font-weight: 600; }
.crm-quill-wrap .ql-toolbar .ql-fullscreen::before { content: 'Full'; font-size: 10px; font-weight: 600; }
.crm-quill-wrap .ql-toolbar .ql-help::before { content: '?'; font-weight: 700; font-size: 12px; }
.crm-quill-wrap .ql-toolbar .ql-undo::before { content: 'Undo'; font-size: 10px; font-weight: 600; }
.crm-quill-wrap .ql-toolbar .ql-redo::before { content: 'Redo'; font-size: 10px; font-weight: 600; }
.crm-quill-wrap.is-source .ql-toolbar .ql-source,
.crm-quill-wrap.is-fullscreen .ql-toolbar .ql-fullscreen {
    background: #e2e8f0;
    border-radius: 3px;
}
.crm-quill-source-hint {
    display: none;
    font-size: 0.75rem;
    color: #64748b;
    background: #f1f5f9;
    border-top: 1px solid #e2e8f0;
    padding: 0.35rem 0.75rem;
}
.crm-quill-wrap.is-source .crm-quill-source-hint { display: block; }
.crm-quill-placeholders {
    display: inline-block;
    margin-left: 0.25rem;
    vertical-align: middle;
}
.crm-quill-placeholders select {
    height: 26px;
    font-size: 0.78rem;
    padding: 0 0.35rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.25rem;
    background: #fff;
    max-width: 150px;
}
.crm-quill-wrap.is-fullscreen {
    position: fixed !important;
    inset: 0;
    z-index: 1055;
    border-radius: 0;
    border: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
    overflow: auto;
}
.crm-quill-wrap.is-fullscreen .ql-container { flex: 1; overflow: auto; }
.crm-quill-wrap.is-fullscreen .ql-editor,
.crm-quill-wrap.is-fullscreen .crm-quill-source {
    min-height: calc(100vh - 140px) !important;
}
.crm-quill-preview-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1060;
    background: rgba(15, 23, 42, 0.45);
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}
.crm-quill-preview-backdrop.is-open { display: flex; }
.crm-quill-preview-modal {
    background: #fff;
    width: min(900px, 96vw);
    max-height: 90vh;
    border-radius: 0.5rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.crm-quill-preview-modal header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
}
.crm-quill-preview-modal header button {
    border: 0;
    background: #f1f5f9;
    border-radius: 0.35rem;
    padding: 0.25rem 0.65rem;
    cursor: pointer;
}
.crm-quill-preview-body {
    padding: 1rem 1.25rem;
    overflow: auto;
    line-height: 1.55;
}
.crm-quill-preview-body table {
    border-collapse: collapse;
    width: 100%;
}
.crm-quill-preview-body td,
.crm-quill-preview-body th {
    border: 1px solid #cbd5e1;
    padding: 6px 8px;
}
/* TinyMCE-like status bar */
.crm-quill-statusbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    border-top: 1px solid #eee;
    background: #fff;
    color: #222f3e;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 12px;
    min-height: 24px;
    padding: 2px 8px;
}
/* Keep table-better UI above editor chrome */
.crm-quill-wrap .ql-table-better,
.ql-table-better-wrapper,
.ql-table-menus-container,
.ql-table-select-container,
.ql-table-properties-form,
.ql-cell-properties-form {
    z-index: 70 !important;
}
.crm-quill-wrap .ql-editor table {
    border-collapse: collapse;
    width: 100%;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1.1.6/dist/quill-table-better.js"></script>
<script>
(function () {
    if (window.CrmQuill) return;
    if (typeof Quill === 'undefined') return;

    var hasTableBetter = typeof QuillTableBetter !== 'undefined';
    if (hasTableBetter) {
        try {
            Quill.register({ 'modules/table-better': QuillTableBetter }, true);
        } catch (err) {
            console.warn('CrmQuill table-better register', err);
            hasTableBetter = false;
        }
    }

    try {
        var FontStyle = Quill.import('attributors/style/font');
        var SizeStyle = Quill.import('attributors/style/size');
        var ColorStyle = Quill.import('attributors/style/color');
        var BackgroundStyle = Quill.import('attributors/style/background');
        var AlignStyle = Quill.import('attributors/style/align');
        var DirectionStyle = Quill.import('attributors/style/direction');

        // Explicit list — toolbar must use this same array (font: [] only shows Quill's 3 defaults)
        var FONT_FAMILIES = [
            'Arial', 'Arial Black', 'Helvetica', 'Verdana', 'Tahoma', 'Trebuchet MS',
            'Times New Roman', 'Georgia', 'Garamond', 'Palatino Linotype',
            'Courier New', 'Lucida Console', 'Consolas',
            'Calibri', 'Cambria', 'Candara', 'Segoe UI',
            'Comic Sans MS', 'Impact', 'Lucida Sans Unicode',
            'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Nunito',
            'DejaVu Sans', 'Noto Sans', 'Source Sans Pro',
            'sans-serif', 'serif', 'monospace'
        ];
        var FONT_SIZES = [
            '8px', '9px', '10px', '11px', '12px', '13px', '14px', '15px', '16px',
            '18px', '20px', '22px', '24px', '26px', '28px', '32px', '36px', '48px', '72px'
        ];

        FontStyle.whitelist = FONT_FAMILIES.slice();
        SizeStyle.whitelist = FONT_SIZES.concat([
            '8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '16pt', '18pt', '20pt', '24pt', '36pt'
        ]);

        Quill.register(FontStyle, true);
        Quill.register(SizeStyle, true);
        Quill.register(ColorStyle, true);
        Quill.register(BackgroundStyle, true);
        Quill.register(AlignStyle, true);
        Quill.register(DirectionStyle, true);
    } catch (err) {
        console.warn('CrmQuill style attributors', err);
        var FONT_FAMILIES = ['Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Courier New', 'Verdana', 'Tahoma', 'Calibri', 'Segoe UI'];
        var FONT_SIZES = ['10px', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'];
    }

    var Delta = Quill.import('delta');

    var FULL_TOOLBAR = [
        ['undo', 'redo'],
        [{ font: FONT_FAMILIES }, { size: FONT_SIZES }],
        [{ header: [1, 2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ script: 'sub' }, { script: 'super' }],
        [{ color: [] }, { background: [] }],
        [{ align: [] }],
        [{ indent: '-1' }, { indent: '+1' }],
        [{ list: 'bullet' }, { list: 'ordered' }],
        ['blockquote', 'code-block', 'link', 'image'].concat(hasTableBetter ? ['table-better'] : ['table']),
        ['clean', 'source', 'preview', 'fullscreen'],
        ['help']
    ];

    // Email templates: no table tooling (Quill table-better mangles email layouts)
    var EMAIL_TOOLBAR = [
        ['undo', 'redo'],
        [{ font: FONT_FAMILIES }, { size: FONT_SIZES }],
        [{ header: [1, 2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ color: [] }, { background: [] }],
        [{ align: [] }],
        [{ list: 'bullet' }, { list: 'ordered' }],
        ['link', 'image'],
        ['clean', 'source', 'preview', 'fullscreen']
    ];

    try {
        var icons = Quill.import('ui/icons');
        icons['source'] = '';
        icons['preview'] = '';
        icons['fullscreen'] = '';
        icons['help'] = '';
        icons['undo'] = '';
        icons['redo'] = '';
        if (!hasTableBetter) icons['table'] = '▦';
    } catch (e) { /* ignore */ }

    function normalizeHtml(html) {
        if (!html) return '';
        var trimmed = String(html).trim();
        if (trimmed === '' || trimmed === '<p><br></p>' || trimmed === '<p></p>') {
            return '';
        }
        trimmed = trimmed
            .replace(/\s*ql-table-block\s*/g, ' ')
            .replace(/\s*ql-cell-focused\s*/g, ' ')
            .replace(/\s*ql-cell-selected\s*/g, ' ')
            .replace(/\s*ql-cursor\s*/g, ' ')
            .replace(/\sclass="\s*"/g, '')
            .replace(/\sclass='\s*'/g, '');
        return trimmed;
    }

    /** True when HTML is blank Quill placeholder / whitespace only (no real email content). */
    function isEffectivelyEmptyEmailHtml(html) {
        var s = String(html || '')
            .replace(/<!--[\s\S]*?-->/g, '')
            .replace(/<p><br\s*\/?><\/p>/gi, '')
            .replace(/<p>\s*<\/p>/gi, '')
            .replace(/<div[^>]*>\s*<\/div>/gi, '')
            .replace(/<br\s*\/?>/gi, '')
            .replace(/&nbsp;/gi, ' ')
            .replace(/<[^>]+>/g, '')
            .replace(/\s+/g, '')
            .trim();
        return s === '';
    }

    function paintEmailHtml(quill, html) {
        if (!quill) return;
        var value = html || quill.__crmEmailHtml || '';
        if (!value) return;
        quill.__crmEmailHtml = value;
        if (quill.__crmEmailCanvas) {
            quill.__crmEmailCanvas.innerHTML = value;
            return;
        }
        if (!quill.root) return;
        quill.root.innerHTML = value;
        quill.root.classList.remove('ql-blank');
    }

    function readEmailHtml(quill) {
        if (!quill) return '';
        if (quill.__crmEmailCanvas) {
            var canvasHtml = quill.__crmEmailCanvas.innerHTML || '';
            if (!isEffectivelyEmptyEmailHtml(canvasHtml)) {
                quill.__crmEmailHtml = canvasHtml;
                return canvasHtml;
            }
        }
        if (quill.__crmEmailHtml) return quill.__crmEmailHtml;
        var live = quill.root ? (quill.root.innerHTML || '') : '';
        return isEffectivelyEmptyEmailHtml(live) ? '' : live;
    }

    function guardEmailHtmlPaint(quill) {
        if (!quill || !quill.__crmEmailHtml) return;
        // Canvas mode does not need Quill DOM guards
        if (quill.__crmEmailCanvas) return;
        if (!quill.root) return;
        var until = Date.now() + 800;
        var obs = new MutationObserver(function () {
            if (Date.now() > until) {
                try { obs.disconnect(); } catch (e) { /* ignore */ }
                return;
            }
            if (isEffectivelyEmptyEmailHtml(quill.root.innerHTML) && quill.__crmEmailHtml) {
                paintEmailHtml(quill, quill.__crmEmailHtml);
            }
        });
        try {
            obs.observe(quill.root, { childList: true, subtree: true, characterData: true });
            setTimeout(function () {
                try { obs.disconnect(); } catch (e2) { /* ignore */ }
            }, 850);
        } catch (e3) { /* ignore */ }
    }

    /**
     * Email templates use nested styled HTML Quill cannot keep in its Delta.
     * Show/edit that HTML in a contenteditable canvas beside a disabled Quill host.
     */
    function attachEmailCanvas(wrap, quill, ta, options) {
        options = options || {};
        if (!wrap || !quill || quill.__crmEmailCanvas) return quill.__crmEmailCanvas || null;

        var container = wrap.querySelector('.ql-container');
        if (!container) return null;

        quill.root.classList.add('crm-quill-email-host');
        try { quill.root.setAttribute('contenteditable', 'false'); } catch (e) { /* ignore */ }

        var canvas = document.createElement('div');
        canvas.className = 'ql-editor crm-email-canvas';
        canvas.setAttribute('data-crm-email-canvas', '1');
        canvas.setAttribute('contenteditable', 'true');
        canvas.setAttribute('spellcheck', 'true');
        if (options.placeholder) {
            canvas.setAttribute('data-placeholder', options.placeholder);
        }
        container.appendChild(canvas);

        quill.__crmEmailCanvas = canvas;
        wrap.__crmEmailCanvas = canvas;
        wrap.classList.add('has-email-canvas');

        function emitChange() {
            var html = canvas.innerHTML || '';
            quill.__crmEmailHtml = html;
            if (ta) ta.value = html;
            if (typeof options.onChange === 'function') options.onChange(html);
            if (typeof options.onCanvasChange === 'function') options.onCanvasChange(html);
        }

        canvas.addEventListener('input', emitChange);
        canvas.addEventListener('blur', emitChange);
        canvas.addEventListener('focus', function () {
            wrap.classList.add('is-email-canvas-focused');
        });
        canvas.addEventListener('blur', function () {
            wrap.classList.remove('is-email-canvas-focused');
        });

        var html = quill.__crmEmailHtml || (ta && ta.value) || '';
        if (html) {
            canvas.innerHTML = html;
            quill.__crmEmailHtml = html;
            if (ta) ta.value = html;
        }

        // Map common Quill toolbar actions onto the canvas selection
        var toolbar = quill.getModule('toolbar');
        if (toolbar && typeof toolbar.addHandler === 'function') {
            var exec = function (command, value) {
                canvas.focus();
                try { document.execCommand(command, false, value || null); } catch (err) { /* ignore */ }
                emitChange();
            };
            ['bold', 'italic', 'underline', 'strike'].forEach(function (fmt) {
                toolbar.addHandler(fmt, function () { exec(fmt === 'strike' ? 'strikeThrough' : fmt); });
            });
            toolbar.addHandler('link', function () {
                var url = window.prompt('Enter link URL');
                if (!url) return;
                exec('createLink', url);
            });
            toolbar.addHandler('clean', function () { exec('removeFormat'); });
            toolbar.addHandler('align', function (value) {
                var map = { center: 'justifyCenter', right: 'justifyRight', justify: 'justifyFull', '': 'justifyLeft', left: 'justifyLeft' };
                exec(map[value] || 'justifyLeft');
            });
            toolbar.addHandler('list', function (value) {
                exec(value === 'ordered' ? 'insertOrderedList' : 'insertUnorderedList');
            });
            toolbar.addHandler('color', function (value) {
                exec('foreColor', value || '#000000');
            });
            toolbar.addHandler('background', function (value) {
                exec('hiliteColor', value || '#ffffff');
            });
        }

        wrap.__crmEmailCanvasSync = emitChange;
        return canvas;
    }

    function insertIntoEmailEditor(quill, text) {
        if (!quill || text == null) return;
        var canvas = quill.__crmEmailCanvas;
        if (canvas) {
            canvas.focus();
            try {
                if (!document.execCommand('insertText', false, String(text))) {
                    // Fallback for browsers that reject insertText
                    var sel = window.getSelection();
                    if (sel && sel.rangeCount) {
                        var range = sel.getRangeAt(0);
                        range.deleteContents();
                        range.insertNode(document.createTextNode(String(text)));
                        range.collapse(false);
                        sel.removeAllRanges();
                        sel.addRange(range);
                    } else {
                        canvas.innerHTML = (canvas.innerHTML || '') + String(text);
                    }
                }
            } catch (e) {
                canvas.innerHTML = (canvas.innerHTML || '') + String(text);
            }
            quill.__crmEmailHtml = canvas.innerHTML || '';
            return;
        }
        var range = quill.getSelection(true);
        quill.insertText(range ? range.index : quill.getLength(), String(text), 'user');
    }

    function countStyleAttrs(html) {
        return (String(html || '').match(/\sstyle\s*=/gi) || []).length;
    }

    function rgbToHex(value) {
        if (!value) return value;
        var m = String(value).match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
        if (!m) return value;
        return '#' + [m[1], m[2], m[3]].map(function (n) {
            return ('0' + parseInt(n, 10).toString(16)).slice(-2);
        }).join('');
    }

    function normalizeFontFamily(family) {
        if (!family) return '';
        return String(family).split(',')[0].replace(/["']/g, '').trim();
    }

    function normalizeFontSize(size) {
        if (!size) return '';
        size = String(size).trim().toLowerCase();
        if (/^\d+(\.\d+)?$/.test(size)) return size + 'px';
        return size;
    }

    function parseLengthToPx(value) {
        if (!value) return 0;
        var s = String(value).trim().toLowerCase();
        var n = parseFloat(s);
        if (isNaN(n)) return 0;
        if (s.indexOf('pt') !== -1) return n * (96 / 72);
        if (s.indexOf('cm') !== -1) return n * 37.8;
        if (s.indexOf('in') !== -1) return n * 96;
        if (s.indexOf('em') !== -1) return n * 16;
        return n;
    }

    function marginToIndentLevel(marginValue) {
        var px = parseLengthToPx(marginValue);
        if (px >= 96) return 3;
        if (px >= 64) return 2;
        if (px >= 24) return 1;
        return 0;
    }

    function isBoldish(el) {
        if (!el || el.nodeType !== 1) return false;
        var tag = el.tagName.toLowerCase();
        if (tag === 'b' || tag === 'strong') return true;
        var fw = (el.style && el.style.fontWeight) || '';
        if (/bold|^[6-9]00$/.test(fw)) return true;
        return !!(el.querySelector && el.querySelector('b, strong'));
    }

    /**
     * Normalize pasted Word/HTML so numbered bold headings + indented bodies
     * look like the Terms screenshot inside Quill.
     */
    function normalizeStructuredPasteHtml(html) {
        if (!html) return '';
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');

        // Word list paragraphs → keep as block paragraphs with detected indent
        doc.querySelectorAll('p, div, li, h1, h2, h3, h4').forEach(function (el) {
            var style = el.getAttribute('style') || '';
            var margin = el.style.marginLeft || '';
            var m = style.match(/margin-left\s*:\s*([^;]+)/i);
            if (!margin && m) margin = m[1].trim();
            // Word often uses text-indent + margin on MsoListParagraph
            var textIndent = el.style.textIndent || '';
            var ti = style.match(/text-indent\s*:\s*([^;]+)/i);
            if (!textIndent && ti) textIndent = ti[1].trim();
            var level = marginToIndentLevel(margin);
            if (!level && /MsoListParagraph/i.test(el.className || '')) {
                level = 1;
            }
            // Negative text-indent with positive margin = list hanging indent → treat as level 0 heading
            if (parseLengthToPx(textIndent) < 0 && parseLengthToPx(margin) > 0) {
                level = 0;
            }
            if (level > 0) {
                el.classList.add('ql-indent-' + level);
                el.style.marginLeft = '';
                el.style.textIndent = '';
                // refresh style attr without margins
                var cleanedStyle = (el.getAttribute('style') || '')
                    .replace(/margin-left\s*:[^;]+;?/ig, '')
                    .replace(/text-indent\s*:[^;]+;?/ig, '')
                    .replace(/;;+/g, ';')
                    .replace(/^;|;$/g, '')
                    .trim();
                if (cleanedStyle) el.setAttribute('style', cleanedStyle);
                else el.removeAttribute('style');
            }
        });

        // Flatten complex <ol>/<ul> (nested <p> inside <li>) into bold title + indented body paragraphs
        doc.querySelectorAll('ol, ul').forEach(function (list) {
            var complex = false;
            Array.prototype.forEach.call(list.children, function (li) {
                if (li.tagName && li.tagName.toLowerCase() === 'li') {
                    if (li.querySelector('p, div, ul, ol, br + br')) complex = true;
                }
            });
            if (!complex) return;

            var frag = doc.createDocumentFragment();
            var index = 1;
            Array.prototype.forEach.call(Array.prototype.slice.call(list.children), function (li) {
                if (!li.tagName || li.tagName.toLowerCase() !== 'li') return;
                var blocks = [];
                var childNodes = Array.prototype.slice.call(li.childNodes);
                var inlineBuf = doc.createElement('div');

                function flushInline() {
                    var t = (inlineBuf.textContent || '').replace(/\u00a0/g, ' ').trim();
                    if (t) {
                        var p = doc.createElement('p');
                        while (inlineBuf.firstChild) p.appendChild(inlineBuf.firstChild);
                        blocks.push(p);
                    } else {
                        inlineBuf.innerHTML = '';
                    }
                }

                childNodes.forEach(function (node) {
                    if (node.nodeType === 1 && /^(P|DIV|H1|H2|H3|H4|UL|OL)$/i.test(node.tagName)) {
                        flushInline();
                        blocks.push(node);
                    } else if (node.nodeType === 1 && node.tagName.toLowerCase() === 'br') {
                        flushInline();
                    } else {
                        inlineBuf.appendChild(node.cloneNode(true));
                    }
                });
                flushInline();

                if (!blocks.length) {
                    var pEmpty = doc.createElement('p');
                    pEmpty.innerHTML = li.innerHTML;
                    blocks.push(pEmpty);
                }

                blocks.forEach(function (block, i) {
                    var p = doc.createElement('p');
                    p.innerHTML = block.innerHTML || block.textContent || '';
                    var text = (p.textContent || '').replace(/\s+/g, ' ').trim();
                    if (!text) return;

                    if (i === 0) {
                        // Ensure "N. Title" bold heading
                        var alreadyNumbered = /^\d+\.\s*/.test(text);
                        var titleHtml = p.innerHTML;
                        if (!alreadyNumbered && list.tagName.toLowerCase() === 'ol') {
                            titleHtml = index + '. ' + titleHtml;
                        }
                        if (!isBoldish(p)) {
                            p.innerHTML = '<strong>' + titleHtml + '</strong>';
                        } else if (!alreadyNumbered && list.tagName.toLowerCase() === 'ol') {
                            p.innerHTML = '<strong>' + index + '. </strong>' + p.innerHTML;
                        }
                    } else {
                        p.classList.add('ql-indent-1');
                    }
                    frag.appendChild(p);
                });
                index += 1;
            });
            list.parentNode.replaceChild(frag, list);
        });

        // Detect plain paragraphs that look like "1. Title" (bold) followed by indented bodies
        // Ensure heading lines stay bold and non-indented
        doc.querySelectorAll('p').forEach(function (p) {
            var text = (p.textContent || '').replace(/\s+/g, ' ').trim();
            if (/^\d+\.\s+\S/.test(text) && isBoldish(p)) {
                p.classList.remove('ql-indent-1', 'ql-indent-2', 'ql-indent-3');
            }
        });

        return doc.body.innerHTML;
    }

    function cleanPastedHtml(html) {
        if (!html) return '';
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        doc.querySelectorAll('script, style, meta, link, xml, w\\:sdt, o\\:p').forEach(function (el) {
            el.remove();
        });
        doc.querySelectorAll('*').forEach(function (el) {
            [
                'id', 'lang', 'dir',
                'xmlns', 'xmlns:w', 'xmlns:o', 'xmlns:v',
                'data-mce-style', 'data-mce-href', 'contenteditable'
            ].forEach(function (attr) {
                if (el.hasAttribute && el.hasAttribute(attr)) el.removeAttribute(attr);
            });
            if (el.hasAttribute && el.hasAttribute('class')) {
                var cls = el.getAttribute('class') || '';
                var kept = cls.split(/\s+/).filter(function (c) {
                    // Keep ql-indent-* so Quill recognizes indentation
                    return c && (!/^ql-/.test(c) || /^ql-indent-\d+$/.test(c)) && !/^mso/i.test(c);
                });
                if (kept.length) el.setAttribute('class', kept.join(' '));
                else el.removeAttribute('class');
            }
            if (el.hasAttribute && el.hasAttribute('bgcolor')) {
                var bg = el.getAttribute('bgcolor');
                var prev = el.getAttribute('style') || '';
                if (bg && !/background(-color)?\s*:/i.test(prev)) {
                    el.setAttribute('style', (prev ? prev.replace(/;?\s*$/, ';') : '') + 'background-color:' + bg);
                }
                el.removeAttribute('bgcolor');
            }
            if (el.hasAttribute && el.hasAttribute('align')) {
                var al = el.getAttribute('align');
                var st = el.getAttribute('style') || '';
                if (al && !/text-align\s*:/i.test(st)) {
                    el.setAttribute('style', (st ? st.replace(/;?\s*$/, ';') : '') + 'text-align:' + al);
                }
                el.removeAttribute('align');
            }
            if (el.hasAttribute && el.hasAttribute('style')) {
                var style = el.getAttribute('style') || '';
                style = style.replace(/mso-[^:;]+:[^;]+;?/gi, '');
                style = style.replace(/;;+/g, ';').replace(/^;|;$/g, '').trim();
                if (style) el.setAttribute('style', style);
                else el.removeAttribute('style');
            }
        });

        // Convert <b> wrapping whole first line patterns already fine; unwrap empty spans
        return normalizeStructuredPasteHtml(doc.body.innerHTML);
    }

    function styleAttrsFromNode(node) {
        if (!node || node.nodeType !== 1) return {};
        var extra = {};

        if (node.style) {
            var color = node.style.color;
            var bg = node.style.backgroundColor || node.style.background;
            var font = normalizeFontFamily(node.style.fontFamily);
            var size = normalizeFontSize(node.style.fontSize);
            if (color) extra.color = rgbToHex(color);
            if (bg && bg !== 'transparent' && bg !== 'rgba(0, 0, 0, 0)') extra.background = rgbToHex(bg);
            if (font) {
                try {
                    var FontStyleDyn = Quill.import('attributors/style/font');
                    if (FontStyleDyn.whitelist && FontStyleDyn.whitelist.indexOf(font) === -1) {
                        FontStyleDyn.whitelist.push(font);
                    }
                } catch (e) { /* ignore */ }
                extra.font = font;
            }
            if (size) {
                try {
                    var SizeStyleDyn = Quill.import('attributors/style/size');
                    if (SizeStyleDyn.whitelist && SizeStyleDyn.whitelist.indexOf(size) === -1) {
                        SizeStyleDyn.whitelist.push(size);
                    }
                } catch (e) { /* ignore */ }
                extra.size = size;
            }
            if (node.style.textAlign) extra.align = node.style.textAlign;
        }
        return extra;
    }

    function wordCount(html) {
        var text = String(html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        if (!text) return 0;
        return text.split(' ').length;
    }

    function getTableModule(quill) {
        return quill ? quill.getModule('table-better') : null;
    }

    /**
     * Export from live DOM — NOT getSemanticHTML (that rebuilds from Delta and drops styles).
     * @param {boolean} cleanupTable  When true, hide table-better UI chrome before export (use on save).
     */
    function exportHtmlFromQuill(quill, cleanupTable) {
        if (!quill) return '';
        if (cleanupTable) {
            var mod = getTableModule(quill);
            if (mod) {
                try {
                    if (typeof mod.hideTools === 'function') mod.hideTools();
                    if (typeof mod.deleteTableTemporary === 'function') mod.deleteTableTemporary();
                } catch (err) { /* ignore */ }
            }
        }

        var clone = quill.root.cloneNode(true);
        clone.querySelectorAll('.ql-ui, .ql-cursor, .ql-table-selection-line').forEach(function (el) {
            el.remove();
        });
        clone.querySelectorAll('*').forEach(function (el) {
            ['contenteditable', 'data-table-temporary'].forEach(function (attr) {
                if (el.hasAttribute && el.hasAttribute(attr)) el.removeAttribute(attr);
            });
            if (el.hasAttribute && el.hasAttribute('class')) {
                var cls = el.getAttribute('class') || '';
                var kept = cls.split(/\s+/).filter(function (c) {
                    return c && !/^ql-/.test(c);
                });
                if (kept.length) el.setAttribute('class', kept.join(' '));
                else el.removeAttribute('class');
            }
        });
        return normalizeHtml(clone.innerHTML);
    }

    // Live sync (keystrokes) — keep styles, don't tear down table UI every time
    function prepareTableHtmlForSave(quill) {
        return exportHtmlFromQuill(quill, false);
    }

    // Final save / getHtml — clean table-better temporary nodes
    function exportHtmlForPersist(quill) {
        return exportHtmlFromQuill(quill, true);
    }

    function lightCleanEmailHtml(html) {
        if (!html) return '';
        var parser = new DOMParser();
        var doc = parser.parseFromString(String(html), 'text/html');
        doc.querySelectorAll('script, iframe, object, embed').forEach(function (el) {
            el.remove();
        });
        return (doc.body && doc.body.innerHTML) ? doc.body.innerHTML.trim() : String(html).trim();
    }

    /**
     * Load existing HTML into Quill without losing styles.
     * Email layouts must skip clipboard table conversion (quill-table-better
     * turns nested email tables into many 1-character columns).
     */
    function loadHtmlIntoQuill(quill, html, options) {
        var raw = html || '';
        var preferRaw = !!(options && options.preferRawHtml) || !!quill.__crmEmailLayout;
        var value = preferRaw ? lightCleanEmailHtml(raw) : (cleanPastedHtml(raw) || String(raw).trim());
        if (!value && raw) value = String(raw).trim();
        if (!value) {
            try { quill.setText(''); } catch (e) { quill.root.innerHTML = '<p><br></p>'; }
            quill.__crmEmailHtml = '';
            return;
        }

        var hasTable = /<table[\s>]/i.test(value);

        // Email composers: keep designed HTML on canvas / recoverable store.
        if (preferRaw) {
            quill.__crmEmailHtml = value;
            if (quill.__crmEmailCanvas) {
                quill.__crmEmailCanvas.innerHTML = value;
            } else {
                paintEmailHtml(quill, value);
                setTimeout(function () { paintEmailHtml(quill, value); }, 0);
                setTimeout(function () { paintEmailHtml(quill, value); }, 50);
                setTimeout(function () { paintEmailHtml(quill, value); }, 150);
                guardEmailHtmlPaint(quill);
            }
            try {
                if (quill.history && typeof quill.history.clear === 'function') quill.history.clear();
            } catch (e) { /* ignore */ }
            return;
        }

        var inStyles = countStyleAttrs(value);
        var hasRich = inStyles > 0;

        try {
            quill.setContents([], 'silent');
            if (typeof quill.clipboard.dangerouslyPasteHTML === 'function') {
                quill.clipboard.dangerouslyPasteHTML(0, value, 'silent');
            } else {
                var delta = quill.clipboard.convert({ html: value });
                quill.updateContents(delta, 'silent');
            }
        } catch (err) {
            quill.root.innerHTML = value;
            return;
        }

        var outStyles = countStyleAttrs(quill.root.innerHTML || '');
        var emptied = !quill.getText().trim() && /<[a-z][\s\S]*>/i.test(value);
        var lostStyles = hasRich && inStyles > 0 && outStyles < Math.max(1, Math.floor(inStyles * 0.6));

        if (emptied || lostStyles) {
            quill.root.innerHTML = value;
            quill.root.classList.remove('ql-blank');
        }
    }

    function ensurePreviewModal() {
        var el = document.getElementById('crmQuillPreviewBackdrop');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'crmQuillPreviewBackdrop';
        el.className = 'crm-quill-preview-backdrop';
        el.innerHTML = ''
            + '<div class="crm-quill-preview-modal" role="dialog" aria-modal="true">'
            + '  <header><span>Preview</span><button type="button" data-close>Close</button></header>'
            + '  <div class="crm-quill-preview-body"></div>'
            + '</div>';
        document.body.appendChild(el);
        el.addEventListener('click', function (e) {
            if (e.target === el || e.target.getAttribute('data-close') !== null) {
                el.classList.remove('is-open');
            }
        });
        return el;
    }

    function showPreview(html) {
        var backdrop = ensurePreviewModal();
        var body = backdrop.querySelector('.crm-quill-preview-body');
        if (body) body.innerHTML = html || '<p><em>Nothing to preview.</em></p>';
        backdrop.classList.add('is-open');
    }

    function showHelp() {
        alert(
            'Quill editor menus\n\n'
            + 'File — New, Preview, Print\n'
            + 'Edit — Undo/Redo, Cut/Copy/Paste, Select all, Find & replace\n'
            + 'View — Source, Visual aids, Invisible chars, Blocks, Preview, Fullscreen\n'
            + 'Insert — Image, Link, Media, Table, Special char, HR, NBSP, Anchor, Date/time\n'
            + 'Format — Bold/Italic/…, Blocks, Fonts, Sizes, Align, Line height, Colors\n'
            + 'Tools — Source code, Word count\n'
            + 'Table — Insert / row / column / cell / delete (click a cell for table bar)\n'
            + 'Help — Alt+0\n\n'
            + 'Shortcuts: Ctrl+B/I/U, Ctrl+Z/Y, Ctrl+F, Ctrl+Shift+F (fullscreen)'
        );
    }

    function insertFallbackTable(quill) {
        var rows = parseInt(window.prompt('Number of rows', '3'), 10);
        var cols = parseInt(window.prompt('Number of columns', '3'), 10);
        if (!rows || !cols || rows < 1 || cols < 1) return;
        rows = Math.min(rows, 20);
        cols = Math.min(cols, 10);
        var html = '<table><tbody>';
        for (var r = 0; r < rows; r++) {
            html += '<tr>';
            for (var c = 0; c < cols; c++) {
                html += r === 0 ? '<th>Header</th>' : '<td>&nbsp;</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table><p><br></p>';
        var range = quill.getSelection(true);
        var index = range ? range.index : quill.getLength();
        var pasted = quill.clipboard.convert({ html: html });
        quill.updateContents(new Delta().retain(index).concat(pasted), 'user');
    }

    function insertTableBetter(quill) {
        var mod = getTableModule(quill);
        if (!mod || typeof mod.insertTable !== 'function') {
            insertFallbackTable(quill);
            return;
        }
        var rows = parseInt(window.prompt('Number of rows', '3'), 10);
        var cols = parseInt(window.prompt('Number of columns', '3'), 10);
        if (!rows || !cols || rows < 1 || cols < 1) return;
        mod.insertTable(Math.min(rows, 20), Math.min(cols, 10));
    }

    function currentHtml(wrap, quill, ta) {
        if (wrap && wrap.__crmQuillIsSource && wrap.__crmQuillIsSource()) {
            var sourceEl = wrap.querySelector('.crm-quill-source');
            return sourceEl ? sourceEl.value : (ta ? ta.value : '');
        }
        if (quill && quill.__crmEmailLayout) {
            return readEmailHtml(quill) || (ta ? ta.value : '') || '';
        }
        return prepareTableHtmlForSave(quill);
    }

    function attachSourceCodeToggle(wrap, quill, ta, onSync) {
        var sourceTa = document.createElement('textarea');
        sourceTa.className = 'crm-quill-source';
        sourceTa.setAttribute('spellcheck', 'false');
        sourceTa.setAttribute('aria-label', 'HTML source code');
        wrap.appendChild(sourceTa);

        var hint = document.createElement('div');
        hint.className = 'crm-quill-source-hint';
        hint.textContent = 'Source code mode — edit HTML, then click </> again to return to the visual editor.';
        wrap.appendChild(hint);

        var inSource = false;

        function applySourceToEditor() {
            var value = sourceTa.value || '';
            if (quill.__crmEmailLayout) {
                quill.__crmEmailHtml = value;
                if (quill.__crmEmailCanvas) {
                    quill.__crmEmailCanvas.innerHTML = value;
                } else {
                    loadHtmlIntoQuill(quill, value, { preferRawHtml: true });
                }
                ta.value = value;
            } else {
                loadHtmlIntoQuill(quill, value, { preferRawHtml: false });
                ta.value = prepareTableHtmlForSave(quill) || normalizeHtml(value);
            }
            if (typeof onSync === 'function') onSync();
        }

        function openSource() {
            if (quill.__crmEmailLayout) {
                sourceTa.value = readEmailHtml(quill) || ta.value || '';
            } else {
                sourceTa.value = prepareTableHtmlForSave(quill) || ta.value || '';
            }
            wrap.classList.add('is-source');
            inSource = true;
            sourceTa.focus();
        }

        function closeSource() {
            applySourceToEditor();
            wrap.classList.remove('is-source');
            inSource = false;
            if (quill.__crmEmailCanvas) {
                quill.__crmEmailCanvas.focus();
            } else {
                quill.focus();
            }
        }

        function toggleSource() {
            if (inSource) closeSource();
            else openSource();
        }

        sourceTa.addEventListener('input', function () {
            ta.value = sourceTa.value;
            if (typeof onSync === 'function') onSync();
        });

        wrap.__crmQuillToggleSource = toggleSource;
        wrap.__crmQuillIsSource = function () { return inSource; };
        wrap.__crmQuillFlushSource = function () {
            if (inSource) applySourceToEditor();
        };
        return toggleSource;
    }

    function ensureToolbarButton(toolbarEl, className, title, onClick) {
        if (!toolbarEl) return null;
        var btn = toolbarEl.querySelector('button.ql-' + className);
        if (!btn) {
            var group = document.createElement('span');
            group.className = 'ql-formats';
            btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ql-' + className;
            group.appendChild(btn);
            toolbarEl.appendChild(group);
        }
        btn.type = 'button';
        btn.title = title;
        btn.setAttribute('aria-label', title);
        // Always bind click — Quill creates custom buttons but often never fires their handlers
        if (btn.getAttribute('data-crm-bound') === '1') return btn;
        btn.setAttribute('data-crm-bound', '1');
        btn.innerHTML = '';
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                onClick();
            } catch (err) {
                console.warn('CrmQuill toolbar:', className, err);
            }
        });
        return btn;
    }

    function getHistoryModule(quill) {
        return quill.getModule('history') || quill.history || null;
    }

    function attachPlaceholders(wrap, quill, items) {
        if (!items || !items.length || !wrap) return;
        var toolbarEl = wrap.querySelector('.ql-toolbar');
        if (!toolbarEl || toolbarEl.querySelector('.crm-quill-placeholders')) return;
        var group = document.createElement('span');
        group.className = 'ql-formats crm-quill-placeholders';
        var select = document.createElement('select');
        select.innerHTML = '<option value="">Placeholders</option>';
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.value;
            opt.textContent = item.text;
            select.appendChild(opt);
        });
        select.addEventListener('change', function () {
            var token = select.value;
            if (!token) return;
            var range = quill.getSelection(true);
            var index = range ? range.index : quill.getLength();
            quill.insertText(index, token, 'user');
            quill.setSelection(index + token.length, 0);
            select.value = '';
        });
        group.appendChild(select);
        toolbarEl.appendChild(group);
    }

    function attachMenubar(wrap, quill, ta, actions) {
        if (wrap.querySelector('.crm-quill-menubar')) return;
        var bar = document.createElement('div');
        bar.className = 'crm-quill-menubar';
        bar.setAttribute('role', 'menubar');

        var fontItems = (typeof FONT_FAMILIES !== 'undefined' ? FONT_FAMILIES : []).map(function (f) {
            return { label: f, action: 'font:' + f };
        });
        var sizeItems = (typeof FONT_SIZES !== 'undefined' ? FONT_SIZES : []).map(function (s) {
            return { label: s, action: 'size:' + s };
        });

        var menus = [
            {
                label: 'File',
                items: [
                    { label: 'New document', action: 'new-doc', shortcut: '' },
                    { label: 'Preview', action: 'preview', shortcut: '' },
                    { label: 'Print…', action: 'print', shortcut: 'Ctrl+P' }
                ]
            },
            {
                label: 'Edit',
                items: [
                    { label: 'Undo', action: 'undo', shortcut: 'Ctrl+Z' },
                    { label: 'Redo', action: 'redo', shortcut: 'Ctrl+Y' },
                    { sep: true },
                    { label: 'Cut', action: 'cut', shortcut: 'Ctrl+X' },
                    { label: 'Copy', action: 'copy', shortcut: 'Ctrl+C' },
                    { label: 'Paste', action: 'paste', shortcut: 'Ctrl+V' },
                    { label: 'Paste as text', action: 'paste-text' },
                    { sep: true },
                    { label: 'Select all', action: 'select-all', shortcut: 'Ctrl+A' },
                    { label: 'Find and replace…', action: 'find-replace', shortcut: 'Ctrl+F' }
                ]
            },
            {
                label: 'View',
                items: [
                    { label: 'Source code', action: 'source' },
                    { sep: true },
                    { label: 'Visual aids', action: 'toggle-visual-aids', check: 'visual-aids' },
                    { label: 'Show invisible characters', action: 'toggle-invisibles', check: 'invisibles' },
                    { label: 'Show blocks', action: 'toggle-blocks', check: 'blocks' },
                    { sep: true },
                    { label: 'Preview', action: 'preview' },
                    { label: 'Fullscreen', action: 'fullscreen', shortcut: 'Ctrl+Shift+F' }
                ]
            },
            {
                label: 'Insert',
                items: [
                    { label: 'Image…', action: 'image' },
                    { label: 'Link…', action: 'link', shortcut: 'Ctrl+K' },
                    { label: 'Media…', action: 'media' },
                    { label: 'Table…', action: 'table-insert' },
                    { sep: true },
                    { label: 'Special character…', action: 'special-char' },
                    { label: 'Horizontal line', action: 'hr' },
                    { sep: true },
                    { label: 'Nonbreaking space', action: 'nbsp' },
                    { label: 'Anchor…', action: 'anchor' },
                    { sep: true },
                    { label: 'Date/time', action: 'datetime' }
                ]
            },
            {
                label: 'Format',
                items: [
                    { label: 'Bold', action: 'bold', shortcut: 'Ctrl+B' },
                    { label: 'Italic', action: 'italic', shortcut: 'Ctrl+I' },
                    { label: 'Underline', action: 'underline', shortcut: 'Ctrl+U' },
                    { label: 'Strikethrough', action: 'strike' },
                    { label: 'Superscript', action: 'script-super' },
                    { label: 'Subscript', action: 'script-sub' },
                    { label: 'Code', action: 'code-block' },
                    { sep: true },
                    {
                        label: 'Blocks',
                        submenu: [
                            { label: 'Paragraph', action: 'format-p' },
                            { label: 'Heading 1', action: 'format-h1' },
                            { label: 'Heading 2', action: 'format-h2' },
                            { label: 'Heading 3', action: 'format-h3' },
                            { label: 'Heading 4', action: 'format-h4' },
                            { label: 'Blockquote', action: 'blockquote' },
                            { label: 'Code block', action: 'code-block' }
                        ]
                    },
                    { label: 'Fonts', submenu: fontItems },
                    { label: 'Font sizes', submenu: sizeItems },
                    {
                        label: 'Align',
                        submenu: [
                            { label: 'Left', action: 'align-left' },
                            { label: 'Center', action: 'align-center' },
                            { label: 'Right', action: 'align-right' },
                            { label: 'Justify', action: 'align-justify' }
                        ]
                    },
                    {
                        label: 'Line height',
                        submenu: [
                            { label: '1', action: 'lineheight:1' },
                            { label: '1.15', action: 'lineheight:1.15' },
                            { label: '1.5', action: 'lineheight:1.5' },
                            { label: '1.75', action: 'lineheight:1.75' },
                            { label: '2', action: 'lineheight:2' },
                            { label: '2.5', action: 'lineheight:2.5' }
                        ]
                    },
                    { sep: true },
                    { label: 'Text color…', action: 'text-color' },
                    { label: 'Background color…', action: 'bg-color' },
                    { sep: true },
                    { label: 'Clear formatting', action: 'clean' }
                ]
            },
            {
                label: 'Tools',
                items: [
                    { label: 'Source code', action: 'source' },
                    { label: 'Word count', action: 'wordcount' }
                ]
            },
            {
                label: 'Table',
                items: [
                    { label: 'Insert table…', action: 'table-insert' },
                    { sep: true },
                    { label: 'Cell properties…', action: 'table-cell' },
                    { label: 'Insert row above', action: 'table-row-above' },
                    { label: 'Insert row below', action: 'table-row-below' },
                    { label: 'Delete row', action: 'table-row-delete' },
                    { label: 'Insert column left', action: 'table-col-left' },
                    { label: 'Insert column right', action: 'table-col-right' },
                    { label: 'Delete column', action: 'table-col-delete' },
                    { sep: true },
                    { label: 'Merge / split cells', action: 'table-merge' },
                    { label: 'Table properties…', action: 'table-props' },
                    { label: 'Delete table', action: 'table-delete' }
                ]
            },
            {
                label: 'Help',
                items: [
                    { label: 'Help', action: 'help', shortcut: 'Alt+0' }
                ]
            }
        ];

        function closeAll() {
            bar.querySelectorAll('.crm-quill-menu.is-open').forEach(function (m) {
                m.classList.remove('is-open');
            });
            bar.querySelectorAll('.crm-quill-submenu.is-open').forEach(function (m) {
                m.classList.remove('is-open');
            });
        }

        function refreshChecks() {
            bar.querySelectorAll('[data-check]').forEach(function (btn) {
                var key = btn.getAttribute('data-check');
                var on = false;
                if (key === 'visual-aids') on = wrap.classList.contains('is-visual-aids');
                if (key === 'invisibles') on = wrap.classList.contains('is-show-invisibles');
                if (key === 'blocks') on = wrap.classList.contains('is-show-blocks');
                btn.classList.toggle('is-check', on);
            });
        }

        function addItems(panel, items) {
            items.forEach(function (item) {
                if (item.sep) {
                    var sep = document.createElement('div');
                    sep.className = 'crm-quill-menu-sep';
                    panel.appendChild(sep);
                    return;
                }
                if (item.submenu && item.submenu.length) {
                    var subWrap = document.createElement('div');
                    subWrap.className = 'crm-quill-submenu';
                    var subBtn = document.createElement('button');
                    subBtn.type = 'button';
                    subBtn.textContent = item.label;
                    subBtn.addEventListener('mouseenter', function () {
                        bar.querySelectorAll('.crm-quill-submenu.is-open').forEach(function (s) {
                            s.classList.remove('is-open');
                        });
                        subWrap.classList.add('is-open');
                    });
                    var subPanel = document.createElement('div');
                    subPanel.className = 'crm-quill-submenu-panel';
                    addItems(subPanel, item.submenu);
                    subWrap.appendChild(subBtn);
                    subWrap.appendChild(subPanel);
                    panel.appendChild(subWrap);
                    return;
                }
                var itemBtn = document.createElement('button');
                itemBtn.type = 'button';
                var labelSpan = document.createElement('span');
                labelSpan.textContent = item.label;
                itemBtn.appendChild(labelSpan);
                if (item.shortcut) {
                    var sc = document.createElement('span');
                    sc.className = 'crm-menu-shortcut';
                    sc.textContent = item.shortcut;
                    itemBtn.appendChild(sc);
                }
                if (item.check) itemBtn.setAttribute('data-check', item.check);
                itemBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeAll();
                    if (typeof actions.run === 'function') actions.run(item.action);
                    refreshChecks();
                });
                panel.appendChild(itemBtn);
            });
        }

        function anyMenuOpen() {
            return !!bar.querySelector('.crm-quill-menu.is-open');
        }

        menus.forEach(function (menu) {
            var wrapMenu = document.createElement('div');
            wrapMenu.className = 'crm-quill-menu';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = menu.label;
            // TinyMCE behavior: click to open; while open, hover switches menus
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var open = wrapMenu.classList.contains('is-open');
                closeAll();
                if (!open) {
                    wrapMenu.classList.add('is-open');
                    refreshChecks();
                }
            });
            btn.addEventListener('mouseenter', function () {
                if (!anyMenuOpen()) return;
                closeAll();
                wrapMenu.classList.add('is-open');
                refreshChecks();
            });
            var panel = document.createElement('div');
            panel.className = 'crm-quill-menu-panel';
            addItems(panel, menu.items);
            wrapMenu.appendChild(btn);
            wrapMenu.appendChild(panel);
            bar.appendChild(wrapMenu);
        });

        document.addEventListener('click', function () { closeAll(); });

        var toolbar = wrap.querySelector('.ql-toolbar');
        if (toolbar) wrap.insertBefore(bar, toolbar);
        else wrap.insertBefore(bar, wrap.firstChild);

        // Find/replace floating box
        var findBox = document.createElement('div');
        findBox.className = 'crm-quill-find-box';
        findBox.innerHTML = ''
            + '<input type="text" data-find placeholder="Find…">'
            + '<input type="text" data-replace placeholder="Replace with…">'
            + '<div class="crm-find-actions">'
            + '  <button type="button" data-act="find-next">Find next</button>'
            + '  <button type="button" data-act="replace">Replace</button>'
            + '  <button type="button" data-act="replace-all">Replace all</button>'
            + '  <button type="button" data-act="close">Close</button>'
            + '</div>';
        wrap.appendChild(findBox);
        wrap.__crmQuillFindBox = findBox;
    }

    function attachAdvancedTools(wrap, quill, ta, options, onSync) {
        options = options || {};
        var toolbar = quill.getModule('toolbar');
        var toolbarEl = wrap.querySelector('.ql-toolbar');
        var toggleSource = attachSourceCodeToggle(wrap, quill, ta, onSync);

        function toggleFullscreen() {
            wrap.classList.toggle('is-fullscreen');
            document.body.style.overflow = wrap.classList.contains('is-fullscreen') ? 'hidden' : '';
        }

        function insertTextAtCursor(text) {
            var range = quill.getSelection(true);
            var index = range ? range.index : quill.getLength();
            quill.insertText(index, text, 'user');
            quill.setSelection(index + text.length, 0);
        }

        function insertHtmlSnippet(html) {
            var range = quill.getSelection(true);
            var index = range ? range.index : quill.getLength();
            var pasted = quill.clipboard.convert({ html: html });
            quill.updateContents(new Delta().retain(index).concat(pasted), 'user');
        }

        function applyLineHeight(value) {
            var range = quill.getSelection(true);
            if (!range) return;
            var leaf = quill.getLeaf(range.index);
            var node = leaf && leaf[0] ? leaf[0].domNode : null;
            while (node && node !== quill.root) {
                if (node.nodeType === 1 && /^(P|DIV|LI|H1|H2|H3|H4|H5|H6)$/i.test(node.tagName)) {
                    node.style.lineHeight = value;
                    if (typeof onSync === 'function') onSync();
                    return;
                }
                node = node.parentNode;
            }
        }

        function openSpecialCharPicker() {
            var chars = '©®™€£¥§¶†‡•…–—‘’“”«»°±×÷≠≈∞µαβπΩ←→↑↓♠♣♥♦★☆✓✗'.split('');
            var backdrop = ensurePreviewModal();
            var body = backdrop.querySelector('.crm-quill-preview-body');
            var header = backdrop.querySelector('header span');
            if (header) header.textContent = 'Special character';
            if (!body) return;
            body.innerHTML = '<div class="crm-quill-char-grid"></div>';
            var grid = body.querySelector('.crm-quill-char-grid');
            chars.forEach(function (ch) {
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = ch;
                b.addEventListener('click', function () {
                    insertTextAtCursor(ch);
                    backdrop.classList.remove('is-open');
                });
                grid.appendChild(b);
            });
            backdrop.classList.add('is-open');
        }

        function openFindReplace() {
            var box = wrap.__crmQuillFindBox;
            if (!box) return;
            box.classList.add('is-open');
            var findInput = box.querySelector('[data-find]');
            if (findInput) findInput.focus();
            if (box.getAttribute('data-bound') === '1') return;
            box.setAttribute('data-bound', '1');
            box.addEventListener('click', function (e) {
                e.stopPropagation();
                var act = e.target.getAttribute('data-act');
                if (!act) return;
                var findVal = (box.querySelector('[data-find]') || {}).value || '';
                var replaceVal = (box.querySelector('[data-replace]') || {}).value || '';
                if (act === 'close') {
                    box.classList.remove('is-open');
                    return;
                }
                if (!findVal) return;
                var text = quill.getText();
                if (act === 'find-next') {
                    var from = (quill.getSelection() || { index: 0 }).index + 1;
                    var pos = text.indexOf(findVal, from);
                    if (pos < 0) pos = text.indexOf(findVal);
                    if (pos >= 0) quill.setSelection(pos, findVal.length);
                    else alert('No matches found.');
                } else if (act === 'replace') {
                    var sel = quill.getSelection();
                    if (sel && quill.getText(sel.index, sel.length) === findVal) {
                        quill.deleteText(sel.index, sel.length, 'user');
                        quill.insertText(sel.index, replaceVal, 'user');
                    }
                } else if (act === 'replace-all') {
                    var full = quill.getText();
                    var next = full.split(findVal).join(replaceVal);
                    if (next !== full) {
                        quill.setText(next);
                        alert('Replaced all occurrences.');
                    }
                }
            });
        }

        function run(action) {
            var mod = getTableModule(quill);
            var hist = getHistoryModule(quill);
            if (!action) return;

            if (action.indexOf('font:') === 0) {
                quill.format('font', action.slice(5));
                return;
            }
            if (action.indexOf('size:') === 0) {
                quill.format('size', action.slice(5));
                return;
            }
            if (action.indexOf('lineheight:') === 0) {
                applyLineHeight(action.slice(11));
                return;
            }

            switch (action) {
                case 'undo':
                    if (hist && typeof hist.undo === 'function') hist.undo();
                    break;
                case 'redo':
                    if (hist && typeof hist.redo === 'function') hist.redo();
                    break;
                case 'source':
                    toggleSource();
                    break;
                case 'preview':
                    showPreview(currentHtml(wrap, quill, ta));
                    break;
                case 'fullscreen':
                    toggleFullscreen();
                    break;
                case 'print':
                    showPreview(currentHtml(wrap, quill, ta));
                    setTimeout(function () { window.print(); }, 200);
                    break;
                case 'new-doc':
                    if (confirm('Clear the editor and start a new document?')) {
                        quill.setText('');
                        if (typeof onSync === 'function') onSync();
                    }
                    break;
                case 'cut':
                    document.execCommand('cut');
                    break;
                case 'copy':
                    document.execCommand('copy');
                    break;
                case 'paste':
                    alert('Use Ctrl+V to paste (browser security blocks menu paste).');
                    break;
                case 'paste-text':
                    if (navigator.clipboard && navigator.clipboard.readText) {
                        navigator.clipboard.readText().then(function (text) {
                            insertTextAtCursor(text || '');
                        }).catch(function () {
                            alert('Clipboard access denied. Use Ctrl+Shift+V.');
                        });
                    } else {
                        alert('Use Ctrl+Shift+V to paste as plain text.');
                    }
                    break;
                case 'select-all':
                    quill.setSelection(0, quill.getLength());
                    break;
                case 'find-replace':
                    openFindReplace();
                    break;
                case 'toggle-visual-aids':
                    wrap.classList.toggle('is-visual-aids');
                    break;
                case 'toggle-invisibles':
                    wrap.classList.toggle('is-show-invisibles');
                    break;
                case 'toggle-blocks':
                    wrap.classList.toggle('is-show-blocks');
                    break;
                case 'table':
                case 'table-insert':
                    insertTableBetter(quill);
                    break;
                case 'table-delete':
                    if (mod && typeof mod.deleteTable === 'function') mod.deleteTable();
                    else alert('Click inside a table first, then use Delete table.');
                    break;
                case 'table-cell':
                case 'table-props':
                case 'table-merge':
                case 'table-row-above':
                case 'table-row-below':
                case 'table-row-delete':
                case 'table-col-left':
                case 'table-col-right':
                case 'table-col-delete':
                    alert('Click inside a table cell to open the table toolbar for row / column / merge / cell / properties.');
                    break;
                case 'table-tip':
                    alert(
                        'Table editing\n\n'
                        + '1. Insert a table from Insert / Table menu\n'
                        + '2. Click any cell to open the table action bar\n'
                        + '3. Use Column / Row / Merge / Cell / Table / Delete'
                    );
                    break;
                case 'help':
                    showHelp();
                    break;
                case 'clean': {
                    var range = quill.getSelection(true);
                    if (range && range.length) {
                        quill.removeFormat(range.index, range.length, 'user');
                    } else if (range) {
                        var line = quill.getLine(range.index);
                        if (line && line[0]) {
                            quill.removeFormat(quill.getIndex(line[0]), line[0].length(), 'user');
                        } else {
                            quill.removeFormat(0, quill.getLength(), 'user');
                        }
                    } else {
                        quill.removeFormat(0, quill.getLength(), 'user');
                    }
                    break;
                }
                case 'link': {
                    var url = window.prompt('Enter URL');
                    if (url) quill.format('link', url);
                    break;
                }
                case 'image':
                    if (toolbar && toolbar.handlers && typeof toolbar.handlers.image === 'function') {
                        toolbar.handlers.image.call(toolbar);
                    } else {
                        var imgUrl = window.prompt('Image URL');
                        if (imgUrl) {
                            var r = quill.getSelection(true);
                            quill.insertEmbed(r ? r.index : quill.getLength(), 'image', imgUrl, 'user');
                        }
                    }
                    break;
                case 'media': {
                    var mediaUrl = window.prompt('Enter video / media URL');
                    if (mediaUrl) {
                        var mr = quill.getSelection(true);
                        quill.insertEmbed(mr ? mr.index : quill.getLength(), 'video', mediaUrl, 'user');
                    }
                    break;
                }
                case 'hr':
                    insertHtmlSnippet('<hr><p><br></p>');
                    break;
                case 'nbsp':
                    insertTextAtCursor('\u00a0');
                    break;
                case 'anchor': {
                    var anchorId = window.prompt('Anchor name / id', 'section-1');
                    if (anchorId) insertHtmlSnippet('<a id="' + anchorId.replace(/[^a-zA-Z0-9\-_]/g, '') + '"></a>');
                    break;
                }
                case 'datetime':
                    insertTextAtCursor(new Date().toLocaleString());
                    break;
                case 'special-char':
                    openSpecialCharPicker();
                    break;
                case 'text-color': {
                    var tc = window.prompt('Text color (e.g. #111827 or red)', '#111827');
                    if (tc) quill.format('color', tc);
                    break;
                }
                case 'bg-color': {
                    var bc = window.prompt('Background color (e.g. #fef3c7)', '#fef3c7');
                    if (bc) quill.format('background', bc);
                    break;
                }
                case 'script-super':
                    quill.format('script', quill.getFormat().script === 'super' ? false : 'super');
                    break;
                case 'script-sub':
                    quill.format('script', quill.getFormat().script === 'sub' ? false : 'sub');
                    break;
                case 'code-block':
                    quill.format('code-block', !quill.getFormat()['code-block']);
                    break;
                case 'align-left':
                    quill.format('align', false);
                    break;
                case 'align-center':
                    quill.format('align', 'center');
                    break;
                case 'align-right':
                    quill.format('align', 'right');
                    break;
                case 'align-justify':
                    quill.format('align', 'justify');
                    break;
                case 'bold':
                case 'italic':
                case 'underline':
                case 'strike':
                case 'blockquote':
                    quill.format(action, !quill.getFormat()[action]);
                    break;
                case 'bullet':
                case 'ordered':
                    quill.format('list', action);
                    break;
                case 'format-p':
                    quill.format('header', false);
                    break;
                case 'format-h1':
                    quill.format('header', 1);
                    break;
                case 'format-h2':
                    quill.format('header', 2);
                    break;
                case 'format-h3':
                    quill.format('header', 3);
                    break;
                case 'format-h4':
                    quill.format('header', 4);
                    break;
                case 'wordcount': {
                    var html = currentHtml(wrap, quill, ta);
                    var words = wordCount(html);
                    var chars = String(quill.getText() || '').replace(/\n/g, '').length;
                    alert('Word count: ' + words + '\nCharacters: ' + chars);
                    break;
                }
                default:
                    break;
            }
        }

        if (toolbar) {
            toolbar.addHandler('undo', function () { run('undo'); });
            toolbar.addHandler('redo', function () { run('redo'); });
            toolbar.addHandler('source', function () { run('source'); });
            toolbar.addHandler('preview', function () { run('preview'); });
            toolbar.addHandler('fullscreen', function () { run('fullscreen'); });
            toolbar.addHandler('help', function () { run('help'); });
            toolbar.addHandler('clean', function () { run('clean'); });
            if (!hasTableBetter) {
                toolbar.addHandler('table', function () { run('table-insert'); });
            }
        }

        ensureToolbarButton(toolbarEl, 'undo', 'Undo', function () { run('undo'); });
        ensureToolbarButton(toolbarEl, 'redo', 'Redo', function () { run('redo'); });
        ensureToolbarButton(toolbarEl, 'source', 'Source code', function () { run('source'); });
        ensureToolbarButton(toolbarEl, 'preview', 'Preview', function () { run('preview'); });
        ensureToolbarButton(toolbarEl, 'fullscreen', 'Fullscreen', function () { run('fullscreen'); });
        ensureToolbarButton(toolbarEl, 'help', 'Help', function () { run('help'); });
        ensureToolbarButton(toolbarEl, 'clean', 'Clear formatting', function () { run('clean'); });
        if (!hasTableBetter) {
            ensureToolbarButton(toolbarEl, 'table', 'Insert table', function () { run('table-insert'); });
        }

        if (options.menubar === true) {
            attachMenubar(wrap, quill, ta, { run: run });
        }
        if (options.placeholders) {
            attachPlaceholders(wrap, quill, options.placeholders);
        }

        var status = document.createElement('div');
        status.className = 'crm-quill-statusbar';
        status.innerHTML = '<span class="crm-quill-path">p</span><span class="crm-quill-words">0 words</span>';
        wrap.appendChild(status);
        function refreshStatus() {
            var pathEl = status.querySelector('.crm-quill-path');
            var wordsEl = status.querySelector('.crm-quill-words');
            var html = currentHtml(wrap, quill, ta);
            if (wordsEl) wordsEl.textContent = wordCount(html) + ' words';
            if (pathEl) {
                try {
                    var range = quill.getSelection();
                    var blot = range ? quill.getLeaf(range.index)[0] : null;
                    var node = blot && blot.domNode ? blot.domNode : null;
                    var parts = [];
                    while (node && node !== quill.root) {
                        if (node.nodeType === 1) parts.unshift(node.tagName.toLowerCase());
                        node = node.parentNode;
                    }
                    pathEl.textContent = parts.length ? parts.join(' > ') : 'p';
                } catch (err) {
                    pathEl.textContent = 'p';
                }
            }
        }
        quill.on('selection-change', refreshStatus);
        quill.on('text-change', refreshStatus);
        refreshStatus();

        wrap.__crmQuillRun = run;
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && wrap.classList.contains('is-fullscreen')) {
                wrap.classList.remove('is-fullscreen');
                document.body.style.overflow = '';
            }
            // Help Alt+0
            if (e.altKey && (e.key === '0' || e.code === 'Digit0')) {
                if (wrap.contains(document.activeElement) || document.activeElement === quill.root) {
                    e.preventDefault();
                    run('help');
                }
            }
            // Fullscreen Ctrl+Shift+F
            if (e.ctrlKey && e.shiftKey && (e.key === 'F' || e.key === 'f')) {
                if (wrap.contains(document.activeElement) || document.activeElement === quill.root) {
                    e.preventDefault();
                    run('fullscreen');
                }
            }
            // Find Ctrl+F when editor focused
            if (e.ctrlKey && !e.shiftKey && (e.key === 'f' || e.key === 'F')) {
                if (wrap.contains(document.activeElement) || document.activeElement === quill.root) {
                    e.preventDefault();
                    run('find-replace');
                }
            }
        });
    }

    function registerPreserveStyleMatchers(quill) {
        quill.clipboard.addMatcher(Node.ELEMENT_NODE, function (node, delta) {
            var extra = styleAttrsFromNode(node);
            if (!Object.keys(extra).length) return delta;
            var next = new Delta();
            delta.ops.forEach(function (op) {
                if (typeof op.insert === 'string') {
                    next.insert(op.insert, Object.assign({}, op.attributes || {}, extra));
                } else {
                    next.insert(op.insert, op.attributes);
                }
            });
            return next;
        });

        quill.root.addEventListener('paste', function (e) {
            var clipboard = e.clipboardData || window.clipboardData;
            if (!clipboard) return;

            var html = clipboard.getData('text/html');
            var plain = clipboard.getData('text/plain') || '';

            // Plain-text numbered sections (copied from PDF / plain view)
            if ((!html || !html.trim()) && plain && /^\s*\d+\.\s+\S/m.test(plain)) {
                e.preventDefault();
                e.stopPropagation();
                html = plainTextNumberedToHtml(plain);
            } else if (!html || !html.trim()) {
                return;
            } else {
                e.preventDefault();
                e.stopPropagation();
            }

            var cleaned = cleanPastedHtml(html);
            if (!cleaned) {
                if (plain) {
                    var r0 = quill.getSelection(true);
                    quill.insertText(r0 ? r0.index : quill.getLength(), plain, 'user');
                }
                return;
            }

            var range = quill.getSelection(true);
            var index = range ? range.index : quill.getLength();
            if (range && range.length) {
                quill.deleteText(index, range.length, 'user');
            }

            // Prefer HTML paste path so bold + ql-indent survive (Delta convert often flattens)
            var structured = /ql-indent-\d+/i.test(cleaned)
                || /<strong>\s*\d+\./i.test(cleaned)
                || /<(ol|ul)[\s>]/i.test(cleaned);

            try {
                if (structured && typeof quill.clipboard.dangerouslyPasteHTML === 'function') {
                    quill.clipboard.dangerouslyPasteHTML(index, cleaned, 'user');
                } else {
                    var pasted = quill.clipboard.convert({ html: cleaned });
                    quill.updateContents(new Delta().retain(index).concat(pasted), 'user');
                    var end = index + (pasted.length() || 0);
                    quill.setSelection(end, 0, 'silent');
                }
            } catch (err) {
                var pasted2 = quill.clipboard.convert({ html: cleaned });
                quill.updateContents(new Delta().retain(index).concat(pasted2), 'user');
            }

            // If indent/bold were lost, restore HTML at caret region via DOM fallback
            var out = quill.root.innerHTML || '';
            var lostIndent = /ql-indent-\d+/i.test(cleaned) && !/ql-indent-\d+/i.test(out);
            var lostBoldNumber = /<strong>\s*\d+\./i.test(cleaned) && !/(<strong>|<b>)\s*\d+\./i.test(out);
            if (lostIndent || lostBoldNumber) {
                try {
                    quill.clipboard.dangerouslyPasteHTML(index, cleaned, 'user');
                } catch (e2) { /* ignore */ }
            }
        }, true);
    }

    function plainTextNumberedToHtml(text) {
        var lines = String(text || '').replace(/\r\n/g, '\n').split('\n');
        var html = '';
        lines.forEach(function (line) {
            var t = line.replace(/\u00a0/g, ' ').replace(/\s+$/g, '');
            if (!t.trim()) return;
            if (/^\d+\.\s+\S/.test(t.trim())) {
                html += '<p><strong>' + escapeHtml(t.trim()) + '</strong></p>';
            } else if (/^\s{2,}|\t/.test(t) || /^[-•*]/.test(t.trim())) {
                html += '<p class="ql-indent-1">' + escapeHtml(t.trim().replace(/^[-•*]\s*/, '')) + '</p>';
            } else {
                // Continuation body under a numbered heading
                html += '<p class="ql-indent-1">' + escapeHtml(t.trim()) + '</p>';
            }
        });
        return html;
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildModules(options) {
        var disableTables = !!options.disableTables;
        var toolbar = options.toolbar || (disableTables ? EMAIL_TOOLBAR : FULL_TOOLBAR);
        var modules = {
            toolbar: toolbar,
            table: false,
            history: {
                delay: 1000,
                maxStack: 100,
                userOnly: true
            },
            clipboard: {
                matchVisual: false
            }
        };

        if (hasTableBetter && !disableTables) {
            modules['table-better'] = {
                language: 'en_US',
                menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                toolbarTable: true,
                toolbarButtons: {
                    whiteList: [
                        'bold', 'italic', 'underline', 'strike',
                        'size', 'color', 'background', 'font',
                        'list', 'header', 'align', 'link', 'image'
                    ],
                    singleWhiteList: ['link', 'image']
                }
            };
            modules.keyboard = {
                bindings: QuillTableBetter.keyboardBindings
            };
        }

        return modules;
    }

    window.CrmQuill = {
        instances: {},
        wraps: {},
        fullToolbar: FULL_TOOLBAR,
        hasTableBetter: hasTableBetter,

        create: function (textareaId, options) {
            options = options || {};
            var ta = document.getElementById(textareaId);
            if (!ta || typeof Quill === 'undefined') return null;
            if (this.instances[textareaId]) {
                if (options.html != null) this.setHtml(textareaId, options.html);
                else if (ta.value) this.setHtml(textareaId, ta.value);
                return this.instances[textareaId];
            }

            ta.classList.add('d-none');
            var wrap = document.createElement('div');
            wrap.className = 'crm-quill-wrap ' + (options.sizeClass || 'crm-quill-medium');
            if (options.wrapClass) wrap.className += ' ' + options.wrapClass;

            var host = document.createElement('div');
            host.id = textareaId + '_quill';
            wrap.appendChild(host);
            ta.parentNode.insertBefore(wrap, ta);

            var quill = new Quill(host, {
                theme: 'snow',
                placeholder: options.placeholder || '',
                modules: buildModules(options)
            });

            if (options.disableTables || options.emailLayout) {
                quill.__crmEmailLayout = true;
            }

            registerPreserveStyleMatchers(quill);

            var initial = options.html != null ? options.html : (ta.value || '');
            if (initial) {
                loadHtmlIntoQuill(quill, initial, { preferRawHtml: !!quill.__crmEmailLayout });
                if (quill.__crmEmailLayout) {
                    ta.value = quill.__crmEmailHtml || initial;
                } else {
                    var cleanedInitial = cleanPastedHtml(initial) || initial;
                    var exported = exportHtmlFromQuill(quill);
                    // Keep original styles if Quill conversion would drop them
                    if (countStyleAttrs(cleanedInitial) > 0
                        && countStyleAttrs(exported) < Math.max(1, Math.floor(countStyleAttrs(cleanedInitial) * 0.6))) {
                        ta.value = normalizeHtml(cleanedInitial);
                    } else {
                        ta.value = exported || normalizeHtml(cleanedInitial);
                    }
                }
            }

            if (options.contentStyle) {
                var familyMatch = options.contentStyle.match(/font-family\s*:\s*([^;]+)/i);
                var sizeMatch = options.contentStyle.match(/font-size\s*:\s*([^;]+)/i);
                if (familyMatch) quill.root.style.fontFamily = familyMatch[1].trim();
                if (sizeMatch) quill.root.style.fontSize = sizeMatch[1].trim();
            }

            var sync = function () {
                if (wrap.__crmQuillIsSource && wrap.__crmQuillIsSource()) {
                    var sourceEl = wrap.querySelector('.crm-quill-source');
                    ta.value = sourceEl ? sourceEl.value : ta.value;
                } else if (quill.__crmEmailLayout) {
                    var emailHtml = readEmailHtml(quill);
                    if (emailHtml) {
                        quill.__crmEmailHtml = emailHtml;
                        ta.value = emailHtml;
                    } else if (quill.__crmEmailHtml) {
                        ta.value = quill.__crmEmailHtml;
                        paintEmailHtml(quill, quill.__crmEmailHtml);
                    } else {
                        ta.value = '';
                    }
                } else {
                    ta.value = prepareTableHtmlForSave(quill);
                }
                if (typeof options.onChange === 'function') options.onChange(ta.value);
            };
            quill.on('text-change', sync);
            attachAdvancedTools(wrap, quill, ta, options, sync);
            if (quill.__crmEmailLayout) {
                attachEmailCanvas(wrap, quill, ta, {
                    placeholder: options.placeholder || '',
                    onChange: function (html) {
                        ta.value = html || '';
                        quill.__crmEmailHtml = html || '';
                        if (typeof options.onChange === 'function') options.onChange(ta.value);
                    }
                });
                if (quill.__crmEmailHtml || ta.value) {
                    paintEmailHtml(quill, quill.__crmEmailHtml || ta.value);
                }
            }
            sync();

            this.instances[textareaId] = quill;
            this.wraps[textareaId] = wrap;
            return quill;
        },

        get: function (textareaId) {
            return this.instances[textareaId] || null;
        },

        getHtml: function (textareaId) {
            var wrap = this.wraps[textareaId];
            if (wrap && wrap.__crmQuillIsSource && wrap.__crmQuillIsSource()) {
                var sourceEl = wrap.querySelector('.crm-quill-source');
                return normalizeHtml(sourceEl ? sourceEl.value : '');
            }
            var quill = this.instances[textareaId];
            if (quill) {
                if (quill.__crmEmailLayout) {
                    return readEmailHtml(quill) || '';
                }
                return exportHtmlForPersist(quill);
            }
            var ta = document.getElementById(textareaId);
            return ta ? (ta.value || '') : '';
        },

        setHtml: function (textareaId, html) {
            var value = html || '';
            var quill = this.instances[textareaId];
            var wrap = this.wraps[textareaId];
            var ta = document.getElementById(textareaId);
            if (wrap && wrap.__crmQuillIsSource && wrap.__crmQuillIsSource()) {
                var sourceEl = wrap.querySelector('.crm-quill-source');
                if (sourceEl) sourceEl.value = value;
                if (ta) ta.value = value;
                if (quill && quill.__crmEmailLayout) quill.__crmEmailHtml = value;
                return;
            }
            if (quill) {
                if (quill.__crmEmailLayout) {
                    quill.__crmEmailHtml = value;
                    paintEmailHtml(quill, value);
                    if (ta) ta.value = value;
                    return;
                }
                loadHtmlIntoQuill(quill, value, {
                    preferRawHtml: false
                });
            }
            if (ta) {
                ta.value = quill ? prepareTableHtmlForSave(quill) : normalizeHtml(value);
            }
        },

        insertText: function (textareaId, text) {
            var quill = this.instances[textareaId];
            if (!quill) return;
            insertIntoEmailEditor(quill, text);
            if (quill.__crmEmailLayout) {
                var ta = document.getElementById(textareaId);
                var html = readEmailHtml(quill);
                quill.__crmEmailHtml = html;
                if (ta) ta.value = html;
            }
        },

        sync: function (textareaId) {
            var wrap = this.wraps[textareaId];
            if (wrap && wrap.__crmQuillFlushSource) {
                wrap.__crmQuillFlushSource();
            }
            var quill = this.instances[textareaId];
            var ta = document.getElementById(textareaId);
            if (quill && ta) {
                if (quill.__crmEmailLayout) {
                    var live = readEmailHtml(quill);
                    ta.value = live || quill.__crmEmailHtml || '';
                    if (!live && quill.__crmEmailHtml) {
                        paintEmailHtml(quill, quill.__crmEmailHtml);
                    }
                } else {
                    ta.value = exportHtmlForPersist(quill);
                }
            }
        },

        syncAll: function () {
            Object.keys(this.instances).forEach(function (id) {
                window.CrmQuill.sync(id);
            });
        }
    };
})();
</script>
@endonce
