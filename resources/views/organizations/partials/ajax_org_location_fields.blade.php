@php
    $countries = $countries ?? [
        'India', 'United Arab Emirates', 'United States', 'United Kingdom', 'Canada', 'Australia',
        'Germany', 'France', 'Singapore', 'Japan', 'China', 'Saudi Arabia', 'Qatar', 'Kuwait',
        'Bahrain', 'Oman', 'Switzerland', 'Malaysia', 'New Zealand', 'South Africa', 'Other',
    ];
@endphp
{{-- Same country / state / city concept as quotation billing address --}}
<div class="mb-3">
    <label for="org_country" class="form-label">Country / Region</label>
    <select class="form-select" id="org_country" name="org_country">
        <option value="">-None-</option>
        @foreach ($countries as $country)
            <option value="{{ $country }}" @if ($country === 'India') selected @endif>{{ $country }}</option>
        @endforeach
    </select>
    <div id="org_country_error" class="text-danger small mt-1" style="display:none;"></div>
</div>
<div class="mb-3">
    <label for="org_state" class="form-label">State / Province</label>
    <select class="form-select" id="org_state" name="org_state">
        <option value="">-None-</option>
    </select>
    <div id="org_state_error" class="text-danger small mt-1" style="display:none;"></div>
</div>
<div class="mb-3">
    <label for="org_city" class="form-label">City</label>
    <input type="text" placeholder="Enter city" class="form-control" id="org_city" name="org_city">
    <div id="org_city_error" class="text-danger small mt-1" style="display:none;"></div>
</div>
