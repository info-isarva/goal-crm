 //Quick attach: delegated search handler so live filtering works even if other scripts fail or elements move
    
    (function(){
            function runPipelineFilter(q) {
                var cards = document.querySelectorAll('.deal-card');
                var shown = 0, hidden = 0;
                cards.forEach(function(card) {
                    var contact = (card.getAttribute('data-contact') || '').toLowerCase();
                    var title = (card.querySelector('a') ? card.querySelector('a').innerText : '').toLowerCase();
                    if (!q) {
                        card.style.display = '';
                        shown++;
                    } else if (contact.indexOf(q) !== -1 || title.indexOf(q) !== -1) {
                        card.style.display = '';
                        shown++;
                    } else {
                        card.style.display = 'none';
                        hidden++;
                    }
                });
                // debug
                // console.log('pipeline search results - shown:', shown, 'hidden:', hidden);
            }

            var debounceTimer = null;
            // delegated handler - listens for input events on the document
            document.addEventListener('input', function(e) {
                try {
                    if (!e.target || e.target.id !== 'pipelineContactSearch') return;
                    var val = (e.target.value || '').trim().toLowerCase();
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        runPipelineFilter(val);
                    }, 200);
                } catch (err) { console.error('pipeline delegated search error', err); }
            }, true);

            // run initial filter if input already has value
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    var input = document.getElementById('pipelineContactSearch');
                    if (input && input.value) {
                        runPipelineFilter((input.value || '').trim().toLowerCase());
                    }
                } catch (err) { console.error('pipeline initial filter error', err); }
            });
        })();


    // Click-and-drag horizontal scrolling for pipeline board
    (function() {
        var board = document.getElementById('pipeline-board');
        var isDragging = false;
        var startX, scrollLeft;
        board.addEventListener('mousedown', function(e) {
            if (e.button !== 0) return; // Only left mouse button
            isDragging = true;
            board.classList.add('dragging');
            startX = e.pageX - board.offsetLeft;
            scrollLeft = board.scrollLeft;
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var x = e.pageX - board.offsetLeft;
            var walk = (x - startX);
            board.scrollLeft = scrollLeft - walk;
        });
        document.addEventListener('mouseup', function() {
            isDragging = false;
            board.classList.remove('dragging');
            document.body.style.userSelect = '';
        });
    })();
    // Prevent text selection while auto-scrolling
    function setUserSelectNone(enable) {
        document.body.style.userSelect = enable ? 'none' : '';
        document.body.style.webkitUserSelect = enable ? 'none' : '';
        document.body.style.msUserSelect = enable ? 'none' : '';
    }
    // Auto-scroll during drag and mouse move near screen edge
    (function() {
        var board = document.getElementById('pipeline-board');
        var scrollSpeed = 12; // px per frame for smoothness
        var edgeThreshold = 80; // px from edge to start scrolling
        var scrollDirection = null;
        var isScrolling = false;

        function autoScroll() {
            if (!scrollDirection) return;
            if (scrollDirection === 'left') {
                board.scrollLeft -= scrollSpeed;
            } else if (scrollDirection === 'right') {
                board.scrollLeft += scrollSpeed;
            }
            if (isScrolling) {
                requestAnimationFrame(autoScroll);
            }
        }
        function startAutoScroll(direction) {
            if (isScrolling && scrollDirection === direction) return;
            scrollDirection = direction;
            isScrolling = true;
            requestAnimationFrame(autoScroll);
        }
        function stopAutoScroll() {
            isScrolling = false;
            scrollDirection = null;
        }
        // Drag auto-scroll
        document.addEventListener('dragover', function(e) {
            var rect = board.getBoundingClientRect();
            if (e.clientX - rect.left < edgeThreshold) {
                startAutoScroll('left');
            } else if (rect.right - e.clientX < edgeThreshold) {
                startAutoScroll('right');
            } else {
                stopAutoScroll();
            }
        });
        document.addEventListener('dragleave', stopAutoScroll);
        document.addEventListener('drop', stopAutoScroll);

        // Mouse move auto-scroll
        document.addEventListener('mousemove', function(e) {
            var windowWidth = window.innerWidth;
            if (e.clientX < edgeThreshold) {
                startAutoScroll('left');
                setUserSelectNone(true);
            } else if (windowWidth - e.clientX < edgeThreshold) {
                startAutoScroll('right');
                setUserSelectNone(true);
            } else {
                stopAutoScroll();
                setUserSelectNone(false);
            }
        });
        document.addEventListener('mouseleave', function() {
            stopAutoScroll();
            setUserSelectNone(false);
        });
    })();

    // Helper to get stage name by id
    function getStageNameById(stageId) {
        var stageMap = {};
        @foreach($stages as $stage)
            stageMap['{{ $stage->id }}'] = @json($stage->name);
        @endforeach
        return stageMap[stageId];
    }

    var lastDrag = null;
    var pendingRevert = null;
    var pendingClosedWon = null;
    var modal = new bootstrap.Modal(document.getElementById('reasonForLossModal'));
    var submitBtn = document.getElementById('submitReasonForLoss');
    var cancelBtn = document.querySelector('#reasonForLossModal .btn-secondary');
    var reasonInput = document.getElementById('reasonForLossInput');
    var reasonError = document.getElementById('reasonForLossError');

    function handleRevertCard() {
        if (pendingRevert && pendingRevert.dealCard && pendingRevert.lastDrag) {
            var from = pendingRevert.lastDrag.from;
            var oldIndex = pendingRevert.lastDrag.oldIndex;
            from.insertBefore(pendingRevert.dealCard, from.children[oldIndex] || null);
        }
        pendingRevert = null;
    }

    submitBtn.addEventListener('click', function() {
        var reason = reasonInput.value.trim();
        if (!reason) {
            reasonError.style.display = 'block';
            return;
        }
        reasonError.style.display = 'none';
        modal.hide();
        if (pendingRevert) {
            // AJAX call to update deal stage with reason
            fetch("{{ route('deals.updateStage') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deal_id: pendingRevert.dealId,
                    stage_id: pendingRevert.newStageId,
                    reason_for_loss: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success === true){
                    let msg = document.getElementById('pipeline-success-msg');
                    if (!msg) {
                        msg = document.createElement('div');
                        msg.id = 'pipeline-success-msg';
                        msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                        msg.style.zIndex = 9999;
                        document.body.appendChild(msg);
                    }
                    msg.innerText = 'Deal stage changed from "' + data.old_stage + '" to "' + data.new_stage + '"!';
                    msg.style.display = 'block';
                    setTimeout(function(){ msg.style.display = 'none'; }, 1200);
                    setTimeout(function(){ window.location.reload(); }, 1200);
                } else {
                    alert('Failed to update stage');
                }
            })
            .catch(() => alert('Error updating stage'));
        }
        pendingRevert = null;
    });
    cancelBtn.addEventListener('click', function() {
        modal.hide();
        handleRevertCard();
    });

    // Closed Won modal submit handling
    var closedWonModalEl = document.getElementById('closedWonModal');
    var closedWonModal = new bootstrap.Modal(closedWonModalEl);
    var submitClosedWonBtn = document.getElementById('submitClosedWon');
    var cancelClosedWonBtn = closedWonModalEl.querySelector('.btn-secondary');
    submitClosedWonBtn && submitClosedWonBtn.addEventListener('click', function() {
        var amt = document.getElementById('closedWonAmount').value.trim();
        var cdate = document.getElementById('closedWonDate').value.trim();
        var amtErr = document.getElementById('closedWonAmountError');
        var dateErr = document.getElementById('closedWonDateError');
        var valid = true;
        if (!amt || isNaN(amt)) { amtErr.style.display = 'block'; valid = false; } else { amtErr.style.display = 'none'; }
        if (!cdate) { dateErr.style.display = 'block'; valid = false; } else { dateErr.style.display = 'none'; }
        if (!valid) return;
        closedWonModal.hide();
        if (pendingClosedWon) {
            fetch("{{ route('deals.updateStage') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deal_id: pendingClosedWon.dealId,
                    stage_id: pendingClosedWon.newStageId,
                    amount: amt,
                    close_date: cdate
                })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Failed to update stage');
                }
            }).catch(() => alert('Error updating stage'));
        }
    });

    // Cancel handler: revert card back to its previous container/position
    cancelClosedWonBtn && cancelClosedWonBtn.addEventListener('click', function() {
        closedWonModal.hide();
        if (pendingClosedWon && pendingClosedWon.dealCard && pendingClosedWon.lastDrag) {
            var from = pendingClosedWon.lastDrag.from;
            var oldIndex = pendingClosedWon.lastDrag.oldIndex;
            // insert back to original position
            from.insertBefore(pendingClosedWon.dealCard, from.children[oldIndex] || null);
        }
        pendingClosedWon = null;
    });

    // Only initialize Sortable when viewing the current/active financial year
    var pipelineIsHistorical = @json($isHistorical);
    if (pipelineIsHistorical) {
        // mark pipeline as readonly for styling or hooks
        document.querySelectorAll('.deal-stage').forEach(function(s){ s.classList.add('pipeline-readonly'); });
        // add small read-only badge to board wrapper
        var board = document.getElementById('pipeline-board');
        if (board && board.parentElement && !document.getElementById('pipeline-readonly-badge')) {
            board.parentElement.style.position = 'relative';
            var note = document.createElement('div');
            note.id = 'pipeline-readonly-badge';
            note.className = 'badge bg-secondary text-white position-absolute';
            note.style.zIndex = 9999;
            note.style.top = '8px';
            note.style.right = '18px';
            note.style.fontSize = '0.85rem';
            note.innerText = 'Read-only (historical FY)';
            board.parentElement.appendChild(note);
        }
        // do not initialize Sortable => no drag/drop
        // additionally block pointer/mouse/dragstart events on deal cards to be extra-safe
        document.querySelectorAll('.deal-card').forEach(function(card){
            ['mousedown','pointerdown','touchstart','dragstart'].forEach(function(ev){
                card.addEventListener(ev, function(e){
                    e.stopImmediatePropagation();
                    e.preventDefault();
                }, { capture: true });
            });
            // make the cursor indicate non-interactive
            card.style.cursor = 'not-allowed';
            card.classList.add('deal-card-locked');
        });
    } else {
        document.querySelectorAll('.deal-stage').forEach(function(el) {
            new Sortable(el, {
                group: 'deals',
                animation: 150,
                onStart: function(evt) {
                    lastDrag = {
                        from: evt.from,
                        oldIndex: evt.oldIndex
                    };
                },
                onMove: function (evt) {
                    // Disable drag for deals already in Closed Won stage
                    var dealCard = evt.dragged;
                    var currentStageName = getStageNameById(evt.from.getAttribute('data-stage-id'));
                    // If the card is currently in Closed Won, block move and show message
                    if (currentStageName && currentStageName.toLowerCase() === 'closed won') {
                        showUnableToMoveMsg();
                        return false;
                    }
                    return true;
                },
                onEnd: function (evt) {
                    var dealCard = evt.item;
                    var dealId = dealCard.getAttribute('data-deal-id');
                    var newStageId = evt.to.getAttribute('data-stage-id');
                    var oldStageId = evt.from.getAttribute('data-stage-id');
                    var oldStageName = getStageNameById(oldStageId);
                    var newStageName = getStageNameById(newStageId);
                    // If the card is currently in Closed Won, block all move logic
                    if (oldStageName && oldStageName.toLowerCase() === 'closed won') {
                        return;
                    }
                    // If moved to Closed Lost, require reason
                    if (newStageName && newStageName.toLowerCase() === 'closed lost') {
                        reasonInput.value = '';
                        reasonError.style.display = 'none';
                        pendingRevert = {
                            dealCard: dealCard,
                            lastDrag: lastDrag,
                            dealId: dealId,
                            newStageId: newStageId
                        };
                        modal.show();
                        return;
                    }
                    // If moved to Closed Won, collect amount and close date
                    if (newStageName && newStageName.toLowerCase() === 'closed won') {
                        // show closed won modal
                        var existingAmount = dealCard.getAttribute('data-amount') || '';
                        var existingCloseDate = dealCard.getAttribute('data-close-date') || '';
                        pendingClosedWon = {
                            dealCard: dealCard,
                            dealId: dealId,
                            newStageId: newStageId,
                            lastDrag: lastDrag
                        };
                        document.getElementById('closedWonAmount').value = existingAmount;
                        document.getElementById('closedWonDate').value = existingCloseDate || new Date().toISOString().slice(0,10);
                        closedWonModal.show();
                        return;
                    }
                    // Normal AJAX call to update deal stage
                    fetch("{{ route('deals.updateStage') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            deal_id: dealId,
                            stage_id: newStageId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success === true){
                            let msg = document.getElementById('pipeline-success-msg');
                            if (!msg) {
                                msg = document.createElement('div');
                                msg.id = 'pipeline-success-msg';
                                msg.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                                msg.style.zIndex = 9999;
                                document.body.appendChild(msg);
                            }
                            msg.innerText = 'Deal stage changed from "' + data.old_stage + '" to "' + data.new_stage + '"!';
                            msg.style.display = 'block';
                            setTimeout(function(){ msg.style.display = 'none'; }, 1200);
                            setTimeout(function(){ window.location.reload(); }, 1200);
                        } else {
                            alert('Failed to update stage');
                        }
                    })
                    .catch(() => alert('Error updating stage'));
                }
            });
        });
    }

    // Show unable to move message for Closed Won deals
    function showUnableToMoveMsg() {
        let msg = document.getElementById('pipeline-unable-move-msg');
        if (!msg) {
            msg = document.createElement('div');
            msg.id = 'pipeline-unable-move-msg';
            msg.className = 'alert alert-warning position-fixed top-0 end-0 m-3';
            msg.style.zIndex = 9999;
            document.body.appendChild(msg);
        }
        msg.innerText = 'Unable to move: This deal is already Closed Won.';
        msg.style.display = 'block';
        setTimeout(function(){ msg.style.display = 'none'; }, 1800);
    }

    // Contact/person search filtering (debounced) - attach on DOMContentLoaded and guard with try/catch
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var input = document.getElementById('pipelineContactSearch');
            if (!input) return;
            var timeout = null;
            input.addEventListener('input', function() {
                try {
                    clearTimeout(timeout);
                    var q = input.value.trim().toLowerCase();
                    timeout = setTimeout(function() {
                        var cards = document.querySelectorAll('.deal-card');
                        cards.forEach(function(card) {
                            var contact = (card.getAttribute('data-contact') || '').toLowerCase();
                            var title = (card.querySelector('a') ? card.querySelector('a').innerText : '').toLowerCase();
                            if (!q) {
                                card.style.display = '';
                            } else if (contact.indexOf(q) !== -1 || title.indexOf(q) !== -1) {
                                card.style.display = '';
                            } else {
                                card.style.display = 'none';
                            }
                        });
                    }, 200);
                } catch (innerErr) {
                    console.error('Pipeline search inner error', innerErr);
                }
            });
        } catch (err) {
            console.error('Pipeline search init error', err);
        }
    });