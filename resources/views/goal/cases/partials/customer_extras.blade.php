<div class="cw-cust cw-reco">
    {{-- ========== BM RECO ========== --}}
    <div class="cw-extra-section" id="cwBmReco">
        <div class="cw-reco-banner">
            <div class="min-w-0">
                <div class="cw-reco-banner-title">
                    <i class="bi bi-lightbulb"></i>
                    BM Recommendation — Goal Corporation
                </div>
                <div class="cw-reco-banner-sub">
                    Proposal brief for <strong>{{ strtoupper($case->customer_name) }}</strong>
                </div>
            </div>
            <div class="cw-reco-banner-meta">Saved with workspace · Export from Branch MIS if needed</div>
        </div>

        <div class="cw-reco-top">
            {{-- Borrower Profile --}}
            <div class="cw-card-tw cw-reco-profile">
                <div class="cw-card-head">
                    <span class="cw-ico cw-ico-amber"><i class="bi bi-people"></i></span>
                    <h6 class="cw-card-title">Borrower Profile</h6>
                </div>
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
                                ['age', 'Age', 'text', 'Age'],
                                ['dob', 'DOB', 'date', 'dd-mm-yyyy'],
                                ['relationship', 'Relationship', 'text', 'Relationship'],
                                ['profession', 'Profession', 'text', 'Profession'],
                                ['gross_income', 'Gross Income', 'money', '0.00'],
                                ['net_income', 'Net Income', 'money', '0.00'],
                                ['pan', 'PAN NO', 'text', 'ABCDE1234F'],
                            ] as [$key, $label, $type, $ph])
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>
                                        @if($type === 'date')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                                                <input type="text" class="cw-input js-datepicker" name="bm_reco[profile][applicant][{{ $key }}]"
                                                       value="{{ data_get($br, "profile.applicant.$key") }}"
                                                       placeholder="{{ $ph }}" autocomplete="off" {{ $ro }}>
                                            </div>
                                        @elseif($type === 'money')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                                       name="bm_reco[profile][applicant][{{ $key }}]"
                                                       value="{{ data_get($br, "profile.applicant.$key") }}"
                                                       placeholder="{{ $ph }}" {{ $ro }}>
                                            </div>
                                        @else
                                            <input class="cw-input" name="bm_reco[profile][applicant][{{ $key }}]"
                                                   value="{{ data_get($br, "profile.applicant.$key", $key === 'name' ? ($app['name'] ?? $case->customer_name) : '') }}"
                                                   placeholder="{{ $ph }}" {{ $ro }}>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type === 'date')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico"><i class="bi bi-calendar3"></i></span>
                                                <input type="text" class="cw-input js-datepicker" name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                       value="{{ data_get($br, "profile.co_applicant.$key") }}"
                                                       placeholder="{{ $ph }}" autocomplete="off" {{ $ro }}>
                                            </div>
                                        @elseif($type === 'money')
                                            <div class="cw-input-group">
                                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                                       name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                       value="{{ data_get($br, "profile.co_applicant.$key") }}"
                                                       placeholder="{{ $ph }}" {{ $ro }}>
                                            </div>
                                        @else
                                            <input class="cw-input" name="bm_reco[profile][co_applicant][{{ $key }}]"
                                                   value="{{ data_get($br, "profile.co_applicant.$key", $key === 'name' ? ($co['name'] ?? '') : '') }}"
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
            <div class="cw-card-tw cw-reco-req">
                <div class="cw-card-head">
                    <span class="cw-ico cw-ico-blue"><i class="bi bi-clipboard-check"></i></span>
                    <h6 class="cw-card-title">Requirement &amp; Eligibility</h6>
                </div>
                <div class="cw-reco-req-body">
                    <div class="cw-reco-req-top">
                        <div>
                            <label class="cw-label">Recommended Amount</label>
                            <div class="cw-input-group">
                                <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal"
                                       name="bm_reco[amount]" value="{{ $br['amount'] ?? $br['recommended_amount'] ?? '' }}"
                                       placeholder="0.00" {{ $ro }}>
                            </div>
                        </div>
                        <div>
                            <label class="cw-label">Requirement</label>
                            <input class="cw-input" name="bm_reco[requirement]" value="{{ $br['requirement'] ?? '' }}" placeholder="Requirement" {{ $ro }}>
                        </div>
                        <div>
                            <label class="cw-label">Eligibility</label>
                            <input class="cw-input" name="bm_reco[eligibility]" value="{{ $br['eligibility'] ?? '' }}" placeholder="Eligibility" {{ $ro }}>
                        </div>
                        <div>
                            <label class="cw-label">Suggested Bank</label>
                            <input class="cw-input" name="bm_reco[suggested_bank]" value="{{ $br['suggested_bank'] ?? '' }}" placeholder="Bank name" {{ $ro }}>
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
                                       value="{{ $br['by'] ?? $br['recommended_by'] ?? ($branchManager?->name ?? '') }}"
                                       placeholder="Name" {{ $ro }}>
                            </div>
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
                        <label class="cw-label-inline">SM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][sm_name]" value="{{ data_get($br, 'contacts.sm_name', $case->owner?->name) }}" placeholder="Enter SM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">SM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][sm_mobile]" value="{{ data_get($br, 'contacts.sm_mobile', $case->owner?->mobile) }}" maxlength="10" inputmode="numeric" placeholder="Enter SM contact number" {{ $ro }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">BM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][bm_name]" value="{{ data_get($br, 'contacts.bm_name', $branchManager?->name) }}" placeholder="Enter BM name" {{ $ro }}>
                    </div>
                </div>
                <div class="cw-reco-contact-col">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">ASM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][asm_name]" value="{{ data_get($br, 'contacts.asm_name') }}" placeholder="Enter ASM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">ASM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][asm_mobile]" value="{{ data_get($br, 'contacts.asm_mobile') }}" maxlength="10" inputmode="numeric" placeholder="Enter ASM contact number" {{ $ro }}>
                        </div>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">BM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][bm_mobile]" value="{{ data_get($br, 'contacts.bm_mobile', $branchManager?->mobile) }}" maxlength="10" inputmode="numeric" placeholder="Enter BM contact number" {{ $ro }}>
                        </div>
                    </div>
                </div>
                <div class="cw-reco-contact-col">
                    <div class="cw-field-row">
                        <label class="cw-label-inline">RSM Name</label>
                        <input class="cw-input" name="bm_reco[contacts][rsm_name]" value="{{ data_get($br, 'contacts.rsm_name') }}" placeholder="Enter RSM name" {{ $ro }}>
                    </div>
                    <div class="cw-field-row">
                        <label class="cw-label-inline">RSM Contact Number</label>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico"><i class="bi bi-telephone"></i></span>
                            <input class="cw-input js-mobile" name="bm_reco[contacts][rsm_mobile]" value="{{ data_get($br, 'contacts.rsm_mobile') }}" maxlength="10" inputmode="numeric" placeholder="Enter RSM contact number" {{ $ro }}>
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

        <div class="cw-card-tw">
            <div class="cw-card-head cw-card-head-soft">
                <span class="cw-ico cw-ico-violet"><i class="bi bi-list-ul"></i></span>
                <h6 class="cw-card-title">Loan Details of Applicant / Co-Applicant</h6>
                @if($canEdit)
                    <button type="button" class="cw-add-row-btn ms-auto" id="cwAddLoanRow"><i class="bi bi-plus-lg"></i> Add Row</button>
                @endif
            </div>
            <div class="cw-bp-table-wrap">
                <table class="cw-bp-table cw-loan-table">
                    <thead>
                        <tr>
                            <th class="cw-loan-num">#</th>
                            <th>Applicant</th>
                            <th>Type of Loan</th>
                            <th>Bank Details</th>
                            <th>Sanction Amount</th>
                            <th>Outstanding</th>
                            <th>EMI</th>
                            <th>EMI Paid</th>
                            <th>EMI Pending</th>
                            <th>Bounces</th>
                            @if($canEdit)<th class="cw-loan-actions"></th>@endif
                        </tr>
                    </thead>
                    <tbody id="cwLoanRowsBody">
                        @foreach($loanRows as $i => $row)
                            @php
                                $row = is_array($row) ? $row : [];
                                $party = $row['party'] ?? 'applicant';
                                $rowType = $row['type'] ?? '';
                                $rowTypeOpts = $loanTypeOpts;
                                if ($rowType !== '' && !array_key_exists($rowType, $rowTypeOpts)) {
                                    $rowTypeOpts[$rowType] = $rowType;
                                }
                            @endphp
                            <tr data-loan-row>
                                <td class="cw-loan-num"><span class="cw-loan-idx">{{ $i + 1 }}</span></td>
                                <td>
                                    <select class="cw-party-chip {{ $party === 'co_applicant' ? 'is-co' : 'is-app' }}"
                                            name="existing_loans[rows][{{ $i }}][party]" {{ $dis }}
                                            onchange="this.classList.toggle('is-co', this.value==='co_applicant'); this.classList.toggle('is-app', this.value!=='co_applicant');">
                                        <option value="applicant" @selected($party === 'applicant' || $party === '')>Applicant</option>
                                        <option value="co_applicant" @selected($party === 'co_applicant')>Co-Applicant</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="cw-select" name="existing_loans[rows][{{ $i }}][type]" {{ $dis }}>
                                        @foreach($rowTypeOpts as $val => $lab)
                                            <option value="{{ $val }}" @selected($rowType === $val)>{{ $lab }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="cw-input" name="existing_loans[rows][{{ $i }}][bank]" value="{{ $row['bank'] ?? '' }}" placeholder="Bank name" {{ $ro }}></td>
                                <td>
                                    <div class="cw-input-group">
                                        <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                        <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][{{ $i }}][sanction]" value="{{ $row['sanction'] ?? '' }}" placeholder="0.00" {{ $ro }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="cw-input-group">
                                        <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                        <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][{{ $i }}][outstanding]" value="{{ $row['outstanding'] ?? '' }}" placeholder="0.00" {{ $ro }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="cw-input-group">
                                        <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                        <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][{{ $i }}][emi]" value="{{ $row['emi'] ?? '' }}" placeholder="0.00" {{ $ro }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="cw-input-group">
                                        <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                        <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][{{ $i }}][paid]" value="{{ $row['paid'] ?? '' }}" placeholder="0.00" {{ $ro }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="cw-input-group">
                                        <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                                        <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][{{ $i }}][pending]" value="{{ $row['pending'] ?? '' }}" placeholder="0.00" {{ $ro }}>
                                    </div>
                                </td>
                                <td><input class="cw-input" name="existing_loans[rows][{{ $i }}][bounces]" value="{{ $row['bounces'] ?? '' }}" placeholder="0" {{ $ro }}></td>
                                @if($canEdit)
                                    <td class="cw-loan-actions">
                                        <button type="button" class="cw-row-remove" data-remove-loan title="Remove row"><i class="bi bi-trash3"></i></button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <template id="cwLoanRowTpl">
                <tr data-loan-row>
                    <td class="cw-loan-num"><span class="cw-loan-idx">__n__</span></td>
                    <td>
                        <select class="cw-party-chip is-app" name="existing_loans[rows][__i__][party]"
                                onchange="this.classList.toggle('is-co', this.value==='co_applicant'); this.classList.toggle('is-app', this.value!=='co_applicant');">
                            <option value="applicant" selected>Applicant</option>
                            <option value="co_applicant">Co-Applicant</option>
                        </select>
                    </td>
                    <td>
                        <select class="cw-select" name="existing_loans[rows][__i__][type]">
                            @foreach($loanTypeOpts as $val => $lab)
                                <option value="{{ $val }}">{{ $lab }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input class="cw-input" name="existing_loans[rows][__i__][bank]" placeholder="Bank name"></td>
                    <td>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][__i__][sanction]" placeholder="0.00">
                        </div>
                    </td>
                    <td>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][__i__][outstanding]" placeholder="0.00">
                        </div>
                    </td>
                    <td>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][__i__][emi]" placeholder="0.00">
                        </div>
                    </td>
                    <td>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][__i__][paid]" placeholder="0.00">
                        </div>
                    </td>
                    <td>
                        <div class="cw-input-group">
                            <span class="cw-input-group-ico font-semibold text-blue-900">₹</span>
                            <input type="text" inputmode="decimal" data-decimals="2" class="cw-input js-decimal" name="existing_loans[rows][__i__][pending]" placeholder="0.00">
                        </div>
                    </td>
                    <td><input class="cw-input" name="existing_loans[rows][__i__][bounces]" placeholder="0"></td>
                    <td class="cw-loan-actions">
                        <button type="button" class="cw-row-remove" data-remove-loan title="Remove row"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            </template>
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
            </div>
        </div>
    </div>
</div>
