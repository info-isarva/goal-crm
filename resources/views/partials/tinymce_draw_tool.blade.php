@once
<div class="modal fade" id="tinymceDrawModal" tabindex="-1" aria-labelledby="tinymceDrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="tinymceDrawModalLabel"><i class="bi bi-brush me-2"></i>Draw</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="tinymceDrawColor" class="form-label mb-0 small">Color</label>
                        <input type="color" id="tinymceDrawColor" value="#111827" class="form-control form-control-color p-1" style="width:42px;height:32px;">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="tinymceDrawSize" class="form-label mb-0 small">Size</label>
                        <input type="range" id="tinymceDrawSize" min="1" max="20" value="3" class="form-range" style="width:120px;">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="tinymceDrawClearBtn">Clear</button>
                </div>
                <div class="border rounded bg-white" style="overflow:hidden;">
                    <canvas id="tinymceDrawCanvas" width="760" height="420" style="display:block;width:100%;height:auto;touch-action:none;cursor:crosshair;"></canvas>
                </div>
                <div class="small text-muted mt-2">Draw with mouse or touch, then click Insert Drawing.</div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-custom btn-sm" id="tinymceDrawInsertBtn">Insert Drawing</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.TinyMceDrawTool) {
        return;
    }

    var state = {
        editor: null,
        uploadUrl: '',
        csrfToken: '',
        modal: null,
        canvas: null,
        ctx: null,
        drawing: false,
        lastX: 0,
        lastY: 0,
        hasStroke: false
    };

    function getModal() {
        if (!state.modal) {
            var el = document.getElementById('tinymceDrawModal');
            state.modal = el && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(el) : null;
        }
        return state.modal;
    }

    function getCanvas() {
        if (!state.canvas) {
            state.canvas = document.getElementById('tinymceDrawCanvas');
            if (state.canvas) {
                state.ctx = state.canvas.getContext('2d');
            }
        }
        return state.canvas;
    }

    function clearCanvas() {
        var canvas = getCanvas();
        if (!canvas || !state.ctx) return;
        state.ctx.fillStyle = '#ffffff';
        state.ctx.fillRect(0, 0, canvas.width, canvas.height);
        state.hasStroke = false;
    }

    function canvasPoint(evt) {
        var canvas = getCanvas();
        var rect = canvas.getBoundingClientRect();
        var scaleX = canvas.width / rect.width;
        var scaleY = canvas.height / rect.height;
        var clientX = evt.clientX !== undefined ? evt.clientX : (evt.touches && evt.touches[0] ? evt.touches[0].clientX : 0);
        var clientY = evt.clientY !== undefined ? evt.clientY : (evt.touches && evt.touches[0] ? evt.touches[0].clientY : 0);
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function startDraw(evt) {
        evt.preventDefault();
        state.drawing = true;
        var point = canvasPoint(evt);
        state.lastX = point.x;
        state.lastY = point.y;
    }

    function draw(evt) {
        if (!state.drawing || !state.ctx) return;
        evt.preventDefault();
        var point = canvasPoint(evt);
        var color = document.getElementById('tinymceDrawColor')?.value || '#111827';
        var size = parseInt(document.getElementById('tinymceDrawSize')?.value || '3', 10) || 3;
        state.ctx.strokeStyle = color;
        state.ctx.lineWidth = size;
        state.ctx.lineCap = 'round';
        state.ctx.lineJoin = 'round';
        state.ctx.beginPath();
        state.ctx.moveTo(state.lastX, state.lastY);
        state.ctx.lineTo(point.x, point.y);
        state.ctx.stroke();
        state.lastX = point.x;
        state.lastY = point.y;
        state.hasStroke = true;
    }

    function endDraw(evt) {
        if (evt) evt.preventDefault();
        state.drawing = false;
    }

    function bindCanvasEvents() {
        var canvas = getCanvas();
        if (!canvas || canvas.__drawBound) return;
        canvas.__drawBound = true;
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', endDraw, { passive: false });
    }

    function uploadBlob(blob) {
        return new Promise(function (resolve, reject) {
            var formData = new FormData();
            formData.append('file', blob, 'drawing-' + Date.now() + '.png');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', state.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', state.csrfToken);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onload = function () {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('Drawing upload failed (' + xhr.status + ').');
                    return;
                }
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (!json || !json.location) {
                        reject('Invalid upload response.');
                        return;
                    }
                    resolve(json.location);
                } catch (err) {
                    reject('Invalid upload response.');
                }
            };
            xhr.onerror = function () { reject('Drawing upload failed.'); };
            xhr.send(formData);
        });
    }

    function openModal(editor, uploadUrl, csrfToken) {
        state.editor = editor;
        state.uploadUrl = uploadUrl;
        state.csrfToken = csrfToken;
        bindCanvasEvents();
        clearCanvas();
        var modal = getModal();
        if (modal) modal.show();
    }

    function insertDrawing() {
        if (!state.editor || !state.hasStroke) {
            alert('Please draw something before inserting.');
            return;
        }
        var canvas = getCanvas();
        var insertBtn = document.getElementById('tinymceDrawInsertBtn');
        if (insertBtn) insertBtn.disabled = true;

        canvas.toBlob(function (blob) {
            if (!blob) {
                if (insertBtn) insertBtn.disabled = false;
                alert('Could not create drawing image.');
                return;
            }
            uploadBlob(blob).then(function (url) {
                state.editor.insertContent('<img src="' + url + '" alt="Drawing" style="max-width:100%;height:auto;" />');
                var modal = getModal();
                if (modal) modal.hide();
            }).catch(function (err) {
                alert(err || 'Drawing upload failed.');
            }).finally(function () {
                if (insertBtn) insertBtn.disabled = false;
            });
        }, 'image/png');
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindCanvasEvents();
        var clearBtn = document.getElementById('tinymceDrawClearBtn');
        var insertBtn = document.getElementById('tinymceDrawInsertBtn');
        if (clearBtn) clearBtn.addEventListener('click', clearCanvas);
        if (insertBtn) insertBtn.addEventListener('click', insertDrawing);
    });

    window.TinyMceDrawTool = {
        registerButton: function (editor, uploadUrl, csrfToken) {
            if (!editor || editor.__drawToolRegistered) return;
            editor.__drawToolRegistered = true;

            editor.ui.registry.addIcon('qt-draw', '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M20.71 7.04c.39-.39.39-1.04 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83zM3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z"/></svg>');

            editor.ui.registry.addButton('draw', {
                icon: 'qt-draw',
                tooltip: 'Draw',
                onAction: function () {
                    openModal(editor, uploadUrl, csrfToken);
                }
            });
        }
    };
})();
</script>
@endonce
