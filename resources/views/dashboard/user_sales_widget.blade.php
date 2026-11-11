@php
    $company = \App\Models\Company::first();
    $currency_symbol = $currency_symbol ?? ($company ? $company->currency_symbol : config('app.currency_symbol', '₹'));
    $showWidget = !in_array(auth()->user()->crm_role_type, [0, 1]); // Hide for superadmin (0) and admin (1)
@endphp
@if($showWidget)
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4 border-0 shadow-sm h-100" style="background: #fff; color: #222;">
            <div class="card-header border-0 bg-gradient py-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center rounded-top-4" style="background: linear-gradient(90deg, #4f8cff 0%, #38e8b0 100%); color: #fff;">
                <span class="fw-bold fs-5 mb-2 mb-md-0"><i class="bi bi-bar-chart-line me-2"></i>Your Sales Target ({{ $salesPeriodLabel ?? date('F Y') }})</span>
                <div class="mt-2 mt-md-0 text-end  w-md-auto">
                    <a href="{{ route('dashboard.user_sales_history') }}" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-list-ul me-1"></i> See All Months
                    </a>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="row text-center">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <div class="fs-6 text-muted">Sales Target</div>
                        <div class="display-7 fw-bold" style="color:#4f8cff; letter-spacing:1px;">{{ $currency_symbol }} {{ number_format($user_target ?? 0, 2) }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="fs-6 text-muted">Achieved Sales</div>
                        <div class="display-7 fw-bold" style="color:#38e8b0; letter-spacing:1px;">{{ $currency_symbol }} {{ number_format($sales->achieved_sales ?? 0, 2) }}</div>
                    </div>
                </div>
                @if(($user_target ?? 0) > 0)
                <div class="mt-4">
                    <div class="progress" style="height: 28px; background: #e9ecef; border-radius: 14px;">
                        <div class="progress-bar" role="progressbar" style="background: linear-gradient(90deg, #4f8cff 0%, #38e8b0 100%); width: {{ round(($sales->achieved_sales ?? 0) / ($user_target ?? 1) * 100, 2) }}%; border-radius: 14px;" aria-valuenow="{{ round(($sales->achieved_sales ?? 0) / ($user_target ?? 1) * 100, 2) }}" aria-valuemin="0" aria-valuemax="100">
                            <span class="fw-bold" style="color:#fff; text-shadow:0 1px 2px #222;">{{ round(($sales->achieved_sales ?? 0) / ($user_target ?? 1) * 100, 2) }}%</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card p-4 border-0 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="fw-bold"><i class="bi bi-envelope card-heading-icon"></i>&nbsp;Today's Task Reminder Emails</span>
            </div>
            
            <div >
                @if($todaysTaskReminders->isEmpty())
                    <div class="d-flex flex-column align-items-center justify-content-start">
                        <img src="https://cdn-icons-png.flaticon.com/512/747/747310.png" alt="No Reminders" style="width:90px;opacity:0.15;">
                        <div class="mt-2 text-muted">No Task Reminders for today.</div>
                    </div>
                @else
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-border align-middle mb-0" style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr class="text-muted small">
                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Task</th>
                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Due Date</th>
                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Reminder Time</th>
                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Owner</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todaysTaskReminders as $task)
                                    @foreach($task->reminders as $reminder)
                                        <tr>
                                            <td>{{ $task->name }}</td>
                                            <td>{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d-m-Y H:i') : 'N/A' }}</td>
                                            <td>{{ $reminder->remind_at ? \Carbon\Carbon::parse($reminder->remind_at)->format('d-m-Y H:i') : 'N/A' }}</td>
                                            <td>{{ $task->owner->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View (Card Layout) -->
                    <div class="d-md-none">
                        @foreach($todaysTaskReminders as $task)
                            @foreach($task->reminders as $reminder)
                            <div class="border rounded p-3 mb-3" style="background: #f9f9f9; width: 100%;">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <p class="text-muted small mb-1"><strong>Task</strong></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="mb-0 fw-500 small">{{ $task->name }}</p>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <p class="text-muted small mb-1"><strong>Due Date</strong></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="mb-0 fw-bold small">{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d-m-Y H:i') : 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <p class="text-muted small mb-1"><strong>Reminder Time</strong></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="mb-0 fw-500 small">{{ $reminder->remind_at ? \Carbon\Carbon::parse($reminder->remind_at)->format('d-m-Y H:i') : 'N/A' }}</p>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <p class="text-muted small mb-1"><strong>Owner</strong></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="mb-0 fw-500 small">{{ $task->owner->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
<style>
    .display-7 {
    font-size: 1.5rem;
}
</style>
