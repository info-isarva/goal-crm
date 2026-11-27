@extends('layouts.app')

@php
    $sourceLabel = $lead->source
        ? (\App\Models\LeadSource::resolveCanonicalName($lead->source) ?: $lead->source)
        : '—';
    $statusLower = strtolower((string) $lead->status);
    $statusTone = str_contains($statusLower, 'pending') ? 'amber'
        : (str_contains($statusLower, 'future') ? 'blue'
        : (str_contains($statusLower, 'closed') || str_contains($statusLower, 'lost') ? 'slate'
        : (str_contains($statusLower, 'assign') ? 'green' : 'blue')));
    $statusDisplay = match ($lead->status) {
        'Assigned' => 'Allocated',
        'Pending BM Allocation' => 'Pending BM Allocation',
        default => $lead->status,
    };
    $activeTab = session('ld_tab', request('tab', 'overview'));
    $ownerInitial = strtoupper(substr($lead->assignee->name ?? $lead->customer_name ?? '?', 0, 1));
    $mobileTel = preg_replace('/\D+/', '', (string) ($lead->mobile ?? ''));
    $fmtInr = fn ($n) => $n !== null && $n !== '' ? '₹ ' . number_format((float) $n, 0) : '—';
    $fmtMobile = function (?string $m) {
        $m = trim((string) $m);
        if ($m === '') return null;
        if (str_starts_with($m, '+')) return $m;
        $digits = preg_replace('/\D+/', '', $m);
        if (strlen($digits) === 10) return '+91 ' . substr($digits, 0, 5) . ' ' . substr($digits, 5);
        return $m;
    };
    $customerType = $lead->channel_partner ? 'Channel' : 'Existing';
    $tabIcons = [
        'overview' => 'bi-layers',
        'customer' => 'bi-person',
        'communication' => 'bi-chat-dots',
        'activities' => 'bi-activity',
        'documents' => 'bi-file-earmark-text',
        'case' => 'bi-briefcase',
    ];
@endphp

