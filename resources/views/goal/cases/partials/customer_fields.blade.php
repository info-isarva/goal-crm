@php
    $consentText = is_array($consent) && !empty($consent['recorded'])
        ? ('Recorded · ' . ($consent['recorded_at'] ?? ''))
        : ($case->lead?->consent_recorded ? 'Recorded on lead' : 'Not recorded');
    $consentOk = $consentText !== 'Not recorded';
    $reqMap = \App\Services\Goal\GoalCaseRequiredFields::map();
    $isReq = fn (string $key) => ! empty($reqMap[$key]);
    $reqMark = fn (string $key) => $isReq($key) ? ' <span class="text-red-500">*</span>' : '';
    $reqAttr = fn (string $key) => $isReq($key) ? 'required' : '';

    $relOptsBase = [
        'Self' => 'Self',
        'Spouse' => 'Spouse',
        'Father' => 'Father',
        'Mother' => 'Mother',
        'Son' => 'Son',
        'Daughter' => 'Daughter',
        'Brother' => 'Brother',
        'Sister' => 'Sister',
        'Other' => 'Other',
    ];
    $relCo = $co['relationship'] ?? '';
    $relCoOpts = ['' => 'Select'] + $relOptsBase;
    unset($relCoOpts['Self']);
    if ($relCo !== '' && ! array_key_exists($relCo, $relCoOpts)) {
        $relCoOpts[$relCo] = $relCo;
    }
    unset($relCoOpts['Other']);
    $relCoOpts['Other'] = 'Other';
    $maritalOpts = ['' => 'Select', 'Married' => 'Married', 'Single' => 'Single'];

    $empTypeOpts = ['' => 'Select', 'Salaried' => 'Salaried', 'Self Employed' => 'Self Employed', 'Business' => 'Business', 'Professional' => 'Professional', 'Other' => 'Other'];
    $empTypeApp = $emp['type_applicant'] ?? ($cd['employment_type'] ?? '');
    if ($empTypeApp !== '' && !array_key_exists($empTypeApp, $empTypeOpts)) {
        $empTypeOpts[$empTypeApp] = $empTypeApp;
    }
    $empTypeCo = $emp['type_co'] ?? '';
    $industryOpts = ['' => 'Select', 'IT / Software' => 'IT / Software', 'Banking / Finance' => 'Banking / Finance', 'Manufacturing' => 'Manufacturing', 'Healthcare' => 'Healthcare', 'Education' => 'Education', 'Retail' => 'Retail', 'Real Estate' => 'Real Estate', 'Government' => 'Government', 'Other' => 'Other'];
    $indApp = $emp['industry_applicant'] ?? '';
    $indCo = $emp['industry_co'] ?? '';
    if ($indApp !== '' && !array_key_exists($indApp, $industryOpts)) {
        $industryOpts[$indApp] = $indApp;
    }
    if ($indCo !== '' && !array_key_exists($indCo, $industryOpts)) {
        $industryOpts[$indCo] = $indCo;
    }
@endphp

