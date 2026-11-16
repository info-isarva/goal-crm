        {{-- ========== BM RECO ========== --}}
        <div class="cw-extra-section" id="cwBmReco">
            <div class="reco-cover">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="fw-bold"><i class="bi bi-lightbulb me-1 text-primary"></i> BM Recommendation — Goal Corporation</div>
                        <div class="small text-muted mt-1">Proposal brief for <strong>{{ strtoupper($case->customer_name) }}</strong></div>
                    </div>
                    <div class="text-end small text-muted">Saved with workspace · Export from Branch MIS if needed</div>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-amber"><i class="bi bi-people"></i></span>
                        <h6>Borrower Profile</h6>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 ws-table align-middle">
                        <thead><tr><th>Field</th><th>Applicant</th><th>Co-Applicant</th></tr></thead>
                        <tbody>
                            @foreach([
                                ['name', 'Name'],
                                ['age', 'Age'],
                                ['dob', 'DOB'],
                                ['relationship', 'Relationship'],
                                ['profession', 'Profession'],
                                ['gross_income', 'Gross Income'],
                                ['net_income', 'Net Income'],
                                ['pan', 'PAN NO'],
                            ] as [$key, $label])
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td><input class="form-control form-control-sm" name="bm_reco[profile][applicant][{{ $key }}]" value="{{ data_get($br, "profile.applicant.$key", $key === 'name' ? ($app['name'] ?? $case->customer_name) : '') }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="bm_reco[profile][co_applicant][{{ $key }}]" value="{{ data_get($br, "profile.co_applicant.$key", $key === 'name' ? ($co['name'] ?? '') : '') }}" {{ $ro }}></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-blue"><i class="bi bi-clipboard-check"></i></span>
                        <h6>Requirement &amp; Eligibility</h6>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Recommended Amount</label>
                            <input class="form-control js-decimal" data-decimals="2" name="bm_reco[amount]" value="{{ $br['amount'] ?? $br['recommended_amount'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Requirement</label>
                            <input class="form-control" name="bm_reco[requirement]" value="{{ $br['requirement'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Eligibility</label>
                            <input class="form-control" name="bm_reco[eligibility]" value="{{ $br['eligibility'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Suggested Bank</label>
                            <input class="form-control" name="bm_reco[suggested_bank]" value="{{ $br['suggested_bank'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-12">
                            <label class="form-label">App &amp; Co-App Background</label>
                            <textarea class="form-control" rows="3" name="bm_reco[background]" {{ $ro }}>{{ $br['background'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mitigants</label>
                            <textarea class="form-control" rows="4" name="bm_reco[mitigants]" {{ $ro }}>{{ $br['mitigants'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deviation</label>
                            <input class="form-control mb-2" name="bm_reco[deviation]" value="{{ $br['deviation'] ?? '' }}" {{ $ro }}>
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" rows="2" name="bm_reco[remarks]" {{ $ro }}>{{ $br['remarks'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recommended By</label>
                            <input class="form-control" name="bm_reco[by]" value="{{ $br['by'] ?? $br['recommended_by'] ?? ($branchManager?->name ?? '') }}" {{ $ro }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recommendation Date</label>
                            <input type="date" class="form-control" name="bm_reco[date]" value="{{ $br['date'] ?? '' }}" {{ $dis }}>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-teal"><i class="bi bi-telephone"></i></span>
                        <h6>Recommendation contacts</h6>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="row g-3">
                        @foreach([
                            ['sm_name', 'SM Name', $case->owner?->name],
                            ['asm_name', 'ASM Name', ''],
                            ['rsm_name', 'RSM Name', ''],
                            ['sm_mobile', 'SM Contact Number', $case->owner?->mobile],
                            ['asm_mobile', 'ASM Contact Number', ''],
                            ['rsm_mobile', 'RSM Contact Number', ''],
                            ['bm_name', 'BM Name', $branchManager?->name],
                            ['bm_mobile', 'BM Contact Number', $branchManager?->mobile],
                        ] as [$key, $label, $default])
                            <div class="col-md-4">
                                <label class="form-label">{{ $label }}</label>
                                <input class="form-control" name="bm_reco[contacts][{{ $key }}]" value="{{ data_get($br, "contacts.$key", $default) }}" {{ $ro }}>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== EXISTING LOANS ========== --}}
        <div class="cw-extra-section" id="cwLoans">
            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-blue"><i class="bi bi-bank2"></i></span>
                        <h6>Existing HL (BT source)</h6>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="row g-3">
                        @foreach([
                            ['loan_type', 'Loan Type'],
                            ['loan_amt', 'Loan Amt'],
                            ['roi', 'ROI %'],
                            ['tenor', 'Tenor'],
                            ['emi', 'EMI'],
                        ] as [$key, $label])
                            <div class="col">
                                <label class="form-label">{{ $label }}</label>
                                <input class="form-control" name="existing_loans[bt_source][{{ $key }}]" value="{{ $bt[$key] ?? '' }}" {{ $ro }}>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico"><i class="bi bi-table"></i></span>
                        <h6>Loan details of Applicant / Co-Applicant</h6>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Type of Loan</th><th>Bank Details</th><th>Sanction Amount</th><th>Outstanding</th>
                                <th>EMI</th><th>EMI Paid</th><th>EMI Pending</th><th>Bounces</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loanRows as $i => $row)
                                @php
                                    $row = is_array($row) ? $row : [];
                                @endphp
                                <tr>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][type]" value="{{ $row['type'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][bank]" value="{{ $row['bank'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][sanction]" value="{{ $row['sanction'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][outstanding]" value="{{ $row['outstanding'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][emi]" value="{{ $row['emi'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][paid]" value="{{ $row['paid'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][pending]" value="{{ $row['pending'] ?? '' }}" {{ $ro }}></td>
                                    <td><input class="form-control form-control-sm" name="existing_loans[rows][{{ $i }}][bounces]" value="{{ $row['bounces'] ?? '' }}" {{ $ro }}></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========== COLLATERAL ========== --}}
        <div class="cw-extra-section" id="cwCollateral">
            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico ico-green"><i class="bi bi-house"></i></span>
                        <h6>Collateral Details</h6>
                    </div>
                </div>
                <div class="sec-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type of Collateral</label>
                            <input class="form-control" name="collateral[type]" value="{{ $col['type'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type of Khata</label>
                            <input class="form-control" name="collateral[khata]" value="{{ $col['khata'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Property Address</label>
                            <input class="form-control" name="collateral[address]" value="{{ $col['address'] ?? ($prop['address'] ?? '') }}" {{ $ro }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">LA/BU Area</label>
                            <input class="form-control" name="collateral[area]" value="{{ $col['area'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Construction Stage</label>
                            <input class="form-control" name="collateral[stage]" value="{{ $col['stage'] ?? '' }}" {{ $ro }}>
                        </div>
                    </div>
                </div>
            </div>
        </div>


