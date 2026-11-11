@extends('layouts.app')



@section('content')

<!-- Main Content Only: Sidebar is now in layout -->
@section('content')
            <!-- <div class="dashboard-header p-4 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h2 class="fw-bold mb-1">Abstergo Ltd.</h2>
                    <div class="text-muted small">4517 Ashwood Ave. Manchester, Kentucky 39495</div>
                </div>
                <div class="dashboard-hero bg-gradient p-4 mb-4 rounded-4 shadow-sm d-flex flex-column flex-md-row align-items-center justify-content-between" style="background:linear-gradient(90deg,#f9c74f 0,#f9844a 100%);">
                    <div class="d-flex align-items-center mb-3 mb-md-0">
                        <span class="avatar rounded-circle bg-white border d-inline-flex align-items-center justify-content-center me-3" style="width:64px;height:64px;font-size:2.5rem;">
                            <i class="bi bi-speedometer2 text-warning"></i>
                        </span>
                        <div>
                            <h2 class="fw-bold mb-1 text-dark">Welcome to your CRM Dashboard</h2>
                            <div class="text-dark small">Get a quick overview of your sales, tasks, and activities</div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('leads.create') }}" class="btn btn-warning fw-bold px-4 py-2 shadow-sm"><i class="bi bi-plus-circle me-2"></i> Add New Lead</a>
                    </div>
                </div>
                <div class="row g-4 mb-4">
                    @php
                        $cards = [
                            [
                                'title' => 'Leads',
                                'value' => $totalLeads,
                                'subtitle' => 'Total Leads',
                                'bg' => 'linear-gradient(135deg, #4facfe, #00f2fe)',
                                'icon' => 'bi-people-fill',
                                'link' => route('leads.index')
                            ],
                            [
                                'title' => 'Deals',
                                'value' => $totalDeals,
                                'subtitle' => 'Total Deals',
                                'bg' => 'linear-gradient(135deg, #f7971e, #ffd200)',
                                                                       <td style="width: 90px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ \App\Helpers\MoneyFormatter::format($deal->amount ?? 0) }}</td>
                                'link' => route('deals.index')
                            ],
                            [
                                'title' => 'Deals Created This Month',
                                'value' => $dealsCreatedThisMonth,
                                'subtitle' => isset($dealsCreatedChangePercent)
                                    ? ($dealsCreatedChangePercent > 0
                                        ? "+{$dealsCreatedChangePercent}% vs last month"
                                        : ($dealsCreatedChangePercent < 0
                                            ? "{$dealsCreatedChangePercent}% vs last month"
                                            : "No change vs last month"))
                                    : "No data",
                                'bg' => 'linear-gradient(135deg, #43e97b, #38f9d7)',
                                'icon' => 'bi-calendar-plus',
                                'link' => route('deals.index', ['created_this_month' => 1])
                            ],
                            [
                                'title' => 'Deals Closing This Month',
                                'value' => $dealsClosingThisMonth,
                                'subtitle' => isset($dealsClosingChangePercent)
                                    ? ($dealsClosingChangePercent > 0
                                        ? "+{$dealsClosingChangePercent}% vs last month"
                                        : ($dealsClosingChangePercent < 0
                                            ? "{$dealsClosingChangePercent}% vs last month"
                                            : "No change vs last month"))
                                    : "No data",
                                'bg' => 'linear-gradient(135deg, #f5576c, #f093fb)',
                                'icon' => 'bi-calendar-event',
                                'link' => route('deals.index', ['closing_this_month' => 1])
                            ]
                        ];
                    @endphp

                    @foreach($cards as $card)
                        <div class="col-md-6 col-lg-3">
                            <a href="{{ $card['link'] }}" class="text-decoration-none">
                                <div class="card border-0 shadow-sm rounded-4 text-center h-100 position-relative overflow-hidden hover-scale stat-card"
                                     style="background: {{ $card['bg'] }}; color: #fff;">
                                    <div class="stat-icon">
                                        <i class="bi {{ $card['icon'] }}"></i>
                                    </div>
                                    <h6 class="fw-semibold mb-1">{{ $card['title'] }}</h6>
                                    <div class="stat-value">{{ $card['value'] }}</div>
                                    <div class="stat-subtitle">{{ $card['subtitle'] }}</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <style>
                    .hover-scale {
                        transition: transform 0.25s ease, box-shadow 0.25s ease;
                    }
                    .hover-scale:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 12px 24px rgba(0,0,0,0.15);
                    }
                </style>

                <style>
                .dashboard-hero {
                    background: linear-gradient(90deg,#f9c74f 0,#f9844a 100%) !important;
                    color: #222;
                }
                .card-hover-link .card {
                    transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
                }
                .card-hover-link:hover .card {
                    box-shadow: 0 0 0 2px #f9c74f, 0 4px 20px rgba(0,0,0,0.10);
                    border-color: #f9c74f;
                    transform: translateY(-4px) scale(1.03);
                    z-index: 2;
                }
                .dashboard-hero .avatar {
                    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
                }
                </style>
                [...rest of dashboard-2.blade.php content...]
                    </a>
                </div>
            </div>
<style>
.card-hover-link:hover .card {
    box-shadow: 0 0 0 2px #0d6efd, 0 4px 20px rgba(0,0,0,0.08);
    border-color: #0d6efd;
    transition: box-shadow 0.2s, border-color 0.2s;
}
</style>
<div class="row mb-4">
        <div class="col-md-12">
            @php
                $pinnedNotes = \App\Models\Note::where('pinned', 1)
                    ->where('created_by', auth()->id())
                    ->orderByDesc('noted_at')
                    ->limit(5)
                    ->get();
            @endphp
            @if (!$pinnedNotes->isEmpty())
                <div class="alert alert-warning d-flex align-items-center mb-4"
                    style="background:#fffbe6; border:1px solid #ffe58f;">

                    <div class="flex-grow-1">
                        <strong><i class="bi bi-pin-angle-fill me-2 text-warning" style="font-size:1.3rem;"></i> Pinned
                            Notes:</strong>
                        <ul class="mb-0 ps-3" style="list-style: disc;">
                            @foreach ($pinnedNotes as $note)
                                <li>
                                    <span class="fw-semibold">{{ $note->content }}</span>
                                    @php
                                        $relatedName = null;
                                        $companyName = null;
                                        if ($note->related_type === 'lead') {
                                            $lead = \App\Models\Lead::find($note->related_id);
                                            if ($lead) {
                                                $relatedName = $lead->title;
                                                $companyName = optional($lead->organization)->name;
                                            }
                                        } elseif ($note->related_type === 'deal') {
                                            $deal = \App\Models\Deal::find($note->related_id);
                                            if ($deal) {
                                                $relatedName = $deal->title;
                                                $companyName = optional($deal->organization)->name;
                                            }
                                        }
                                    @endphp
                                    <span class="text-muted small">
                                        @if ($relatedName)
                                            | <span class="fw-bold">
                                                @if ($note->related_type === 'lead')
                                                    <a href="{{ route('leads.show', $note->related_id) }}" class="text-decoration-underline text-dark">{{ $relatedName }}</a>
                                                @elseif ($note->related_type === 'deal')
                                                    <a href="{{ route('deals.show', $note->related_id) }}" class="text-decoration-underline text-dark">{{ $relatedName }}</a>
                                                @else
                                                    {{ $relatedName }}
                                                @endif
                                            </span>
                                        @endif
                                        @if ($companyName)
                                            ({{ $companyName }})
                                        @endif
                                        {{-- ({{ $note->noted_at ? \Carbon\Carbon::parse($note->noted_at)->format('d M Y H:i') : $note->created_at->format('d M Y H:i') }})
                                        @if ($note->created_by)
                                            | {{ optional(\App\Models\User::find($note->created_by))->name }}
                                        @endif --}}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="row mb-4 g-4">
        <div class="col-md-6">
            <div class="card shadow-sm p-3 mb-4">
                <h6 class="fw-bold mb-3">Deals by Lead Source</h6>
                <canvas id="dealsByLeadSourceChart" height="180"></canvas>
            </div>
        </div>
         <!-- Analytics Graphs Section -->
                                <div class="container-fluid mt-4">
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <div class="card shadow-sm p-3">
                                                <h6 class="fw-bold mb-3">Leads Created (Last 6 Months)</h6>
                                                <canvas id="leadsChart" height="180"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <div class="card shadow-sm p-3">
                                                <h6 class="fw-bold mb-3">Deals Created (Last 6 Months)</h6>
                                                <canvas id="dealsChart" height="180"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
    </div>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card p-4 border-0 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fw-bold">My Open Tasks</span>
                                {{-- <span class="ms-auto"><i class="bi bi-arrow-clockwise" style="cursor:pointer;"></i></span>
                                <span class="dropdown ms-2">
                                    <i class="bi bi-three-dots-vertical" style="cursor:pointer;" data-bs-toggle="dropdown"></i>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">Refresh</a></li>
                                        <li><a class="dropdown-item" href="#">Settings</a></li>
                                    </ul>
                                </span> --}}
                            </div>
                            <div class="d-flex flex-column align-items-center justify-content-start" >
                                @if($tasks->isEmpty())
                                    <img src="https://cdn-icons-png.flaticon.com/512/747/747310.png" alt="No Tasks" style="width:90px;opacity:0.15;">
                                    <div class="mt-2 text-muted">No Tasks found.</div>
                                @else
                                    <div class="table-responsive w-100">
                                        <table class="table table-border align-middle mb-0" style="table-layout: fixed; width: 100%;">
                                            <thead>
                                                <tr class="text-muted small">
                                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Subject</th>
                                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Due Date</th>
                                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Status</th>
                                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Priority</th>
                                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Related To</th>
                                                    <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Contact Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tasks as $task)
                                                    @if(!$task->completed_at)
                                                    <tr>
                                                        {{-- <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $task->title ?? $task->name }}</td> --}}
                                                           <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                               <a href="{{ route('tasks.show', $task->id) }}" class="text-decoration-none text-primary">{{ $task->title ?? $task->name }}</a>
                                                           </td>
                                                        <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $task->due_at ? \Carbon\Carbon::parse($task->due_at)->format('d M Y') : '' }}</td>
                                                        <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $task->status }}</td>
                                                        <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ ucfirst($task->priority) }}</td>
                                                        <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            @if($task->related_type === 'lead')
                                                                <a href="{{ route('leads.show', $task->related_id) }}" class="text-decoration-underline">Lead</a>
                                                            @elseif($task->related_type === 'deal')
                                                                <a href="{{ route('deals.show', $task->related_id) }}" class="text-decoration-underline">Deal</a>
                                                            @else
                                                                {{ ucfirst($task->related_type) }}
                                                            @endif
                                                        </td>
                                                        <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            @php
                                                                $contact = null;
                                                                if($task->related_type === 'lead') {
                                                                    $lead = \App\Models\Lead::find($task->related_id);
                                                                    $contact = $lead && $lead->person ? $lead->person->first_name . ' ' . $lead->person->last_name : '';
                                                                } elseif($task->related_type === 'deal') {
                                                                    $deal = \App\Models\Deal::find($task->related_id);
                                                                    $contact = $deal && $deal->person ? $deal->person->first_name . ' ' . $deal->person->last_name : '';
                                                                }
                                                            @endphp
                                                            {{ $contact ?? '' }}
                                                        </td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if($tasks instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted mb-1">
                                Showing {{ $tasks->firstItem() ?? 0 }}
                                to {{ $tasks->lastItem() ?? 0 }}
                                of {{ $tasks->total() }} tasks
                            </div>
                            <div class="mt-2">{{ $tasks->links('vendor.pagination.arrows-only') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4 border-0 shadow-sm h-100">
                         <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold">My Meetings</span>
                            {{-- <span class="ms-auto"><i class="bi bi-arrow-clockwise" style="cursor:pointer;"></i></span>
                            <span class="dropdown ms-2">
                                <i class="bi bi-three-dots-vertical" style="cursor:pointer;" data-bs-toggle="dropdown"></i>
                                <ul class="dropdown-menu">
                                     <li><a class="dropdown-item" href="#">Refresh</a></li>
                                    <li><a class="dropdown-item" href="#">Settings</a></li>
                                </ul>
                            </span> --}}
                        </div>
                        <div class="d-flex flex-column align-items-center justify-content-start" >
                            @if($meetings instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                <div class="table-responsive w-100">
                                    <table class="table table-border align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Title</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">From</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">To</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Related To</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Contact Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($meetings as $meeting)
                                            <tr>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;"><a class="text-decoration-none text-primary" href="{{ route('meetings.show', $meeting->id) }}">{{ $meeting->name }}</a></td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('d M Y H:i') : '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('d M Y H:i') : '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->related_type ?? '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">@php
                                                                $contact = null;
                                                                if($task->related_type === 'lead') {
                                                                    $lead = \App\Models\Lead::find($task->related_id);
                                                                    $contact = $lead && $lead->person ? $lead->person->first_name . ' ' . $lead->person->last_name : '';
                                                                } elseif($task->related_type === 'deal') {
                                                                    $deal = \App\Models\Deal::find($task->related_id);
                                                                    $contact = $deal && $deal->person ? $deal->person->first_name . ' ' . $deal->person->last_name : '';
                                                                }
                                                            @endphp
                                                            {{ $contact ?? '' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No meetings found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    </table>
                                </div>
                                 <div class="d-flex justify-content-between align-items-center" style="gap:15rem;">
                                <div class="small text-muted mb-1">
                                    Showing {{ $meetings->firstItem() ?? 0 }}
                                    to {{ $meetings->lastItem() ?? 0 }}
                                    of {{ $meetings->total() }} meetings
                                </div>
                                <div class="mt-2">{{ $meetings->links('vendor.pagination.arrows-only') }}</div>
                            </div>

                            @else
                                <div class="table-responsive w-100">
                                    <table class="table table-border align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Title</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">From</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">To</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Related To</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Contact Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($meetings as $meeting)
                                            <tr>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->name }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->start_at ? \Carbon\Carbon::parse($meeting->start_at)->format('d M Y H:i') : '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->finish_at ? \Carbon\Carbon::parse($meeting->finish_at)->format('d M Y H:i') : '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $meeting->related_type ?? '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">@php
                                                                $contact = null;
                                                                if($task->related_type === 'lead') {
                                                                    $lead = \App\Models\Lead::find($task->related_id);
                                                                    $contact = $lead && $lead->person ? $lead->person->first_name . ' ' . $lead->person->last_name : '';
                                                                } elseif($task->related_type === 'deal') {
                                                                    $deal = \App\Models\Deal::find($task->related_id);
                                                                    $contact = $deal && $deal->person ? $deal->person->first_name . ' ' . $deal->person->last_name : '';
                                                                }
                                                            @endphp
                                                            {{ $contact ?? '' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No meetings found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4 border-0 shadow-sm h-100 d-flex flex-column justify-content-between">

                         <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold">My Deals Closing This Month</span>
                            {{-- <span class="ms-auto"><i class="bi bi-arrow-clockwise" style="cursor:pointer;"></i></span>
                            <span class="dropdown ms-2">
                                <i class="bi bi-three-dots-vertical" style="cursor:pointer;" data-bs-toggle="dropdown"></i>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item @if(request('deals_closing_filter') === 'today') active @endif" href="{{ request()->fullUrlWithQuery(['deals_closing_filter' => 'today']) }}">Today Closing</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item @if(request('deals_closing_filter') === 'week') active @endif" href="{{ request()->fullUrlWithQuery(['deals_closing_filter' => 'week']) }}">This Week</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item @if(!request()->has('deals_closing_filter') || request('deals_closing_filter') === 'month') active @endif" href="{{ request()->fullUrlWithQuery(['deals_closing_filter' => 'month']) }}">This Month</a>
                                    </li>
                                </ul>
                            </span> --}}
                        </div>
                        <div class="d-flex flex-column align-items-center justify-content-start" >
                            @if($dealsClosingThisMonthList instanceof \Illuminate\Pagination\LengthAwarePaginator)

                                <div class="table-responsive w-100">
                                    <table class="table table-border align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Deal Name</th>
                                            <th style="width: 90px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Amount</th>
                                            <th style="width: 110px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Stage</th>
                                            <th style="width: 120px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Closing Date</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Company</th>
                                            <th style="width: 130px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Contact Name</th>
                                            <th style="width: 120px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Deal Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($dealsClosingThisMonthList as $deal)
                                            <tr>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;"><a href="{{ route('deals.show', $deal->id) }}" class="text-decoration-none">{{ $deal->title ?? $deal->name }}</a></td>
                                                <td style="width: 90px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ \App\Helpers\MoneyFormatter::format($deal->amount ?? 0) }}</td>
                                                <td style="width: 110px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $deal->stage_name ?? '' }}</td>
                                                <td style="width: 120px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d M Y') : '' }}</td>
                                                <td style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $deal->organization->name ?? '' }}</td>
                                                <td style="width: 130px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $deal->person->first_name ?? '' }} {{ $deal->person->last_name ?? '' }}</td>
                                                <td style="width: 120px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $deal->owner->name ?? '' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No deals closing this month found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-start d-flex align-items-center  justify-content-between" style="gap: 15.5rem;">
                                    <div class="small text-muted mb-1">
                                        Showing {{ $dealsClosingThisMonthList->firstItem() ?? 0 }}
                                        to {{ $dealsClosingThisMonthList->lastItem() ?? 0 }}
                                        of {{ $dealsClosingThisMonthList->total() }} deals
                                    </div>
                                    <div>
                                    {{ $dealsClosingThisMonthList->appends(request()->only('deals_closing_filter'))->links('vendor.pagination.arrows-only') }}

                                    </div>
                                </div>
                            @else
                                <div class="table-responsive w-100">
                                    <table class="table table-border align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                             <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Deal Name</th>
                                            <th style="width: 90px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Amount</th>
                                            <th style="width: 110px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Stage</th>
                                            <th style="width: 120px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Closing Date</th>
                                            <th style="width: 140px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Company</th>
                                            <th style="width: 130px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Contact Name</th>
                                            <th style="width: 120px; white-space:nowrap; overflow: hidden; text-overflow: ellipsis;">Deal Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- @forelse($dealsClosingThisMonthList as $deal)
                                            <tr>
                                                <td>{{ $deal->title ?? $deal->name }}</td>
                                                <td>{{ $deal->amount }}</td>
                                                <td>{{ $deal->stage_name ?? '' }}</td>
                                                <td>{{ $deal->close_date ? \Carbon\Carbon::parse($deal->close_date)->format('d M Y') : '' }}</td>
                                                <td>{{ $deal->organization->name ?? '' }}</td>
                                                <td>{{ $deal->person->first_name ?? '' }} {{ $deal->person->last_name ?? '' }}</td>
                                                <td>{{ $deal->owner->name ?? '' }}</td>
                                            </tr>
                                        @empty --}}
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No deals closing this month found.</td>
                                            </tr>
                                        {{-- @endforelse --}}
                                    </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-4 border-0 shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold">Today's Leads</span>
                            {{-- <span class="ms-auto"><i class="bi bi-arrow-clockwise" style="cursor:pointer;"></i></span>
                            <span class="dropdown ms-2">
                                <i class="bi bi-three-dots-vertical" style="cursor:pointer;" data-bs-toggle="dropdown"></i>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Refresh</a></li>
                                    <li><a class="dropdown-item" href="#">Settings</a></li>
                                </ul>
                            </span> --}}
                        </div>
                        <div class="d-flex flex-column align-items-center justify-content-start" >
                            @if($leadsToday instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                <div class="table-responsive w-100">
                                    <table class="table table-border align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;"> Title</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Company</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Contact</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Email</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Phone</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Lead Source</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Lead Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leadsToday as $lead)
                                            <tr>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;"><a href="{{ route('leads.show', $lead->id) }}" class="text-decoration-none text-primary">{{ $lead->title ?? $lead->name }}</a></td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->organization->name ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->person->first_name ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->person->email ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->person->mobile ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->leadSource->name ?? $lead->lead_source ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->owner->name ?? '' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No leads found for today.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center" style="gap:15rem;">
                                <div class="small text-muted mb-1">
                                    Showing {{ $leadsToday->firstItem() ?? 0 }}
                                    to {{ $leadsToday->lastItem() ?? 0 }}
                                    of {{ $leadsToday->total() }} leads
                                </div>
                                <div class="mt-2">{{ $leadsToday->links('vendor.pagination.arrows-only') }}</div>
                            </div>

                            @else
                                <div class="table-responsive w-100">
                                    <table class="table table-border align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <tr class="text-muted small">
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;"> Title</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Company</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Contact</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Email</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Phone</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Lead Source</th>
                                            <th style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">Lead Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leadsToday as $lead)
                                            <tr>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;"><a href="{{ route('leads.show', $lead->id) }}" class="text-decoration-underline text-primary">{{ $lead->title ?? $lead->name }}</a></td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->organization->name ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->person->first_name ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->person->email ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->person->mobile ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->leadSource->name ?? $lead->lead_source ?? '' }}</td>
                                                <td style="white-space:nowrap;  overflow:hidden; text-overflow:ellipsis;">{{ $lead->owner->name ?? '' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No leads found for today.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
 <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/js/dashboard-analytics.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
            // Example: Leads Created Per Month (last 6 months)
            var leadsData = @json($leadsAnalytics);
            var dealsData = @json($dealsAnalytics);
            var dealsByLeadSource = @json($dealsByLeadSource ?? []);
            // Prepare labels for last 6 months
            var months = [];
            var now = new Date();
            for (let i = 5; i >= 0; i--) {
                let d = new Date(now.getFullYear(), now.getMonth() - i, 1);
                months.push(d.toLocaleString('default', { month: 'short', year: '2-digit' }));
            }
            // Fill data arrays
            var leadsCounts = [];
            var dealsCounts = [];
            for (let i = 5; i >= 0; i--) {
                let m = (now.getMonth() - i + 12) % 12 + 1;
                leadsCounts.push(leadsData[m] || 0);
                dealsCounts.push(dealsData[m] || 0);
            }
            // Leads Chart
            var ctxLeads = document.getElementById('leadsChart').getContext('2d');
            new Chart(ctxLeads, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Leads Created',
                        data: leadsCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
            // Deals Chart
            var ctxDeals = document.getElementById('dealsChart').getContext('2d');
            new Chart(ctxDeals, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Deals Created',
                        data: dealsCounts,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Deals by Lead Source Pie Chart
            var ctxPie = document.getElementById('dealsByLeadSourceChart').getContext('2d');
            var pieLabels = Object.keys(dealsByLeadSource);
            var pieData = Object.values(dealsByLeadSource);
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: [
                            '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
@endsection