<div class="cw-cust" id="cwCustomerLayout" data-party="applicant">
    {{-- Customer Details header --}}
    <div class="cw-card-tw">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="cw-ico cw-ico-blue"><i class="bi bi-grid-1x2"></i></span>
                <div>
                    <h6 class="cw-card-title">Customer Details</h6>
                    <div class="mt-1 text-xs text-slate-500">Applicant · Co-Applicant · Property · References</div>
                </div>
            </div>
            @if($case->lead)
                <a href="{{ route('goal.leads.show', $case->lead) }}" class="cw-profile-btn">
                    <i class="bi bi-person"></i> View Customer Profile
                </a>
            @else
                <button type="button" class="cw-profile-btn" disabled>
                    <i class="bi bi-person"></i> View Customer Profile
                </button>
            @endif
        </div>
    </div>

    {{-- Identity & Contact --}}
    <div class="cw-card-tw">
        <div class="cw-card-head">
            <span class="cw-ico"><i class="bi bi-person"></i></span>
            <h6 class="cw-card-title">Identity &amp; Contact</h6>
        </div>
        <div class="cw-identity-split">
            {{-- Applicant --}}
            <div class="cw-party-col">
                <div class="cw-party-head cw-party-head-app">
                    <i class="bi bi-person-fill"></i><span>Applicant</span>
                </div>
                <div class="cw-party-body">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Name{!! $reqMark('applicant.name') !!}</label>
                        <input class="cw-input" name="customer_details[applicant][name]" value="{{ $app['name'] ?? $case->customer_name }}" placeholder="Enter name" {{ $ro }} {{ $reqAttr('applicant.name') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Father Name{!! $reqMark('applicant.father_name') !!}</label>
                        <input class="cw-input" name="customer_details[applicant][father_name]" value="{{ $app['father_name'] ?? '' }}" placeholder="Enter father name" {{ $ro }} {{ $reqAttr('applicant.father_name') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Mother Name{!! $reqMark('applicant.mother_name') !!}</label>
                        <input class="cw-input" name="customer_details[applicant][mother_name]" value="{{ $app['mother_name'] ?? '' }}" placeholder="Enter mother name" {{ $ro }} {{ $reqAttr('applicant.mother_name') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Mobile Number{!! $reqMark('applicant.mobile') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="customer_details[applicant][mobile]" value="{{ $app['mobile'] ?? $case->mobile }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }} {{ $reqAttr('applicant.mobile') }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Date of Birth{!! $reqMark('applicant.dob') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                            <input type="text" class="cw-input js-datepicker js-dob-input" name="customer_details[applicant][dob]"
                                   value="{{ $app['dob'] ?? '' }}" placeholder="dd-mm-yyyy" autocomplete="off"
                                   data-party="applicant" data-min-age="18" {{ $ro }} {{ $reqAttr('applicant.dob') }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Age{!! $reqMark('applicant.age') !!}</label>
                        <input class="cw-input js-age-from-dob" name="customer_details[applicant][age]" value="{{ $app['age'] ?? '' }}" placeholder="Auto from DOB" data-party="applicant" readonly tabindex="-1" {{ $ro }} {{ $reqAttr('applicant.age') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">PAN{!! $reqMark('applicant.pan') !!}</label>
                        <input class="cw-input js-pan-input" name="customer_details[applicant][pan]" value="{{ $app['pan'] ?? '' }}"
                               placeholder="ABCDE1234F" maxlength="10" style="text-transform:uppercase" {{ $ro }} {{ $reqAttr('applicant.pan') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Marital Status{!! $reqMark('applicant.marital_status') !!}</label>
                        <select class="cw-select js-select2" name="customer_details[applicant][marital_status]" {{ $dis }} {{ $reqAttr('applicant.marital_status') }}>
                            @foreach($maritalOpts as $val => $label)
                                <option value="{{ $val }}" @selected(($app['marital_status'] ?? '') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="customer_details[applicant][relationship]" value="Self">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Personal Mail ID{!! $reqMark('applicant.email') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="cw-input" name="customer_details[applicant][email]" value="{{ $app['email'] ?? ($cd['email'] ?? '') }}" placeholder="name@email.com" {{ $ro }} {{ $reqAttr('applicant.email') }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Official Mail ID{!! $reqMark('applicant.official_email') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" class="cw-input" name="customer_details[applicant][official_email]" value="{{ $app['official_email'] ?? '' }}" placeholder="work@company.com" {{ $ro }} {{ $reqAttr('applicant.official_email') }}>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Co-Applicant --}}
            <div class="cw-party-col">
                <div class="cw-party-head cw-party-head-co">
                    <i class="bi bi-people-fill"></i><span>Co-Applicant</span>
                </div>
                <div class="cw-party-body">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Name{!! $reqMark('co_applicant.name') !!}</label>
                        <input class="cw-input" name="customer_details[co_applicant][name]" value="{{ $co['name'] ?? '' }}" placeholder="Enter name" {{ $ro }} {{ $reqAttr('co_applicant.name') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Father Name{!! $reqMark('co_applicant.father_name') !!}</label>
                        <input class="cw-input" name="customer_details[co_applicant][father_name]" value="{{ $co['father_name'] ?? '' }}" placeholder="Enter father name" {{ $ro }} {{ $reqAttr('co_applicant.father_name') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Mother Name{!! $reqMark('co_applicant.mother_name') !!}</label>
                        <input class="cw-input" name="customer_details[co_applicant][mother_name]" value="{{ $co['mother_name'] ?? '' }}" placeholder="Enter mother name" {{ $ro }} {{ $reqAttr('co_applicant.mother_name') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Mobile Number{!! $reqMark('co_applicant.mobile') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="customer_details[co_applicant][mobile]" value="{{ $co['mobile'] ?? '' }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }} {{ $reqAttr('co_applicant.mobile') }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Date of Birth{!! $reqMark('co_applicant.dob') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                            <input type="text" class="cw-input js-datepicker js-dob-input" name="customer_details[co_applicant][dob]"
                                   value="{{ $co['dob'] ?? '' }}" placeholder="dd-mm-yyyy" autocomplete="off"
                                   data-party="co_applicant" data-min-age="18" {{ $ro }} {{ $reqAttr('co_applicant.dob') }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Age{!! $reqMark('co_applicant.age') !!}</label>
                        <input class="cw-input js-age-from-dob" name="customer_details[co_applicant][age]" value="{{ $co['age'] ?? '' }}" placeholder="Auto from DOB" data-party="co_applicant" readonly tabindex="-1" {{ $ro }} {{ $reqAttr('co_applicant.age') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">PAN{!! $reqMark('co_applicant.pan') !!}</label>
                        <input class="cw-input js-pan-input" name="customer_details[co_applicant][pan]" value="{{ $co['pan'] ?? '' }}"
                               placeholder="ABCDE1234F" maxlength="10" style="text-transform:uppercase" {{ $ro }} {{ $reqAttr('co_applicant.pan') }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Marital Status{!! $reqMark('co_applicant.marital_status') !!}</label>
                        <select class="cw-select js-select2" name="customer_details[co_applicant][marital_status]" {{ $dis }} {{ $reqAttr('co_applicant.marital_status') }}>
                            @foreach($maritalOpts as $val => $label)
                                <option value="{{ $val }}" @selected(($co['marital_status'] ?? '') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Relationship with applicant{!! $reqMark('co_applicant.relationship') !!}</label>
                        <select class="cw-select js-select2" name="customer_details[co_applicant][relationship]"
                                data-search="true" data-placeholder="Select relationship" {{ $dis }} {{ $reqAttr('co_applicant.relationship') }}>
                            @foreach($relCoOpts as $val => $label)
                                <option value="{{ $val }}" @selected($relCo === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Personal Mail ID{!! $reqMark('co_applicant.email') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="cw-input" name="customer_details[co_applicant][email]" value="{{ $co['email'] ?? '' }}" placeholder="name@email.com" {{ $ro }} {{ $reqAttr('co_applicant.email') }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">Official Mail ID{!! $reqMark('co_applicant.official_email') !!}</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" class="cw-input" name="customer_details[co_applicant][official_email]" value="{{ $co['official_email'] ?? '' }}" placeholder="work@company.com" {{ $ro }} {{ $reqAttr('co_applicant.official_email') }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Residence --}}
    <div class="cw-card-tw">
        <div class="cw-card-head">
            <span class="cw-ico"><i class="bi bi-house-door"></i></span>
            <h6 class="cw-card-title">Residence</h6>
        </div>
        <div class="px-4 pb-4">
            <div class="cw-emp-grid">
                <div class="cw-emp-row">
                    <div></div>
                    <div class="cw-party-head cw-party-head-app mb-0"><i class="bi bi-person-fill"></i><span>Applicant</span></div>
                    <div class="cw-party-head cw-party-head-co mb-0"><i class="bi bi-people-fill"></i><span>Co-Applicant</span></div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Present Residence Address</label>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                            <input class="cw-input" name="customer_details[residence][present_address]" value="{{ $res['present_address'] ?? '' }}" placeholder="Enter present address" {{ $ro }} {{ $reqAttr('residence.present_address') }}>
                        </div>
                    </div>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                            <input class="cw-input" name="customer_details[residence][present_address_co]" value="{{ $res['present_address_co'] ?? '' }}" placeholder="Enter present address" {{ $ro }} {{ $reqAttr('residence.present_address_co') }}>
                        </div>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Pincode</label>
                    <div>
                        <input class="cw-input" name="customer_details[residence][present_pincode]" value="{{ $res['present_pincode'] ?? '' }}" placeholder="Pincode" maxlength="6" {{ $ro }} {{ $reqAttr('residence.present_pincode') }}>
                    </div>
                    <div>
                        <input class="cw-input" name="customer_details[residence][present_pincode_co]" value="{{ $res['present_pincode_co'] ?? '' }}" placeholder="Pincode" maxlength="6" {{ $ro }} {{ $reqAttr('residence.present_pincode_co') }}>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">No. of Years at above Residence</label>
                    <div>
                        <input class="cw-input" name="customer_details[residence][years_applicant]" value="{{ $res['years_applicant'] ?? '' }}" placeholder="Enter years" {{ $ro }} {{ $reqAttr('residence.years_applicant') }}>
                    </div>
                    <div>
                        <input class="cw-input" name="customer_details[residence][years_co]" value="{{ $res['years_co'] ?? '' }}" placeholder="Enter years" {{ $ro }} {{ $reqAttr('residence.years_co') }}>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Permanent Address</label>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                            <input class="cw-input" name="customer_details[residence][permanent_address]" value="{{ $res['permanent_address'] ?? '' }}" placeholder="Enter permanent address" {{ $ro }} {{ $reqAttr('residence.permanent_address') }}>
                        </div>
                    </div>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                            <input class="cw-input" name="customer_details[residence][permanent_address_co]" value="{{ $res['permanent_address_co'] ?? '' }}" placeholder="Enter permanent address" {{ $ro }} {{ $reqAttr('residence.permanent_address_co') }}>
                        </div>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Permanent Pincode</label>
                    <div>
                        <input class="cw-input" name="customer_details[residence][permanent_pincode]" value="{{ $res['permanent_pincode'] ?? '' }}" placeholder="Pincode" maxlength="6" {{ $ro }} {{ $reqAttr('residence.permanent_pincode') }}>
                    </div>
                    <div>
                        <input class="cw-input" name="customer_details[residence][permanent_pincode_co]" value="{{ $res['permanent_pincode_co'] ?? '' }}" placeholder="Pincode" maxlength="6" {{ $ro }} {{ $reqAttr('residence.permanent_pincode_co') }}>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Employment --}}
    <div class="cw-card-tw">
        <div class="cw-card-head">
            <span class="cw-ico"><i class="bi bi-briefcase"></i></span>
            <h6 class="cw-card-title">Employment</h6>
        </div>
        <div class="px-4 pb-4">
            <div class="cw-emp-grid">
                <div class="cw-emp-row">
                    <div></div>
                    <div class="cw-party-head cw-party-head-app mb-0"><i class="bi bi-person-fill"></i><span>Applicant</span></div>
                    <div class="cw-party-head cw-party-head-co mb-0"><i class="bi bi-people-fill"></i><span>Co-Applicant</span></div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Office Address</label>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                            <input class="cw-input" name="customer_details[employment][office_address]" value="{{ $emp['office_address'] ?? '' }}" placeholder="Enter office address" {{ $ro }}>
                        </div>
                    </div>
                        <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                            <input class="cw-input" name="customer_details[employment][office_address_co]" value="{{ $emp['office_address_co'] ?? '' }}" placeholder="Enter office address" {{ $ro }}>
                        </div>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Employment Type</label>
                    <div>
                        <select class="cw-select js-select2" name="customer_details[employment][type_applicant]" {{ $dis }} {{ $reqAttr('employment.type_applicant') }}>
                            @foreach($empTypeOpts as $val => $label)
                                <option value="{{ $val }}" @selected($empTypeApp === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                    </div>
                    <div>
                        <select class="cw-select js-select2" name="customer_details[employment][type_co]" {{ $dis }} {{ $reqAttr('employment.type_co') }}>
                            @foreach(['' => 'Select', 'Salaried' => 'Salaried', 'Self Employed' => 'Self Employed', 'Business' => 'Business', 'Professional' => 'Professional', 'Other' => 'Other'] as $val => $label)
                                <option value="{{ $val }}" @selected($empTypeCo === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Total job experience</label>
                    <div><input class="cw-input" name="customer_details[employment][total_exp_applicant]" value="{{ $emp['total_exp_applicant'] ?? ($cd['work_experience_years'] ?? '') }}" placeholder="Enter years" {{ $ro }}></div>
                    <div><input class="cw-input" name="customer_details[employment][total_exp_co]" value="{{ $emp['total_exp_co'] ?? '' }}" placeholder="Enter years" {{ $ro }}></div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Present company job experience</label>
                    <div><input class="cw-input" name="customer_details[employment][present_exp_applicant]" value="{{ $emp['present_exp_applicant'] ?? '' }}" placeholder="Enter years" {{ $ro }}></div>
                    <div><input class="cw-input" name="customer_details[employment][present_exp_co]" value="{{ $emp['present_exp_co'] ?? '' }}" placeholder="Enter years" {{ $ro }}></div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Designation</label>
                    <div><input class="cw-input" name="customer_details[employment][designation_applicant]" value="{{ $emp['designation_applicant'] ?? '' }}" placeholder="Enter designation" {{ $ro }}></div>
                    <div><input class="cw-input" name="customer_details[employment][designation_co]" value="{{ $emp['designation_co'] ?? '' }}" placeholder="Enter designation" {{ $ro }}></div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Industry Type</label>
                    <div>
                        <select class="cw-select js-select2" name="customer_details[employment][industry_applicant]" {{ $dis }}>
                            @foreach($industryOpts as $val => $label)
                                <option value="{{ $val }}" @selected($indApp === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select class="cw-select js-select2" name="customer_details[employment][industry_co]" {{ $dis }}>
                            @foreach($industryOpts as $val => $label)
                                <option value="{{ $val }}" @selected($indCo === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Employer / Business Name</label>
                    <div><input class="cw-input" name="customer_details[employment][employer_applicant]" value="{{ $emp['employer_applicant'] ?? ($cd['employer_name'] ?? '') }}" placeholder="Enter employer / business name" {{ $ro }} {{ $reqAttr('employment.employer_applicant') }}></div>
                    <div><input class="cw-input" name="customer_details[employment][employer_co]" value="{{ $emp['employer_co'] ?? '' }}" placeholder="Enter employer / business name" {{ $ro }} {{ $reqAttr('employment.employer_co') }}></div>
                </div>

                <div class="cw-emp-row">
                    <label class="cw-label-inline">Monthly / Net Income</label>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input class="cw-input js-decimal" data-decimals="2" name="customer_details[employment][income_applicant]" value="{{ $emp['income_applicant'] ?? ($cd['monthly_income'] ?? '') }}" placeholder="Enter amount" {{ $ro }} {{ $reqAttr('employment.income_applicant') }}>
                        </div>
                    </div>
                    <div>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input class="cw-input js-decimal" data-decimals="2" name="customer_details[employment][income_co]" value="{{ $emp['income_co'] ?? '' }}" placeholder="Enter amount" {{ $ro }} {{ $reqAttr('employment.income_co') }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Property | References --}}
    <div class="mb-3 grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.15fr)]">
        <div class="cw-card-tw mb-0 h-full">
            <div class="cw-card-head border-b border-slate-100">
                <span class="cw-ico cw-ico-blue"><i class="bi bi-building"></i></span>
                <h6 class="cw-card-title">Property</h6>
            </div>
            <div class="space-y-3 px-4 py-3.5">
                <div>
                    <label class="cw-label">Property Address and Pin Code</label>
                    <div class="grid grid-cols-12 gap-2.5">
                        <div class="col-span-12 sm:col-span-8">
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                                <input class="cw-input" name="customer_details[property][address]" value="{{ $prop['address'] ?? '' }}" placeholder="Enter property address" {{ $ro }}>
                            </div>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <input class="cw-input" name="customer_details[property][pincode]" value="{{ $prop['pincode'] ?? '' }}" placeholder="Pincode" maxlength="6" {{ $ro }}>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="cw-label">Property Market Value</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input class="cw-input js-decimal" data-decimals="2" name="customer_details[property][market_value]" value="{{ $prop['market_value'] ?? '' }}" placeholder="Enter amount" {{ $ro }}>
                        </div>
                    </div>
                    <div>
                        <label class="cw-label">Property SR Value</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input class="cw-input js-decimal" data-decimals="2" name="customer_details[property][sr_value]" value="{{ $prop['sr_value'] ?? '' }}" placeholder="Enter amount" {{ $ro }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cw-card-tw mb-0 h-full">
            <div class="cw-card-head border-b border-slate-100">
                <span class="cw-ico cw-ico-blue"><i class="bi bi-people"></i></span>
                <h6 class="cw-card-title">References</h6>
            </div>
            <div class="cw-ref-split">
                <div class="cw-ref-col">
                    <div class="cw-ref-head cw-ref-head-1"><i class="bi bi-person"></i> Reference 1</div>
                    <div class="cw-ref-fields">
                        <div class="cw-field-row">
                            <label class="cw-label-inline">Name</label>
                            <input class="cw-input" name="customer_details[references][ref1_name]" value="{{ $refs['ref1_name'] ?? '' }}" placeholder="Enter name" {{ $ro }}>
                        </div>
                        <div class="cw-field-row">
                            <label class="cw-label-inline">Mobile Number</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                                <input class="cw-input js-mobile" name="customer_details[references][ref1_mobile]" value="{{ $refs['ref1_mobile'] ?? '' }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                            </div>
                        </div>
                        <div class="cw-field-row">
                            <label class="cw-label-inline">Address</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                                <input class="cw-input" name="customer_details[references][ref1_address]" value="{{ $refs['ref1_address'] ?? '' }}" placeholder="Enter address" {{ $ro }}>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cw-ref-col">
                    <div class="cw-ref-head cw-ref-head-2"><i class="bi bi-person"></i> Reference 2</div>
                    <div class="cw-ref-fields">
                        <div class="cw-field-row">
                            <label class="cw-label-inline">Name</label>
                            <input class="cw-input" name="customer_details[references][ref2_name]" value="{{ $refs['ref2_name'] ?? '' }}" placeholder="Enter name" {{ $ro }}>
                        </div>
                        <div class="cw-field-row">
                            <label class="cw-label-inline">Mobile Number</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                                <input class="cw-input js-mobile" name="customer_details[references][ref2_mobile]" value="{{ $refs['ref2_mobile'] ?? '' }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                            </div>
                        </div>
                        <div class="cw-field-row">
                            <label class="cw-label-inline">Address</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-geo-alt"></i></span>
                                <input class="cw-input" name="customer_details[references][ref2_address]" value="{{ $refs['ref2_address'] ?? '' }}" placeholder="Enter address" {{ $ro }}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Case Information (Product · Amount · Contact · Consent) --}}
    <div class="cw-card-tw">
        <div class="cw-card-head border-b border-slate-100">
            <span class="cw-ico"><i class="bi bi-layers"></i></span>
            <h6 class="cw-card-title">Case Information</h6>
        </div>
        <div class="cw-case-info-grid">
            <div>
                <label class="cw-label">Product</label>
                <input class="cw-input cw-product-input" name="product" value="{{ old('product', $case->product) }}" placeholder="e.g. Home Loan" {{ $ro }}>
            </div>
            <div>
                <label class="cw-label">Requested Amount</label>
                <div class="cw-input-group">
                    <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                    <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="loan_amount" value="{{ old('loan_amount', $case->loan_amount) }}" placeholder="0.00" {{ $ro }}>
                </div>
            </div>
            <div>
                <label class="cw-label">Preferred Contact Time</label>
                @php
                    $pctVal = old('customer_details.preferred_contact_time', $cd['preferred_contact_time'] ?? '');
                    $pctFrom = old('customer_details.preferred_contact_from', '');
                    $pctTo = old('customer_details.preferred_contact_to', '');
                    if ($pctVal && str_contains((string) $pctVal, ' - ') && ($pctFrom === '' || $pctTo === '')) {
                        [$pctFrom, $pctTo] = array_map('trim', explode(' - ', (string) $pctVal, 2));
                    } elseif ($pctVal && $pctFrom === '' && $pctTo === '') {
                        $pctFrom = (string) $pctVal;
                    }
                @endphp
                <input type="hidden" name="customer_details[preferred_contact_time]" id="cw_preferred_contact_time" value="{{ $pctVal }}">
                <div class="cw-pct-row">
                    <div class="cw-input-group">
                        <span class="cw-input-group-ico"><i class="bi bi-clock"></i></span>
                        <input type="text" class="cw-input js-timepicker" id="cw_preferred_contact_from"
                               value="{{ $pctFrom }}" placeholder="From" autocomplete="off"
                               data-minute-increment="15" {{ $ro }}>
                    </div>
                    <span class="cw-pct-sep">–</span>
                    <div class="cw-input-group">
                        <span class="cw-input-group-ico"><i class="bi bi-clock"></i></span>
                        <input type="text" class="cw-input js-timepicker" id="cw_preferred_contact_to"
                               value="{{ $pctTo }}" placeholder="To" autocomplete="off"
                               data-minute-increment="15" {{ $ro }}>
                    </div>
                </div>
            </div>
            <div>
                <label class="cw-label">Consent</label>
                <div class="cw-consent {{ $consentOk ? 'cw-consent-ok' : 'cw-consent-warn' }}">
                    @if($consentOk)<i class="bi bi-check-circle-fill"></i>@else<i class="bi bi-exclamation-circle"></i>@endif
                    <span>{{ $consentText }}</span>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="customer_name" id="cw_customer_name" value="{{ old('customer_name', $case->customer_name) }}">
    <input type="hidden" name="mobile" id="cw_mobile" value="{{ old('mobile', $case->mobile) }}">
        </div>
