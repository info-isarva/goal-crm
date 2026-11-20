@extends('layouts.app')

@push('styles')
<style>
.user-create-page {
    --uc-pad-x: 1.25rem;
    --uc-pad-y: 1rem;
    color: #1e293b;
    position: relative;
    margin: 0;
    padding: 0 0 1.25rem;
}

/* Hero = flush page background under top bar (not a card) */
.user-create-page .page-hero {
    position: relative;
    overflow: hidden;
    margin: 0 0 1rem;
    padding: 1rem var(--uc-pad-x) 1.15rem;
    border: none;
    border-radius: 0;
    box-shadow: none;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    background:
        radial-gradient(ellipse 280px 200px at 92% 18%, rgba(153,246,228,.35), transparent 70%),
        radial-gradient(ellipse 220px 160px at 78% 70%, rgba(110,231,183,.22), transparent 70%),
        linear-gradient(105deg, #ecfdf5 0%, #f0fdf4 40%, #e8f5f1 100%);
}
.user-create-page .page-hero .hero-art {
    position: absolute;
    right: 150px;
    top: 50%;
    transform: translateY(-50%);
    width: 180px;
    height: 110px;
    pointer-events: none;
    z-index: 0;
    opacity: .85;
}
.user-create-page .page-hero .hero-art svg { width: 100%; height: 100%; display: block; }

.user-create-page .hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.user-create-page .hero-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #34d399 0%, #009B80 55%, #0f766e 100%);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.2rem; box-shadow: 0 8px 16px rgba(0,155,128,.28);
}
.user-create-page .hero-title {
    margin: 0; font-size: 1.4rem; font-weight: 800; color: #1e3a5f; line-height: 1.2;
}
.user-create-page .hero-sub {
    margin: 4px 0 0; color: #64748b; font-size: .875rem; line-height: 1.4;
}
.user-create-page .btn-back {
    position: relative;
    z-index: 2;
    flex-shrink: 0;
    height: 36px; border-radius: 999px; border: 1px solid #e2e8f0; background: #fff;
    color: #334155; padding: 0 14px; font-weight: 700; font-size: .82rem; text-decoration: none;
    display: inline-flex; align-items: center; gap: .35rem;
    box-shadow: 0 2px 6px rgba(15,23,42,.04);
    margin-top: 2px;
}
.user-create-page .btn-back:hover { background: #f8fafc; color: #0f172a; }
@media (max-width: 767.98px) {
    .user-create-page .page-hero {
        flex-direction: column;
        align-items: stretch;
    }
    .user-create-page .page-hero .hero-art { display: none; }
    .user-create-page .btn-back { align-self: flex-start; }
}

/* Content column — same left/right edge as hero text */
.user-create-page .page-body {
    padding: 0 var(--uc-pad-x);
}

.user-create-page .seat-note {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 8px 12px; font-size: .82rem; color: #64748b; margin-bottom: 12px;
}

/* Main white card */
.user-create-page .main-card {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(148,163,184,.1);
    padding: 18px;
    margin-bottom: 1rem;
}

/* Top: profile | details */
.user-create-page .main-top {
    display: grid;
    grid-template-columns: 200px minmax(0, 1fr);
    gap: 18px;
    align-items: stretch;
}
@media (max-width: 991.98px) {
    .user-create-page .main-top { grid-template-columns: 1fr; }
}

/* Profile upload — empty + filled states */
.user-create-page .profile-card {
    background: linear-gradient(180deg, #ecfdf5 0%, #f0fdf4 40%, #f8fafc 100%);
    border: 1px solid #d1fae5;
    border-radius: 14px;
    padding: 32px 14px 24px;
    text-align: center;
    min-height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.user-create-page .avatar-wrap {
    position: relative;
    width: 118px;
    height: 118px;
}
.user-create-page .avatar-preview {
    width: 118px; height: 118px; border-radius: 50%;
    border: 2.5px dashed #99f6e4;
    background: #f0fdfa;
    box-shadow: inset 0 0 0 4px #fff;
    display: flex; align-items: center; justify-content: center;
    color: #0f766e; font-size: 2.6rem; overflow: hidden;
    position: relative;
}
.user-create-page .avatar-preview .avatar-placeholder {
    display: inline-flex; align-items: center; justify-content: center;
    width: 100%; height: 100%;
    color: #0d9488; font-size: 2.75rem; line-height: 1;
    z-index: 1;
}
.user-create-page .avatar-preview.has-image {
    border-style: solid;
    border-color: #fff;
    background: #fff;
    box-shadow: 0 8px 20px rgba(0, 155, 128, .16);
}
.user-create-page .avatar-preview.has-image .avatar-placeholder {
    display: none !important;
}
.user-create-page .avatar-preview img.avatar-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%; object-fit: cover;
    display: none; border-radius: 50%;
    z-index: 2; background: #fff;
}
.user-create-page .avatar-preview.has-image img.avatar-img {
    display: block !important;
}
.user-create-page .avatar-camera {
    position: absolute; right: 0; bottom: 2px; z-index: 3;
    width: 34px; height: 34px; border-radius: 50%;
    border: 2.5px solid #fff;
    background: #009B80; color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 14px rgba(0, 155, 128, .35);
}
.user-create-page .avatar-camera:hover { background: #007F68; color: #fff; }
.user-create-page .profile-title {
    margin: 6px 0 0; font-size: .95rem; font-weight: 800; color: #0f766e;
}
.user-create-page .profile-hint {
    margin: 0; font-size: .78rem; color: #64748b; line-height: 1.35; max-width: 150px;
}

/* User details */
.user-create-page .details-block { min-width: 0; }
.user-create-page .section-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 1rem; font-weight: 800; color: #0f766e; margin: 2px 0 14px;
}
.user-create-page .section-title i { color: #009B80; }
.user-create-page .section-kicker {
    font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
    color: #009B80; margin: 0 0 12px;
}

.user-create-page .field-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px 16px;
}
@media (max-width: 767.98px) {
    .user-create-page .field-grid-2 { grid-template-columns: 1fr; }
}

.user-create-page .form-label {
    display: block; font-size: .8rem; font-weight: 700; color: #475569; margin: 0 0 6px;
}
.user-create-page .form-label .req { color: #ef4444; margin-left: 2px; }

.user-create-page .input-icon { position: relative; width: 100%; }
.user-create-page .input-icon > i.field-icn {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #5eead4; font-size: .95rem; z-index: 2; pointer-events: none;
}
.user-create-page .input-icon .form-control {
    width: 100%; min-height: 42px; height: 42px; border-radius: 10px; border: 1px solid #e2e8f0;
    background-color: #fff; color: #0f172a; padding: 0 14px 0 38px; box-shadow: none;
    font-size: .9rem; line-height: 1.4;
}
.user-create-page .input-icon .form-control:focus {
    border-color: #99f6e4; box-shadow: 0 0 0 3px rgba(0,155,128,.14); outline: none;
}

/* Hide native select — custom menu replaces OS/global dropdown */
.user-create-page .input-icon select.uc-dropdown {
    position: absolute !important;
    width: 1px !important; height: 1px !important;
    opacity: 0 !important; pointer-events: none !important;
    clip: rect(0,0,0,0) !important; border: 0 !important; padding: 0 !important; margin: 0 !important;
}
.user-create-page .input-icon .select2-container { display: none !important; }

.user-create-page .uc-dd {
    position: relative;
    width: 100%;
}
.user-create-page .uc-dd-toggle {
    width: 100%; min-height: 42px; height: 42px; border-radius: 10px; border: 1px solid #e2e8f0;
    background: #fff; color: #0f172a; padding: 0 38px 0 38px;
    font-size: .9rem; line-height: 1.4; text-align: left;
    display: flex; align-items: center;
    box-shadow: none; cursor: pointer;
}
.user-create-page .uc-dd-toggle.is-placeholder { color: #94a3b8; }
.user-create-page .uc-dd-toggle:focus,
.user-create-page .uc-dd.is-open .uc-dd-toggle {
    border-color: #99f6e4; box-shadow: 0 0 0 3px rgba(0,155,128,.14); outline: none;
}
.user-create-page .uc-dd-caret {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .75rem; pointer-events: none; z-index: 2;
    transition: transform .15s ease;
}
.user-create-page .uc-dd.is-open .uc-dd-caret { transform: translateY(-50%) rotate(180deg); color: #009B80; }
.user-create-page .org-card,
.user-create-page .main-card,
.user-create-page .details-block {
    overflow: visible;
}
.user-create-page .uc-dd-menu {
    position: absolute; left: 0; right: 0; top: calc(100% + 6px);
    z-index: 1080; display: none;
    max-height: 280px; overflow-y: auto;
    background: #fff; border: 1px solid #d1fae5; border-radius: 12px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
    padding: 6px;
    scrollbar-width: thin; scrollbar-color: #99f6e4 transparent;
}
.user-create-page .uc-dd-menu::-webkit-scrollbar { width: 6px; }
.user-create-page .uc-dd-menu::-webkit-scrollbar-thumb { background: #99f6e4; border-radius: 999px; }
.user-create-page .uc-dd.is-open .uc-dd-menu { display: block; }
.user-create-page .uc-dd-option {
    display: block; width: 100%; border: 0; background: transparent;
    text-align: left; padding: 9px 12px; border-radius: 8px;
    color: #334155; font-size: .88rem; cursor: pointer;
}
.user-create-page .uc-dd-option:hover {
    background: #ecfdf5; color: #0f766e;
}
.user-create-page .uc-dd-option.is-selected {
    background: #d1fae5; color: #0f766e; font-weight: 700;
}
.user-create-page .uc-dd-option.is-placeholder-opt {
    color: #94a3b8; font-weight: 500;
}
.user-create-page .input-icon.is-password .form-control { padding-right: 42px; }
.user-create-page .toggle-password {
    position: absolute; right: 8px; top: 50%; transform: translateY(-50%); z-index: 3;
    border: none; background: transparent; color: #94a3b8; width: 32px; height: 32px;
    display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;
}
.user-create-page .toggle-password:hover { color: #009B80; background: #f0fdf4; }
.user-create-page .field-error-text { color: #dc2626; font-size: .75rem; margin-top: 4px; }

/* Organization — full width under profile + details */
.user-create-page .org-card {
    margin-top: 16px;
    border: 1px solid #e8edf5;
    border-radius: 12px;
    background: #f8fafc;
    padding: 14px 14px 12px;
}
.user-create-page .field-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px 14px;
    align-items: end;
}
@media (max-width: 991.98px) {
    .user-create-page .field-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .user-create-page .field-grid-3 { grid-template-columns: 1fr; }
}

.user-create-page .status-box {
    min-height: 42px; border-radius: 999px; border: 1px solid #bbf7d0; background: #f0fdf4;
    padding: 0 14px; display: flex; align-items: center; gap: 10px;
}
.user-create-page .status-box.is-inactive {
    border-color: #e2e8f0; background: #fff;
}
.user-create-page .form-switch { padding-left: 2.6em; margin: 0; }
.user-create-page .form-switch .form-check-input {
    width: 2.5em; height: 1.3em; margin-left: -2.6em; cursor: pointer;
}
.user-create-page .form-switch .form-check-input:checked {
    background-color: #22c55e; border-color: #22c55e;
}
.user-create-page .status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: .82rem; font-weight: 800; color: #15803d;
}
.user-create-page .status-pill.is-inactive { color: #64748b; }
.user-create-page .status-pill .dot {
    width: 8px; height: 8px; border-radius: 50%; background: currentColor;
}

/* Permissions panel */
.user-create-page .panel {
    background: #f8fafc; border: 1px solid #e8edf5; border-radius: 16px;
    box-shadow: 0 8px 24px rgba(148,163,184,.08); margin-bottom: 1rem; overflow: hidden;
}
.user-create-page .panel-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    padding: 18px 20px 14px; background: #fff; border-bottom: 1px solid #eef2f7;
}
.user-create-page .panel-head-left h5 {
    margin: 0; font-size: 1.05rem; font-weight: 800; color: #1e3a5f;
    display: inline-flex; align-items: center; gap: 8px;
}
.user-create-page .panel-head-left h5 i { color: #009B80; }
.user-create-page .panel-head-left .panel-sub {
    margin: 4px 0 0 28px; font-size: .82rem; color: #64748b;
}
.user-create-page .panel-body { padding: 16px 20px 20px; }
.user-create-page .perm-tools { display: flex; flex-wrap: wrap; gap: 8px; }
.user-create-page .perm-tools .btn {
    border-radius: 8px; font-weight: 700; font-size: .8rem; height: 34px;
    display: inline-flex; align-items: center; gap: 6px; padding: 0 12px;
    background: #fff;
}
.user-create-page .btn-perm-select { border: 1px solid #cbd5e1; color: #475569; }
.user-create-page .btn-perm-select:hover { background: #f8fafc; color: #0f172a; }
.user-create-page .btn-perm-clear { border: 1px solid #cbd5e1; color: #475569; }
.user-create-page .btn-perm-clear:hover { background: #f8fafc; color: #0f172a; }
.user-create-page .btn-perm-defaults {
    border: 1px solid #99f6e4; color: #007F68; background: #fff;
}
.user-create-page .btn-perm-defaults:hover { background: #f0fdf4; color: #0f766e; }

.user-create-page .permission-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    align-items: start;
}
@media (max-width: 1199.98px) {
    .user-create-page .permission-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .user-create-page .permission-grid { grid-template-columns: 1fr; }
}
.user-create-page .permission-card {
    border: 1px solid #e8edf5; border-radius: 14px; background: #fff; padding: 16px;
    box-shadow: 0 4px 14px rgba(148,163,184,.08); min-height: 0;
}
.user-create-page .permission-card.is-wide {
    grid-column: span 1;
}
.user-create-page .permission-card .card-title {
    display: flex; align-items: center; gap: 10px;
    font-weight: 800; color: #0f172a; font-size: .95rem; margin-bottom: 12px;
}
.user-create-page .permission-card .card-title .icon {
    width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .95rem;
}
.user-create-page .permission-card .icon.tone-blue { background: #dbeafe; color: #2563eb; }
.user-create-page .permission-card .icon.tone-violet { background: #d1fae5; color: #009B80; }
.user-create-page .permission-card .icon.tone-pink { background: #fce7f3; color: #db2777; }
.user-create-page .permission-card .icon.tone-green { background: #dcfce7; color: #16a34a; }
.user-create-page .permission-card .icon.tone-orange { background: #ffedd5; color: #ea580c; }
.user-create-page .permission-card .icon.tone-cyan { background: #cffafe; color: #0891b2; }
.user-create-page .permission-card .icon.tone-indigo { background: #e0e7ff; color: #4f46e5; }
.user-create-page .permission-card .icon.tone-slate { background: #f1f5f9; color: #475569; }

.user-create-page .permission-card .perm-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px 20px;
}
.user-create-page .permission-card .perm-list.cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
@media (max-width: 575.98px) {
    .user-create-page .permission-card .perm-list.cols-2 { grid-template-columns: 1fr; }
}
.user-create-page .permission-card .form-check {
    margin: 0; padding-left: 1.65em; min-height: 0;
}
.user-create-page .permission-card .form-check-input {
    width: 1.05em; height: 1.05em; margin-top: .2em;
    border: 1.5px solid #cbd5e1; border-radius: 4px;
}
.user-create-page .permission-card .form-check-input:checked {
    background-color: #009B80; border-color: #009B80;
}
.user-create-page .permission-card .form-check-label {
    font-size: .84rem; color: #475569; line-height: 1.35;
}
.user-create-page #permission-message {
    border: 1px dashed #d1fae5; background: #ecfdf5; color: #007F68;
    border-radius: 10px; padding: 10px 12px; font-size: .875rem; margin-bottom: 14px;
}

/* Sticky footer actions */
.user-create-page .page-body { padding-bottom: 140px; }
.user-create-page .form-actions-fixed {
    position: fixed;
    left: 0; right: 0; bottom: 50px; /* sit above global slim footer */
    z-index: 1040;
    background: #fff;
    border-top: 1px solid #e8edf5;
    box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.06);
    padding: 12px 1.25rem;
}
.user-create-page .form-actions-fixed-inner {
    display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
    max-width: 100%;
}
/* Offset for desktop sidebar so footer spans main content only */
@media (min-width: 1024px) {
    body:not(.sidebar-collapsed) .user-create-page .form-actions-fixed {
        left: 280px;
    }
    body.sidebar-collapsed .user-create-page .form-actions-fixed {
        left: 80px;
    }
}
.user-create-page .btn-create {
    height: 44px; border: none; border-radius: 10px; padding: 0 18px; font-weight: 800;
    color: #fff; background: linear-gradient(90deg, #009B80, #007F68);
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 10px 18px rgba(0,155,128,.28);
}
.user-create-page .btn-create:hover { filter: brightness(1.05); color: #fff; }
.user-create-page .btn-cancel {
    height: 44px; border-radius: 10px; border: 1px solid #99f6e4; background: #fff; color: #007F68;
    padding: 0 16px; font-weight: 700; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.user-create-page .btn-cancel:hover { background: #f0fdf4; color: #0f766e; }
</style>
@endpush

@section('content')
@php
    $isGoalTenant = \App\Services\Goal\GoalDashboardService::userUsesGoalDashboard(auth()->user());
    $permMeta = [
        'Goal Dashboard' => ['icon' => 'bi-bar-chart-fill', 'tone' => 'tone-blue'],
        'Goal Leads & Calling' => ['icon' => 'bi-telephone-outbound', 'tone' => 'tone-cyan'],
        'Goal Allocation & Cases' => ['icon' => 'bi-briefcase', 'tone' => 'tone-green'],
        'Goal PMS & Accounts' => ['icon' => 'bi-database', 'tone' => 'tone-blue'],
        'Goal Organisation' => ['icon' => 'bi-diagram-2', 'tone' => 'tone-indigo'],
        'Goal MIS & Targets' => ['icon' => 'bi-bullseye', 'tone' => 'tone-orange'],
        'Settings' => ['icon' => 'bi-gear', 'tone' => 'tone-violet'],
        'No Parent' => ['icon' => 'bi-grid-3x3-gap', 'tone' => 'tone-pink'],
        'Reports' => ['icon' => 'bi-graph-up', 'tone' => 'tone-violet'],
        'Social Ads Management' => ['icon' => 'bi-share', 'tone' => 'tone-pink'],
        'Quotation' => ['icon' => 'bi-file-earmark-text', 'tone' => 'tone-blue'],
        'Web Forms Management' => ['icon' => 'bi-ui-checks', 'tone' => 'tone-green'],
    ];
    $permOrder = $isGoalTenant
        ? [
            'Goal Dashboard',
            'Goal Leads & Calling',
            'Goal Allocation & Cases',
            'Goal PMS & Accounts',
            'Goal MIS & Targets',
            'Goal Organisation',
        ]
        : [
            'Settings',
            'No Parent',
            'Reports',
            'Social Ads Management',
            'Quotation',
            'Web Forms Management',
        ];
    $orderedPermissions = collect();
    foreach ($permOrder as $name) {
        if (isset($groupedPermissions[$name])) {
            $orderedPermissions[$name] = $groupedPermissions[$name];
        }
    }
    if (!$isGoalTenant) {
        foreach ($groupedPermissions as $name => $group) {
            if (!$orderedPermissions->has($name)) {
                $orderedPermissions[$name] = $group;
            }
        }
    }
@endphp
<div class="container-fluid p-0 user-create-page">
    {{-- Background band flush under top bar (not a card) --}}
    <div class="page-hero">
        <div class="hero-art" aria-hidden="true">
            <svg viewBox="0 0 200 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="78" cy="38" r="18" fill="url(#uGradA)"/>
                <path d="M48 98c0-18.8 13.4-34 30-34s30 15.2 30 34" fill="url(#uGradA)" opacity="0.85"/>
                <circle cx="118" cy="32" r="22" fill="url(#uGradB)"/>
                <path d="M82 102c0-22.1 16.1-40 36-40s36 17.9 36 40" fill="url(#uGradB)"/>
                <circle cx="148" cy="40" r="16" fill="url(#uGradC)" opacity="0.75"/>
                <path d="M124 98c0-15.5 10.7-28 24-28s24 12.5 24 28" fill="url(#uGradC)" opacity="0.7"/>
                <circle cx="138" cy="78" r="16" fill="#009B80"/>
                <path d="M130.5 78h15M138 70.5v15" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/>
                <defs>
                    <linearGradient id="uGradA" x1="48" y1="20" x2="108" y2="98" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#99f6e4"/><stop offset="1" stop-color="#d1fae5" stop-opacity="0.55"/>
                    </linearGradient>
                    <linearGradient id="uGradB" x1="82" y1="10" x2="154" y2="102" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#5eead4"/><stop offset="1" stop-color="#d1fae5" stop-opacity="0.65"/>
                    </linearGradient>
                    <linearGradient id="uGradC" x1="124" y1="24" x2="172" y2="98" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#6ee7b7"/><stop offset="1" stop-color="#ecfdf5" stop-opacity="0.5"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>
        <div class="hero-content">
            <span class="hero-icon"><i class="bi bi-person-plus-fill"></i></span>
            <div>
                <h1 class="hero-title">Add User</h1>
                <p class="hero-sub">Create a new CRM user, assign role, reporting and permissions.</p>
            </div>
        </div>
        <a href="{{ route('users.index') }}" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Users</a>
    </div>

    <div class="page-body">
    @if(!empty($maxUsers))
        <div class="seat-note">
            User seats: <strong>{{ $userCount ?? 0 }}</strong> / <strong>{{ $maxUsers }}</strong>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}" id="createUserForm" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="main-card">
            <div class="main-top">
                <aside class="profile-card">
                    <div class="avatar-wrap">
                        <div class="avatar-preview" id="avatarPreview">
                            <span class="avatar-placeholder" id="avatarPlaceholder" aria-hidden="true">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <img id="avatarImage" class="avatar-img" alt="">
                        </div>
                        <label class="avatar-camera" for="avatar" title="Upload profile picture">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/jpg,image/gif" class="d-none">
                    </div>
                    <div class="profile-title">User Profile</div>
                    <p class="profile-hint">Upload a profile picture (optional).</p>
                </aside>

                <div class="details-block">
                    <div class="section-title"><i class="bi bi-person-vcard"></i> User Details</div>
                    <div class="field-grid-2">
                        <div>
                            <label for="name" class="form-label">Name <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-person field-icn"></i>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                       value="{{ old('name') }}" placeholder="Enter full name" autocomplete="name">
                            </div>
                            @error('name')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="email" class="form-label">Email <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-envelope field-icn"></i>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                       value="{{ old('email') }}" placeholder="Enter email address" autocomplete="email">
                            </div>
                            @error('email')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="mobile" class="form-label">Mobile / Phone <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-telephone field-icn"></i>
                                <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile"
                                       value="{{ old('mobile') }}" maxlength="10" inputmode="numeric" pattern="[0-9]{10}"
                                       placeholder="10-digit mobile" autocomplete="tel" required>
                            </div>
                            @error('mobile')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="crm_role_type" class="form-label">CRM Role <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-layers field-icn"></i>
                                <select class="uc-dropdown @error('crm_role_type') is-invalid @enderror" id="crm_role_type" name="crm_role_type" data-select2-skip="1">
                                    <option value="">Select CRM Role</option>
                                    @foreach($crmRoles as $role)
                                        <option value="{{ $role->id }}" {{ (string) old('crm_role_type') === (string) $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('crm_role_type')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="password" class="form-label">Password <span class="req">*</span></label>
                            <div class="input-icon is-password">
                                <i class="bi bi-lock field-icn"></i>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                                       placeholder="Enter password" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            @error('password')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="req">*</span></label>
                            <div class="input-icon is-password">
                                <i class="bi bi-lock field-icn"></i>
                                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation"
                                       placeholder="Re-enter password" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="password_confirmation" aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            @error('password_confirmation')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        @unless($isGoalTenant)
                        <div>
                            <label for="sales_target" class="form-label">Sales Target ({{ config('app.currency_symbol', '₹') }})</label>
                            <div class="input-icon">
                                <i class="bi bi-bullseye field-icn"></i>
                                <input type="text" inputmode="decimal" data-decimals="2" class="form-control js-decimal @error('sales_target') is-invalid @enderror"
                                       id="sales_target" name="sales_target" value="{{ old('sales_target', 0) }}" placeholder="0">
                            </div>
                            @error('sales_target')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        @endunless
                        <div id="assign-manager-container" style="display: none;">
                            <label for="assign_manager" class="form-label">Assign Manager</label>
                            <div class="input-icon">
                                <i class="bi bi-person-check field-icn"></i>
                                <select class="uc-dropdown" id="assign_manager" name="assign_manager" data-select2-skip="1">
                                    <option value="">Select Manager</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}" @selected(old('assign_manager')==$manager->id)>{{ $manager->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('assign_manager')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Full-width Organization Details (spans under profile + details) --}}
            <div class="org-card">
                <div class="section-kicker">Organization Details</div>
                <div class="field-grid-2">
                    <div>
                        <label for="goal_org_unit_id" class="form-label">Goal Org Unit <span class="req">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-diagram-3 field-icn"></i>
                            @php
                                $selectedOrgUnit = old('goal_org_unit_id', $defaultOrgUnitId ?? null);
                                $orgUnitIsLocked = !empty($orgUnitLocked) && $selectedOrgUnit;
                            @endphp
                            <select class="uc-dropdown @error('goal_org_unit_id') is-invalid @enderror"
                                    id="goal_org_unit_id" name="goal_org_unit_id" data-select2-skip="1"
                                    @if($orgUnitIsLocked) disabled @else required @endif>
                                <option value="">Select Org Unit</option>
                                @foreach(($goalOrgUnits ?? []) as $unit)
                                    <option value="{{ $unit->id }}"
                                            data-type="{{ $unit->type }}"
                                            @selected((string) $selectedOrgUnit === (string) $unit->id)>
                                        {{ $unit->name }} ({{ $unit->type }})
                                    </option>
                                @endforeach
                            </select>
                            @if($orgUnitIsLocked)
                                <input type="hidden" name="goal_org_unit_id" value="{{ $selectedOrgUnit }}">
                            @endif
                        </div>
                        <div class="form-text text-muted mt-1" id="orgUnitHint" style="font-size:.72rem;">
                            Org units filter by CRM Role (e.g. BM → branches only).
                        </div>
                        @error('goal_org_unit_id')<div class="field-error-text">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label d-block">Status</label>
                        <div class="status-box {{ old('status', '1') == '1' ? '' : 'is-inactive' }}" id="statusBox">
                            <div class="form-check form-switch m-0">
                                <input type="hidden" name="status" id="statusValue" value="{{ old('status', '1') == '1' ? '1' : '0' }}">
                                <input class="form-check-input" type="checkbox" role="switch" id="status" value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                            </div>
                            <span class="status-pill {{ old('status', '1') == '1' ? '' : 'is-inactive' }}" id="statusLabel">
                                <span class="dot"></span>{{ old('status', '1') == '1' ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        @error('status')<div class="field-error-text">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-head-left">
                    <h5><i class="bi bi-shield-lock"></i> Permissions</h5>
                    <div class="panel-sub">Select a CRM role to view and assign permissions.</div>
                </div>
                <div class="perm-tools">
                    <button type="button" class="btn btn-perm-select" id="selectAllPerms"><i class="bi bi-check2-all"></i> Select All</button>
                    <button type="button" class="btn btn-perm-clear" id="clearAllPerms"><i class="bi bi-slash-circle"></i> Clear All</button>
                    <button type="button" class="btn btn-perm-defaults" id="applyRoleDefaults"><i class="bi bi-gear"></i> Role Defaults</button>
                </div>
            </div>
            <div class="panel-body">
                <div id="permissions-list" data-role-permissions='@json($rolePermissionsMap)'>
                    <div id="permission-message" class="{{ old('crm_role_type') ? 'd-none' : '' }}">Select a CRM Role to apply default permissions, then adjust as needed.</div>
                    <div class="permission-grid">
                        @foreach($orderedPermissions as $parentName => $group)
                            @php
                                $meta = $permMeta[$parentName] ?? ['icon' => 'bi-grid-3x3-gap', 'tone' => 'tone-slate'];
                                $useTwoCols = $group->count() >= 6;
                            @endphp
                            <div class="permission-card">
                                <div class="card-title">
                                    <span class="icon {{ $meta['tone'] }}"><i class="bi {{ $meta['icon'] }}"></i></span>
                                    <span>{{ $parentName }}</span>
                                </div>
                                <div class="perm-list {{ $useTwoCols ? 'cols-2' : '' }}">
                                    @foreach($group as $permission)
                                        <div class="form-check" data-guard-name="{{ $permission->guard_name }}">
                                            <input class="form-check-input" type="checkbox" name="crm_page_right[]" value="{{ $permission->guard_name }}" id="perm_{{ $permission->id }}" data-guard-name="{{ $permission->guard_name }}" {{ in_array($permission->guard_name, old('crm_page_right', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
    </div>{{-- /.page-body --}}

    <div class="form-actions-fixed">
        <div class="form-actions-fixed-inner">
            <button type="submit" class="btn-create" form="createUserForm"><i class="bi bi-person-plus"></i> Create User</button>
            <a href="{{ route('users.index') }}" class="btn-cancel"><i class="bi bi-x-lg"></i> Cancel</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const crmRoleSelect = document.getElementById('crm_role_type');
    const permissionsList = document.getElementById('permissions-list');
    const permissionMessage = document.getElementById('permission-message');
    const statusSwitch = document.getElementById('status');
    const statusLabel = document.getElementById('statusLabel');
    const statusBox = document.getElementById('statusBox');
    const assignManagerContainer = document.getElementById('assign-manager-container');
    const createForm = document.getElementById('createUserForm');
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            const sel = document.getElementById('goal_org_unit_id');
            const hidden = createForm.querySelector('input[type="hidden"][name="goal_org_unit_id"]');
            const value = (sel && !sel.disabled ? sel.value : (hidden ? hidden.value : '')) || '';
            if (!String(value).trim()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const msg = 'Goal Org Unit is required.';
                if (typeof showFlashSwal === 'function') showFlashSwal('error', msg);
                else if (window.Swal) Swal.fire({ icon: 'error', title: 'Required', text: msg, confirmButtonColor: '#009B80' });
                else alert(msg);
                if (sel && !sel.disabled) sel.focus();
            }
        }, true);
    }
    if (typeof confirmFormSubmit === 'function' && createForm) {
        confirmFormSubmit(createForm, {
            title: 'Create user?',
            text: 'Save this user with the selected role and permissions?',
            confirmText: 'Yes, create user'
        });
    }
    if (!crmRoleSelect || !permissionsList) return;

    let rolePermissions = {};
    try {
        rolePermissions = JSON.parse(permissionsList.getAttribute('data-role-permissions') || '{}');
    } catch (e) {
        rolePermissions = {};
    }

    function updatePermissionsChecked() {
        const selectedRoleId = crmRoleSelect.value;
        const perms = (rolePermissions[selectedRoleId] || []).map(function (p) { return String(p).trim(); });
        permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(function (checkbox) {
            const guardName = String(checkbox.getAttribute('data-guard-name') || '').trim();
            checkbox.checked = perms.includes(guardName);
        });
        if (permissionMessage) permissionMessage.classList.toggle('d-none', !!selectedRoleId);
    }

    function syncAssignManager() {
        if (!assignManagerContainer) return;
        assignManagerContainer.style.display = parseInt(crmRoleSelect.value, 10) === 3 ? 'block' : 'none';
    }

    const orgUnitSelect = document.getElementById('goal_org_unit_id');
    const orgUnitTypesByRole = @json($orgUnitTypesByRole ?? []);
    const orgUnitHint = document.getElementById('orgUnitHint');

    function filterOrgUnitsByRole() {
        if (!orgUnitSelect) return;
        const roleId = String(crmRoleSelect.value || '');
        const allowedTypes = orgUnitTypesByRole[roleId] || null;
        const prev = orgUnitSelect.value;
        let keep = false;

        Array.prototype.forEach.call(orgUnitSelect.options, function (opt) {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            const type = opt.getAttribute('data-type') || '';
            const ok = !allowedTypes || allowedTypes.indexOf(type) !== -1;
            opt.hidden = !ok;
            if (ok && String(opt.value) === String(prev)) keep = true;
        });

        if (!keep) {
            orgUnitSelect.value = '';
        }

        if (typeof orgUnitSelect._ucRefresh === 'function') {
            orgUnitSelect._ucRefresh();
        }

        if (orgUnitHint) {
            if (!roleId) {
                orgUnitHint.textContent = 'Select a CRM Role to see matching org units.';
            } else if (allowedTypes && allowedTypes.length === 1) {
                orgUnitHint.textContent = 'Showing ' + allowedTypes[0] + ' units for this role.';
            } else if (allowedTypes) {
                orgUnitHint.textContent = 'Showing: ' + allowedTypes.join(', ') + '.';
            }
        }
    }

    crmRoleSelect.addEventListener('change', function () {
        updatePermissionsChecked();
        syncAssignManager();
        filterOrgUnitsByRole();
    });
    filterOrgUnitsByRole();

    const statusValue = document.getElementById('statusValue');
    if (statusSwitch && statusLabel && statusValue) {
        statusSwitch.addEventListener('change', function () {
            statusLabel.classList.toggle('is-inactive', !statusSwitch.checked);
            statusLabel.innerHTML = '<span class="dot"></span>' + (statusSwitch.checked ? 'Active' : 'Inactive');
            statusValue.value = statusSwitch.checked ? '1' : '0';
            if (statusBox) statusBox.classList.toggle('is-inactive', !statusSwitch.checked);
        });
    }

    document.getElementById('selectAllPerms')?.addEventListener('click', function () {
        permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(function (cb) { cb.checked = true; });
    });
    document.getElementById('clearAllPerms')?.addEventListener('click', function () {
        permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
    });
    document.getElementById('applyRoleDefaults')?.addEventListener('click', updatePermissionsChecked);

    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.getAttribute('data-target'));
            if (!target) return;
            const show = target.type === 'password';
            target.type = show ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // Custom dropdown UI (replaces native/OS/Select2 open menu)
    function closeAllUcDropdowns(except) {
        document.querySelectorAll('.user-create-page .uc-dd.is-open').forEach(function (dd) {
            if (except && dd === except) return;
            dd.classList.remove('is-open');
            const btn = dd.querySelector('.uc-dd-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('.user-create-page select.uc-dropdown').forEach(function (sel) {
        const wrap = sel.closest('.input-icon');
        if (!wrap || wrap.querySelector('.uc-dd')) return;

        const dd = document.createElement('div');
        dd.className = 'uc-dd';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'uc-dd-toggle';
        toggle.setAttribute('aria-haspopup', 'listbox');
        toggle.setAttribute('aria-expanded', 'false');

        const caret = document.createElement('i');
        caret.className = 'bi bi-chevron-down uc-dd-caret';

        const menu = document.createElement('div');
        menu.className = 'uc-dd-menu';
        menu.setAttribute('role', 'listbox');

        function selectedLabel() {
            const opt = sel.options[sel.selectedIndex];
            return opt ? opt.textContent.trim() : '';
        }

        function refresh() {
            const label = selectedLabel() || (sel.options[0] ? sel.options[0].textContent.trim() : 'Select');
            toggle.textContent = label;
            toggle.classList.toggle('is-placeholder', !sel.value);
            menu.querySelectorAll('.uc-dd-option').forEach(function (btn) {
                btn.classList.toggle('is-selected', btn.getAttribute('data-value') === String(sel.value));
            });
        }

        function rebuildOptions() {
            menu.innerHTML = '';
            Array.prototype.forEach.call(sel.options, function (opt) {
                if (opt.hidden) return;
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'uc-dd-option' + (!opt.value ? ' is-placeholder-opt' : '');
                item.setAttribute('role', 'option');
                item.setAttribute('data-value', opt.value);
                item.textContent = opt.textContent.trim();
                if (opt.disabled) item.disabled = true;
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    sel.value = opt.value;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    refresh();
                    closeAllUcDropdowns();
                });
                menu.appendChild(item);
            });
            refresh();
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const willOpen = !dd.classList.contains('is-open');
            closeAllUcDropdowns();
            if (willOpen) {
                rebuildOptions();
                dd.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });

        dd.appendChild(toggle);
        dd.appendChild(menu);
        wrap.appendChild(dd);
        wrap.appendChild(caret);
        sel._ucRefresh = function () {
            rebuildOptions();
        };
        rebuildOptions();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.user-create-page .uc-dd')) closeAllUcDropdowns();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllUcDropdowns();
    });

    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarImage = document.getElementById('avatarImage');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');
    avatarInput?.addEventListener('change', function () {
        const file = avatarInput.files && avatarInput.files[0];
        if (!file || !avatarImage) return;
        if (avatarImage.dataset.objectUrl) {
            URL.revokeObjectURL(avatarImage.dataset.objectUrl);
        }
        const objectUrl = URL.createObjectURL(file);
        avatarImage.dataset.objectUrl = objectUrl;
        avatarImage.src = objectUrl;
        avatarImage.alt = 'Profile preview';
        avatarPreview?.classList.add('has-image');
    });

    if (crmRoleSelect.value) {
        @unless(old('crm_page_right'))
            updatePermissionsChecked();
        @endunless
        if (permissionMessage) permissionMessage.classList.add('d-none');
    }
    syncAssignManager();

    @if ($errors->any())
        if (typeof showFlashSwal === 'function') {
            showFlashSwal('error', @json($errors->first()));
        } else if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: @json($errors->first()), confirmButtonColor: '#009B80' });
        }
    @endif
})();
</script>
@endpush
