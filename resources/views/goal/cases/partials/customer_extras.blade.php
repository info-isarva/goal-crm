<div class="cw-cust cw-reco">
    {{-- ========== BM RECO ========== --}}
    <div class="cw-extra-section" id="cwBmReco">
        @php
            $recoStatusUi = $recoStatus ?? (string) ($br['approval_status'] ?? $br['status'] ?? 'Draft');
            if ($recoStatusUi === '') { $recoStatusUi = 'Draft'; }
            $recoStatusClass = match ($recoStatusUi) {
                'Submitted' => 'is-submitted',
                'Approved' => 'is-approved',
                'Rejected' => 'is-rejected',
                default => 'is-draft',
            };
            $authName = auth()->user()?->name ?? '';
            $recommendedBy = (!empty($canEditReco) || in_array($recoStatusUi, ['Draft', 'Rejected'], true))
                ? ($authName !== '' ? $authName : ($br['by'] ?? $br['recommended_by'] ?? ''))
                : ($br['by'] ?? $br['recommended_by'] ?? $authName);
            $fmtRecoAt = static function ($raw): ?string {
                if (! filled($raw)) {
                    return null;
                }
                try {
                    return \Illuminate\Support\Carbon::parse($raw)->timezone(config('app.timezone'))->format('d M Y, h:i a');
                } catch (\Throwable $e) {
                    return (string) $raw;
                }
            };
            $approvedAtUi = $fmtRecoAt($br['approved_at'] ?? null);
            $rejectedAtUi = $fmtRecoAt($br['rejected_at'] ?? null);
        @endphp
        <div class="cw-reco-banner {{ $recoStatusClass }}">
            <div class="min-w-0">
                <div class="cw-reco-banner-title">
                    @if($recoStatusUi === 'Rejected')
                        <i class="bi bi-x-octagon"></i>
                    @elseif($recoStatusUi === 'Approved')
                        <i class="bi bi-check2-circle"></i>
                    @elseif($recoStatusUi === 'Submitted')
                        <i class="bi bi-hourglass-split"></i>
                    @else
                        <i class="bi bi-lightbulb"></i>
                    @endif
                    BM Recommendation — Goal Corporation
                    <span class="cw-reco-status {{ $recoStatusClass }}">{{ $recoStatusUi }}</span>
                </div>
                <div class="cw-reco-banner-sub">
                    Proposal brief for <strong>{{ strtoupper($case->customer_name) }}</strong>
                    @if($recoStatusUi === 'Submitted')
                        · Awaiting Cluster / RD / MD approval
                    @elseif($recoStatusUi === 'Approved')
                        · Approved{{ !empty($br['approved_by']) ? ' by '.$br['approved_by'] : '' }}
                        @if($approvedAtUi) · {{ $approvedAtUi }} @endif
                        · Ready for Banking
                    @elseif($recoStatusUi === 'Rejected')
                        · Revise the recommendation, save, then resubmit for approval
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('goal.cases.bm-reco.export', $case) }}" class="btn-ghost" style="height:34px;padding:0 12px">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </a>
            </div>
        </div>

        @if($recoStatusUi === 'Rejected')
        <div class="cw-reco-reject-notice" role="status">
            <div class="cw-reco-reject-notice-icon" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></div>
            <div class="cw-reco-reject-notice-body">
                <div class="cw-reco-reject-notice-title">Recommendation rejected</div>
                <div class="cw-reco-reject-notice-meta">
                    @if(!empty($br['rejected_by']))
                        By <strong>{{ $br['rejected_by'] }}</strong>
                    @else
                        By higher authority
                    @endif
                    @if($rejectedAtUi)
                        · {{ $rejectedAtUi }}
                    @endif
                </div>
                @if(!empty($br['rejection_reason']))
                    <div class="cw-reco-reject-notice-reason">
                        <span class="k">Reason</span>
                        <span class="v">{{ $br['rejection_reason'] }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <div class="cw-reco-top">
            {{-- Borrower Profile --}}
            <div class="cw-card-tw cw-reco-profile">
                <div class="cw-card-head">
                    <span class="cw-ico cw-ico-amber"><i class="bi bi-people"></i></span>
                    <h6 class="cw-card-title">Borrower Profile</h6>
                </div>
                @php
                    $bpFilled = static function ($v): bool {
                        return $v !== null && !(is_string($v) && trim($v) === '');
                    };
                    // Customer Details is source of truth for identity; BM profile only fills gaps / extras.
                    $bpIdentityKeys = ['name', 'age', 'dob', 'pan', 'relationship'];
                    $bpVal = function (string $party, string $key, $fallback = '') use ($br, $bpFilled, $bpIdentityKeys) {
                        $stored = data_get($br, "profile.$party.$key");
                        $isIdentity = in_array($key, $bpIdentityKeys, true);
                        if ($isIdentity && $bpFilled($fallback)) {
                            return $fallback;
                        }
                        if ($bpFilled($stored)) {
                            return $stored;
                        }

                        return $fallback ?? '';
                    };
                    $bpAppDefaults = [
                        'name' => $app['name'] ?? $case->customer_name ?? '',
                        'age' => $app['age'] ?? '',
                        'dob' => $app['dob'] ?? '',
                        'relationship' => $app['relationship'] ?? 'Self',
                        'profession' => $emp['designation_applicant'] ?? ($emp['type_applicant'] ?? ($cd['employment_type'] ?? '')),
                        'gross_income' => $emp['gross_income_applicant'] ?? ($emp['income_applicant'] ?? ($cd['monthly_income'] ?? '')),
                        'net_income' => $emp['income_applicant'] ?? ($cd['monthly_income'] ?? ''),
                        'pan' => $app['pan'] ?? '',
                    ];
                    $bpCoDefaults = [
                        'name' => $co['name'] ?? '',
                        'age' => $co['age'] ?? '',
                        'dob' => $co['dob'] ?? '',
                        'relationship' => $co['relationship'] ?? '',
                        'profession' => $emp['designation_co'] ?? ($emp['type_co'] ?? ''),
                        'gross_income' => $emp['gross_income_co'] ?? ($emp['income_co'] ?? ''),
                        'net_income' => $emp['income_co'] ?? '',
                        'pan' => $co['pan'] ?? '',
                    ];
                @endphp
                <div class="cw-bp-table-wrap">
                    <table class="cw-bp-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Applicant</th>
                                <th>Co-Applicant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['name', 'Name', 'text', 'Enter name'],
                                ['dob', 'DOB', 'date', 'dd-mm-yyyy'],
                                ['age', 'Age', 'age', 'Auto from DOB'],
                                ['relationship', 'Relationship', 'text', 'Relationship'],
                                ['profession', 'Profession', 'text', 'Profession'],
                                ['gross_income', 'Gross Income', 'money', '0.00'],
                                ['net_income', 'Net Income', 'money', '0.00'],
                                ['pan', 'PAN NO', 'text', 'ABCDE1234F'],
                            ] as [$key, $label, $type, $ph])
                                @php
                                    $appVal = $bpVal('applicant', $key, $bpAppDefaults[$key] ?? '');
                                    $coVal = $bpVal('co_applicant', $key, $bpCoDefaults[$key] ?? '');
                                @endphp
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>
                                        @if($type === 'date')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                                                <input type="text" class="cw-input js-datepicker js-dob-input" name="bm_reco[profile][applicant][{{ $key }}]"
                                                       value="{{ $appVal }}"
                                                       placeholder="{{ $ph }}" autocomplete="off"
                                                       data-party="bm_applicant" data-min-age="18" {{ $ro }}>
                                            </div>
                                        @elseif($type === 'age')
                                            <input class="cw-input js-age-from-dob" name="bm_reco[profile][applicant][{{ $key }}]"
                                                   value="{{ $appVal }}"
                                                   placeholder="{{ $ph }}" data-party="bm_applicant" readonly tabindex="-1" {{ $ro }}>
                                        @elseif($type === 'money')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                                       name="bm_reco[profile][applicant][{{ $key }}]"
                                                       value="{{ $appVal }}"
                                                       placeholder="{{ $ph }}" {{ $ro }}>
                                            </div>
                                        @else
                                            <input class="cw-input" name="bm_reco[profile][applicant][{{ $key }}]"
                                                   value="{{ $appVal }}"
                                                   placeholder="{{ $ph }}" {{ $ro }}>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type === 'date')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                                                <input type="text" class="cw-input js-datepicker js-dob-input" name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                       value="{{ $coVal }}"
                                                       placeholder="{{ $ph }}" autocomplete="off"
                                                       data-party="bm_co_applicant" data-min-age="18" {{ $ro }}>
                                            </div>
                                        @elseif($type === 'age')
                                            <input class="cw-input js-age-from-dob" name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                   value="{{ $coVal }}"
                                                   placeholder="{{ $ph }}" data-party="bm_co_applicant" readonly tabindex="-1" {{ $ro }}>
                                        @elseif($type === 'money')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                                       name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                       value="{{ $coVal }}"
                                                       placeholder="{{ $ph }}" {{ $ro }}>
                                            </div>
                                        @else
                                            <input class="cw-input" name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                   value="{{ $coVal }}"
                                                   placeholder="{{ $ph }}" {{ $ro }}>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Requirement & Eligibility --}}
            @php
                $sugBankName = (string) ($br['suggested_bank'] ?? '');
                $sugBankCode = (string) ($br['bank_code'] ?? '');
                $sugBankId = (string) ($br['bank_id'] ?? '');
                $sugBankLabel = $sugBankName !== ''
                    ? ($sugBankCode !== '' ? $sugBankName.' ('.$sugBankCode.')' : $sugBankName)
                    : '';
            @endphp
            <div class="cw-card-tw cw-reco-req">
                <div class="cw-card-head">
                    <span class="cw-ico cw-ico-blue"><i class="bi bi-clipboard-check"></i></span>
                    <h6 class="cw-card-title">Requirement &amp; Eligibility</h6>
                </div>
                <div class="cw-reco-req-body">
                    <div class="cw-reco-bank-block">
                        <div class="cw-reco-bank-title">Bank information</div>
                        <div class="cw-reco-req-bank">
                            <div class="cw-reco-bank-search">
                                <label class="cw-label">Suggested Bank</label>
                                <select id="cwBmSuggestedBank"
                                        class="cw-select js-bank-master-select"
                                        data-select2-skip="1"
                                        data-placeholder="Type bank name or code"
                                        data-allow-clear="true"
                                        data-bank-search-url="{{ route('goal.masters.banks.search') }}"
                                        {{ $dis }}>
                                    <option value=""></option>
                                    @if($sugBankId !== '' || $sugBankName !== '')
                                        <option value="{{ $sugBankId !== '' ? $sugBankId : $sugBankName }}"
                                                data-name="{{ $sugBankName }}"
                                                data-code="{{ $sugBankCode }}"
                                                selected>{{ $sugBankLabel !== '' ? $sugBankLabel : 'Selected bank' }}</option>
                                    @endif
                                </select>
                                <input type="hidden" name="bm_reco[bank_id]" id="cwBmBankId" value="{{ $sugBankId }}">
                                <input type="hidden" name="bm_reco[suggested_bank]" id="cwBmBankName" value="{{ $sugBankName }}">
                            </div>
                            <div>
                                <label class="cw-label">Bank Name</label>
                                <input class="cw-input" id="cwBmBankNameDisplay" value="{{ $sugBankName }}" placeholder="Fetched from master" readonly tabindex="-1">
                            </div>
                            <div>
                                <label class="cw-label">Bank Code</label>
                                <input class="cw-input" name="bm_reco[bank_code]" id="cwBmBankCode" value="{{ $sugBankCode }}" placeholder="Fetched from master" readonly tabindex="-1">
                            </div>
                        </div>
                        <div class="cw-reco-req-banker">
                            <div>
                                <label class="cw-label">Banker Name</label>
                                <div class="cw-input-group">
                                    <span class="cw-input-group-ico"><i class="bi bi-person"></i></span>
                                    <input class="cw-input" name="bm_reco[banker_name]" value="{{ $br['banker_name'] ?? '' }}" placeholder="Enter banker name" {{ $ro }}>
                                </div>
                            </div>
                            <div>
                                <label class="cw-label">Banker Contact</label>
                                <div class="cw-input-group">
                                    <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                                    <input class="cw-input js-mobile" name="bm_reco[banker_contact]" value="{{ $br['banker_contact'] ?? '' }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                                </div>
                            </div>
                            <div>
                                <label class="cw-label">Banker Mail</label>
                                <div class="cw-input-group">
                                    <span class="cw-input-group-ico"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="cw-input" name="bm_reco[banker_mail]" value="{{ $br['banker_mail'] ?? '' }}" placeholder="name@bank.com" {{ $ro }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $recoAmountRaw = $br['amount'] ?? $br['recommended_amount'] ?? null;
                        $recoAmountNorm = is_string($recoAmountRaw)
                            ? str_replace([',', ' '], '', trim($recoAmountRaw))
                            : $recoAmountRaw;
                        $recoAmountEmpty = $recoAmountRaw === null
                            || $recoAmountRaw === ''
                            || (is_numeric($recoAmountNorm) && (float) $recoAmountNorm == 0.0);
                        $recoAmountValue = ! $recoAmountEmpty
                            ? $recoAmountRaw
                            : ($case->loan_amount ?? ($case->lead->loan_amount ?? ''));
                        $recoRequirementValue = filled($br['requirement'] ?? null)
                            ? (string) $br['requirement']
                            : (string) ($case->product ?? '');
                        $requirementOptions = collect($products ?? []);
                        $requirementInMaster = $recoRequirementValue !== ''
                            && $requirementOptions->contains(fn ($p) => $p->product_name === $recoRequirementValue);
                    @endphp
                    <div class="cw-reco-req-top">
                        <div>
                            <label class="cw-label">Recommended Amount</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                       name="bm_reco[amount]" value="{{ $recoAmountValue }}"
                                       placeholder="From lead loan amount" {{ $ro }}>
                            </div>
                            <div class="cw-field-hint">Prefills from lead / case loan amount; you can revise before submit.</div>
                        </div>
                        <div>
                            <label class="cw-label">Requirement</label>
                            <select class="cw-select js-select2" name="bm_reco[requirement]"
                                    data-placeholder="Select requirement" data-allow-clear="false" data-search="true" {{ $dis }}>
                                <option value="">Select requirement</option>
                                @if($recoRequirementValue !== '' && ! $requirementInMaster)
                                    <option value="{{ $recoRequirementValue }}" selected>{{ $recoRequirementValue }}</option>
                                @endif
                                @foreach($requirementOptions as $p)
                                    <option value="{{ $p->product_name }}" @selected($requirementInMaster && $recoRequirementValue === $p->product_name)>{{ $p->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="cw-label">Eligibility</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                       name="bm_reco[eligibility]" value="{{ $br['eligibility'] ?? '' }}"
                                       placeholder="0.00" {{ $ro }}>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="cw-label">App &amp; Co-App Background</label>
                        <textarea class="cw-textarea" rows="3" name="bm_reco[background]" placeholder="Background notes" {{ $ro }}>{{ $br['background'] ?? '' }}</textarea>
                    </div>

                    <div class="cw-reco-req-mid">
                        <div>
                            <label class="cw-label">Mitigants</label>
                            <textarea class="cw-textarea" rows="5" name="bm_reco[mitigants]" placeholder="Mitigants" {{ $ro }}>{{ $br['mitigants'] ?? '' }}</textarea>
                        </div>
                        <div class="cw-reco-req-side">
                            <div>
                                <label class="cw-label">Deviation</label>
                                <input class="cw-input" name="bm_reco[deviation]" value="{{ $br['deviation'] ?? '' }}" placeholder="Deviation" {{ $ro }}>
                            </div>
                            <div>
                                <label class="cw-label">Remarks</label>
                                <textarea class="cw-textarea" rows="3" name="bm_reco[remarks]" placeholder="Remarks" {{ $ro }}>{{ $br['remarks'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="cw-reco-req-bottom">
                        <div>
                            <label class="cw-label">Recommended By</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-person"></i></span>
                                <input class="cw-input" name="bm_reco[by]"
                                       value="{{ $recommendedBy }}"
                                       placeholder="Logged-in user" readonly tabindex="-1">
                            </div>
                            <div class="cw-field-hint">Locked to the logged-in user who saves / submits.</div>
                        </div>
                        <div>
                            <label class="cw-label">Recommendation Date</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                                <input type="text" class="cw-input js-datepicker" name="bm_reco[date]"
                                       value="{{ $br['date'] ?? '' }}" placeholder="dd-mm-yyyy" autocomplete="off" {{ $ro }}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recommendation Contacts --}}
        <div class="cw-card-tw">
            <div class="cw-card-head cw-card-head-soft">
                <span class="cw-ico cw-ico-green"><i class="bi bi-person-plus"></i></span>
                <h6 class="cw-card-title">Recommendation Contacts</h6>
            </div>
            <div class="cw-reco-contacts">
                <div class="cw-reco-contact-col">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">BM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][bm_name]" value="{{ data_get($br, 'contacts.bm_name', $branchManager?->name) }}" placeholder="Enter BM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">BM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][bm_mobile]" value="{{ data_get($br, 'contacts.bm_mobile', $branchManager?->mobile) }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">SSM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][rsm_name]" value="{{ data_get($br, 'contacts.rsm_name') }}" placeholder="Enter SSM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">SSM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][rsm_mobile]" value="{{ data_get($br, 'contacts.rsm_mobile') }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                        </div>
                    </div>
                </div>
                <div class="cw-reco-contact-col">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">SM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][sm_name]" value="{{ data_get($br, 'contacts.sm_name', $case->owner?->name) }}" placeholder="Enter SM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">SM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][sm_mobile]" value="{{ data_get($br, 'contacts.sm_mobile', $case->owner?->mobile) }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">JSM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][asm_name]" value="{{ data_get($br, 'contacts.asm_name') }}" placeholder="Enter JSM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">JSM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][asm_mobile]" value="{{ data_get($br, 'contacts.asm_mobile') }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" {{ $ro }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== EXISTING LOANS ========== --}}
    <div class="cw-extra-section" id="cwLoans">
        <div class="cw-card-tw">
            <div class="cw-card-head cw-card-head-soft">
                <span class="cw-ico cw-ico-violet"><i class="bi bi-stack"></i></span>
                <h6 class="cw-card-title">Existing HL (BT Source)</h6>
                @if($canEdit)
                    <button type="button" class="cw-add-row-btn ms-auto" id="cwAddBtRow"><i class="bi bi-plus-lg"></i> Add Row</button>
                @endif
            </div>
            <div class="cw-reco-bt-list" id="cwBtSourceList">
                @foreach($btRows as $bi => $btRow)
                    @php
                        $btRow = is_array($btRow) ? $btRow : [];
                        $btType = $btRow['loan_type'] ?? '';
                        $btTypeOpts = $loanTypeOpts;
                        if ($btType !== '' && !array_key_exists($btType, $btTypeOpts)) {
                            $btTypeOpts[$btType] = $btType;
                        }
                    @endphp
                    <div class="cw-reco-bt-grid" data-bt-row>
                        <div>
                            <label class="cw-label">Loan Type</label>
                            <select class="cw-select" name="existing_loans[bt_source][{{ $bi }}][loan_type]" {{ $dis }}>
                                @foreach($btTypeOpts as $val => $lab)
                                    <option value="{{ $val }}" @selected($btType === $val)>{{ $lab }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="cw-label">Loan Amt</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                       name="existing_loans[bt_source][{{ $bi }}][loan_amt]"
                                       value="{{ $btRow['loan_amt'] ?? '' }}" placeholder="Enter loan amt" {{ $ro }}>
                            </div>
                        </div>
                        <div>
                            <label class="cw-label">ROI %</label>
                            <input class="cw-input" name="existing_loans[bt_source][{{ $bi }}][roi]"
                                   value="{{ $btRow['roi'] ?? '' }}" placeholder="Enter ROI %" {{ $ro }}>
                        </div>
                        <div>
                            <label class="cw-label">Tenor</label>
                            <input class="cw-input" name="existing_loans[bt_source][{{ $bi }}][tenor]"
                                   value="{{ $btRow['tenor'] ?? '' }}" placeholder="Enter tenor" {{ $ro }}>
                        </div>
                        <div class="cw-bt-emi-cell">
                            <label class="cw-label">EMI</label>
                            <div class="cw-bt-emi-row">
                                <div class="cw-input-group">
                                    <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                    <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                           name="existing_loans[bt_source][{{ $bi }}][emi]"
                                           value="{{ $btRow['emi'] ?? '' }}" placeholder="Enter EMI" {{ $ro }}>
                                </div>
                                @if($canEdit)
                                    <button type="button" class="cw-row-remove" data-remove-bt title="Remove row"><i class="bi bi-trash3"></i></button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <template id="cwBtRowTpl">
                <div class="cw-reco-bt-grid" data-bt-row>
                    <div>
                        <label class="cw-label">Loan Type</label>
                        <select class="cw-select" name="existing_loans[bt_source][__i__][loan_type]">
                            @foreach($loanTypeOpts as $val => $lab)
                                <option value="{{ $val }}">{{ $lab }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="cw-label">Loan Amt</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                   name="existing_loans[bt_source][__i__][loan_amt]" placeholder="Enter loan amt">
                        </div>
                    </div>
                    <div>
                        <label class="cw-label">ROI %</label>
                        <input class="cw-input" name="existing_loans[bt_source][__i__][roi]" placeholder="Enter ROI %">
                    </div>
                    <div>
                        <label class="cw-label">Tenor</label>
                        <input class="cw-input" name="existing_loans[bt_source][__i__][tenor]" placeholder="Enter tenor">
                    </div>
                    <div class="cw-bt-emi-cell">
                        <label class="cw-label">EMI</label>
                        <div class="cw-bt-emi-row">
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                       name="existing_loans[bt_source][__i__][emi]" placeholder="Enter EMI">
                            </div>
                            <button type="button" class="cw-row-remove" data-remove-bt title="Remove row"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>

    {{-- ========== EQUIFAX ========== --}}
    @php
        $equifax = data_get($br, 'equifax', []) ?: [];
        $hasEquifaxFile = filled($equifax['path'] ?? null);
        $hasEquifaxData = filled($equifax['score'] ?? null) || filled($equifax['notes'] ?? null);
        $equifaxReceived = ! empty($equifax['received']) || filled($equifax['recorded_at'] ?? null) || $hasEquifaxFile || $hasEquifaxData;
        $equifaxWhen = $equifax['recorded_at'] ?? ($equifax['uploaded_at'] ?? null);
        $equifaxBy = $equifax['recorded_by'] ?? ($equifax['uploaded_by'] ?? null);
        $equifaxWhenFmt = null;
        if (! empty($equifaxWhen)) {
            try { $equifaxWhenFmt = \Carbon\Carbon::parse($equifaxWhen)->format('d M Y H:i'); } catch (\Throwable) { $equifaxWhenFmt = (string) $equifaxWhen; }
        }
    @endphp
    <div class="cw-extra-section" id="cwEquifax">
        <div class="cw-card-tw cw-card-soft">
            <div class="cw-card-head cw-card-head-soft cw-equifax-head">
                <div class="cw-equifax-head-left">
                    <span class="cw-ico cw-ico-amber"><i class="bi bi-file-earmark-lock"></i></span>
                    <h6 class="cw-card-title">Equifax / Credit Bureau</h6>
                    @if($equifaxReceived)
                        <span class="cw-equifax-pill is-saved"><i class="bi bi-check2-circle"></i> Saved</span>
                    @else
                        <span class="cw-equifax-pill">Not recorded</span>
                    @endif
                </div>
                <div class="cw-equifax-head-right">
                    @if($equifaxReceived && ($equifaxWhenFmt || $equifaxBy))
                        <span class="cw-equifax-meta">
                            {{ $equifaxWhenFmt }}
                            @if($equifaxBy) · {{ $equifaxBy }} @endif
                        </span>
                    @endif
                    @if($hasEquifaxFile)
                        <a href="{{ route('goal.cases.bm-reco.equifax.download', $case) }}" target="_blank" rel="noopener" class="btn-ghost cw-equifax-view">
                            <i class="bi bi-eye"></i>
                            <span class="cw-equifax-view-name">{{ $equifax['file_name'] ?? 'View report' }}</span>
                        </a>
                    @endif
                    @if($canEdit && $equifaxReceived)
                        <button type="submit" form="cwEquifaxRemoveForm" class="btn btn-outline-danger btn-sm js-cw-equifax-clear cw-equifax-clear"
                                data-swal-title="Clear Equifax?"
                                data-swal-text="This removes the uploaded file and any entered bureau data.">
                            <i class="bi bi-x-lg"></i> Clear
                        </button>
                    @endif
                </div>
            </div>

            @if($equifaxReceived && ($hasEquifaxData || $hasEquifaxFile) && ! $canEdit)
                <div class="cw-equifax-readonly">
                    @if(filled($equifax['score'] ?? null))
                        <div><span class="cw-equifax-ro-label">Score / Ref</span> {{ $equifax['score'] }}</div>
                    @endif
                    @if(filled($equifax['notes'] ?? null))
                        <div class="cw-equifax-ro-notes">{{ $equifax['notes'] }}</div>
                    @endif
                    @if(! $hasEquifaxData && $hasEquifaxFile)
                        <div class="cw-equifax-hint">Report file attached — open View to see it.</div>
                    @endif
                </div>
            @endif

            @if($canEdit)
                <div class="cw-reco-col-grid cw-equifax-fields">
                    <div>
                        <label class="cw-label" for="cwEquifaxScore">Score / Reference</label>
                        <input type="text" id="cwEquifaxScore" name="score" form="cwEquifaxUploadForm"
                               class="cw-input cw-input-soft" maxlength="80"
                               value="{{ old('score', $equifax['score'] ?? '') }}"
                               placeholder="e.g. 742 / EQX-12345">
                    </div>
                    <div>
                        <label class="cw-label" for="cwEquifaxFile">{{ $hasEquifaxFile ? 'Replace file' : 'Upload Equifax PDF / image' }}</label>
                        <label class="cw-file-pick" for="cwEquifaxFile">
                            <input type="file" id="cwEquifaxFile" name="file" form="cwEquifaxUploadForm"
                                   class="cw-file-pick-input js-cw-equifax-file"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*">
                            <span class="cw-file-pick-btn"><i class="bi bi-upload"></i> Choose file</span>
                            <span class="cw-file-pick-name js-cw-equifax-file-name">{{ $hasEquifaxFile ? ($equifax['file_name'] ?? 'Current file kept') : 'No file chosen' }}</span>
                        </label>
                    </div>
                    <div class="cw-reco-col-span">
                        <label class="cw-label" for="cwEquifaxNotes">Bureau data / notes</label>
                        <textarea id="cwEquifaxNotes" name="notes" form="cwEquifaxUploadForm"
                                  class="cw-textarea cw-textarea-soft" rows="3" maxlength="2000"
                                  placeholder="Enter Equifax / bureau findings if you are not uploading a file">{{ old('notes', $equifax['notes'] ?? '') }}</textarea>
                    </div>
                    <div class="cw-reco-col-span cw-equifax-footer">
                        <p class="cw-equifax-hint">Upload a report, enter score / notes, or both — then save.</p>
                        <button type="submit" form="cwEquifaxUploadForm" class="btn-save">
                            <i class="bi bi-check2"></i> {{ $equifaxReceived ? 'Update Equifax' : 'Save Equifax' }}
                        </button>
                    </div>
                </div>
            @elseif(! $equifaxReceived)
                <div class="cw-equifax-empty">No Equifax report or bureau data yet.</div>
            @endif
        </div>
    </div>

    {{-- ========== COLLATERAL ========== --}}
    @php
        $collateralTypeOpts = [
            '' => 'Select type of collateral',
            'Residential Property' => 'Residential Property',
            'Commercial Property' => 'Commercial Property',
            'Plot / Land' => 'Plot / Land',
            'Mixed Use' => 'Mixed Use',
            'Other' => 'Other',
        ];
        $khataOpts = [
            '' => 'Select type of khata',
            'BBMP A Khatha' => 'BBMP A Khatha',
            'BBMP B Khatha' => 'BBMP B Khatha',
            'Gramathana' => 'Gramathana',
            'DC Conversion' => 'DC Conversion',
            'Other' => 'Other',
        ];
        $stageOpts = [
            '' => 'Select construction stage',
            'Not Started' => 'Not Started',
            'Foundation' => 'Foundation',
            'Under Construction' => 'Under Construction',
            'Completed' => 'Completed',
            'Done (Ready to Occupy)' => 'Done (Ready to Occupy)',
        ];
        $colType = $col['type'] ?? '';
        $colKhata = $col['khata'] ?? '';
        $colStage = $col['stage'] ?? '';
        if ($colType !== '' && !array_key_exists($colType, $collateralTypeOpts)) {
            $collateralTypeOpts[$colType] = $colType;
        }
        if ($colKhata !== '' && !array_key_exists($colKhata, $khataOpts)) {
            $khataOpts[$colKhata] = $colKhata;
        }
        if ($colStage !== '' && !array_key_exists($colStage, $stageOpts)) {
            $stageOpts[$colStage] = $colStage;
        }
    @endphp
    <div class="cw-extra-section" id="cwCollateral">
        <div class="cw-card-tw cw-card-soft">
            <div class="cw-card-head cw-card-head-soft">
                <span class="cw-ico cw-ico-green"><i class="bi bi-shield-check"></i></span>
                <h6 class="cw-card-title">Collateral Details</h6>
            </div>
            <div class="cw-reco-col-grid cw-collateral-fields">
                <div>
                    <label class="cw-label">Type of Collateral</label>
                    <select class="cw-select cw-select-soft" name="collateral[type]" {{ $dis }}>
                        @foreach($collateralTypeOpts as $val => $lab)
                            <option value="{{ $val }}" @selected($colType === $val)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="cw-label">Type of Khata</label>
                    <select class="cw-select cw-select-soft" name="collateral[khata]" {{ $dis }}>
                        @foreach($khataOpts as $val => $lab)
                            <option value="{{ $val }}" @selected($colKhata === $val)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cw-reco-col-span">
                    <label class="cw-label">Property Address</label>
                    <input class="cw-input cw-input-soft" name="collateral[address]"
                           value="{{ $col['address'] ?? ($prop['address'] ?? '') }}"
                           placeholder="Enter property address" {{ $ro }}>
                </div>
                <div>
                    <label class="cw-label">LA/BU Area</label>
                    <input class="cw-input cw-input-soft" name="collateral[area]" value="{{ $col['area'] ?? '' }}"
                           placeholder="Enter LA/BU area" {{ $ro }}>
                </div>
                <div>
                    <label class="cw-label">Construction Stage</label>
                    <select class="cw-select cw-select-soft" name="collateral[stage]" {{ $dis }}>
                        @foreach($stageOpts as $val => $lab)
                            <option value="{{ $val }}" @selected($colStage === $val)>{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cw-reco-col-span">
                    <label class="cw-label">Notes</label>
                    <textarea class="cw-textarea cw-textarea-soft" name="collateral[notes]" rows="3"
                              maxlength="2000" placeholder="Enter collateral notes"
                              {{ $ro }}>{{ $col['notes'] ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
