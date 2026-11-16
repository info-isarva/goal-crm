            <div class="sec-card">
                <div class="sec-head">
                    <div class="sec-head-left">
                        <span class="sec-ico"><i class="bi bi-person-vcard"></i></span>
                        <div>
                            <h6>Customer Details</h6>
                            <div class="sec-sub">Applicant · Co-Applicant · Property · References</div>
                        </div>
                    </div>
                </div>

                <div class="ws-section-title">Identity &amp; contact</div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 ws-table align-middle">
                        <thead><tr><th>Details</th><th>Applicant</th><th>Co-Applicant</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Name <span class="text-danger">*</span></td>
                                <td><input class="form-control form-control-sm" name="customer_details[applicant][name]" value="{{ $app['name'] ?? $case->customer_name }}" {{ $ro }} required></td>
                                <td><input class="form-control form-control-sm" name="customer_details[co_applicant][name]" value="{{ $co['name'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Father Name</td>
                                <td><input class="form-control form-control-sm" name="customer_details[applicant][father_name]" value="{{ $app['father_name'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[co_applicant][father_name]" value="{{ $co['father_name'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Mother Name</td>
                                <td><input class="form-control form-control-sm" name="customer_details[applicant][mother_name]" value="{{ $app['mother_name'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[co_applicant][mother_name]" value="{{ $co['mother_name'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Mobile Number</td>
                                <td><input class="form-control form-control-sm js-mobile" name="customer_details[applicant][mobile]" value="{{ $app['mobile'] ?? $case->mobile }}" maxlength="10" inputmode="numeric" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm js-mobile" name="customer_details[co_applicant][mobile]" value="{{ $co['mobile'] ?? '' }}" maxlength="10" inputmode="numeric" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Age</td>
                                <td><input class="form-control form-control-sm" name="customer_details[applicant][age]" value="{{ $app['age'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[co_applicant][age]" value="{{ $co['age'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Marital Status</td>
                                <td>
                                    <select class="form-select form-select-sm" name="customer_details[applicant][marital_status]" {{ $dis }}>
                                        @foreach(['', 'Married', 'Single', 'Others'] as $opt)
                                            <option value="{{ $opt }}" @selected(($app['marital_status'] ?? '') === $opt)>{{ $opt ?: 'Select' }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="customer_details[co_applicant][marital_status]" {{ $dis }}>
                                        @foreach(['', 'Married', 'Single', 'Others'] as $opt)
                                            <option value="{{ $opt }}" @selected(($co['marital_status'] ?? '') === $opt)>{{ $opt ?: 'Select' }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Relationship with applicant</td>
                                <td><input class="form-control form-control-sm" name="customer_details[applicant][relationship]" value="{{ $app['relationship'] ?? 'Self' }}" readonly></td>
                                <td><input class="form-control form-control-sm" name="customer_details[co_applicant][relationship]" value="{{ $co['relationship'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Personal Mail ID</td>
                                <td><input type="email" class="form-control form-control-sm" name="customer_details[applicant][email]" value="{{ $app['email'] ?? ($cd['email'] ?? '') }}" {{ $ro }}></td>
                                <td><input type="email" class="form-control form-control-sm" name="customer_details[co_applicant][email]" value="{{ $co['email'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Official Mail ID</td>
                                <td><input type="email" class="form-control form-control-sm" name="customer_details[applicant][official_email]" value="{{ $app['official_email'] ?? '' }}" {{ $ro }}></td>
                                <td><input type="email" class="form-control form-control-sm" name="customer_details[co_applicant][official_email]" value="{{ $co['official_email'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ws-section-title">Residence</div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 ws-table align-middle">
                        <thead><tr><th>Details</th><th>Applicant</th><th>Co-Applicant</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Present Residence Address and Pincode</td>
                                <td colspan="2"><input class="form-control form-control-sm" name="customer_details[residence][present_address]" value="{{ $res['present_address'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>No. of Years at above Residence</td>
                                <td><input class="form-control form-control-sm" name="customer_details[residence][years_applicant]" value="{{ $res['years_applicant'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[residence][years_co]" value="{{ $res['years_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Permanent Address &amp; Pin Code</td>
                                <td colspan="2"><input class="form-control form-control-sm" name="customer_details[residence][permanent_address]" value="{{ $res['permanent_address'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ws-section-title">Employment</div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 ws-table align-middle">
                        <thead><tr><th>Details</th><th>Applicant</th><th>Co-Applicant</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Office address &amp; Pincode</td>
                                <td colspan="2"><input class="form-control form-control-sm" name="customer_details[employment][office_address]" value="{{ $emp['office_address'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Employment Type</td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][type_applicant]" value="{{ $emp['type_applicant'] ?? ($cd['employment_type'] ?? '') }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][type_co]" value="{{ $emp['type_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Total job experience</td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][total_exp_applicant]" value="{{ $emp['total_exp_applicant'] ?? ($cd['work_experience_years'] ?? '') }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][total_exp_co]" value="{{ $emp['total_exp_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Present company job experience</td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][present_exp_applicant]" value="{{ $emp['present_exp_applicant'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][present_exp_co]" value="{{ $emp['present_exp_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Designation</td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][designation_applicant]" value="{{ $emp['designation_applicant'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][designation_co]" value="{{ $emp['designation_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Industry Type</td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][industry_applicant]" value="{{ $emp['industry_applicant'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][industry_co]" value="{{ $emp['industry_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Employer / Business Name</td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][employer_applicant]" value="{{ $emp['employer_applicant'] ?? ($cd['employer_name'] ?? '') }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[employment][employer_co]" value="{{ $emp['employer_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Monthly / Net Income</td>
                                <td><input class="form-control form-control-sm js-decimal" data-decimals="2" name="customer_details[employment][income_applicant]" value="{{ $emp['income_applicant'] ?? ($cd['monthly_income'] ?? '') }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm js-decimal" data-decimals="2" name="customer_details[employment][income_co]" value="{{ $emp['income_co'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ws-section-title">Property</div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 ws-table align-middle">
                        <thead><tr><th>Details</th><th colspan="2">Value</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Property Address and Pin Code</td>
                                <td colspan="2"><input class="form-control form-control-sm" name="customer_details[property][address]" value="{{ $prop['address'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Property Market Value</td>
                                <td colspan="2"><input class="form-control form-control-sm" name="customer_details[property][market_value]" value="{{ $prop['market_value'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Property SR value</td>
                                <td colspan="2"><input class="form-control form-control-sm" name="customer_details[property][sr_value]" value="{{ $prop['sr_value'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="ws-section-title">References</div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 ws-table align-middle">
                        <thead><tr><th>Details</th><th>Reference 1</th><th>Reference 2</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Name</td>
                                <td><input class="form-control form-control-sm" name="customer_details[references][ref1_name]" value="{{ $refs['ref1_name'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[references][ref2_name]" value="{{ $refs['ref2_name'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Mobile Number</td>
                                <td><input class="form-control form-control-sm js-mobile" name="customer_details[references][ref1_mobile]" value="{{ $refs['ref1_mobile'] ?? '' }}" maxlength="10" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm js-mobile" name="customer_details[references][ref2_mobile]" value="{{ $refs['ref2_mobile'] ?? '' }}" maxlength="10" {{ $ro }}></td>
                            </tr>
                            <tr>
                                <td>Address</td>
                                <td><input class="form-control form-control-sm" name="customer_details[references][ref1_address]" value="{{ $refs['ref1_address'] ?? '' }}" {{ $ro }}></td>
                                <td><input class="form-control form-control-sm" name="customer_details[references][ref2_address]" value="{{ $refs['ref2_address'] ?? '' }}" {{ $ro }}></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="sec-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <input class="form-control" name="product" value="{{ old('product', $case->product) }}" {{ $ro }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Requested Amount (₹)</label>
                            <input type="text" inputmode="decimal" data-decimals="2" class="form-control js-decimal" name="loan_amount" value="{{ old('loan_amount', $case->loan_amount) }}" {{ $ro }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Preferred Contact Time</label>
                            <input class="form-control" name="customer_details[preferred_contact_time]" value="{{ $cd['preferred_contact_time'] ?? '' }}" {{ $ro }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Consent</label>
                            <input class="form-control" value="{{ is_array($consent) && !empty($consent['recorded']) ? ('Recorded · ' . ($consent['recorded_at'] ?? '')) : ($case->lead?->consent_recorded ? 'Recorded on lead' : 'Not recorded') }}" readonly>
                        </div>
                    </div>
                    {{-- Hidden sync fields for top-level identity --}}
                    <input type="hidden" name="customer_name" id="cw_customer_name" value="{{ old('customer_name', $case->customer_name) }}">
                    <input type="hidden" name="mobile" id="cw_mobile" value="{{ old('mobile', $case->mobile) }}">
                </div>
            </div>
