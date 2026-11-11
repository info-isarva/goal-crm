function toggleFileInput() {
    var type = document.getElementById('file_type').value;
    document.getElementById('file_upload_div').classList.toggle('d-none', type !== 'file upload');
    document.getElementById('file_link_div').classList.toggle('d-none', type !== 'file links');
}
document.addEventListener('DOMContentLoaded', function () {
    toggleFileInput();
    var tabFlags = window.leadShowTabs || {};
    if (tabFlags.files) {
        var tab = document.getElementById('files-tab');
        if (tab) tab.click();
    } else if (tabFlags.notes) {
        var tab = document.getElementById('notes-tab');
        if (tab) tab.click();
    }
    else if (tabFlags.tasks) {
        var tab = document.getElementById('tasks-tab');
        if (tab) tab.click();
    }
    else if (tabFlags.calls) {
        var tab = document.getElementById('calls-tab');
        if (tab) tab.click();
    }
    else if (tabFlags.meetings) {
        var tab = document.getElementById('meetings-tab');
        if (tab) tab.click();
    }


});
document.addEventListener('DOMContentLoaded', function () {
    $('#meeting_user_participant_id').select2({
        placeholder: 'Select participants',
        allowClear: true,
        width: '100%'
    });

    $('#call_user_participant_id').select2({
        placeholder: 'Select participants',
        allowClear: true,
        width: '100%'
    });

    // Enable Select2 for all edit meeting participant selects in modals
    $('select[id^="edit_meeting_user_participant_id"]').each(function () {
        $(this).select2({
            dropdownParent: $(this).closest('.modal'),
            placeholder: 'Select participants',
            allowClear: true,
            width: '100%'
        });
    });

    // Enable Select2 for all edit call participant selects in modals (if needed)
    $('select[id^="edit_call_user_participant_id"]').each(function () {
        $(this).select2({
            dropdownParent: $(this).closest('.modal'),
            placeholder: 'Select participants',
            allowClear: true,
            width: '100%'
        });
    });

    // Helper functions
    function pad(n) { return n < 10 ? '0' + n : n; }
    function toDatetimeLocal(dt) {
        return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
    }
    var now = new Date();
    // --- Call create form ---
    var callFromInput = document.getElementById('call_start_at');
    var callToInput = document.getElementById('call_finish_at');
    if (callFromInput && callToInput) {
        if (!callFromInput.value) {
            callFromInput.value = toDatetimeLocal(now);
        }
        if (!callToInput.value) {
            var to = new Date(callFromInput.value);
            to.setHours(to.getHours() + 1);
            callToInput.value = toDatetimeLocal(to);
        }
        callFromInput.addEventListener('change', function () {
            if (callFromInput.value) {
                callToInput.min = callFromInput.value;
                var to = new Date(callFromInput.value);
                to.setHours(to.getHours() + 1);
                callToInput.value = toDatetimeLocal(to);
            } else {
                callToInput.min = '';
            }
        });
        if (callFromInput.value) {
            callToInput.min = callFromInput.value;
        }
    }
    // --- Call edit modals ---
    document.querySelectorAll('input[id^="edit_call_start_at"]').forEach(function (editFromInput) {
        var id = editFromInput.id.replace('edit_call_start_at', '');
        var editToInput = document.getElementById('edit_call_finish_at' + id);
        if (editFromInput && editToInput) {
            if (!editFromInput.value) {
                editFromInput.value = toDatetimeLocal(now);
            }
            if (!editToInput.value) {
                var to = new Date(editFromInput.value);
                to.setHours(to.getHours() + 1);
                editToInput.value = toDatetimeLocal(to);
            }
            editFromInput.addEventListener('change', function () {
                if (editFromInput.value) {
                    editToInput.min = editFromInput.value;
                    var to = new Date(editFromInput.value);
                    to.setHours(to.getHours() + 1);
                    editToInput.value = toDatetimeLocal(to);
                } else {
                    editToInput.min = '';
                }
            });
            if (editFromInput.value) {
                editToInput.min = editFromInput.value;
            }
        }
    });
    // --- Meeting create form ---
    var meetingFromInput = document.getElementById('meeting_start_at');
    var meetingToInput = document.getElementById('meeting_finish_at');
    if (meetingFromInput && meetingToInput) {
        if (!meetingFromInput.value) {
            meetingFromInput.value = toDatetimeLocal(now);
        }
        if (!meetingToInput.value) {
            var to = new Date(meetingFromInput.value);
            to.setHours(to.getHours() + 1);
            meetingToInput.value = toDatetimeLocal(to);
        }
        meetingFromInput.addEventListener('change', function () {
            if (meetingFromInput.value) {
                meetingToInput.min = meetingFromInput.value;
                var to = new Date(meetingFromInput.value);
                to.setHours(to.getHours() + 1);
                meetingToInput.value = toDatetimeLocal(to);
            } else {
                meetingToInput.min = '';
            }
        });
        if (meetingFromInput.value) {
            meetingToInput.min = meetingFromInput.value;
        }
    }
    // --- Meeting edit modals ---
    document.querySelectorAll('input[id^="edit_meeting_start_at"]').forEach(function (editFromInput) {
        var id = editFromInput.id.replace('edit_meeting_start_at', '');
        var editToInput = document.getElementById('edit_meeting_finish_at' + id);
        if (editFromInput && editToInput) {
            if (!editFromInput.value) {
                editFromInput.value = toDatetimeLocal(now);
            }
            if (!editToInput.value) {
                var to = new Date(editFromInput.value);
                to.setHours(to.getHours() + 1);
                editToInput.value = toDatetimeLocal(to);
            }
            editFromInput.addEventListener('change', function () {
                if (editFromInput.value) {
                    editToInput.min = editFromInput.value;
                    var to = new Date(editFromInput.value);
                    to.setHours(to.getHours() + 1);
                    editToInput.value = toDatetimeLocal(to);
                } else {
                    editToInput.min = '';
                }
            });
            if (editFromInput.value) {
                editToInput.min = editFromInput.value;
            }
        }
    });


    //Swal Alert for delete confirmation
    var buttons = document.querySelectorAll('.delete-leads-btn');
    var name = 'data-lead-name';
    attachDeleteHandlers(buttons, name); 

    //Swal Alert for delete confirmation
    var buttons = document.querySelectorAll('.delete-note-btn');
    var name = 'data-note-name';
    attachDeleteHandlers(buttons, name); 
});