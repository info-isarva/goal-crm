@php
    $receiptAction = $receiptAction ?? '';
@endphp
<div class="modal fade" id="payReceiptModal" tabindex="-1" aria-labelledby="payReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e2e8f0">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="payReceiptModalLabel" style="color:#0f172a">Record Receipt</h5>
                    <p class="mb-0 small text-muted">Posts to payout ledger; partial receipts allowed.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ $receiptAction }}" id="payReceiptForm">
                @csrf
                <div class="modal-body pt-2">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcCase">Case Reference</label>
                            <input type="text" class="form-control bg-light" id="payRcCase" readonly value="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcExpected">Expected Payout (₹)</label>
                            <input type="text" class="form-control bg-light" id="payRcExpected" readonly value="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcReceived">Previously Received (₹)</label>
                            <input type="text" class="form-control bg-light" id="payRcReceived" readonly value="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcAmount">Receipt Amount (₹) <span class="text-danger">*</span></label>
                            <input type="text" inputmode="decimal" name="amount" id="payRcAmount" class="form-control js-decimal" required placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcOn">Received On <span class="text-danger">*</span></label>
                            <input type="text" name="received_on" id="payRcOn" class="form-control js-datepicker" required placeholder="Select date" value="{{ old('received_on', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcRef">Payment Reference</label>
                            <input type="text" name="reference" id="payRcRef" class="form-control" maxlength="100" placeholder="UTR / cheque no." value="{{ old('reference') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted mb-1" for="payRcDiff">Difference Reason / Remarks</label>
                            <textarea name="difference_reason" id="payRcDiff" class="form-control" rows="2" maxlength="255" placeholder="Required when receipt is less than outstanding expected amount">{{ old('difference_reason') }}</textarea>
                            <input type="hidden" name="remarks" id="payRcRemarks" value="">
                        </div>
                    </div>
                    @if($errors->has('amount') || $errors->has('received_on'))
                        <div class="alert alert-danger py-2 mt-3 mb-0 small">{{ $errors->first() }}</div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="pd-btn"><i class="bi bi-check2-lg me-1"></i> Save Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>