@push('styles')
<style>
.ld-page{
    --ld-ink:#14213D;--ld-muted:#71819D;--ld-line:#E3EAF5;--ld-blue:#245BEB;--ld-blue2:#318BFA;--ld-purple:#7542F5;
    --ld-success:#16B978;--ld-danger:#EF4444;--ld-warn:#F59E0B;--ld-bg:#EAF2FF;
    --ld-grad:linear-gradient(90deg,#7542F5 0%,#245BEB 100%);
    color:var(--ld-ink);background:var(--ld-bg);
    min-height:100%;margin:-0.25rem -0.5rem;padding:1.1rem 1.25rem 2rem !important;
}
.ld-page .ld-top{
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem 1.25rem;
    margin-bottom:1.15rem;
}
.ld-page .ld-top-left{display:flex;align-items:flex-start;gap:14px;min-width:0}
.ld-page .ld-back{
    width:42px;height:42px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;color:#475569;
    display:inline-flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;
    box-shadow:0 2px 8px rgba(15,23,42,.05);margin-top:2px;
}
.ld-page .ld-back:hover{background:var(--theme-50);color:var(--theme-primary-hover);border-color:var(--theme-200)}
.ld-page .ld-title{margin:0;font-size:1.55rem;font-weight:800;color:var(--ld-ink);letter-spacing:-.025em;line-height:1.15}
.ld-page .ld-sub{margin:.28rem 0 0;font-size:.86rem;color:var(--ld-muted);font-weight:500}
.ld-page .ld-crumbs{margin-top:.4rem;font-size:.78rem;color:#94a3b8;display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.ld-page .ld-crumbs a{color:#64748b;text-decoration:none;font-weight:600}
.ld-page .ld-crumbs a:hover{color:var(--ld-blue)}
.ld-page .ld-crumbs .sep{opacity:.45}
.ld-page .ld-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-left:auto}
.ld-page .ld-btn{
    height:40px;border-radius:12px;padding:0 16px;font-size:.86rem;font-weight:700;
    display:inline-flex;align-items:center;gap:8px;border:1px solid #e2e8f0;background:#fff;color:#1e293b;
    text-decoration:none;cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.ld-page .ld-btn i{color:var(--ld-blue);font-size:.95rem}
.ld-page .ld-btn:hover{background:#f8fafc;color:var(--ld-ink);border-color:#cbd5e1}
.ld-page .ld-btn-primary{
    border:0;color:#fff;background:var(--ld-grad);
    box-shadow:0 10px 20px rgba(117,66,245,.28);
}
.ld-page .ld-btn-primary i{color:#fff}
.ld-page .ld-btn-primary:hover,
.ld-page .ld-btn-primary:focus,
.ld-page .ld-btn.ld-btn-primary:hover{
    background:var(--ld-grad);color:#fff;border-color:transparent;filter:brightness(1.06);
}
.ld-page .ld-btn-primary:hover i,
.ld-page .ld-btn.ld-btn-primary:hover i{color:#fff}
.ld-page .ld-btn:disabled,.ld-page .ld-btn.disabled{opacity:.5;pointer-events:none}
.ld-page .ld-btn.dropdown-toggle::after{margin-left:.15rem}

/* Progress + tabs span center+right (full width of that band). Follow-up starts below. */
.ld-page .ld-grid{
    display:grid;
    grid-template-columns:290px minmax(0,1fr) 330px;
    grid-template-areas:
        "left progress progress"
        "left tabs tabs"
        "left main right";
    gap:16px;
    align-items:start;
}
.ld-page .ld-col-left{grid-area:left;min-width:0}
.ld-page .ld-progress-wrap{grid-area:progress;min-width:0}
.ld-page .ld-tabs-wrap{grid-area:tabs;min-width:0}
.ld-page .ld-col-main{grid-area:main;min-width:0}
.ld-page .ld-col-right{grid-area:right;min-width:0}
@media(max-width:1400px){.ld-page .ld-grid{grid-template-columns:270px minmax(0,1fr) 300px}}
@media(max-width:1200px){
    .ld-page .ld-grid{
        grid-template-columns:minmax(0,1fr) minmax(0,1fr);
        grid-template-areas:
            "progress progress"
            "tabs tabs"
            "left right"
            "main main";
    }
}
@media(max-width:900px){
    .ld-page{
        margin:0 !important;padding:.7rem .7rem 1.25rem !important;
        overflow-x:hidden;
    }
    .ld-page .ld-grid{
        grid-template-columns:minmax(0,1fr);
        grid-template-areas:
            "progress"
            "tabs"
            "left"
            "main"
            "right";
        gap:10px;
        width:100%;
    }
    .ld-page .ld-top{flex-direction:column;align-items:stretch;gap:.65rem;margin-bottom:.7rem}
    .ld-page .ld-top-left{width:100%;gap:10px}
    .ld-page .ld-back{width:38px;height:38px;margin-top:0}
    .ld-page .ld-title{font-size:1.15rem}
    .ld-page .ld-sub{display:none}
    .ld-page .ld-crumbs{font-size:.7rem;margin-top:4px;gap:4px}
    .ld-page .ld-actions{
        margin-left:0;width:100%;
        display:grid;grid-template-columns:1fr 1fr;gap:8px;
    }
    .ld-page .ld-actions .ld-btn,
    .ld-page .ld-actions form,
    .ld-page .ld-actions .dropdown{width:100%;min-width:0}
    .ld-page .ld-actions .ld-btn{
        justify-content:center;padding:0 8px;font-size:.78rem;height:40px;border-radius:10px;gap:6px;
    }
    .ld-page .ld-actions .dropdown .ld-btn{width:100%}
    .ld-page .ld-actions .ld-btn-primary,
    .ld-page .ld-actions .dropdown{grid-column:1 / -1}

    /* Progress: swipeable compact chips */
    .ld-page .ld-progress-wrap,
    .ld-page .ld-tabs-wrap{width:100%;max-width:100%;min-width:0}
    .ld-page .ld-progress{padding:10px 8px 10px;gap:0}
    .ld-page .ld-step{min-width:72px;flex:0 0 auto}
    .ld-page .ld-step-ico{width:30px;height:30px;font-size:.8rem}
    .ld-page .ld-step:not(:last-child)::after{top:15px;left:calc(50% + 16px);right:calc(-50% + 16px);height:2px}
    .ld-page .ld-step-label{margin-top:5px;font-size:.6rem;line-height:1.2}
    .ld-page .ld-step-at{font-size:.55rem;margin-top:2px}

    /* Tabs */
    .ld-page .ld-tabs{gap:6px}
    .ld-page .ld-tab{padding:.55rem .9rem;font-size:.76rem;gap:6px;box-shadow:0 2px 8px rgba(20,33,61,.05)}
    .ld-page .ld-tab i{font-size:.95rem}

    /* Cards denser */
    .ld-page .ld-card{border-radius:14px}
    .ld-page .ld-card + .ld-card{margin-top:10px}
    .ld-page .ld-card-pad{padding:12px}
    .ld-page .ld-card-head{padding:0 0 8px;margin:0 0 8px}
    .ld-page .ld-card-head h6{font-size:.88rem}
    .ld-page .ld-card-head .ld-head-ico{width:26px;height:26px;margin-right:8px;font-size:.85rem}

    /* Lead profile meta: keep label | value, tighter */
    .ld-page .ld-profile-hero{padding:1rem .95rem .9rem}
    .ld-page .ld-ref{font-size:1.05rem;word-break:break-word}
    .ld-page .ld-status{margin-top:8px;padding:.26rem .65rem;font-size:.68rem}
    .ld-page .ld-meta-list li{
        grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);
        padding:9px 12px;min-height:0;column-gap:8px;align-items:center;
    }
    .ld-page .ld-meta-k{font-size:.72rem;gap:7px}
    .ld-page .ld-meta-k .ld-meta-ico{width:18px;height:18px;font-size:.9rem}
    .ld-page .ld-meta-v{
        justify-content:flex-end;text-align:right;font-size:.78rem;
        flex-wrap:wrap;gap:4px;min-width:0;
    }
    .ld-page .ld-pill{font-size:.65rem;padding:.18rem .5rem}

    /* Owner */
    .ld-page .ld-owner{gap:10px;flex-wrap:nowrap}
    .ld-page .ld-avatar{width:40px;height:40px;font-size:.95rem}
    .ld-page .ld-owner-name{font-size:.84rem}
    .ld-page .ld-owner-role{font-size:.7rem}
    .ld-page .ld-owner-acts{gap:6px}
    .ld-page .ld-ico{width:36px;height:36px}

    /* Summary KV denser */
    .ld-page .ld-summary-table{border-radius:12px}
    .ld-page .ld-sum-cell{
        padding:9px 12px;min-height:0;gap:8px;
    }
    .ld-page .ld-sum-cell .ld-field-label{font-size:.72rem;flex-basis:40%}
    .ld-page .ld-sum-cell .ld-field-val{font-size:.8rem;gap:5px}

    /* Timeline denser */
    .ld-page .ld-timeline li{gap:10px;padding:9px 0}
    .ld-page .ld-timeline li:not(:last-child)::before{left:14px;top:34px}
    .ld-page .ld-timeline li:not(:last-child)::after{left:11px}
    .ld-page .ld-tl-ico{width:28px;height:28px;font-size:.78rem}
    .ld-page .ld-tl-main{flex-direction:column;align-items:flex-start;gap:2px}
    .ld-page .ld-tl-title{font-size:.8rem}
    .ld-page .ld-tl-meta{font-size:.72rem;margin-top:2px}
    .ld-page .ld-tl-at{font-size:.68rem;white-space:normal}

    /* Follow-up / QA / notes */
    .ld-page .ld-follow{padding:14px}
    .ld-page .ld-follow-body{min-height:0;margin-bottom:10px}
    .ld-page .ld-follow-date{font-size:1.15rem}
    .ld-page .ld-follow-time{font-size:.78rem;margin-top:.25rem}
    .ld-page .ld-follow-art{width:72px;height:72px;font-size:2.6rem;opacity:.9}
    .ld-page .ld-follow .ld-btn{height:38px;font-size:.8rem;padding:0 14px}
    .ld-page .ld-qa-grid{gap:8px}
    .ld-page .ld-qa{min-height:64px;padding:.55rem .4rem;font-size:.72rem;border-radius:12px;gap:4px}
    .ld-page .ld-qa span{font-size:1rem}
    .ld-page .ld-note{padding:11px;border-radius:12px;margin-bottom:8px}
    .ld-page .ld-note-body{font-size:.8rem}
    .ld-page .ld-note-foot{margin-top:8px}
}
@media(max-width:576px){
    .ld-page{padding:.65rem .65rem 1.1rem !important}
    .ld-page .ld-actions{grid-template-columns:1fr 1fr}
    .ld-page .ld-ref{font-size:.98rem}
    .ld-page .ld-meta-list li{padding:8px 10px}
    .ld-page .ld-sum-cell{padding:8px 10px}
    .ld-page .ld-qa{min-height:60px}
}

.ld-page .ld-card{
    background:#fff;border:1px solid var(--ld-line);border-radius:16px;
    box-shadow:0 4px 16px rgba(20,33,61,.04);overflow:hidden;
}
.ld-page .ld-card + .ld-card{margin-top:16px}
.ld-page .ld-card-pad{padding:16px}
.ld-page .ld-card-head{
    display:flex;align-items:center;justify-content:space-between;gap:8px;
    padding:0 0 12px;margin:0 0 14px;border-bottom:1px solid #F1F5F9;
}
.ld-page .ld-card-head h6{margin:0;font-size:.95rem;font-weight:800;color:var(--ld-ink);display:inline-flex;align-items:center}
.ld-page .ld-card-head .ld-head-ico{
    width:30px;height:30px;border-radius:999px;background:#EEF4FF;color:var(--ld-blue);
    display:inline-flex;align-items:center;justify-content:center;margin-right:10px;font-size:.95rem;flex-shrink:0;
}
.ld-page .ld-card-head .ld-head-ico.medal{
    background:rgba(237,233,254,.95);color:#7C3AED;
    box-shadow:0 0 0 6px rgba(167,139,250,.16),0 4px 12px rgba(124,58,237,.14);
}
.ld-page .ld-card-head .ld-head-ico.doc{
    background:rgba(237,233,254,.95);color:#7C3AED;
    box-shadow:0 0 0 6px rgba(167,139,250,.16),0 4px 12px rgba(124,58,237,.14);
}
.ld-page .ld-card-head .ld-head-ico.bolt{
    background:rgba(237,233,254,.95);color:#7C3AED;border-radius:999px;
    box-shadow:0 0 0 6px rgba(167,139,250,.16),0 4px 12px rgba(124,58,237,.16);
}
.ld-page .ld-link{font-size:.8rem;font-weight:700;color:var(--ld-blue);text-decoration:none;border:0;background:none;cursor:pointer;padding:0;display:inline-flex;align-items:center;gap:4px}
.ld-page .ld-link:hover{text-decoration:underline}

/* Left profile card */
.ld-page .ld-profile-hero{
    position:relative;padding:1.35rem 1.25rem 1.2rem;overflow:hidden;
    background:
        radial-gradient(140px 90px at 92% 8%, rgba(167,139,250,.75), transparent 70%),
        radial-gradient(160px 100px at 70% 115%, rgba(129,140,248,.55), transparent 72%),
        linear-gradient(145deg,#f3e8ff 0%,#eef2ff 45%,#e0e7ff 100%);
}
.ld-page .ld-profile-hero::before{
    content:"";position:absolute;left:-8%;right:-8%;bottom:-30%;height:110px;
    background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 90' preserveAspectRatio='none'%3E%3Cpath d='M0 52 C70 22 120 68 190 44 C260 20 320 62 400 34 L400 90 L0 90 Z' fill='%23c4b5fd' fill-opacity='0.55'/%3E%3Cpath d='M0 62 C90 36 150 74 220 52 C290 32 350 68 400 48 L400 90 L0 90 Z' fill='%238b5cf6' fill-opacity='0.28'/%3E%3C/svg%3E") no-repeat bottom/100% 100%;
    pointer-events:none;
}
.ld-page .ld-ref-label{position:relative;z-index:1;font-size:.72rem;font-weight:600;color:#64748b;letter-spacing:.02em}
.ld-page .ld-ref-row{position:relative;z-index:1;display:flex;align-items:center;gap:8px;margin-top:4px}
.ld-page .ld-ref{margin:0;font-size:1.2rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;line-height:1.2}
.ld-page .ld-copy{
    width:30px;height:30px;border-radius:8px;border:1.5px solid var(--theme-300);background:#fff;color:var(--theme-primary);
    display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;
}
.ld-page .ld-copy:hover{background:var(--theme-50);border-color:var(--theme-400)}
.ld-page .ld-status{
    position:relative;z-index:1;
    display:inline-flex;align-items:center;margin-top:12px;padding:.32rem .8rem;border-radius:999px;
    font-size:.72rem;font-weight:800;
}
.ld-page .ld-status.green{background:#D1FAE5;color:#047857}
.ld-page .ld-status.blue{background:var(--theme-100);color:var(--theme-primary-hover)}
.ld-page .ld-status.amber{background:#FFEDD5;color:#C2410C}
.ld-page .ld-status.slate{background:#E2E8F0;color:#475569}

.ld-page .ld-meta-list{list-style:none;margin:0;padding:.05rem 0}
.ld-page .ld-meta-list li{
    display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);align-items:center;column-gap:10px;
    padding:14px 16px;border-bottom:1px solid #F1F5F9;min-height:52px;
}
.ld-page .ld-meta-list li:last-child{border-bottom:0}
.ld-page .ld-meta-k{
    display:inline-flex;align-items:center;gap:10px;min-width:0;
    font-size:.8rem;color:var(--ld-muted);font-weight:600;
}
.ld-page .ld-meta-k .ld-meta-ico{
    width:20px;height:20px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    color:var(--ld-blue2);font-size:1.02rem;line-height:1;
}
.ld-page .ld-meta-v{
    font-size:.84rem;font-weight:700;color:var(--ld-ink);text-align:right;
    justify-self:end;width:100%;
    display:inline-flex;align-items:center;justify-content:flex-end;gap:6px;
}
.ld-page .ld-priority-high{color:var(--ld-danger);font-weight:800}
.ld-page .ld-pill{display:inline-flex;align-items:center;gap:5px;padding:.22rem .65rem;border-radius:999px;font-size:.7rem;font-weight:800;line-height:1.2}
.ld-page .ld-pill-green{background:#D1FAE5;color:#047857}
.ld-page .ld-pill-red{background:#FEE2E2;color:var(--ld-danger)}
.ld-page .ld-pill-amber{background:#FFEDD5;color:#C2410C}
.ld-page .ld-pill-blue{background:var(--theme-100);color:var(--theme-primary-hover)}
.ld-page .ld-pill-purple{background:#EDE9FE;color:var(--ld-purple)}
.ld-page .ld-pill-slate{background:#F1F5F9;color:#334155}
.ld-page .ld-pill-slate i{font-size:.78rem;color:#64748b}
.ld-page .ld-meta-v .ld-branch{color:var(--ld-ink);font-weight:700}

.ld-page .ld-owner-card .ld-card-head{padding:0 0 10px;margin:0 0 4px;border-bottom:0}
.ld-page .ld-owner-card .ld-card-head .ld-head-ico{
    width:auto;height:auto;border-radius:0;background:transparent;color:var(--ld-blue);
    margin-right:8px;font-size:1.1rem;
}
.ld-page .ld-owner{display:flex;align-items:center;gap:12px;padding:.1rem 0}
.ld-page .ld-avatar{
    width:46px;height:46px;border-radius:999px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,#A78BFA,#6366F1);color:#fff;font-weight:800;font-size:1.05rem;
    box-shadow:0 6px 14px rgba(99,102,241,.25);
}
.ld-page .ld-owner-info{min-width:0;flex:1}
.ld-page .ld-owner-name{margin:0;font-size:.88rem;font-weight:800;color:var(--ld-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ld-page .ld-owner-role{margin:2px 0 0;font-size:.74rem;color:var(--ld-muted);font-weight:600}
.ld-page .ld-owner-acts{margin-left:auto;display:inline-flex;gap:8px;flex-shrink:0}
.ld-page .ld-ico{
    width:34px;height:34px;border-radius:999px;border:1.5px solid var(--theme-200);background:#fff;color:var(--ld-blue);
    display:inline-flex;align-items:center;justify-content:center;text-decoration:none;
}
.ld-page .ld-ico:hover{background:var(--theme-50);color:var(--theme-primary-hover);border-color:var(--theme-300)}
.ld-page .ld-ico.is-off{opacity:.35;pointer-events:none;border-color:#E2E8F0;color:#94A3B8}

/* Progress — center column only */
.ld-page .ld-progress{
    display:flex;align-items:flex-start;justify-content:space-between;gap:0;
    padding:18px 14px 14px;overflow-x:auto;scrollbar-width:none;
}
.ld-page .ld-progress::-webkit-scrollbar{display:none}
.ld-page .ld-step{flex:1 1 0;min-width:78px;text-align:center;position:relative}
.ld-page .ld-step:not(:last-child)::after{
    content:"";position:absolute;top:18px;left:calc(50% + 18px);right:calc(-50% + 18px);
    height:3px;background:#E3EAF5;border-radius:99px;z-index:0;
}
.ld-page .ld-step.done:not(:last-child)::after{background:var(--ld-success)}
.ld-page .ld-step-ico{
    position:relative;z-index:1;width:36px;height:36px;margin:0 auto;border-radius:999px;
    display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;
    background:#F8FAFC;color:#CBD5E1;border:2px solid #E3EAF5;
}
.ld-page .ld-step.done .ld-step-ico{background:var(--ld-success);color:#fff;border-color:#059669;box-shadow:0 4px 10px rgba(22,185,120,.25)}
.ld-page .ld-step.current .ld-step-ico{background:var(--ld-blue);color:#fff;border-color:var(--theme-primary-hover);box-shadow:0 0 0 4px rgba(36,91,235,.16)}
.ld-page .ld-step-label{display:block;margin-top:8px;font-size:.7rem;font-weight:700;color:#94A3B8;line-height:1.25}
.ld-page .ld-step.done .ld-step-label{color:var(--ld-success)}
.ld-page .ld-step.current .ld-step-label{color:var(--ld-ink)}
.ld-page .ld-step-at{display:block;margin-top:3px;font-size:.62rem;color:var(--ld-muted);font-weight:600;line-height:1.2}
.ld-page .ld-step.done .ld-step-at{color:var(--ld-success)}

/* Tabs — separate white pills; scrollable when overflow, scrollbar hidden */
.ld-page .ld-tabs-wrap{
    min-width:0;max-width:100%;
    overflow-x:auto;overflow-y:hidden;
    -webkit-overflow-scrolling:touch;
    overscroll-behavior-x:contain;touch-action:pan-x;
    scrollbar-width:none;-ms-overflow-style:none;
}
.ld-page .ld-tabs-wrap::-webkit-scrollbar{display:none;width:0;height:0}
.ld-page .ld-tabs{
    display:inline-flex;flex-wrap:nowrap;align-items:center;gap:10px;
    padding:2px 2px 2px 0;margin:0;width:max-content;min-width:100%;
    box-sizing:border-box;
    background:transparent;border:0;border-radius:0;box-shadow:none;
    overflow:visible;
}
.ld-page .ld-tab{
    border:1px solid #E8EEF6;background:#fff;color:#1E293B;font-weight:700;font-size:.84rem;
    padding:.72rem 1.25rem;border-radius:999px;cursor:pointer;white-space:nowrap;
    display:inline-flex;align-items:center;gap:8px;flex:0 0 auto;
    box-shadow:0 4px 12px rgba(20,33,61,.05);
    transition:background .15s ease,color .15s ease,box-shadow .15s ease,border-color .15s ease;
}
.ld-page .ld-tab i{color:#334155;font-size:1.05rem;line-height:1}
.ld-page .ld-tab:hover{background:#F8FAFC;border-color:#D0D9E8;color:#0F172A}
.ld-page .ld-tab.active{
    border-color:transparent;
    background:linear-gradient(90deg,var(--theme-primary) 0%,var(--theme-primary) 55%,var(--theme-primary-hover) 100%);
    color:#fff;
    box-shadow:none;
}
.ld-page .ld-tab.active i{color:#fff}
.ld-page .ld-pane{display:none}
.ld-page .ld-pane.active{display:block}

/* Lead summary — 2-col: label left / value right, thin H+V separators */
.ld-page .ld-summary-table{
    display:grid;grid-template-columns:1fr 1fr;
    border:1px solid #E8EEF6;border-radius:14px;overflow:hidden;background:#fff;
}
.ld-page .ld-sum-cell{
    display:flex;align-items:center;justify-content:space-between;gap:14px;
    padding:15px 18px;min-height:54px;box-sizing:border-box;
    border-bottom:1px solid #EEF2F7;border-right:1px solid #EEF2F7;
}
.ld-page .ld-sum-cell:nth-child(2n){border-right:0}
.ld-page .ld-sum-cell:nth-last-child(-n+2){border-bottom:0}
.ld-page .ld-sum-cell .ld-field-label{
    margin:0;flex:0 1 48%;
    font-size:.84rem;font-weight:500;color:#64748B;line-height:1.35;
    white-space:nowrap;
}
.ld-page .ld-sum-cell .ld-field-val{
    margin:0;flex:1 1 auto;justify-content:flex-end;text-align:right;
    font-size:.9rem;font-weight:700;color:#0F172A;line-height:1.35;
    display:inline-flex;align-items:center;gap:7px;flex-wrap:wrap;
    min-width:0;
}
.ld-page .ld-sum-cell .ld-field-val i{color:#64748B;font-size:.92rem;flex-shrink:0}
.ld-page .ld-sum-cell .ld-field-val a{color:var(--ld-blue);text-decoration:none;font-weight:700}
.ld-page .ld-sum-cell .ld-pill{font-size:.75rem;padding:.26rem .68rem;font-weight:700}
@media(max-width:700px){
    .ld-page .ld-summary-table{grid-template-columns:1fr}
    .ld-page .ld-sum-cell{border-right:0}
    .ld-page .ld-sum-cell:nth-last-child(-n+2){border-bottom:1px solid #EEF2F7}
    .ld-page .ld-sum-cell:last-child{border-bottom:0}
    .ld-page .ld-sum-cell .ld-field-label{white-space:normal;flex-basis:42%}
}
.ld-page .ld-summary-grid{display:grid;grid-template-columns:1fr 1fr;column-gap:1.5rem;row-gap:1rem}
@media(max-width:700px){.ld-page .ld-summary-grid{grid-template-columns:1fr}}
.ld-page .ld-field-label{font-size:.72rem;font-weight:700;color:var(--ld-muted);margin-bottom:6px}
.ld-page .ld-field-val{
    font-size:.9rem;font-weight:700;color:var(--ld-ink);
    display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;
}
.ld-page .ld-field-val i{color:var(--ld-muted);font-size:.9rem}
.ld-page .ld-field-val a{color:var(--ld-blue);text-decoration:none;font-weight:700}

/* BM Recommendation — 4 columns with vertical separators */
.ld-page .ld-reco-grid{
    display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;
    border-top:0;
}
.ld-page .ld-reco-cell{
    padding:4px 16px 2px 0;border-right:1px solid #EEF2F7;
    display:flex;flex-direction:column;gap:6px;min-width:0;
}
.ld-page .ld-reco-cell:last-child{border-right:0;padding-right:0}
.ld-page .ld-reco-cell .ld-field-label{
    margin:0;font-size:.72rem;font-weight:600;color:#94A3B8;letter-spacing:.01em;
}
.ld-page .ld-reco-cell .ld-field-val{
    margin:0;font-size:.9rem;font-weight:800;color:#0F172A;align-items:flex-start;line-height:1.35;
}
.ld-page .ld-reco-cell .ld-field-val.is-muted{font-weight:600;color:#475569}
@media(max-width:900px){
    .ld-page .ld-reco-grid{grid-template-columns:1fr 1fr;gap:14px 0}
    .ld-page .ld-reco-cell{border-right:0;padding:0 12px 8px 0}
    .ld-page .ld-reco-cell:nth-child(2n){padding-right:0;padding-left:12px;border-left:1px solid #EEF2F7}
}
@media(max-width:576px){
    .ld-page .ld-reco-grid{grid-template-columns:1fr}
    .ld-page .ld-reco-cell,.ld-page .ld-reco-cell:nth-child(2n){border:0;padding:0 0 12px}
}

/* Activity timeline — reference: H dividers + blue connector + dots */
.ld-page .ld-timeline{list-style:none;margin:0;padding:0}
.ld-page .ld-timeline li{
    display:flex;gap:14px;align-items:flex-start;
    padding:14px 0;position:relative;
    border-bottom:1px solid #F1F5F9;
}
.ld-page .ld-timeline li:first-child{padding-top:2px}
.ld-page .ld-timeline li:last-child{padding-bottom:0;border-bottom:0}
.ld-page .ld-timeline li:not(:last-child)::before{
    content:"";position:absolute;left:17px;top:42px;bottom:-2px;width:2px;
    background:var(--theme-200);border-radius:99px;z-index:0;
}
.ld-page .ld-timeline li:not(:last-child)::after{
    content:"";position:absolute;left:14px;bottom:-5px;width:8px;height:8px;
    border-radius:999px;background:var(--theme-400);border:2px solid #fff;
    box-shadow:0 0 0 2px rgba(96,165,250,.25);z-index:2;
}
.ld-page .ld-tl-ico{
    position:relative;z-index:1;
    width:36px;height:36px;border-radius:999px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:.92rem;
    box-shadow:0 4px 10px rgba(15,23,42,.12);
}
.ld-page .ld-tl-ico.green{background:#16B978}
.ld-page .ld-tl-ico.blue{background:#245BEB}
.ld-page .ld-tl-ico.purple{background:#7542F5}
.ld-page .ld-tl-ico.amber{background:#F59E0B}
.ld-page .ld-tl-ico.slate{background:#94A3B8}
.ld-page .ld-tl-main{flex:1;min-width:0;display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.ld-page .ld-tl-title{margin:0;font-size:.9rem;font-weight:800;color:#0F172A;line-height:1.3}
.ld-page .ld-tl-meta{margin:4px 0 0;font-size:.8rem;color:#71819D;line-height:1.4}
.ld-page .ld-tl-at{margin:2px 0 0;font-size:.75rem;color:#94A3B8;font-weight:600;white-space:nowrap;flex-shrink:0}

/* Right widgets — Next Follow-up (reference: soft wash + watermark + pill) */
.ld-page .ld-card.ld-follow-card{
    border:1px solid rgba(226,232,240,.9);
    box-shadow:0 12px 32px var(--theme-primary-ring),0 2px 8px rgba(15,23,42,.03);
    overflow:hidden;
}
.ld-page .ld-follow{
    position:relative;padding:18px 18px 16px;overflow:hidden;
    background:
        radial-gradient(120% 90% at 100% 100%,rgba(147,197,253,.28) 0%,rgba(196,181,253,.10) 35%,transparent 62%),
        #fff;
}
.ld-page .ld-follow-head{
    display:flex;align-items:center;gap:10px;margin:0 0 14px;
}
.ld-page .ld-follow-head h6{
    margin:0;font-size:.98rem;font-weight:800;color:#0F172A;letter-spacing:-.01em;
}
.ld-page .ld-follow-head .ld-head-ico{
    width:34px;height:34px;border-radius:999px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;font-size:1rem;
    background:rgba(237,233,254,.95);color:#7C3AED;
    box-shadow:0 0 0 6px rgba(167,139,250,.18),0 6px 14px rgba(124,58,237,.18);
}
.ld-page .ld-follow-body{
    position:relative;display:flex;align-items:center;justify-content:space-between;
    gap:8px;min-height:76px;margin-bottom:14px;
}
.ld-page .ld-follow-meta{position:relative;z-index:1;min-width:0;flex:1}
.ld-page .ld-follow-date{
    margin:0;font-size:1.42rem;font-weight:800;color:#0F172A;letter-spacing:-.03em;line-height:1.15;
}
.ld-page .ld-follow-time{
    margin:.4rem 0 0;font-size:.88rem;color:#64748B;font-weight:500;
    display:inline-flex;align-items:center;gap:7px;
}
.ld-page .ld-follow-time i{font-size:.9rem;color:#94A3B8}
.ld-page .ld-follow-art{
    position:absolute;right:2px;top:50%;transform:translateY(-42%);
    z-index:0;pointer-events:none;
    width:110px;height:110px;
    display:flex;align-items:center;justify-content:center;
    background:transparent;font-size:4.2rem;line-height:1;
}
.ld-page .ld-follow-art i{
    opacity:.38;
    color:var(--theme-300);
    filter:drop-shadow(0 10px 20px rgba(96,165,250,.25));
}
.ld-page .ld-follow .ld-btn{
    position:relative;z-index:1;
    border:0;color:var(--theme-primary);background:#fff;
    width:auto;min-width:0;padding:0 20px;justify-content:center;margin-top:0;
    border-radius:999px;font-weight:700;height:42px;font-size:.88rem;
    box-shadow:0 6px 16px var(--theme-primary-ring),0 1px 3px rgba(15,23,42,.06);
}
.ld-page .ld-follow .ld-btn i{color:var(--theme-primary)}
.ld-page .ld-follow .ld-btn:hover{
    background:#fff;color:var(--theme-primary-hover);
    box-shadow:0 8px 20px var(--theme-primary-ring),0 1px 3px rgba(15,23,42,.06);
}
/* Quick Actions — full pastel tiles (reference) */
.ld-page .ld-card.ld-qa-card{
    border:1px solid rgba(226,232,240,.9);
    box-shadow:0 12px 32px rgba(99,102,241,.08),0 2px 8px rgba(15,23,42,.03);
}
.ld-page .ld-qa-card .ld-card-head{border-bottom:0;padding-bottom:10px;margin-bottom:4px}
.ld-page .ld-qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.ld-page .ld-qa{
    border:0;border-radius:14px;padding:.72rem .5rem;
    display:flex;flex-direction:column;align-items:center;gap:6px;text-decoration:none;cursor:pointer;
    color:#0F172A;font-weight:700;font-size:.76rem;transition:.15s ease;min-height:78px;justify-content:center;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.65),0 3px 10px rgba(15,23,42,.04);
}
.ld-page .ld-qa:hover{transform:translateY(-1px);filter:brightness(.985)}
.ld-page .ld-qa span{
    width:auto;height:auto;border-radius:0;background:transparent !important;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.05rem;line-height:1;
}
.ld-page .ld-qa.call{background:#ECFDF5}
.ld-page .ld-qa.call span{color:#059669}
.ld-page .ld-qa.sms{background:var(--theme-50)}
.ld-page .ld-qa.sms span{color:var(--theme-primary)}
.ld-page .ld-qa.email{background:#F5F3FF}
.ld-page .ld-qa.email span{color:#7C3AED}
.ld-page .ld-qa.schedule{background:#FFF7ED}
.ld-page .ld-qa.schedule span{color:#EA580C}
.ld-page .ld-qa.is-off{opacity:.45;pointer-events:none}

/* Notes — soft nested card + author/time footer (reference) */
.ld-page .ld-notes-card .ld-card-head{border-bottom:0;padding-bottom:10px;margin-bottom:4px}
.ld-page .ld-note{
    border:0;border-radius:14px;padding:14px 14px 12px;
    background:#F4F6FB;margin-bottom:10px;position:relative;
}
.ld-page .ld-note:last-child{margin-bottom:0}
.ld-page .ld-note-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.ld-page .ld-note-body{margin:0;font-size:.88rem;font-weight:600;color:#0F172A;line-height:1.5;flex:1}
.ld-page .ld-note-menu{
    border:0;background:transparent;color:#94A3B8;padding:0 2px;cursor:pointer;line-height:1;
    flex-shrink:0;font-size:1.05rem;
}
.ld-page .ld-note-menu:hover{color:#64748B}
.ld-page .ld-note-foot{
    margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:10px;
    font-size:.75rem;color:#64748B;font-weight:600;
}
.ld-page .ld-note-author{display:inline-flex;align-items:center;gap:6px;min-width:0}
.ld-page .ld-note-author i{font-size:.9rem;color:#94A3B8}
.ld-page .ld-note-at{white-space:nowrap;flex-shrink:0}
.ld-page .ld-empty{padding:1.25rem;text-align:center;color:var(--ld-muted);font-size:.86rem}

.ld-page .table.ld-table{font-size:.84rem}
.ld-page .table.ld-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;font-weight:700}
/* Shared Lead Details modals */
.ld-modal .modal-content{border:0;overflow:hidden;box-shadow:0 24px 48px rgba(15,23,42,.18)}
@media (max-width:767.98px){
    .ld-modal .modal-footer .btn-cancel,
    .ld-modal .modal-footer .btn-apply,
    .ld-modal .modal-footer button{
        width:100%;min-height:48px;justify-content:center;
    }
}
.ld-modal .modal-header{
    border-bottom:1px solid #e8edf5;padding:16px 20px;align-items:flex-start;background:#fff;
}
.ld-modal .ld-modal-title-wrap{display:flex;align-items:flex-start;gap:12px;min-width:0}
.ld-modal .ld-modal-ico{
    width:42px;height:42px;border-radius:12px;flex-shrink:0;
    display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;
    background:linear-gradient(135deg,#ede9fe,var(--theme-100));color:#4f46e5;
}
.ld-modal .modal-title{font-size:1.1rem;font-weight:800;color:#0f172a;margin:0;line-height:1.25}
.ld-modal .ld-modal-sub{font-size:.82rem;color:#64748b;margin-top:3px;line-height:1.35}
.ld-modal .modal-body{padding:18px 20px}
.ld-modal .modal-footer{
    border-top:1px solid #e8edf5;padding:12px 20px;background:#fafbfc;gap:8px;
}
.ld-modal .form-label{
    display:block;font-size:.78rem;font-weight:700;color:#475569;margin-bottom:6px;
}
.ld-modal .form-label .req{color:#ef4444;margin-left:2px}
.ld-modal .ld-field{margin-bottom:14px}
.ld-modal .ld-field:last-child{margin-bottom:0}
.ld-modal .ld-input-ico{position:relative;width:100%}
.ld-modal .ld-input-ico > i,
.ld-modal .ld-input-ico > .rupee{
    position:absolute;left:0;top:0;bottom:0;width:40px;
    display:inline-flex;align-items:center;justify-content:center;
    color:#94a3b8;font-size:.92rem;line-height:1;z-index:3;pointer-events:none;
}
.ld-modal .ld-input-ico.textarea-ico > i{align-items:flex-start;padding-top:12px}
.ld-modal .ld-input-ico .rupee{font-weight:800;font-size:.9rem;font-style:normal}
.ld-modal .form-control,.ld-modal .form-select{
    min-height:42px;border-radius:10px;border-color:#e2e8f0;font-size:.9rem;color:#0f172a;
}
.ld-modal .ld-input-ico .form-control,
.ld-modal .ld-input-ico .form-select{
    padding-left:40px !important;
}
.ld-modal textarea.form-control{min-height:100px;padding-top:10px}
.ld-modal .ld-input-ico textarea.form-control{padding-left:40px !important}
/* Select2 sits on top of native select — pad rendered text for icon gutter */
.ld-modal .ld-input-ico .select2-container{width:100%!important;z-index:1}
.ld-modal .ld-input-ico .select2-container--default .select2-selection--single{
    min-height:42px;height:42px;border-radius:10px;border-color:#e2e8f0;
}
.ld-modal .ld-input-ico .select2-container--default .select2-selection--single .select2-selection__rendered{
    padding-left:40px !important;padding-right:2rem;line-height:40px;font-size:.9rem;font-weight:500;
}
.ld-modal .ld-input-ico .select2-container--default .select2-selection--single .select2-selection__arrow{
    height:40px;top:0;
}
.ld-modal .ld-input-ico .select2-container--default.select2-container--focus .select2-selection--single,
.ld-modal .ld-input-ico .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.ld-modal .form-control:focus,.ld-modal .form-select:focus{
    border-color:var(--theme-300);box-shadow:0 0 0 3px var(--theme-primary-ring);
}
.ld-modal .ld-current{
    display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;
    padding:10px 12px;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7;
    font-size:.84rem;color:#64748b;margin-bottom:14px;
}
.ld-modal .ld-current strong{color:#0f172a}
.ld-modal .ld-check{
    display:flex;align-items:center;gap:10px;min-height:42px;
    padding:10px 12px 10px 14px;border-radius:10px;background:#f8fafc;border:1px solid #eef2f7;
}
.ld-modal .ld-check .form-check-input{margin:0;flex-shrink:0;width:1.05rem;height:1.05rem}
.ld-modal .ld-check .form-check-label{margin:0;font-size:.86rem;font-weight:600;color:#334155}
.ld-modal .ld-hint{
    display:flex;align-items:flex-start;gap:8px;margin-top:14px;
    padding:10px 12px;border-radius:10px;background:var(--theme-50);border:1px solid var(--theme-100);
    font-size:.8rem;color:#475569;line-height:1.4;
}
.ld-modal .ld-hint i{color:var(--theme-primary);margin-top:2px}
.ld-modal .btn-cancel{
    height:40px;padding:0 16px;border-radius:10px;font-weight:700;font-size:.86rem;
    border:1px solid #e2e8f0;background:#fff;color:#334155;
}
.ld-modal .btn-cancel:hover{background:#f8fafc;color:#0f172a}
.ld-modal .btn-apply{
    height:40px;padding:0 16px;border-radius:10px;font-weight:700;font-size:.86rem;
    border:0;color:#fff;background:linear-gradient(90deg,var(--theme-primary),var(--theme-primary-hover));
    display:inline-flex;align-items:center;gap:6px;
    box-shadow:0 8px 16px var(--theme-primary-ring);
}
.ld-modal .btn-apply:hover{filter:brightness(1.05);color:#fff}
.ld-modal .btn-apply:disabled{opacity:.5;box-shadow:none}
</style>
@endpush

@section('content')
<div class="container-fluid ld-page"
     data-ref="{{ $lead->ref_no }}"
     data-active-tab="{{ $activeTab }}">

    <div class="ld-top">
        <div class="ld-top-left">
            <a href="{{ route('goal.leads.index') }}" class="ld-back" title="Back"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h1 class="ld-title">Lead Details</h1>
                <p class="ld-sub">View complete lead information, activities and processing status.</p>
                <div class="ld-crumbs">
                    <a href="{{ url('/') }}">Home</a><span class="sep">›</span>
                    <a href="{{ route('goal.leads.index') }}">Leads</a><span class="sep">›</span>
                    <span>Lead Details</span>
                </div>
            </div>
        </div>
        <div class="ld-actions">
            @if(!empty($canEditLead))
            <button type="button" class="ld-btn" data-bs-toggle="modal" data-bs-target="#editLeadModal">
                <i class="bi bi-pencil-square"></i> Edit
            </button>
            @endif
            @if($canAllocate)
                <button type="button" class="ld-btn" data-bs-toggle="modal" data-bs-target="#reassignLeadModal"
                    @disabled(!$lead->assigned_to && $lead->status !== 'Pending BM Allocation')>
                    <i class="bi bi-person-plus"></i> Reassign
                </button>
            @endif
            @if(!empty($needsAllocation))
                <a href="{{ route('goal.allocation.index', ['q' => $lead->ref_no]) }}" class="ld-btn ld-btn-primary">
                    <i class="bi bi-person-check"></i> Allocate
                </a>
            @elseif(!empty($next))
                <button type="button" class="ld-btn ld-btn-primary" data-bs-toggle="modal" data-bs-target="#transitionModal">
                    <i class="bi bi-signpost-2"></i> Update Status
                </button>
            @endif
            <div class="dropdown">
                <button type="button" class="ld-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    @if(!empty($next))
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#transitionModal"><i class="bi bi-signpost-2 me-2"></i>Update status</button></li>
                    @endif
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addNoteModal"><i class="bi bi-journal-plus me-2"></i>Add note</button></li>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#rescheduleModal"><i class="bi bi-calendar-event me-2"></i>Reschedule follow-up</button></li>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#logVisitModal"><i class="bi bi-geo-alt me-2"></i>Log customer visit</button></li>
                    @if($lead->loanCase)
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('goal.cases.show', $lead->loanCase) }}"><i class="bi bi-folder me-2"></i>View case {{ $lead->loanCase->case_no }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="ld-grid">
        {{-- LEFT --}}
        <aside class="ld-col-left">
            <div class="ld-card">
                <div class="ld-profile-hero">
                    <div class="ld-ref-label">Lead Ref No.</div>
                    <div class="ld-ref-row">
                        <h2 class="ld-ref" id="ldRefText">{{ $lead->ref_no }}</h2>
                        <button type="button" class="ld-copy" id="copyRefBtn" title="Copy ref"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <span class="ld-status {{ $statusTone }}">{{ $statusDisplay }}</span>
                </div>
                <ul class="ld-meta-list">
                    <li>
                        <span class="ld-meta-k"><span class="ld-meta-ico"><i class="bi bi-people"></i></span> Lead Type</span>
                        <span class="ld-meta-v">
                            @if(strtolower($lead->lead_type) === 'immediate')
                                <span class="ld-pill ld-pill-green">Immediate</span>
                            @else
                                <span class="ld-pill ld-pill-blue">{{ ucfirst($lead->lead_type ?: '—') }}</span>
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="ld-meta-k"><span class="ld-meta-ico"><i class="bi bi-shield"></i></span> Priority</span>
                        <span class="ld-meta-v">
                            @if($priority === 'High')
                                <span class="ld-priority-high">High</span>
                            @elseif($priority === 'Medium')
                                <span class="ld-pill ld-pill-amber">Medium</span>
                            @else
                                <span class="ld-pill ld-pill-slate">Low</span>
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="ld-meta-k"><span class="ld-meta-ico"><i class="bi bi-diagram-3"></i></span> Source</span>
                        <span class="ld-meta-v">{{ $sourceLabel }}</span>
                    </li>
                    <li>
                        <span class="ld-meta-k"><span class="ld-meta-ico"><i class="bi bi-person"></i></span> Assigned To</span>
                        <span class="ld-meta-v">
                            @if($assigneeLabel !== '—')
                                <span class="ld-pill ld-pill-slate"><i class="bi bi-person-fill"></i>{{ $assigneeLabel }}</span>
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="ld-meta-k"><span class="ld-meta-ico"><i class="bi bi-building"></i></span> Branch</span>
                        <span class="ld-meta-v">
                            @if($lead->orgUnit)
                                <span class="ld-branch">{{ $lead->orgUnit->name }}</span>
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li>
                        <span class="ld-meta-k"><span class="ld-meta-ico"><i class="bi bi-calendar3"></i></span> Created On</span>
                        <span class="ld-meta-v" style="font-size:.78rem;white-space:nowrap">{{ optional($lead->created_at)->format('d M Y, h:i a') }}</span>
                    </li>
                </ul>
            </div>

            <div class="ld-card ld-owner-card">
                <div class="ld-card-pad">
                    <div class="ld-card-head">
                        <h6><span class="ld-head-ico"><i class="bi bi-person-circle"></i></span>Current Owner</h6>
                    </div>
                    <div class="ld-owner">
                        <span class="ld-avatar">{{ $ownerInitial }}</span>
                        <div class="ld-owner-info">
                            <p class="ld-owner-name">{{ $assigneeLabel !== '—' ? $assigneeLabel : 'Unassigned' }}</p>
                            <p class="ld-owner-role">
                                @if($lead->assignee)
                                    @php
                                        $ownerKey = \App\Services\Goal\GoalDashboardService::ROLE_KEYS[(int) ($lead->assignee->crm_role_type ?? -1)] ?? strtolower((string) ($lead->assignee->job_role ?? ''));
                                        $ownerRole = \App\Services\Goal\GoalDashboardService::ROLE_LABELS[$ownerKey] ?? 'Sales Executive';
                                    @endphp
                                    {{ $ownerRole }}
                                @else
                                    Awaiting BM allocation
                                @endif
                            </p>
                        </div>
                        <div class="ld-owner-acts">
                            @php $ownerMobile = preg_replace('/\D+/', '', (string) ($lead->assignee->mobile ?? '')); @endphp
                            <a class="ld-ico {{ $ownerMobile ? '' : 'is-off' }}" href="{{ $ownerMobile ? 'tel:'.$ownerMobile : '#' }}" title="Call owner"><i class="bi bi-telephone"></i></a>
                            <a class="ld-ico {{ $lead->assignee?->email ? '' : 'is-off' }}" href="{{ $lead->assignee?->email ? 'mailto:'.$lead->assignee->email : '#' }}" title="Email owner"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Progress + tabs: full width across center + right (not beside Next Follow-up) --}}
        <div class="ld-progress-wrap">
            <div class="ld-card">
                <div class="ld-progress">
                    @foreach($progress as $step)
                        <div class="ld-step {{ $step['state'] }}">
                            <span class="ld-step-ico"><i class="bi {{ $step['state'] === 'done' ? 'bi-check-lg' : $step['icon'] }}"></i></span>
                            <span class="ld-step-label">{{ $step['label'] }}</span>
                            @if(!empty($step['at']))
                                <span class="ld-step-at">{{ $step['at'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="ld-tabs-wrap">
            <div class="ld-tabs" role="tablist">
                @foreach(['overview'=>'Overview','customer'=>'Customer','communication'=>'Communication','activities'=>'Activities','documents'=>'Documents','case'=>'Case'] as $key => $label)
                    <button type="button" class="ld-tab {{ $activeTab === $key ? 'active' : '' }}" data-ld-tab="{{ $key }}">
                        <i class="bi {{ $tabIcons[$key] }}"></i> {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- CENTER content panes --}}
        <section class="ld-col-main">
            <div class="ld-pane {{ $activeTab === 'overview' ? 'active' : '' }}" data-ld-pane="overview">
                <div class="ld-card">
                    <div class="ld-card-pad">
                        <div class="ld-card-head">
                            <h6><span class="ld-head-ico"><i class="bi bi-info-circle"></i></span>Lead Summary</h6>
                        </div>
                        <div class="ld-summary-table">
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Customer Name</div>
                                <div class="ld-field-val">{{ $lead->customer_name }}</div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Product</div>
                                <div class="ld-field-val">
                                    @if($lead->product)
                                        <span class="ld-pill ld-pill-blue">{{ $lead->product }}</span>
                                    @else — @endif
                                </div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Mobile Number</div>
                                <div class="ld-field-val">
                                    @if($lead->mobile)
                                        <i class="bi bi-telephone"></i>
                                        <a href="tel:{{ $mobileTel }}">{{ $fmtMobile($lead->mobile) }}</a>
                                    @else — @endif
                                </div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Interest / Amount</div>
                                <div class="ld-field-val">{{ $fmtInr($lead->loan_amount) }}</div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Alternate Number</div>
                                <div class="ld-field-val"><i class="bi bi-telephone"></i> —</div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Preferred Branch</div>
                                <div class="ld-field-val">
                                    <i class="bi bi-building"></i>
                                    {{ $lead->orgUnit?->name ?: '—' }}
                                </div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Email</div>
                                <div class="ld-field-val">
                                    @if($lead->email)
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                                    @else
                                        <i class="bi bi-envelope"></i> —
                                    @endif
                                </div>
                            </div>
                            <div class="ld-sum-cell">
                                <div class="ld-field-label">Customer Type</div>
                                <div class="ld-field-val">
                                    <span class="ld-pill ld-pill-purple">{{ $customerType }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ld-card">
                    <div class="ld-card-pad">
                        <div class="ld-card-head">
                            <h6><span class="ld-head-ico medal"><i class="bi bi-award"></i></span>BM Recommendation</h6>
                        </div>
                        <div class="ld-reco-grid">
                            <div class="ld-reco-cell">
                                <div class="ld-field-label">Recommended Amount</div>
                                <div class="ld-field-val">{{ $fmtInr(data_get($bmReco, 'amount')) }}</div>
                            </div>
                            <div class="ld-reco-cell">
                                <div class="ld-field-label">Recommended By</div>
                                <div class="ld-field-val">{{ data_get($bmReco, 'by') ?: '—' }}</div>
                            </div>
                            <div class="ld-reco-cell">
                                <div class="ld-field-label">Recommendation Date</div>
                                <div class="ld-field-val">{{ data_get($bmReco, 'date') ?: '—' }}</div>
                            </div>
                            <div class="ld-reco-cell">
                                <div class="ld-field-label">Remarks</div>
                                <div class="ld-field-val is-muted">{{ data_get($bmReco, 'remarks') ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ld-card">
                    <div class="ld-card-pad">
                        <div class="ld-card-head">
                            <h6><span class="ld-head-ico doc"><i class="bi bi-file-earmark-text"></i></span>Lead Activity Timeline</h6>
                            <button type="button" class="ld-link" data-ld-tab="activities">View All Activities <i class="bi bi-arrow-right"></i></button>
                        </div>
                        <ul class="ld-timeline">
                            @forelse(array_slice($timeline, 0, 8) as $item)
                                <li>
                                    <span class="ld-tl-ico {{ $item['tone'] }}"><i class="bi {{ $item['icon'] }}"></i></span>
                                    <div class="ld-tl-main">
                                        <div>
                                            <p class="ld-tl-title">{{ $item['title'] }}</p>
                                            @if(!empty($item['meta']))<p class="ld-tl-meta">{{ $item['meta'] }}</p>@endif
                                        </div>
                                        <p class="ld-tl-at">{{ $item['at'] }}</p>
                                    </div>
                                </li>
                            @empty
                                <li><div class="ld-empty w-100">No activity yet.</div></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ld-pane {{ $activeTab === 'customer' ? 'active' : '' }}" data-ld-pane="customer">
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head">
                        <h6>Customer Details</h6>
                        <button type="button" class="ld-link" data-bs-toggle="modal" data-bs-target="#editLeadModal">Edit</button>
                    </div>
                    <div class="ld-summary-grid">
                        <div><div class="ld-field-label">Full Name</div><div class="ld-field-val">{{ $lead->customer_name }}</div></div>
                        <div><div class="ld-field-label">Mobile</div><div class="ld-field-val">{{ $lead->mobile ?: '—' }}</div></div>
                        <div><div class="ld-field-label">Email</div><div class="ld-field-val">{{ $lead->email ?: '—' }}</div></div>
                        <div><div class="ld-field-label">Channel Partner</div><div class="ld-field-val">{{ $lead->channel_partner ?: '—' }}</div></div>
                        <div><div class="ld-field-label">Consent</div><div class="ld-field-val">{{ $lead->consent_recorded ? 'Recorded' : 'Not recorded' }}</div></div>
                        <div><div class="ld-field-label">Disposition</div><div class="ld-field-val">{{ $lead->disposition ?: '—' }}</div></div>
                    </div>
                </div></div>
            </div>

            <div class="ld-pane {{ $activeTab === 'communication' ? 'active' : '' }}" data-ld-pane="communication">
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head"><h6>VRM / Call Logs</h6></div>
                    <div class="table-responsive">
                        <table class="table ld-table align-middle mb-0">
                            <thead><tr><th>When</th><th>Disposition</th><th>Notes</th></tr></thead>
                            <tbody>
                            @forelse($lead->vrmCalls as $call)
                                <tr>
                                    <td>{{ optional($call->called_at ?: $call->created_at)->format('d M Y, h:i a') }}</td>
                                    <td>{{ $call->disposition ?: '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($call->notes, 80) ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="ld-empty">No linked call logs.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div></div>
            </div>

            <div class="ld-pane {{ $activeTab === 'activities' ? 'active' : '' }}" data-ld-pane="activities">
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head"><h6><span class="ld-head-ico"><i class="bi bi-activity"></i></span>All Activities</h6></div>
                    <ul class="ld-timeline">
                        @forelse($timeline as $item)
                            <li>
                                <span class="ld-tl-ico {{ $item['tone'] }}"><i class="bi {{ $item['icon'] }}"></i></span>
                                <div class="ld-tl-main">
                                    <div>
                                        <p class="ld-tl-title">{{ $item['title'] }}</p>
                                        @if(!empty($item['meta']))<p class="ld-tl-meta">{{ $item['meta'] }}</p>@endif
                                    </div>
                                    <p class="ld-tl-at">{{ $item['at'] }}</p>
                                </div>
                            </li>
                        @empty
                            <li><div class="ld-empty w-100">No activity yet.</div></li>
                        @endforelse
                    </ul>
                </div></div>
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head"><h6>Status Change History</h6></div>
                    <div class="table-responsive">
                        <table class="table ld-table mb-0">
                            <thead><tr><th>When</th><th>From</th><th>To</th><th>By</th><th>Details</th></tr></thead>
                            <tbody>
                            @forelse($statusHistory as $ev)
                                <tr>
                                    <td>{{ optional($ev->created_at)->format('d M Y, h:i a') }}</td>
                                    <td>{{ $ev->from_status ?: '—' }}</td>
                                    <td>{{ $ev->to_status ?: '—' }}</td>
                                    <td>{{ $ev->user?->name ?: '—' }}</td>
                                    <td>{{ $ev->body ?: $ev->title }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="ld-empty">No status changes recorded yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div></div>
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head"><h6>Assignment History</h6></div>
                    <div class="table-responsive">
                        <table class="table ld-table mb-0">
                            <thead><tr><th>When</th><th>Action</th><th>From</th><th>To</th><th>By</th><th>Reason</th></tr></thead>
                            <tbody>
                            @forelse($lead->assignments as $a)
                                <tr>
                                    <td>{{ optional($a->created_at)->format('d M Y, h:i a') }}</td>
                                    <td>{{ $a->action }}</td>
                                    <td>{{ $a->fromUser?->name ?: '—' }}</td>
                                    <td>{{ $a->toUser?->name ?: '—' }}</td>
                                    <td>{{ $a->assigner?->name ?: '—' }}</td>
                                    <td>{{ $a->reason ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="ld-empty">No assignments yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div></div>
            </div>

            <div class="ld-pane {{ $activeTab === 'documents' ? 'active' : '' }}" data-ld-pane="documents">
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head"><h6>Documents</h6></div>
                    <div class="ld-empty">Document checklist unlocks after case creation.
                        @if($lead->loanCase)
                            <div class="mt-2"><a class="ld-link" href="{{ route('goal.cases.show', $lead->loanCase) }}">Open case workspace</a></div>
                        @endif
                    </div>
                </div></div>
            </div>

            <div class="ld-pane {{ $activeTab === 'case' ? 'active' : '' }}" data-ld-pane="case">
                <div class="ld-card"><div class="ld-card-pad">
                    <div class="ld-card-head"><h6>Linked records</h6></div>
                    <div class="ld-card-body" style="padding:12px 14px">
                        @if(($linkedLeads ?? collect())->isEmpty() && ($linkedCases ?? collect())->isEmpty())
                            <div class="ld-muted" style="font-size:.82rem">No other leads/cases on this mobile yet.</div>
                        @else
                            @foreach(($linkedLeads ?? []) as $ll)
                                <div style="margin-bottom:6px">
                                    <a href="{{ route('goal.leads.show', $ll) }}">{{ $ll->ref_no }}</a>
                                    <span class="ld-muted"> · {{ $ll->status }}</span>
                                </div>
                            @endforeach
                            @foreach(($linkedCases ?? []) as $lc)
                                <div style="margin-bottom:6px">
                                    <a href="{{ route('goal.cases.show', $lc) }}">{{ $lc->case_no }}</a>
                                    <span class="ld-muted"> · {{ $lc->status }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div class="ld-card-head" style="border-top:1px solid #eef2f7"><h6>Linked Case</h6></div>
                    @if($lead->loanCase)
                        <div class="ld-summary-grid">
                            <div><div class="ld-field-label">Case No</div><div class="ld-field-val"><a href="{{ route('goal.cases.show', $lead->loanCase) }}">{{ $lead->loanCase->case_no }}</a></div></div>
                            <div><div class="ld-field-label">Status</div><div class="ld-field-val">{{ $lead->loanCase->status }}</div></div>
                            <div><div class="ld-field-label">Product</div><div class="ld-field-val">{{ $lead->loanCase->product ?: '—' }}</div></div>
                            <div><div class="ld-field-label">Amount</div><div class="ld-field-val">{{ $fmtInr($lead->loanCase->loan_amount) }}</div></div>
                        </div>
                    @else
                        <div class="ld-empty">No case linked yet. Use Update Status → <strong>Create Case</strong> when ready.</div>
                    @endif
                </div></div>
            </div>
        </section>

        {{-- RIGHT --}}
        <aside class="ld-col-right">
            <div class="ld-card ld-follow-card">
                <div class="ld-follow">
                    <div class="ld-follow-head">
                        <span class="ld-head-ico"><i class="bi bi-calendar3"></i></span>
                        <h6>Next Follow-up</h6>
                    </div>
                    <div class="ld-follow-body">
                        <div class="ld-follow-meta">
                            @if($lead->follow_up_at)
                                <div class="ld-follow-date">{{ $lead->follow_up_at->format('d M Y') }}</div>
                                <div class="ld-follow-time"><i class="bi bi-clock"></i> {{ $lead->follow_up_at->format('h:i a') }}</div>
                            @else
                                <div class="ld-follow-date">Not set</div>
                                <div class="ld-follow-time">Schedule a follow-up</div>
                            @endif
                        </div>
                        <div class="ld-follow-art" aria-hidden="true"><i class="bi bi-calendar3"></i></div>
                    </div>
                    <button type="button" class="ld-btn" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                        <i class="bi bi-calendar3"></i> Reschedule
                    </button>
                </div>
            </div>

            <div class="ld-card ld-qa-card">
                <div class="ld-card-pad">
                    <div class="ld-card-head">
                        <h6><span class="ld-head-ico bolt"><i class="bi bi-lightning-charge-fill"></i></span>Quick Actions</h6>
                    </div>
                    <div class="ld-qa-grid">
                        <a class="ld-qa call {{ $mobileTel ? '' : 'is-off' }}" href="{{ $mobileTel ? 'tel:'.$mobileTel : '#' }}">
                            <span><i class="bi bi-telephone-fill"></i></span> Call
                        </a>
                        <a class="ld-qa sms {{ $mobileTel ? '' : 'is-off' }}" href="{{ $mobileTel ? 'sms:'.$mobileTel : '#' }}">
                            <span><i class="bi bi-chat-dots-fill"></i></span> SMS
                        </a>
                        <a class="ld-qa email {{ $lead->email ? '' : 'is-off' }}" href="{{ $lead->email ? 'mailto:'.$lead->email : '#' }}">
                            <span><i class="bi bi-envelope-fill"></i></span> Email
                        </a>
                        <button type="button" class="ld-qa schedule" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                            <span><i class="bi bi-calendar3"></i></span> Schedule
                        </button>
                    </div>
                </div>
            </div>

            <div class="ld-card ld-notes-card">
                <div class="ld-card-pad">
                    <div class="ld-card-head">
                        <h6><span class="ld-head-ico doc"><i class="bi bi-file-earmark-text"></i></span>Notes</h6>
                        <button type="button" class="ld-link" data-bs-toggle="modal" data-bs-target="#addNoteModal">+ Add Note</button>
                    </div>
                    @forelse(array_slice($noteEntries, 0, 4) as $note)
                        <div class="ld-note">
                            <div class="ld-note-top">
                                <p class="ld-note-body">{{ $note['body'] }}</p>
                                <div class="dropdown">
                                    <button type="button" class="ld-note-menu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Note actions">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form method="POST" action="{{ route('goal.leads.notes.destroy', [$lead, $note['index']]) }}"
                                                  onsubmit="return confirm('Delete this note?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash3 me-2"></i>Delete note
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="ld-note-foot">
                                <span class="ld-note-author">
                                    <i class="bi bi-person"></i>
                                    {{ $note['author'] ?: 'Unknown author' }}
                                </span>
                                <span class="ld-note-at">{{ $note['at'] ?: 'No date' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="ld-empty">No notes yet.</div>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>

{{-- Edit modal --}}
<div class="modal fade ld-modal" id="editLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.leads.update', $lead) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <div class="ld-modal-title-wrap">
                    <span class="ld-modal-ico"><i class="bi bi-pencil-square"></i></span>
                    <div>
                        <h5 class="modal-title">Edit Lead</h5>
                        <div class="ld-modal-sub">Update customer and loan details for {{ $lead->ref_no }}.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Customer name <span class="req">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-person"></i>
                            <input name="customer_name" class="form-control" value="{{ old('customer_name', $lead->customer_name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile <span class="req">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-telephone"></i>
                            <input name="mobile" class="form-control" value="{{ old('mobile', $lead->mobile) }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10-digit mobile" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $lead->email) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lead type <span class="req">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-layers"></i>
                            <select name="lead_type" class="form-select" data-search="false" required>
                                <option value="immediate" @selected(old('lead_type', $lead->lead_type) === 'immediate')>Immediate</option>
                                <option value="future" @selected(old('lead_type', $lead->lead_type) === 'future')>Future</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Product</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-box"></i>
                            <select name="product" class="form-select">
                                <option value="">Select product</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->product_name }}" @selected(old('product', $lead->product) === $p->product_name)>{{ $p->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Loan amount</label>
                        <div class="ld-input-ico">
                            <span class="rupee">₹</span>
                            <input type="number" step="0.01" min="0" name="loan_amount" class="form-control" value="{{ old('loan_amount', $lead->loan_amount) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        @php
                            $branchLocked = !empty($branchLocked);
                            $branchChangeLocked = !empty($branchChangeLocked) || $branchLocked;
                            $currentSource = old('source', $lead->source);
                            $showSources = $leadSources ?? collect();
                            if ($currentSource && ! $showSources->contains(fn ($s) => $s->name === $currentSource)) {
                                $showSources = $showSources->prepend((object) ['name' => $currentSource]);
                            }
                        @endphp
                        <div class="ld-input-ico">
                            <i class="bi bi-broadcast"></i>
                            @if($branchLocked)
                                <input type="hidden" name="source" value="{{ $currentSource }}">
                                <input type="text" class="form-control" value="{{ $currentSource ?: '-' }}" readonly disabled>
                            @else
                                <select name="source" class="form-select js-select2" data-placeholder="Select source" data-search="false">
                                    @forelse($showSources as $src)
                                        <option value="{{ $src->name }}" @selected($currentSource === $src->name)>{{ $src->name }}</option>
                                    @empty
                                        <option value="{{ $currentSource }}">{{ $currentSource ?: 'Manual' }}</option>
                                    @endforelse
                                </select>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        @php
                            $editBranchId = old('goal_org_unit_id', $lead->goal_org_unit_id);
                            $editBranchName = optional($branches->firstWhere('id', (int) $editBranchId))->name
                                ?? optional($lead->orgUnit)->name
                                ?? optional(auth()->user()->orgUnit)->name
                                ?? 'Your branch';
                        @endphp
                        <div class="ld-input-ico">
                            <i class="bi bi-building"></i>
                            @if($branchChangeLocked)
                                <input type="hidden" name="goal_org_unit_id" value="{{ $editBranchId }}">
                                <input type="text" class="form-control" value="{{ $editBranchName }}" readonly disabled>
                            @else
                                <select name="goal_org_unit_id" class="form-select js-select2" data-placeholder="Select branch">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" @selected((int) $editBranchId === (int) $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Channel partner</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-people"></i>
                            <input name="channel_partner" class="form-control" value="{{ old('channel_partner', $lead->channel_partner) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Disposition</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-chat-left-dots"></i>
                            <input type="hidden" name="disposition" value="{{ $lead->disposition }}">
                            <input type="text" class="form-control" value="{{ $lead->disposition ?: '-' }}" readonly disabled>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="ld-check">
                            <input type="checkbox" class="form-check-input" name="consent_recorded" value="1" id="editConsent" @checked(old('consent_recorded', $lead->consent_recorded))>
                            <label class="form-check-label" for="editConsent">Consent recorded</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-apply"><i class="bi bi-check2"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Reassign --}}
@if($canAllocate)
<div class="modal fade ld-modal" id="reassignLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.allocation.assign', $lead) }}" class="modal-content">
            @csrf
            <input type="hidden" name="action" value="{{ $lead->assigned_to ? 'reassign' : 'assign' }}">
            <div class="modal-header">
                <div class="ld-modal-title-wrap">
                    <span class="ld-modal-ico"><i class="bi bi-person-plus"></i></span>
                    <div>
                        <h5 class="modal-title">{{ $lead->assigned_to ? 'Reassign Lead' : 'Assign Lead' }}</h5>
                        <div class="ld-modal-sub">{{ $lead->ref_no }} · {{ $lead->customer_name }}</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="ld-field">
                    <label class="form-label">Sales person (JSM / SM / SSM) <span class="req">*</span></label>
                    <div class="ld-input-ico">
                        <i class="bi bi-person-badge"></i>
                        <select name="assigned_to" class="form-select" data-search="true" required>
                            <option value="">Select Sales</option>
                            @foreach($sales as $s)
                                @php
                                    $rk = \App\Services\Goal\GoalDashboardService::ROLE_KEYS[(int) ($s->crm_role_type ?? -1)] ?? null;
                                    $code = strtoupper($rk ?: ($s->job_role ?: 'SALES'));
                                @endphp
                                <option value="{{ $s->id }}" @selected((int) $lead->assigned_to === (int) $s->id)>{{ $code }} - {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($lead->assigned_to)
                    <div class="ld-field">
                        <label class="form-label">Reason <span class="req">*</span></label>
                        <div class="ld-input-ico textarea-ico">
                            <i class="bi bi-chat-left-text"></i>
                            <textarea name="reason" class="form-control" required placeholder="Mandatory for reassignment"></textarea>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-apply"><i class="bi bi-check2"></i> {{ $lead->assigned_to ? 'Reassign' : 'Assign' }}</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Transition / convert helper --}}
<div class="modal fade ld-modal" id="transitionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.leads.transition', $lead) }}" class="modal-content" id="ldTransitionForm">
            @csrf
            <div class="modal-header">
                <div class="ld-modal-title-wrap">
                    <span class="ld-modal-ico"><i class="bi bi-signpost-2"></i></span>
                    <div>
                        <h5 class="modal-title">Update Lead Status</h5>
                        <div class="ld-modal-sub">Move this lead to the next workflow stage.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="ld-current"><i class="bi bi-info-circle"></i> Current: <strong>{{ $lead->status }}</strong></div>
                @if(empty($next))
                    <div class="alert alert-light border mb-0">No further transitions available.</div>
                @else
                    <div class="ld-field">
                        <label class="form-label">Move to <span class="req">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-arrow-right-circle"></i>
                            <select name="to_status" id="ldToStatus" class="form-select" data-search="false" required>
                                @foreach($next as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="ld-field" id="ldFollowUpField">
                        <label class="form-label">Follow-up <span class="req d-none" id="ldFollowUpReq">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-calendar3"></i>
                            <input type="text" name="follow_up_at" id="ldFollowUpAt"
                                   class="form-control js-datetimepicker"
                                   data-min-date="today"
                                   placeholder="dd-mm-yyyy --:--"
                                   value="{{ ($lead->follow_up_at && $lead->follow_up_at->gte(now()->startOfDay())) ? $lead->follow_up_at->format('Y-m-d H:i') : '' }}"
                                   autocomplete="off">
                        </div>
                        <div class="form-text" id="ldFollowUpHint" style="display:none;">Required for Appointment Fixed / Future Prospect.</div>
                    </div>
                    <div class="ld-field">
                        <label class="form-label">Product</label>
                        <div class="ld-input-ico">
                            <i class="bi bi-box"></i>
                            <select name="product" class="form-select" data-allow-clear="true">
                                <option value="">Select product</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->product_name }}" @selected($lead->product === $p->product_name)>{{ $p->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="ld-field" id="ldConsentField">
                        <div class="ld-check">
                            <input type="checkbox" class="form-check-input" name="consent_recorded" value="1" id="trConsent" @checked($lead->consent_recorded)>
                            <label for="trConsent" class="form-check-label">Consent recorded <span class="req d-none" id="ldConsentReq">*</span></label>
                        </div>
                        <div class="form-text" id="ldConsentHint" style="display:none;">Required when moving to Qualified.</div>
                    </div>
                    <div class="ld-field" id="ldClosureField">
                        <label class="form-label">Closure reason <span class="req d-none" id="ldClosureReq">*</span></label>
                        <div class="ld-input-ico">
                            <i class="bi bi-x-octagon"></i>
                            <input name="closure_reason" id="ldClosureReason" class="form-control" placeholder="Required when closing">
                        </div>
                    </div>
                    @if($lead->status === 'Pending BM Allocation')
                        <div class="ld-hint">
                            <i class="bi bi-info-circle"></i>
                            <span>Use <strong>BM Allocation</strong> to assign sales. Status becomes Assigned automatically.</span>
                        </div>
                    @elseif(collect($next)->intersect(['Contact Pending', 'Appointment Fixed', 'Create Case'])->isNotEmpty())
                        <div class="ld-hint">
                            <i class="bi bi-info-circle"></i>
                            <span>Move to <strong>Contact Pending</strong> / <strong>Appointment Fixed</strong>. Choosing <strong>Create Case</strong> creates the case.</span>
                        </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-apply" @disabled(empty($next))><i class="bi bi-check2"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

{{-- Reschedule --}}
<div class="modal fade ld-modal" id="rescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.leads.reschedule', $lead) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div class="ld-modal-title-wrap">
                    <span class="ld-modal-ico"><i class="bi bi-calendar-event"></i></span>
                    <div>
                        <h5 class="modal-title">Reschedule Follow-up</h5>
                        <div class="ld-modal-sub">Set the next follow-up date and time.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="ld-field">
                    <label class="form-label">Follow-up date &amp; time <span class="req">*</span></label>
                    <div class="ld-input-ico">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" name="follow_up_at" class="form-control js-datetimepicker" required
                               data-min-date="today"
                               placeholder="dd-mm-yyyy --:--"
                               value="{{ ($lead->follow_up_at && $lead->follow_up_at->gte(now()->startOfDay())) ? $lead->follow_up_at->format('Y-m-d H:i') : '' }}"
                               autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-apply"><i class="bi bi-check2"></i> Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Add note --}}
<div class="modal fade ld-modal" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.leads.notes', $lead) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div class="ld-modal-title-wrap">
                    <span class="ld-modal-ico"><i class="bi bi-journal-plus"></i></span>
                    <div>
                        <h5 class="modal-title">Add Note</h5>
                        <div class="ld-modal-sub">Notes are stamped with your name and time.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="ld-field">
                    <label class="form-label">Note <span class="req">*</span></label>
                    <div class="ld-input-ico textarea-ico">
                        <i class="bi bi-file-text"></i>
                        <textarea name="note" class="form-control" required maxlength="2000" placeholder="Write a note…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-apply"><i class="bi bi-check2"></i> Save note</button>
            </div>
        </form>
    </div>
</div>

{{-- Log visit --}}
<div class="modal fade ld-modal" id="logVisitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('goal.leads.visit', $lead) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div class="ld-modal-title-wrap">
                    <span class="ld-modal-ico"><i class="bi bi-geo-alt"></i></span>
                    <div>
                        <h5 class="modal-title">Log Customer Visit</h5>
                        <div class="ld-modal-sub">Advances the Visited stage on the progress tracker.</div>
                    </div>
                </div>
                <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="ld-field">
                    <label class="form-label">Visited at</label>
                    <div class="ld-input-ico">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" name="visited_at" class="form-control js-datetimepicker"
                               placeholder="dd-mm-yyyy --:--"
                               value="{{ now()->format('d-m-Y H:i') }}"
                               autocomplete="off">
                    </div>
                </div>
                <div class="ld-field">
                    <label class="form-label">Remarks</label>
                    <div class="ld-input-ico textarea-ico">
                        <i class="bi bi-chat-left-text"></i>
                        <textarea name="remarks" class="form-control" maxlength="1000" placeholder="Outcome of the visit…"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-apply"><i class="bi bi-check2"></i> Save visit</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var page = document.querySelector('.ld-page');
    if (!page) return;

    var tabsWrap = page.querySelector('.ld-tabs-wrap');

    function scrollActiveTabIntoView() {
        if (!tabsWrap) return;
        var active = tabsWrap.querySelector('.ld-tab.active');
        if (!active) return;
        var wrapRect = tabsWrap.getBoundingClientRect();
        var tabRect = active.getBoundingClientRect();
        var pad = 12;
        if (tabRect.left < wrapRect.left + pad) {
            tabsWrap.scrollBy({ left: tabRect.left - wrapRect.left - pad, behavior: 'smooth' });
        } else if (tabRect.right > wrapRect.right - pad) {
            tabsWrap.scrollBy({ left: tabRect.right - wrapRect.right + pad, behavior: 'smooth' });
        }
    }

    function activateTab(key) {
        page.querySelectorAll('[data-ld-tab]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-ld-tab') === key);
        });
        page.querySelectorAll('[data-ld-pane]').forEach(function (pane) {
            pane.classList.toggle('active', pane.getAttribute('data-ld-pane') === key);
        });
        scrollActiveTabIntoView();
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    page.addEventListener('click', function (e) {
        var tabBtn = e.target.closest('[data-ld-tab]');
        if (tabBtn) {
            e.preventDefault();
            activateTab(tabBtn.getAttribute('data-ld-tab'));
        }
    });

    scrollActiveTabIntoView();
    window.addEventListener('resize', scrollActiveTabIntoView);

    var copyBtn = document.getElementById('copyRefBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var text = page.getAttribute('data-ref') || '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
                    setTimeout(function () { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1200);
                });
            }
        });
    }

    // Update Lead Status — conditional required fields
    var toStatus = document.getElementById('ldToStatus');
    var followUpAt = document.getElementById('ldFollowUpAt');
    var followUpReq = document.getElementById('ldFollowUpReq');
    var followUpHint = document.getElementById('ldFollowUpHint');
    var consentBox = document.getElementById('trConsent');
    var consentReq = document.getElementById('ldConsentReq');
    var consentHint = document.getElementById('ldConsentHint');
    var closureReason = document.getElementById('ldClosureReason');
    var closureReq = document.getElementById('ldClosureReq');

    function syncTransitionRequired() {
        if (!toStatus) return;
        var v = toStatus.value || '';
        var needFollow = v === 'Future Prospect' || v === 'Appointment Fixed';
        var needConsent = v === 'Qualified';
        var needClosure = v === 'Closed / Lost';
        var needProduct = v === 'Create Case';
        var productEl = document.querySelector('#ldTransitionForm select[name="product"]');

        if (followUpAt) followUpAt.required = needFollow;
        if (followUpReq) followUpReq.classList.toggle('d-none', !needFollow);
        if (followUpHint) followUpHint.style.display = needFollow ? 'block' : 'none';

        if (productEl) productEl.required = needProduct;

        if (consentReq) consentReq.classList.toggle('d-none', !needConsent);
        if (consentHint) consentHint.style.display = needConsent ? 'block' : 'none';
        if (consentBox && needConsent && !consentBox.checked) {
            consentBox.setAttribute('data-required-consent', '1');
        } else if (consentBox) {
            consentBox.removeAttribute('data-required-consent');
        }

        if (closureReason) closureReason.required = needClosure;
        if (closureReq) closureReq.classList.toggle('d-none', !needClosure);
    }

    if (toStatus) {
        toStatus.addEventListener('change', syncTransitionRequired);
        if (window.jQuery) {
            jQuery(toStatus).on('change select2:select', syncTransitionRequired);
        }
        syncTransitionRequired();
    }

    var trForm = document.getElementById('ldTransitionForm');
    if (trForm) {
        trForm.addEventListener('submit', function (e) {
            if (consentBox && consentBox.getAttribute('data-required-consent') === '1' && !consentBox.checked) {
                e.preventDefault();
                consentBox.focus();
                alert('Consent must be recorded before moving to Qualified.');
            }
        });
    }
})();
</script>
@endpush
