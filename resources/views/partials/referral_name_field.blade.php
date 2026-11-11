@php
    $referralNameValue = old('referral_name', $referralNameValue ?? '');
@endphp
<div class="mb-3" id="referral_name_wrap" style="display:none;">
    <label for="referral_name" class="form-label">Referral Name <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control"
           id="referral_name"
           name="referral_name"
           value="{{ $referralNameValue }}"
           placeholder="Enter referral name"
           autocomplete="off">
    @error('referral_name')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
