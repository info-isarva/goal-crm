<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="avatar" class="form-label">Profile Picture</label>
            <div class="d-flex align-items-center gap-3">
                @if($user->avatar)
                    <img src="{{ asset('assets/employee_profile_image/' . $user->avatar) }}" alt="Profile Picture" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover;">
                @else
                    <img src="{{ asset('user-thumbnail.png') }}" alt="Profile Picture" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover;">
                @endif
                <input type="file" name="avatar" id="avatar" class="form-control" accept="image/*">
            </div>
            @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        @csrf
        @method('patch')
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" autofocus autocomplete="name">
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="text" class="form-control" value="{{ old('email', $user->email) }}"  autocomplete="username">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <span class="text-warning">Your email address is unverified.</span>
                    <button form="send-verification" class="btn btn-link p-0 align-baseline">Click here to re-send the verification email.</button>
                    @if (session('status') === 'verification-link-sent')
                        <span class="text-success ms-2">A new verification link has been sent to your email address.</span>
                    @endif
                </div>
            @endif
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input id="address" name="address" type="text" class="form-control" value="{{ old('address', optional($user->userDetail)->address) }}" autocomplete="address">
            @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="mobile" class="form-label">Mobile</label>
            <input id="mobile" name="mobile" type="text" class="form-control" value="{{ old('mobile', optional($user->userDetail)->mobile) }}" autocomplete="mobile">
            @error('mobile')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">City</label>
            <input id="city" name="city" type="text" class="form-control" value="{{ old('city', optional($user->userDetail)->city) }}" autocomplete="city">
            @error('city')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="state" class="form-label">State</label>
            <select id="state" name="state" class="form-select">
                <option value="">Select state</option>
            </select>
            @error('state')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="country" class="form-label">Country</label>
            <select class="form-select" id="country" name="country">
                <option value="">Select country</option>
                @foreach($countries as $country)
                    <option value="{{ $country }}" @if($country == old('country', 'India')) selected @endif>{{ $country }}</option>
                @endforeach
            </select>
            {{-- <input id="country" name="country" type="text" class="form-control" value="{{ old('country', optional($user->userDetail)->country) }}" autocomplete="country"> --}}
            @error('country')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-custom" style="padding: 6px 20px !important;">Save</button>
            @if (session('status') === 'profile-updated')
                <span class="text-success ms-3">Saved.</span>
            @endif
        </div>
    </form>
</section>

@push('scripts')
<script>
    (function(){
        // Simple mapping of country -> states/provinces. Extend as needed.
        var statesByCountry = {
            'India': ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi'],
            'United States': ['Alabama','Alaska','Arizona','Arkansas','California','Colorado','Connecticut','Delaware','Florida','Georgia','Hawaii','Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts','Michigan','Minnesota','Mississippi','Missouri','Montana','Nebraska','Nevada','New Hampshire','New Jersey','New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Rhode Island','South Carolina','South Dakota','Tennessee','Texas','Utah','Vermont','Virginia','Washington','West Virginia','Wisconsin','Wyoming'],
            'Canada': ['Alberta','British Columbia','Manitoba','New Brunswick','Newfoundland and Labrador','Nova Scotia','Ontario','Prince Edward Island','Quebec','Saskatchewan'],
            'Australia': ['New South Wales','Queensland','South Australia','Tasmania','Victoria','Western Australia','Australian Capital Territory','Northern Territory'],
            'United Kingdom': ['England','Scotland','Wales','Northern Ireland']
        };

        function populateStates(country, selected) {
            var stateSelect = document.getElementById('state');
            stateSelect.innerHTML = '';
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.text = 'Select state';
            stateSelect.appendChild(placeholder);

            var list = statesByCountry[country] || [];
            if (list.length > 0) {
                list.forEach(function(s){
                    var opt = document.createElement('option');
                    opt.value = s;
                    opt.text = s;
                    if (selected && selected === s) opt.selected = true;
                    stateSelect.appendChild(opt);
                });
                stateSelect.disabled = false;
            } else {
                // If we don't have a list for this country, keep the previous value if exists
                var prev = selected || '';
                if (prev) {
                    var opt = document.createElement('option');
                    opt.value = prev;
                    opt.text = prev;
                    opt.selected = true;
                    stateSelect.appendChild(opt);
                } else {
                    // show disabled placeholder
                    stateSelect.querySelector('option').text = 'No states available';
                }
                stateSelect.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function(){
            var countryEl = document.getElementById('country');
            var initialCountry = countryEl.value || '{{ old("country", optional($user->userDetail)->country ?? "India") }}';
            var initialState = '{{ old("state", optional($user->userDetail)->state) }}';
            populateStates(initialCountry, initialState);

            countryEl.addEventListener('change', function(){
                populateStates(this.value, '');
            });
        });
    })();
</script>
@endpush
