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
        radial-gradient(ellipse 280px 200px at 92% 18%, rgba(167,139,250,.28), transparent 70%),
        radial-gradient(ellipse 220px 160px at 78% 70%, rgba(147,197,253,.22), transparent 70%),
        linear-gradient(105deg, #eef2ff 0%, #f5f3ff 40%, #e8eef8 100%);
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
    background: linear-gradient(135deg, #3b82f6 0%, #7c3aed 55%, #9333ea 100%);
    color: #fff; display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.2rem; box-shadow: 0 8px 16px rgba(109,40,217,.25);
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

/* Profile upload — exact mockup */
.user-create-page .profile-card {
    background: linear-gradient(180deg, #e0e7ff 0%, #eef2ff 35%, #f5f3ff 100%);
    border: 1px solid #ddd6fe;
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
    border: 2.5px dashed #c4b5fd;
    background: #e2e8f0;
    box-shadow: inset 0 0 0 4px #fff;
    display: flex; align-items: center; justify-content: center;
    color: #8b5cf6; font-size: 2.6rem; overflow: hidden;
}
.user-create-page .avatar-preview.has-image {
    border-style: solid;
    border-color: #fff;
    box-shadow: 0 8px 20px rgba(124,58,237,.14);
}
.user-create-page .avatar-preview img {
    width: 100%; height: 100%; object-fit: cover; display: block;
}
.user-create-page .avatar-camera {
    position: absolute; right: 0; bottom: 2px;
    width: 34px; height: 34px; border-radius: 50%;
    border: 2.5px solid #fff;
    background: #7c3aed; color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 14px rgba(124,58,237,.35);
}
.user-create-page .avatar-camera:hover { background: #6d28d9; }
.user-create-page .profile-title {
    margin: 6px 0 0; font-size: .95rem; font-weight: 800; color: #5b21b6;
}
.user-create-page .profile-hint {
    margin: 0; font-size: .78rem; color: #64748b; line-height: 1.35; max-width: 150px;
}

/* User details */
.user-create-page .details-block { min-width: 0; }
.user-create-page .section-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 1rem; font-weight: 800; color: #4c1d95; margin: 2px 0 14px;
}
.user-create-page .section-title i { color: #7c3aed; }
.user-create-page .section-kicker {
    font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
    color: #7c3aed; margin: 0 0 12px;
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
    color: #a78bfa; font-size: .95rem; z-index: 2; pointer-events: none;
}
.user-create-page .input-icon .form-control {
    width: 100%; min-height: 42px; height: 42px; border-radius: 10px; border: 1px solid #e2e8f0;
    background-color: #fff; color: #0f172a; padding: 0 14px 0 38px; box-shadow: none;
    font-size: .9rem; line-height: 1.4;
}
.user-create-page .input-icon .form-control:focus {
    border-color: #c4b5fd; box-shadow: 0 0 0 3px rgba(124,58,237,.12); outline: none;
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
    border-color: #c4b5fd; box-shadow: 0 0 0 3px rgba(124,58,237,.12); outline: none;
}
.user-create-page .uc-dd-caret {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .75rem; pointer-events: none; z-index: 2;
    transition: transform .15s ease;
}
.user-create-page .uc-dd.is-open .uc-dd-caret { transform: translateY(-50%) rotate(180deg); color: #7c3aed; }
.user-create-page .org-card,
.user-create-page .main-card,
.user-create-page .details-block {
    overflow: visible;
}
.user-create-page .uc-dd-menu {
    position: absolute; left: 0; right: 0; top: calc(100% + 6px);
    z-index: 1080; display: none;
    max-height: 260px; overflow-y: auto;
    background: #fff; border: 1px solid #e9d5ff; border-radius: 12px;
    box-shadow: 0 12px 28px rgba(109, 40, 217, 0.12);
    padding: 6px;
}
.user-create-page .uc-dd.is-open .uc-dd-menu { display: block; }
.user-create-page .uc-dd-option {
    display: block; width: 100%; border: 0; background: transparent;
    text-align: left; padding: 9px 12px; border-radius: 8px;
    color: #334155; font-size: .88rem; cursor: pointer;
}
.user-create-page .uc-dd-option:hover {
    background: #f5f3ff; color: #5b21b6;
}
.user-create-page .uc-dd-option.is-selected {
    background: #ede9fe; color: #5b21b6; font-weight: 700;
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
.user-create-page .toggle-password:hover { color: #7c3aed; background: #f5f3ff; }
.user-create-page .field-error-text { color: #dc2626; font-size: .75rem; margin-top: 4px; }

/* Organization — full width under profile + details */
.user-create-page .org-card {
    margin-top: 16px;
    border: 1px solid #e8edf5;
    border-radius: 12px;
    background: #f8fafc;
    padding: 14px 14px 12px;
}
.user-create-page .field-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px 14px;
    align-items: end;
}
@media (max-width: 1199.98px) {
    .user-create-page .field-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .user-create-page .field-grid-4 { grid-template-columns: 1fr; }
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
.user-create-page .panel-head-left h5 i { color: #7c3aed; }
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
    border: 1px solid #c4b5fd; color: #6d28d9; background: #fff;
}
.user-create-page .btn-perm-defaults:hover { background: #f5f3ff; color: #5b21b6; }

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
.user-create-page .permission-card .icon.tone-violet { background: #ede9fe; color: #7c3aed; }
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
    background-color: #7c3aed; border-color: #7c3aed;
}
.user-create-page .permission-card .form-check-label {
    font-size: .84rem; color: #475569; line-height: 1.35;
}
.user-create-page #permission-message {
    border: 1px dashed #ddd6fe; background: #faf5ff; color: #6d28d9;
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
    color: #fff; background: linear-gradient(90deg, #7c3aed, #6d28d9);
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 10px 18px rgba(109,40,217,.25);
}
.user-create-page .btn-create:hover { filter: brightness(1.05); color: #fff; }
.user-create-page .btn-cancel {
    height: 44px; border-radius: 10px; border: 1px solid #c4b5fd; background: #fff; color: #6d28d9;
    padding: 0 16px; font-weight: 700; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.user-create-page .btn-cancel:hover { background: #f5f3ff; color: #5b21b6; }
</style>
@endpush

@section('content')
@php
    $permMeta = [
        'Goal PMS & Accounts' => ['icon' => 'bi-database', 'tone' => 'tone-blue'],
        'Settings' => ['icon' => 'bi-gear', 'tone' => 'tone-violet'],
        'No Parent' => ['icon' => 'bi-grid-3x3-gap', 'tone' => 'tone-pink'],
        'Goal Allocation & Cases' => ['icon' => 'bi-briefcase', 'tone' => 'tone-green'],
        'Goal MIS & Targets' => ['icon' => 'bi-bullseye', 'tone' => 'tone-orange'],
        'Goal Leads & Calling' => ['icon' => 'bi-telephone-outbound', 'tone' => 'tone-cyan'],
        'Goal Organisation' => ['icon' => 'bi-diagram-2', 'tone' => 'tone-indigo'],
        'Reports' => ['icon' => 'bi-graph-up', 'tone' => 'tone-violet'],
        'Social Ads Management' => ['icon' => 'bi-share', 'tone' => 'tone-pink'],
        'Quotation' => ['icon' => 'bi-file-earmark-text', 'tone' => 'tone-blue'],
        'Web Forms Management' => ['icon' => 'bi-ui-checks', 'tone' => 'tone-green'],
    ];
    $permOrder = [
        'Goal PMS & Accounts',
        'Settings',
        'No Parent',
        'Goal Allocation & Cases',
        'Goal MIS & Targets',
        'Goal Leads & Calling',
        'Goal Organisation',
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
    foreach ($groupedPermissions as $name => $group) {
        if (!$orderedPermissions->has($name)) {
            $orderedPermissions[$name] = $group;
        }
    }
@endphp
<div class="container-fluid p-0 user-create-page user-edit-page">
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
                <circle cx="138" cy="78" r="16" fill="#7c3aed"/>
                <path d="M130.5 78h15M138 70.5v15" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/>
                <defs>
                    <linearGradient id="uGradA" x1="48" y1="20" x2="108" y2="98" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#c4b5fd"/><stop offset="1" stop-color="#e9d5ff" stop-opacity="0.55"/>
                    </linearGradient>
                    <linearGradient id="uGradB" x1="82" y1="10" x2="154" y2="102" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#a78bfa"/><stop offset="1" stop-color="#ddd6fe" stop-opacity="0.65"/>
                    </linearGradient>
                    <linearGradient id="uGradC" x1="124" y1="24" x2="172" y2="98" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#bfdbfe"/><stop offset="1" stop-color="#e0e7ff" stop-opacity="0.5"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>
        <div class="hero-content">
            <span class="hero-icon"><i class="bi bi-person-gear"></i></span>
            <div>
                <h1 class="hero-title">Edit User</h1>
                <p class="hero-sub">Update CRM user details, role, reporting and permissions.</p>
            </div>
        </div>
        <a href="{{ route('users.index') }}" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Users</a>
    </div>

    <div class="page-body">
    @php
        $assignedPermissions = [];
        if ($user->crm_page_right) {
            $decoded = json_decode($user->crm_page_right, true);
            $assignedPermissions = is_array($decoded) ? $decoded : [];
            // Legacy "all" means every assignable guard for this form.
            if ($assignedPermissions === ['all']) {
                $assignedPermissions = collect($rolePermissionsMap)->flatten()->unique()->values()->all();
            }
        }
        // Seeded Goal users often have null rights — fall back to role defaults so edit shows the real matrix.
        $effectivePermissions = !empty($assignedPermissions)
            ? $assignedPermissions
            : ($defaultPermissions ?? ($rolePermissionsMap[(int) $user->crm_role_type] ?? []));
        $nameParts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $editInitials = strtoupper(substr($nameParts[0] ?? 'U', 0, 1) . substr($nameParts[1] ?? '', 0, 1)) ?: 'U';
        $statusVal = (string) old('status', (string) (int) $user->status);
    @endphp

    @php
        $hasAvatar = $user->avatar && file_exists(public_path('assets/employee_profile_image/' . $user->avatar));
        $avatarUrl = $hasAvatar ? asset('assets/employee_profile_image/' . $user->avatar) : '';
    @endphp

    <form method="POST" action="{{ route('users.update', $user->id) }}" id="editUserForm" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <div class="main-card">
            <div class="main-top">
                <aside class="profile-card">
                    <div class="avatar-wrap">
                        <div class="avatar-preview {{ $hasAvatar ? 'has-image' : '' }}" id="avatarPreview" @unless($hasAvatar) style="background:#e0e7ff;color:#4338ca;font-weight:800;font-size:1.6rem;border-style:solid;border-color:#fff;" @endunless>
                            <span id="avatarPlaceholder" class="{{ $hasAvatar ? 'd-none' : '' }}">{{ $editInitials }}</span>
                            <img src="{{ $avatarUrl }}" alt="Profile preview" id="avatarImage" class="{{ $hasAvatar ? '' : 'd-none' }}">
                        </div>
                        <label class="avatar-camera" for="avatar" title="Upload profile picture">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/jpg,image/gif" class="d-none">
                    </div>
                    <div class="profile-title">{{ $user->name }}</div>
                    <p class="profile-hint">{{ $user->email }}</p>
                    <p class="profile-hint mt-1 mb-0">Click the camera to update photo.</p>
                    @error('avatar')<div class="field-error-text mt-1">{{ $message }}</div>@enderror
                </aside>

                <div class="details-block">
                    <div class="section-title"><i class="bi bi-person-vcard"></i> User Details</div>
                    <div class="field-grid-2">
                        <div>
                            <label for="name" class="form-label">Name <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-person field-icn"></i>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                       value="{{ old('name', $user->name) }}" placeholder="Enter full name" autocomplete="name">
                            </div>
                            @error('name')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="email" class="form-label">Email <span class="req">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-envelope field-icn"></i>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                       value="{{ old('email', $user->email) }}" placeholder="Enter email address" autocomplete="email">
                            </div>
                            @error('email')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="mobile" class="form-label">Mobile / Phone</label>
                            <div class="input-icon">
                                <i class="bi bi-telephone field-icn"></i>
                                <input type="text" class="form-control @error('mobile') is-invalid @enderror" id="mobile" name="mobile"
                                       value="{{ old('mobile', $user->mobile) }}" maxlength="20" placeholder="Enter mobile number" autocomplete="tel">
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
                                        <option value="{{ $role->id }}" {{ (string) old('crm_role_type', $user->crm_role_type) === (string) $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('crm_role_type')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="password" class="form-label">Password <span class="text-muted fw-600" style="font-weight:600;color:#94a3b8;">(optional)</span></label>
                            <div class="input-icon is-password">
                                <i class="bi bi-lock field-icn"></i>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                                       placeholder="Leave blank to keep current" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            @error('password')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <div class="input-icon is-password">
                                <i class="bi bi-lock field-icn"></i>
                                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation"
                                       placeholder="Re-enter new password" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="password_confirmation" aria-label="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            @error('password_confirmation')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="sales_target" class="form-label">Sales Target ({{ config('app.currency_symbol', '₹') }})</label>
                            <div class="input-icon">
                                <i class="bi bi-bullseye field-icn"></i>
                                <input type="text" inputmode="decimal" data-decimals="2" class="form-control js-decimal @error('sales_target') is-invalid @enderror"
                                       id="sales_target" name="sales_target" value="{{ old('sales_target', $user->sales_target ?? 0) }}" placeholder="0">
                            </div>
                            @error('sales_target')<div class="field-error-text">{{ $message }}</div>@enderror
                        </div>
                        <div id="assign-manager-container" style="display: none;">
                            <label for="assign_manager" class="form-label">Assign Manager</label>
                            <div class="input-icon">
                                <i class="bi bi-person-check field-icn"></i>
                                <select class="uc-dropdown" id="assign_manager" name="assign_manager" data-select2-skip="1">
                                    <option value="">Select Manager</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}" @selected(old('assign_manager', $user->assign_manager)==$manager->id)>{{ $manager->name }}</option>
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
                <div class="field-grid-4">
                    <div>
                        <label for="goal_org_unit_id" class="form-label">Goal Org Unit</label>
                        <div class="input-icon">
                            <i class="bi bi-diagram-3 field-icn"></i>
                            <select class="uc-dropdown" id="goal_org_unit_id" name="goal_org_unit_id" data-select2-skip="1">
                                <option value="">Select Org Unit</option>
                                @foreach(($goalOrgUnits ?? []) as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('goal_org_unit_id', $user->goal_org_unit_id)==$unit->id)>{{ $unit->name }} ({{ $unit->type }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="job_role" class="form-label">Job Role</label>
                        <div class="input-icon">
                            <i class="bi bi-briefcase field-icn"></i>
                            <select class="uc-dropdown" id="job_role" name="job_role" data-select2-skip="1">
                                <option value="">Auto from CRM Role</option>
                                @foreach(($jobRoles ?? []) as $jr)
                                    <option value="{{ $jr }}" @selected(old('job_role', $user->job_role)===$jr)>{{ strtoupper($jr) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="reports_to_user_id" class="form-label">Reports To</label>
                        <div class="input-icon">
                            <i class="bi bi-people field-icn"></i>
                            <select class="uc-dropdown" id="reports_to_user_id" name="reports_to_user_id" data-select2-skip="1">
                                <option value="">Select Reporting User</option>
                                @foreach($managers as $manager)
                                    @php
                                        $mgrRole = optional($crmRoles->firstWhere('id', (int) $manager->crm_role_type))->name;
                                    @endphp
                                    <option value="{{ $manager->id }}" @selected(old('reports_to_user_id', $user->reports_to_user_id)==$manager->id)>
                                        {{ $manager->name }}{{ $mgrRole ? ' ('.$mgrRole.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label d-block">Status</label>
                        <div class="status-box {{ $statusVal === '1' ? '' : 'is-inactive' }}" id="statusBox">
                            <div class="form-check form-switch m-0">
                                <input type="hidden" name="status" id="statusValue" value="{{ $statusVal === '1' ? '1' : '0' }}">
                                <input class="form-check-input" type="checkbox" role="switch" id="status" value="1" {{ $statusVal === '1' ? 'checked' : '' }}>
                            </div>
                            <span class="status-pill {{ $statusVal === '1' ? '' : 'is-inactive' }}" id="statusLabel">
                                <span class="dot"></span>{{ $statusVal === '1' ? 'Active' : 'Inactive' }}
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
                    <button type="button" class="btn btn-perm-defaults" id="restoreAssignedPerms"><i class="bi bi-arrow-counterclockwise"></i> Restore Assigned</button>
                </div>
            </div>
            <div class="panel-body">
                <div id="permissions-list"
                     data-role-permissions='@json($rolePermissionsMap)'
                     data-assigned-permissions='@json($effectivePermissions)'
                     data-original-role="{{ $user->crm_role_type }}">
                    <div id="permission-message" class="d-none">Select a CRM Role to apply default permissions, then adjust as needed.</div>
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
                                            <input class="form-check-input" type="checkbox" name="crm_page_right[]" value="{{ $permission->guard_name }}" id="perm_{{ $permission->id }}" data-guard-name="{{ $permission->guard_name }}" {{ in_array($permission->guard_name, old('crm_page_right', $effectivePermissions)) ? 'checked' : '' }}>
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
            <button type="submit" class="btn-create" form="editUserForm"><i class="bi bi-check2-circle"></i> Update User</button>
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
    const jobRoleSelect = document.getElementById('job_role');
    const statusSwitch = document.getElementById('status');
    const statusLabel = document.getElementById('statusLabel');
    const statusBox = document.getElementById('statusBox');
    const assignManagerContainer = document.getElementById('assign-manager-container');
    const editForm = document.getElementById('editUserForm');
    if (typeof confirmFormSubmit === 'function' && editForm) {
        confirmFormSubmit(editForm, {
            title: 'Update user?',
            text: 'Save changes to this user?',
            confirmText: 'Yes, update user'
        });
    }
    if (!crmRoleSelect || !permissionsList) return;

    const jobRoleMap = {
        '4': 'md', '5': 'ceo', '6': 'rd', '7': 'cluster', '8': 'bm',
        '9': 'vrm', '10': 'jsm', '11': 'sm', '12': 'ssm', '13': 'pms', '14': 'accounts'
    };

    let rolePermissions = {};
    let assignedPermissions = [];
    let originalRoleId = '';
    try {
        rolePermissions = JSON.parse(permissionsList.getAttribute('data-role-permissions') || '{}');
    } catch (e) {
        rolePermissions = {};
    }
    try {
        assignedPermissions = JSON.parse(permissionsList.getAttribute('data-assigned-permissions') || '[]');
    } catch (e) {
        assignedPermissions = [];
    }
    originalRoleId = String(permissionsList.getAttribute('data-original-role') || '').trim();

    function setCheckedPermissions(perms) {
        const list = (perms || []).map(function (p) { return String(p).trim(); });
        permissionsList.querySelectorAll('.form-check input[type="checkbox"]').forEach(function (checkbox) {
            const guardName = String(checkbox.getAttribute('data-guard-name') || '').trim();
            checkbox.checked = list.includes(guardName);
        });
    }

    function applyRoleDefaults() {
        const selectedRoleId = crmRoleSelect.value;
        setCheckedPermissions(rolePermissions[selectedRoleId] || []);
        if (permissionMessage) permissionMessage.classList.toggle('d-none', !!selectedRoleId);
    }

    function syncJobRoleFromCrmRole(force) {
        if (!jobRoleSelect) return;
        const mapped = jobRoleMap[String(crmRoleSelect.value)] || '';
        if (!mapped) return;
        if (force || !jobRoleSelect.dataset.userOverride) {
            jobRoleSelect.value = mapped;
            jobRoleSelect.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof jobRoleSelect._ucRefresh === 'function') jobRoleSelect._ucRefresh();
        }
    }

    function syncAssignManager() {
        if (!assignManagerContainer) return;
        assignManagerContainer.style.display = parseInt(crmRoleSelect.value, 10) === 3 ? 'block' : 'none';
    }

    crmRoleSelect.addEventListener('change', function () {
        if (jobRoleSelect) delete jobRoleSelect.dataset.userOverride;
        syncJobRoleFromCrmRole(true);
        if (String(crmRoleSelect.value) === String(originalRoleId)) {
            setCheckedPermissions(assignedPermissions);
        } else {
            applyRoleDefaults();
        }
        syncAssignManager();
    });

    if (jobRoleSelect) {
        if (jobRoleSelect.value) jobRoleSelect.dataset.userOverride = '1';
        jobRoleSelect.addEventListener('change', function () {
            jobRoleSelect.dataset.userOverride = '1';
        });
    }

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
    document.getElementById('applyRoleDefaults')?.addEventListener('click', applyRoleDefaults);
    document.getElementById('restoreAssignedPerms')?.addEventListener('click', function () {
        setCheckedPermissions(assignedPermissions);
    });

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
        avatarImage.src = URL.createObjectURL(file);
        avatarImage.classList.remove('d-none');
        avatarPlaceholder?.classList.add('d-none');
        avatarPreview?.classList.add('has-image');
        if (avatarPreview) {
            avatarPreview.style.background = '';
            avatarPreview.style.color = '';
            avatarPreview.style.fontWeight = '';
            avatarPreview.style.fontSize = '';
        }
    });

    if (crmRoleSelect.value) {
        if (jobRoleSelect && !jobRoleSelect.value) syncJobRoleFromCrmRole(true);
        if (permissionMessage) permissionMessage.classList.add('d-none');
    }
    syncAssignManager();

    @if ($errors->any())
        if (typeof showFlashSwal === 'function') {
            showFlashSwal('error', @json($errors->first()));
        } else if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: @json($errors->first()), confirmButtonColor: '#7c3aed' });
        }
    @endif
})();
</script>
@endpush
