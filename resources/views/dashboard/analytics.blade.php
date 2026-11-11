@extends('layouts.app')

@section('content')
    <style>
        /* Styling for dynamic analytics dashboard */
        .analytics-header {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .analytics-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .analytics-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .analytics-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .analytics-card .card-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .analytics-card .card-body {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            position: relative;
        }

        /* Loading overlay */
        .card-loader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.85);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
        }

        .analytics-page-skeleton {
            position: absolute;
            inset: 0;
            z-index: 20;
            padding: 16px;
            min-height: 100%;
            background: #eef2fa;
        }

        .analytics-skeleton-shell {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(148, 163, 184, 0.08);
        }

        .analytics-skeleton-block {
            position: relative;
            overflow: hidden;
            background: #edf1f9;
            border-radius: 8px;
        }

        .analytics-skeleton-block::after {
            position: absolute;
            inset: 0;
            content: "";
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.65), transparent);
            transform: translateX(-100%);
            animation: analytics-skeleton-shimmer 1.35s infinite;
        }

        .analytics-skeleton-header {
            height: 62px;
            margin-bottom: 24px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .analytics-skeleton-kpi {
            height: 82px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .analytics-skeleton-avatar {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 50%;
        }

        .analytics-skeleton-copy {
            display: grid;
            gap: 9px;
            width: min(100%, 190px);
        }

        .analytics-skeleton-line {
            height: 10px;
        }

        .analytics-skeleton-line.short {
            width: 58%;
        }

        .analytics-skeleton-line.medium {
            width: 78%;
        }

        .analytics-skeleton-chart {
            height: 274px;
            padding: 18px 24px;
        }

        .analytics-skeleton-chart-title {
            width: 120px;
            height: 10px;
            margin-bottom: 18px;
        }

        .analytics-skeleton-bars {
            height: 190px;
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 14px;
        }

        .analytics-skeleton-bar {
            width: 18px;
            height: 44%;
            border-radius: 5px 5px 0 0;
        }

        .analytics-skeleton-bar:nth-child(2) { height: 68%; }
        .analytics-skeleton-bar:nth-child(3) { height: 52%; }
        .analytics-skeleton-bar:nth-child(4) { height: 82%; }
        .analytics-skeleton-bar:nth-child(5) { height: 58%; }
        .analytics-skeleton-bar:nth-child(6) { height: 74%; }

        .analytics-skeleton-donut {
            height: 274px;
            padding: 18px 24px;
        }

        .analytics-skeleton-ring {
            width: 132px;
            height: 132px;
            margin: 14px auto 12px;
            border: 18px solid #edf1f9;
            border-right-color: #e3e9f5;
            border-radius: 50%;
        }

        .analytics-skeleton-list {
            height: 230px;
            padding: 18px 24px;
            display: grid;
            align-content: start;
            gap: 18px;
        }

        .analytics-skeleton-list-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .analytics-skeleton-list-copy {
            display: grid;
            gap: 8px;
            width: 55%;
        }

        .analytics-skeleton-list-value {
            width: 20%;
            height: 10px;
        }

        .analytics-skeleton-kpi,
        .analytics-skeleton-chart,
        .analytics-skeleton-donut,
        .analytics-skeleton-list {
            border-radius: 12px;
        }

        @media (max-width: 767.98px) {
            .analytics-page-skeleton {
                padding: 10px;
            }

            .analytics-skeleton-header {
                padding: 0 14px;
            }

            .analytics-skeleton-bars {
                gap: 8px;
            }

            .analytics-skeleton-bar {
                width: 12px;
            }
        }

        .analytics-skeleton-card {
            border-radius: 12px;
        }

        @keyframes analytics-skeleton-shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        /* KPI Styles */
        .kpi-container {
            text-align: left;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .kpi-change {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .kpi-change.up {
            color: #10b981;
        }

        .kpi-change.down {
            color: #ef4444;
        }

        .kpi-label {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .target-meter-gauge {
            position: relative;
            width: min(100%, 440px);
            height: 200px;
            margin: 0 auto;
            color: #0f2d5c;
            font-size: 12px;
        }

        .target-meter-track {
            position: absolute;
            top: 4px;
            left: 4%;
            right: 4%;
            height: 275px;
            overflow: hidden;
        }

        .target-meter-track::before,
        .target-meter-track::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
        }

        .target-meter-track::before {
            background: conic-gradient(from 270deg, #f8b778 0deg var(--target-angle), #e6e6e6 var(--target-angle) 180deg, transparent 180deg);
        }

        .target-meter-track::after {
            inset: 15%;
            background: #ffffff;
        }

        .target-meter-needle {
            position: absolute;
            left: 50%;
            bottom: 55px;
            width: 10px;
            height: 50%;
            border-radius: 65%;
            background: #565656;
            transform: translateX(-50%) rotate(var(--needle-angle));
            transform-origin: 50% 100%;
            clip-path: polygon(50% 0, 100% 100%, 0 100%);
        }

        .target-meter-needle::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: -4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #565656;
            transform: translateX(-50%);
        }

        .target-meter-zero,
        .target-meter-target {
            position: absolute;
            white-space: nowrap;
        }

        .target-meter-zero {
            left: 10%;
            bottom: 42px;
        }

        .target-meter-target {
            right: 4%;
            bottom: 42px;
        }

        .target-meter-status {
            position: absolute;
            left: 10%;
            right: 4%;
            bottom: -2px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: start;
            column-gap: 10px;
            font-size: 10px;
            line-height: 1.25;
        }

        .target-meter-stat {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .target-meter-stat-label {
            color: #64748b;
            white-space: nowrap;
        }

        .target-meter-stat-value {
            min-width: 0;
            white-space: nowrap;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .target-meter-progress .target-meter-stat-value {
            color: #22a879;
        }

        @media (max-width: 480px) {
            .target-meter-status {
                column-gap: 6px;
                font-size: 9px;
            }
        }

        @media (max-width: 767.98px) {
            .container-fluid.p-4 {
                padding: 12px 10px !important;
            }

            .analytics-header {
                padding: 12px;
                margin-bottom: 16px;
                gap: 12px !important;
                align-items: stretch !important;
            }

            .analytics-header>div:first-child,
            .analytics-header>div:last-child {
                width: 100%;
                min-width: 0;
            }

            .analytics-header>div:first-child {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 8px !important;
            }

            .analytics-header>div:first-child>.d-inline-flex {
                grid-column: 1 / -1;
                width: 100%;
                margin-right: 0 !important;
                justify-content: center;
            }

            .analytics-header>div:first-child>.dropdown,
            .analytics-header>div:first-child .dashboard-select-btn {
                min-width: 0;
                width: 100%;
            }

            .analytics-header>div:last-child {
                display: grid !important;
                grid-template-columns: auto 1fr 1fr auto;
                gap: 6px !important;
                align-items: center;
            }

            .analytics-header>div:last-child>div:first-child {
                display: contents !important;
            }

            .analytics-header>div:last-child>div:first-child>span {
                grid-column: 1;
                font-size: 10px;
            }

            .analytics-header>div:last-child>div:first-child>.dropdown {
                grid-column: 2;
            }

            .analytics-header>div:last-child .dashboard-select-btn {
                min-width: 0;
                width: 100%;
                padding: 8px 10px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #globalPeriodSelectorBtn {
                grid-column: 2;
            }

            #refreshDashboardBtn {
                grid-column: 3;
                justify-self: stretch;
            }

            .analytics-header>div:last-child>.dropdown {
                grid-column: 4;
                grid-row: 1;
            }

            #addComponentBtn,
            #createDashboardBtn {
                min-width: 0;
                width: 100%;
                padding: 8px 6px;
                white-space: normal;
                line-height: 1.15;
            }

            #addComponentBtn {
                grid-column: 1 / 3;
                grid-row: 2;
            }

            #createDashboardBtn {
                grid-column: 3 / 5;
                grid-row: 2;
            }

            .analytics-card .card-header {
                padding: 12px 14px;
            }

            .analytics-card .card-title {
                min-width: 0;
                font-size: 12px;
                letter-spacing: 0.3px;
            }

            .analytics-card .card-title .badge {
                flex: 0 0 auto;
            }

            .analytics-card .card-body {
                min-width: 0;
            }

            .target-meter-gauge {
                width: 100%;
                max-width: 440px;
            }

            .target-meter-status {
                left: 4%;
                right: 4%;
                column-gap: 5px;
            }

            #dashboardGrid {
                --bs-gutter-y: 12px;
            }
        }

        /* Table Widget Styling */
        .widget-table-container {
            flex-grow: 1;
            overflow-y: auto;
        }

        .widget-table {
            width: 100%;
            margin-bottom: 0;
            font-size: 13px;
        }

        .widget-table th {
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 8px 12px;
        }

        .widget-table td {
            padding: 6px 12px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Funnel Widget Styling */
        .funnel-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            width: 100%;
            padding: 5px 0;
        }

        .funnel-stage {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 12px;
            color: #ffffff;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .funnel-stage:hover {
            transform: scale(1.02);
        }

        /* Dropdown customization */
        .dashboard-select-btn {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            color: #334155;
            border-radius: 8px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dashboard-select-btn::after {
            display: none;
        }

        /* Action Menu Buttons */
        .card-action-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .card-action-btn:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .analytics-card.is-dragging {
            opacity: 0.45;
        }

        .analytics-card-drop-target {
            outline: 2px dashed #3b82f6;
            outline-offset: 4px;
        }

        #dashboardGrid.is-drag-over {
            outline: 2px dashed rgba(59, 130, 246, 0.45);
            outline-offset: 8px;
            border-radius: 12px;
        }

        .card-drag-handle {
            cursor: grab;
            color: #94a3b8;
            padding: 4px 2px;
            touch-action: none;
        }

        .card-drag-handle:active {
            cursor: grabbing;
        }
    </style>

    <div class="container-fluid p-4 position-relative">
        <div class="analytics-page-skeleton" id="analyticsPageSkeleton" role="status" aria-label="Loading analytics dashboard">
            <div class="analytics-skeleton-shell analytics-skeleton-header">
                <div class="analytics-skeleton-block analytics-skeleton-line short"></div>
                <div class="analytics-skeleton-block analytics-skeleton-line medium"></div>
                <div class="analytics-skeleton-block analytics-skeleton-line short"></div>
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-3"><div class="analytics-skeleton-shell analytics-skeleton-kpi"><div class="analytics-skeleton-block analytics-skeleton-avatar"></div><div class="analytics-skeleton-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div></div></div>
                <div class="col-12 col-md-3"><div class="analytics-skeleton-shell analytics-skeleton-kpi"><div class="analytics-skeleton-block analytics-skeleton-avatar"></div><div class="analytics-skeleton-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div></div></div>
                <div class="col-12 col-md-3"><div class="analytics-skeleton-shell analytics-skeleton-kpi"><div class="analytics-skeleton-block analytics-skeleton-avatar"></div><div class="analytics-skeleton-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div></div></div>
                <div class="col-12 col-md-3"><div class="analytics-skeleton-shell analytics-skeleton-kpi"><div class="analytics-skeleton-block analytics-skeleton-avatar"></div><div class="analytics-skeleton-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div></div></div>
                <div class="col-12 col-md-8"><div class="analytics-skeleton-shell analytics-skeleton-chart"><div class="analytics-skeleton-block analytics-skeleton-chart-title"></div><div class="analytics-skeleton-bars"><div class="analytics-skeleton-block analytics-skeleton-bar"></div><div class="analytics-skeleton-block analytics-skeleton-bar"></div><div class="analytics-skeleton-block analytics-skeleton-bar"></div><div class="analytics-skeleton-block analytics-skeleton-bar"></div><div class="analytics-skeleton-block analytics-skeleton-bar"></div><div class="analytics-skeleton-block analytics-skeleton-bar"></div></div></div></div>
                <div class="col-12 col-md-4"><div class="analytics-skeleton-shell analytics-skeleton-donut"><div class="analytics-skeleton-block analytics-skeleton-chart-title"></div><div class="analytics-skeleton-block analytics-skeleton-ring"></div><div class="analytics-skeleton-block analytics-skeleton-line medium"></div></div></div>
                <div class="col-12 col-md-6"><div class="analytics-skeleton-shell analytics-skeleton-list"><div class="analytics-skeleton-block analytics-skeleton-chart-title"></div><div class="analytics-skeleton-list-row"><div class="analytics-skeleton-list-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div><div class="analytics-skeleton-block analytics-skeleton-list-value"></div></div><div class="analytics-skeleton-list-row"><div class="analytics-skeleton-list-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div><div class="analytics-skeleton-block analytics-skeleton-list-value"></div></div><div class="analytics-skeleton-list-row"><div class="analytics-skeleton-list-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div><div class="analytics-skeleton-block analytics-skeleton-list-value"></div></div></div></div>
                <div class="col-12 col-md-6"><div class="analytics-skeleton-shell analytics-skeleton-list"><div class="analytics-skeleton-block analytics-skeleton-chart-title"></div><div class="analytics-skeleton-list-row"><div class="analytics-skeleton-list-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div><div class="analytics-skeleton-block analytics-skeleton-list-value"></div></div><div class="analytics-skeleton-list-row"><div class="analytics-skeleton-list-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div><div class="analytics-skeleton-block analytics-skeleton-list-value"></div></div><div class="analytics-skeleton-list-row"><div class="analytics-skeleton-list-copy"><div class="analytics-skeleton-block analytics-skeleton-line medium"></div><div class="analytics-skeleton-block analytics-skeleton-line short"></div></div><div class="analytics-skeleton-block analytics-skeleton-list-value"></div></div></div></div>
            </div>
        </div>

        <!-- Header -->
        <div class="analytics-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Toggle Switcher between Classic Dashboard and Analytics Dashboard -->
                <div class="d-inline-flex p-1 rounded-3 border bg-white shadow-sm me-2"
                    style="height: 38px; align-items: center;">
                    <a href="{{ route('dashboard') }}"
                        class="btn btn-sm px-3 py-1 fw-semibold d-flex align-items-center gap-2 text-muted"
                        style="border-radius: 6px; font-size: 13px; transition: all 0.2s;">
                        <i class="bi bi-grid-fill"></i> Dashboard
                    </a>
                    <a href="{{ route('analytics.dashboard') }}"
                        class="btn btn-sm btn-primary px-3 py-1 fw-semibold d-flex align-items-center gap-2"
                        style="border-radius: 6px; font-size: 13px; transition: all 0.2s;">
                        <i class="bi bi-bar-chart-line-fill"></i> Analytics
                    </a>
                </div>
                <!-- Owner Filter (All) -->
                <div class="dropdown">
                    <button class="btn dropdown-toggle dashboard-select-btn" type="button" id="userSelectorBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="activeUserName">All</span>
                        <i class="bi bi-chevron-down ms-1" style="font-size:10px;"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0" id="userDropdownItems"
                        style="border-radius:8px; max-height: 250px; overflow-y: auto;">
                        <li><a class="dropdown-item fw-bold" href="#" onclick="switchUser('all', 'All')">All Users</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        @foreach($users as $u)
                            <li><a class="dropdown-item" href="#"
                                    onclick="switchUser('{{ $u->id }}', '{{ $u->name }}')">{{ $u->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Dashboard Selector -->
                <div class="dropdown">
                    <button class="btn dropdown-toggle dashboard-select-btn" type="button" id="dashboardSelectorBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-star-fill text-warning"></i>
                        <span id="activeDashboardName">Loading...</span>
                        <i class="bi bi-chevron-down ms-2" style="font-size:10px;"></i>
                    </button>
                    <ul class="dropdown-menu shadow-lg border-0" id="dashboardDropdownItems" style="border-radius:8px;">
                        <!-- Dynamically populated -->
                    </ul>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Global Period Selector -->
                <div class="d-flex align-items-center gap-2 me-2">
                    <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Period</span>
                    <div class="dropdown">
                        <button class="btn dropdown-toggle dashboard-select-btn" type="button" id="globalPeriodSelectorBtn"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="activeGlobalPeriodName">This Month</span>
                            <i class="bi bi-chevron-down ms-1" style="font-size:10px;"></i>
                        </button>
                        <ul class="dropdown-menu shadow-lg border-0" id="globalPeriodDropdownItems"
                            style="border-radius:8px;">
                            <li><a class="dropdown-item" href="#"
                                    onclick="switchGlobalPeriod('this_month', 'This Month')">This Month</a></li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="switchGlobalPeriod('last_month', 'Last Month')">Last Month</a></li>
                            <li><a class="dropdown-item" href="#" onclick="switchGlobalPeriod('6_months', '6 Months')">6
                                    Months</a></li>
                            <li><a class="dropdown-item" href="#" onclick="switchGlobalPeriod('this_year', 'Year')">Year</a>
                            </li>
                            <li><a class="dropdown-item" href="#"
                                    onclick="switchGlobalPeriod('previous_year', 'Previous Year')">Previous Year</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Action buttons -->
                <button class="btn btn-outline-secondary" id="refreshDashboardBtn" title="Refresh Dashboard"
                    style="border-radius:8px;">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button class="btn btn-outline-primary" id="addComponentBtn" style="border-radius:8px; font-weight:600;">
                    <i class="bi bi-plus-lg me-1"></i> Add Component
                </button>
                <button class="btn btn-primary" id="createDashboardBtn" style="border-radius:8px; font-weight:600;">
                    Create Dashboard
                </button>

                <!-- More options -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary card-action-btn" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:8px;">
                        <li><a class="dropdown-item" href="#" id="cloneCurrentDashboardBtn"><i
                                    class="bi bi-copy me-2"></i>Clone Current Dashboard</a></li>
                        <li><a class="dropdown-item" href="#" id="toggleReorderBtn"><i
                                    class="bi bi-arrows-move me-2"></i>Reorder Cards</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#" id="deleteCurrentDashboardBtn"><i
                                    class="bi bi-trash3 me-2"></i>Delete Current Dashboard</a></li>
                        <li><a class="dropdown-item" href="#" id="resetToDefaultBtn"><i
                                    class="bi bi-arrow-counterclockwise me-2"></i>Reset to Defaults</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Widgets Grid -->
        <div class="row g-4" id="dashboardGrid">
            <!-- Dynamically loaded widgets go here -->
        </div>
    </div>

    <!-- Modal: Add Component -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius:12px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addComponentModalLabel" style="color:#0f172a;">Add Analytics
                        Component</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="widgetBuilderForm">
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="widgetTitle" class="form-label fw-semibold text-muted small">Component Title</label>
                            <input type="text" class="form-control border-slate" id="widgetTitle"
                                placeholder="e.g. Sales Funnel" required style="border-radius:8px;">
                        </div>

                        <!-- Component Type -->
                        <div class="mb-3">
                            <label for="widgetType" class="form-label fw-semibold text-muted small">Component Type</label>
                            <select class="form-select border-slate" id="widgetType" required style="border-radius:8px;">
                                <option value="kpi">KPI (Metric Card)</option>
                                <option value="target_meter">Target Meter</option>
                                <option value="bar" selected>Bar Chart</option>
                                <option value="donut">Donut Chart</option>
                                <option value="funnel">Funnel Chart</option>
                                <option value="table">Table Widget</option>
                            </select>
                        </div>

                        <!-- Target value (Target Meter only) -->
                        <div class="mb-3" id="targetFormGroup" style="display:none;">
                            <label for="widgetTarget" class="form-label fw-semibold text-muted small">Target</label>
                            <input type="number" class="form-control border-slate" id="widgetTarget" min="0.01" step="0.01"
                                placeholder="Enter target amount" style="border-radius:8px;">
                        </div>

                        <!-- Module / Source -->
                        <div class="mb-3">
                            <label for="widgetModule" class="form-label fw-semibold text-muted small">Base Module</label>
                            <select class="form-select border-slate" id="widgetModule" required style="border-radius:8px;">
                                <option value="leads">Leads</option>
                                <option value="converted_leads">Converted Leads</option>
                                <option value="deals" selected>Deals</option>
                            </select>
                        </div>

                        <!-- Metric -->
                        <div class="mb-3">
                            <label for="widgetMetric" class="form-label fw-semibold text-muted small">Calculate
                                Metric</label>
                            <select class="form-select border-slate" id="widgetMetric" required style="border-radius:8px;">
                                <option value="count" selected>Count (Number of records)</option>
                                <option value="sum">Sum of Amount</option>
                                <option value="avg">Average of Amount</option>
                            </select>
                        </div>

                        <!-- Dimension (group by) -->
                        <div class="mb-3" id="dimensionFormGroup">
                            <label for="widgetDimension" class="form-label fw-semibold text-muted small">Grouping
                                Dimension</label>
                            <select class="form-select border-slate" id="widgetDimension" style="border-radius:8px;">
                                <!-- Populated dynamically based on Module -->
                            </select>
                        </div>

                        <!-- Time Period Filter -->
                        <div class="mb-3">
                            <label for="widgetPeriod" class="form-label fw-semibold text-muted small">Time Period
                                Filter</label>
                            <select class="form-select border-slate" id="widgetPeriod" required style="border-radius:8px;">
                                <option value="all" selected>All Time</option>
                                <option value="today">Today</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_quarter">This Quarter</option>
                                <option value="this_year">This Year</option>
                            </select>
                        </div>

                        <!-- Layout Column Size -->
                        <div class="mb-3">
                            <label for="widgetSize" class="form-label fw-semibold text-muted small">Layout Width</label>
                            <select class="form-select border-slate" id="widgetSize" required style="border-radius:8px;">
                                <option value="1">1/12 Width (Col-md-1)</option>
                                <option value="2">2/12 Width (Col-md-2)</option>
                                <option value="3">3/12 Width (Col-md-3) - Best for KPIs</option>
                                <option value="4">4/12 Width (Col-md-4)</option>
                                <option value="5">5/12 Width (Col-md-5)</option>
                                <option value="6" selected>6/12 Width (Col-md-6) - Half Width</option>
                                <option value="7">7/12 Width (Col-md-7)</option>
                                <option value="8">8/12 Width (Col-md-8)</option>
                                <option value="9">9/12 Width (Col-md-9)</option>
                                <option value="10">10/12 Width (Col-md-10)</option>
                                <option value="11">11/12 Width (Col-md-11)</option>
                                <option value="12">12/12 Width (Col-md-12) - Full Width</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                        style="border-radius:8px;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveWidgetBtn"
                        style="border-radius:8px; fw-semibold">Add Component</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Configuration & dashboard persistence
            const currencySymbol = @json($currency_symbol);
            const apiEndpoint = "{{ route('analytics.widget_data') }}";
            const saveDashboardsEndpoint = "{{ route('analytics.save_dashboards') }}";
            const cloneDashboardEndpoint = "{{ route('analytics.clone_dashboard') }}";

            // Default widget sets
            const defaultDashboards = {
                "Deal Insights": [
                    { id: "deal_kpi_rev", title: "Revenue This Month", type: "kpi", module: "deals", metric: "sum", dimension: "label", period: "this_month", size: "3" },
                    { id: "deal_kpi_cnt", title: "Deals Created", type: "kpi", module: "deals", metric: "count", dimension: "label", period: "this_month", size: "3" },
                    { id: "deal_kpi_pipe", title: "Deals in Pipeline", type: "kpi", module: "deals", metric: "count", dimension: "stage", period: "all", size: "3" },
                    { id: "deal_kpi_lost", title: "Revenue Lost", type: "kpi", module: "deals", metric: "sum", dimension: "stage", period: "this_month", size: "3" },
                    { id: "deal_funnel", title: "Deals by Stages", type: "funnel", module: "deals", metric: "count", dimension: "stage", period: "all", size: "6" },
                    { id: "deal_tbl_rev_owner", title: "Revenue by Users", type: "table", module: "deals", metric: "sum", dimension: "owner", period: "this_month", size: "6" },
                    { id: "deal_target_meter", title: "Sales Target Progress", type: "target_meter", module: "deals", metric: "sum", dimension: "label", period: "this_month", size: "6" },
                    { id: "deal_bar_amt_stage", title: "Amount by Stage", type: "bar", module: "deals", metric: "sum", dimension: "stage", period: "all", size: "6" },
                    { id: "deal_bar_amt_source", title: "Amount by Lead Source", type: "bar", module: "deals", metric: "sum", dimension: "lead_source", period: "all", size: "6" }
                ],
                "Lead Analytics": [
                    { id: "lead_kpi_today", title: "Today's Leads", type: "kpi", module: "leads", metric: "count", dimension: "status", period: "today", size: "3" },
                    { id: "lead_kpi_converted", title: "Converted Leads", type: "kpi", module: "converted_leads", metric: "count", dimension: "status", period: "all", size: "3" },
                    { id: "lead_bar_monthly", title: "Monthly Lead Creation", type: "bar", module: "leads", metric: "count", dimension: "status", period: "all", size: "6" },
                    { id: "lead_donut_source", title: "Leads by Industry", type: "donut", module: "leads", metric: "count", dimension: "lead_source", period: "all", size: "6" },
                    { id: "lead_tbl_source", title: "Top Lead Sources", type: "table", module: "leads", metric: "count", dimension: "lead_source", period: "all", size: "6" },
                    { id: "lead_bar_status", title: "Lead Status Analysis", type: "bar", module: "leads", metric: "count", dimension: "status", period: "all", size: "6" }
                ]
            };

            // Current State: the server is authoritative after the first successful save.
            const serverDashboards = @json($savedDashboards);
            const localDashboards = JSON.parse(localStorage.getItem("crm_analytics_dashboards")) || null;
            const hasServerDashboards = Object.keys(serverDashboards || {}).length > 0;
            let dashboards = hasServerDashboards ? serverDashboards : (localDashboards || defaultDashboards);
            let reorderMode = false;

            function placeTargetMeterAfterDealsStage(widgets) {
                const targetIndex = widgets.findIndex(widget => widget.type === "target_meter");
                const stageIndex = widgets.findIndex(widget => widget.id === "deal_funnel");

                if (targetIndex === -1 || stageIndex === -1) {
                    return widgets;
                }

                const [targetWidget] = widgets.splice(targetIndex, 1);
                const updatedRevenueUsersIndex = widgets.findIndex(widget => widget.id === "deal_tbl_rev_owner");
                const insertAfterIndex = updatedRevenueUsersIndex >= 0
                    ? updatedRevenueUsersIndex
                    : widgets.findIndex(widget => widget.id === "deal_funnel");
                widgets.splice(insertAfterIndex + 1, 0, targetWidget);
                return widgets;
            }

            if (dashboards["Deal Insights"] && !dashboards["Deal Insights"].some(widget => widget.type === "target_meter")) {
                dashboards["Deal Insights"].push({ id: "deal_target_meter", title: "Sales Target Progress", type: "target_meter", module: "deals", metric: "sum", dimension: "label", period: "this_month", size: "6" });
                placeTargetMeterAfterDealsStage(dashboards["Deal Insights"]);
            }
            let activeDashboard = localStorage.getItem("crm_analytics_active_dashboard") || Object.keys(dashboards)[0] || "Deal Insights";
            let activeUserId = "all";
            let activeGlobalPeriod = "this_month";
            let hasUserChangedGlobalPeriod = false;

            // Chart Palette Colors
            const chartPalette = [
                '#22c55e', '#3b82f6', '#f97316', '#a855f7', '#06b6d4', '#ec4899', '#facc15', '#64748b', '#475569', '#334155'
            ];

            const crmDimensions = {
                leads: [
                    { value: "status", text: "Status" },
                    { value: "lead_source", text: "Lead Source" },
                    { value: "label", text: "Priority Label" },
                    { value: "owner", text: "Owner (User)" }
                ],
                converted_leads: [
                    { value: "status", text: "Status" },
                    { value: "lead_source", text: "Lead Source" },
                    { value: "label", text: "Priority Label" },
                    { value: "owner", text: "Owner (User)" }
                ],
                deals: [
                    { value: "stage", text: "Deal Stage" },
                    { value: "lead_source", text: "Lead Source" },
                    { value: "label", text: "Priority Label" },
                    { value: "owner", text: "Owner (User)" }
                ]
            };

            $(document).ready(function () {
                // Init UI
                initDashboardDropdown();
                renderDashboard();
                $("#analyticsPageSkeleton").fadeOut(180, function () {
                    $(this).remove();
                });
                if (!hasServerDashboards && localDashboards) {
                    saveState();
                }

                // Listeners
                $("#refreshDashboardBtn").on("click", function () {
                    renderDashboard();
                });

                // Add Component click
                $("#addComponentBtn").click(function () {
                    // Setup selector dimensions based on current module selections
                    $("#addComponentModalLabel").text("Add Analytics Component");
                    $("#saveWidgetBtn").text("Add Component").removeData("edit-widget-id");
                    $("#widgetTitle").val("");
                    $("#widgetType").val("bar").trigger("change");
                    $("#widgetModule").val("deals").trigger("change");
                    $("#widgetMetric").val("count");
                    $("#widgetPeriod").val("all");
                    $("#widgetSize").val("6");
                    $("#widgetTarget").val("");
                    updateDimensionOptions();
                    $("#addComponentModal").modal("show");
                });

                $("#widgetModule").change(function () {
                    updateDimensionOptions();
                });

                $("#widgetType").change(function () {
                    const type = $(this).val();
                    const isTargetMeter = type === 'target_meter';

                    $("#targetFormGroup").toggle(isTargetMeter);
                    $("#widgetTarget").prop('required', isTargetMeter);

                    // If KPI is selected, defaults size to 3. If others, defaults size to 6.
                    if (type === 'kpi' || type === 'target_meter') {
                        $("#widgetSize").val("3");
                        $("#dimensionFormGroup").hide();
                    } else {
                        $("#widgetSize").val("6");
                        $("#dimensionFormGroup").show();
                    }
                });

                // Save component button
                $("#saveWidgetBtn").click(function () {
                    const title = $("#widgetTitle").val();
                    const type = $("#widgetType").val();
                    const target = Number($("#widgetTarget").val());
                    if (!title) {
                        $("#widgetTitle").focus();
                        return;
                    }
                    if (type === 'target_meter' && (!Number.isFinite(target) || target <= 0)) {
                        $("#widgetTarget").focus();
                        return;
                    }

                    const newWidget = {
                        id: "custom_" + Date.now(),
                        title: title,
                        type: type,
                        module: $("#widgetModule").val(),
                        metric: $("#widgetMetric").val(),
                        dimension: ['kpi', 'target_meter'].includes(type) ? 'status' : $("#widgetDimension").val(),
                        period: $("#widgetPeriod").val(),
                        size: $("#widgetSize").val()
                    };

                    if (type === 'target_meter') {
                        newWidget.target = target;
                    }

                    if (!dashboards[activeDashboard]) {
                        dashboards[activeDashboard] = [];
                    }
                    const editWidgetId = $("#saveWidgetBtn").data("edit-widget-id");
                    if (editWidgetId) {
                        const existingWidget = dashboards[activeDashboard].find(widget => widget.id === editWidgetId);
                        if (existingWidget) {
                            Object.assign(existingWidget, newWidget, { id: editWidgetId });
                        }
                    } else {
                        dashboards[activeDashboard].push(newWidget);
                    }
                    saveState();

                    $("#addComponentModal").modal("hide");
                    $("#saveWidgetBtn").removeData("edit-widget-id");
                    renderDashboard();
                });

                window.editWidget = function (id) {
                    const widget = (dashboards[activeDashboard] || []).find(item => item.id === id);
                    if (!widget) return;

                    $("#addComponentModalLabel").text("Edit Analytics Component");
                    $("#saveWidgetBtn").text("Save Changes").data("edit-widget-id", id);
                    $("#widgetTitle").val(widget.title || "");
                    $("#widgetType").val(widget.type || "bar").trigger("change");
                    $("#widgetModule").val(widget.module || "deals").trigger("change");
                    $("#widgetMetric").val(widget.metric || "count");
                    $("#widgetPeriod").val(widget.period || "all");
                    $("#widgetSize").val(widget.size || "6");
                    $("#widgetTarget").val(widget.type === 'target_meter' ? (widget.target || "") : "");
                    updateDimensionOptions();
                    $("#widgetDimension").val(widget.dimension || "status");
                    $("#addComponentModal").modal("show");
                };

                window.cloneWidget = function (id) {
                    const sourceWidget = (dashboards[activeDashboard] || []).find(item => item.id === id);
                    if (!sourceWidget) return;

                    const clone = name => {
                        const cleanName = String(name || '').trim();
                        if (!cleanName) return;

                        const clonedWidget = JSON.parse(JSON.stringify(sourceWidget));
                        clonedWidget.id = "custom_" + Date.now();
                        clonedWidget.title = cleanName;
                        dashboards[activeDashboard].push(clonedWidget);
                        saveState();
                        renderDashboard();
                    };

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            title: 'Clone component',
                            input: 'text',
                            inputValue: `${sourceWidget.title} Copy`,
                            inputLabel: 'Component title',
                            showCancelButton: true,
                            confirmButtonText: 'Clone',
                            cancelButtonText: 'Cancel',
                            inputValidator: value => value && value.trim() ? undefined : 'Enter a component title.'
                        }).then(result => {
                            if (result.isConfirmed) clone(result.value);
                        });
                        return;
                    }

                    const name = window.prompt('Enter a title for the cloned component:', `${sourceWidget.title} Copy`);
                    if (name) clone(name);
                };

                // Create Dashboard button
                $("#createDashboardBtn").click(function () {
                    const createDashboard = newName => {
                        const cleanedName = newName.trim();
                        if (dashboards[cleanedName]) {
                            if (window.Swal && typeof window.Swal.fire === 'function') {
                                window.Swal.fire({
                                    title: 'Dashboard already exists',
                                    text: 'Choose a different dashboard name.',
                                    icon: 'warning'
                                });
                            } else {
                                window.alert('A dashboard with this name already exists!');
                            }
                            return;
                        }

                        dashboards[cleanedName] = [];
                        activeDashboard = cleanedName;
                        saveState();
                        initDashboardDropdown();
                        renderDashboard();

                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            window.Swal.fire({
                                title: 'Dashboard created',
                                text: `${cleanedName} is ready for analytics components.`,
                                icon: 'success',
                                timer: 1300,
                                showConfirmButton: false
                            });
                        }
                    };

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            title: 'Create dashboard',
                            input: 'text',
                            inputLabel: 'Dashboard name',
                            inputPlaceholder: 'e.g. Sales Overview',
                            showCancelButton: true,
                            confirmButtonText: 'Create',
                            cancelButtonText: 'Cancel',
                            inputValidator: value => {
                                if (!value || !value.trim()) return 'Enter a dashboard name.';
                                if (dashboards[value.trim()]) return 'A dashboard with this name already exists.';
                                return undefined;
                            }
                        }).then(result => {
                            if (result.isConfirmed && result.value) createDashboard(result.value);
                        });
                        return;
                    }

                    const newName = window.prompt('Enter a name for the new Analytics Dashboard:');
                    if (newName && newName.trim()) createDashboard(newName);
                });

                $("#cloneCurrentDashboardBtn").click(function (e) {
                    e.preventDefault();
                    const cloneDashboard = name => {
                        const cleanedName = name.trim();
                        if (dashboards[cleanedName]) {
                            window.Swal?.fire({
                                title: 'Dashboard already exists',
                                text: 'Choose a different name for the clone.',
                                icon: 'warning'
                            });
                            return;
                        }

                        const widgets = JSON.parse(JSON.stringify(dashboards[activeDashboard] || []));
                        $.ajax({
                            url: cloneDashboardEndpoint,
                            method: 'POST',
                            contentType: 'application/json',
                            processData: false,
                            dataType: 'json',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            data: JSON.stringify({
                                name: cleanedName,
                                source_name: activeDashboard,
                                widgets: widgets
                            })
                        }).done(function () {
                            dashboards[cleanedName] = widgets;
                            activeDashboard = cleanedName;
                            localStorage.setItem('crm_analytics_dashboards', JSON.stringify(dashboards));
                            localStorage.setItem('crm_analytics_active_dashboard', activeDashboard);
                            initDashboardDropdown();
                            renderDashboard();
                            window.Swal?.fire({
                                title: 'Dashboard cloned',
                                text: `${cleanedName} is now active.`,
                                icon: 'success',
                                timer: 1300,
                                showConfirmButton: false
                            });
                        }).fail(function (xhr) {
                            window.Swal?.fire({
                                title: 'Clone failed',
                                text: xhr.responseJSON?.message || 'The cloned dashboard could not be saved.',
                                icon: 'error'
                            });
                        });
                    };

                    const defaultCloneName = `${activeDashboard} Copy`;
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            title: 'Clone dashboard',
                            input: 'text',
                            inputValue: defaultCloneName,
                            inputLabel: 'New dashboard name',
                            showCancelButton: true,
                            confirmButtonText: 'Clone',
                            cancelButtonText: 'Cancel',
                            inputValidator: value => {
                                if (!value || !value.trim()) return 'Enter a dashboard name.';
                                if (dashboards[value.trim()]) return 'A dashboard with this name already exists.';
                                return undefined;
                            }
                        }).then(result => {
                            if (result.isConfirmed && result.value) cloneDashboard(result.value);
                        });
                    } else {
                        const cloneName = window.prompt('Enter a name for the cloned dashboard:', defaultCloneName);
                        if (cloneName && cloneName.trim()) cloneDashboard(cloneName);
                    }
                });

                $("#toggleReorderBtn").click(function (e) {
                    e.preventDefault();
                    reorderMode = !reorderMode;
                    $(this).html(reorderMode
                        ? '<i class="bi bi-check2 me-2"></i>Finish Reordering'
                        : '<i class="bi bi-arrows-move me-2"></i>Reorder Cards');
                    renderDashboard();
                });

                // Delete Dashboard
                $("#deleteCurrentDashboardBtn").click(function (e) {
                    e.preventDefault();
                    const keys = Object.keys(dashboards);
                    if (keys.length <= 1) {
                        if (window.Swal && typeof window.Swal.fire === 'function') {
                            window.Swal.fire({
                                title: 'Cannot Delete',
                                text: 'You must keep at least one dashboard.',
                                icon: 'warning',
                                confirmButtonColor: '#3b82f6'
                            });
                        } else {
                            alert("You must keep at least one dashboard.");
                        }
                        return;
                    }

                    const dashboardToDelete = activeDashboard;
                    const performDelete = () => {
                        delete dashboards[dashboardToDelete];
                        activeDashboard = Object.keys(dashboards)[0];
                        saveState();
                        initDashboardDropdown();
                        renderDashboard();
                    };

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            title: 'Are you sure?',
                            text: `You want to delete the dashboard "${dashboardToDelete}"? This action cannot be undone.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Yes, delete it!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                performDelete();
                                window.Swal.fire({
                                    title: 'Deleted!',
                                    text: `Dashboard "${dashboardToDelete}" has been deleted successfully.`,
                                    icon: 'success',
                                    timer: 1300,
                                    showConfirmButton: false
                                });
                            }
                        });
                    } else {
                        if (confirm(`Are you sure you want to delete the dashboard "${dashboardToDelete}"?`)) {
                            performDelete();
                        }
                    }
                });

                // Reset to default
                $("#resetToDefaultBtn").click(function (e) {
                    e.preventDefault();
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            title: 'Are you sure?',
                            text: 'You want to reset all dashboards to system defaults? This will erase custom layouts.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Yes, reset them!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                dashboards = JSON.parse(JSON.stringify(defaultDashboards));
                                activeDashboard = Object.keys(dashboards)[0];
                                hasUserChangedGlobalPeriod = false;
                                activeGlobalPeriod = "this_month";
                                $("#activeGlobalPeriodName").text("This Month");
                                saveState();
                                initDashboardDropdown();
                                renderDashboard();
                                window.Swal.fire({
                                    title: 'Reset Completed',
                                    text: 'All dashboards have been reset to defaults.',
                                    icon: 'success',
                                    timer: 1300,
                                    showConfirmButton: false
                                });
                            }
                        });
                    } else {
                        if (confirm("Are you sure you want to reset all dashboards to system defaults? This will erase custom layouts.")) {
                            dashboards = JSON.parse(JSON.stringify(defaultDashboards));
                            activeDashboard = Object.keys(dashboards)[0];
                            hasUserChangedGlobalPeriod = false;
                            activeGlobalPeriod = "this_month";
                            $("#activeGlobalPeriodName").text("This Month");
                            saveState();
                            initDashboardDropdown();
                            renderDashboard();
                        }
                    }
                });
            });

            // Save configuration state
            function saveState() {
                localStorage.setItem("crm_analytics_dashboards", JSON.stringify(dashboards));
                localStorage.setItem("crm_analytics_active_dashboard", activeDashboard);

                $.ajax({
                    url: saveDashboardsEndpoint,
                    method: "POST",
                    contentType: "application/json",
                    processData: false,
                    dataType: "json",
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    data: JSON.stringify({
                        dashboards: dashboards,
                        active_dashboard: activeDashboard
                    })
                }).fail(function (xhr) {
                    console.error('Analytics dashboard save failed', xhr.status, xhr.responseText);
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            title: 'Could not save layout',
                            text: 'Your card order could not be saved to the account.',
                            icon: 'error'
                        });
                    }
                });
            }

            // Switch global user owner filter
            window.switchUser = function (userId, userName) {
                activeUserId = userId;
                $("#activeUserName").text(userName);
                renderDashboard();
            };

            // Switch global period filter
            window.switchGlobalPeriod = function (periodValue, periodName) {
                activeGlobalPeriod = periodValue;
                hasUserChangedGlobalPeriod = true;
                $("#activeGlobalPeriodName").text(periodName);
                renderDashboard();
            };

            // Populate the dropdown selection of dashboards
            function initDashboardDropdown() {
                const $dropdown = $("#dashboardDropdownItems");
                $dropdown.empty();

                Object.keys(dashboards).forEach(name => {
                    $dropdown.append(`
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" onclick="switchDashboard('${name}')">
                                <span>${name}</span>
                                ${name === activeDashboard ? '<i class="bi bi-check-lg text-primary"></i>' : ''}
                            </a>
                        </li>
                    `);
                });

                $("#activeDashboardName").text(activeDashboard);
            }

            // Switch active dashboard view
            window.switchDashboard = function (name) {
                activeDashboard = name;
                saveState();
                initDashboardDropdown();
                renderDashboard();
            };

            // Update modal dimensions input options based on selected module
            function updateDimensionOptions() {
                const module = $("#widgetModule").val();
                const $select = $("#widgetDimension");
                $select.empty();

                crmDimensions[module].forEach(dim => {
                    $select.append(`<option value="${dim.value}">${dim.text}</option>`);
                });
            }

            // Render the active dashboard widgets grid
            function renderDashboard() {
                const grid = $("#dashboardGrid");
                grid.empty();

                grid.off('.cardDrop')
                    .on('dragover.cardDrop', event => {
                        event.preventDefault();
                        grid.addClass('is-drag-over');
                    })
                    .on('dragleave.cardDrop', event => {
                        if (!grid[0].contains(event.relatedTarget)) {
                            grid.removeClass('is-drag-over');
                        }
                    })
                    .on('drop.cardDrop', event => {
                        event.preventDefault();
                        event.stopPropagation();
                        grid.removeClass('is-drag-over');

                        const draggedId = event.originalEvent.dataTransfer.getData('text/plain');
                        if (!draggedId) return;

                        const widgets = dashboards[activeDashboard];
                        const draggedIndex = widgets.findIndex(item => item.id === draggedId);
                        if (draggedIndex === -1) return;

                        const [draggedWidget] = widgets.splice(draggedIndex, 1);
                        const columns = [...grid[0].querySelectorAll('.analytics-widget-col')]
                            .filter(column => column.dataset.widgetId !== draggedId);
                        let insertIndex = widgets.length;
                        let nearestDistance = Number.POSITIVE_INFINITY;

                        columns.forEach(column => {
                            const rect = column.getBoundingClientRect();
                            const distance = Math.hypot(
                                event.clientX - (rect.left + rect.width / 2),
                                event.clientY - (rect.top + rect.height / 2)
                            );
                            if (distance < nearestDistance) {
                                nearestDistance = distance;
                                const targetIndex = widgets.findIndex(item => item.id === column.dataset.widgetId);
                                insertIndex = event.clientX > rect.left + rect.width / 2 ? targetIndex + 1 : targetIndex;
                            }
                        });

                        widgets.splice(Math.max(0, Math.min(insertIndex, widgets.length)), 0, draggedWidget);
                        saveState();
                        renderDashboard();
                    });

                const activeWidgets = dashboards[activeDashboard] || [];

                if (activeWidgets.length === 0) {
                    grid.html(`
                        <div class="col-12 text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="bi bi-graph-up" style="font-size: 48px;"></i>
                            </div>
                            <h5 class="fw-semibold">This dashboard has no components yet.</h5>
                            <p class="text-muted">Click the "Add Component" button to start building your visual layout.</p>
                            <button class="btn btn-primary" onclick="$('#addComponentBtn').click()" style="border-radius: 8px;">
                                <i class="bi bi-plus-lg me-1"></i> Add Component
                            </button>
                        </div>
                    `);
                    return;
                }

                // Generate card layout HTML for each widget
                activeWidgets.forEach(widget => {
                    const size = widget.size || "6";

                    let displayTitle = widget.title;
                    const periodNames = {
                        'today': 'Today',
                        'this_week': 'This Week',
                        'this_month': 'This Month',
                        'last_month': 'Last Month',
                        '6_months': '6 Months',
                        'this_quarter': 'This Quarter',
                        'this_year': 'This Year',
                        'previous_year': 'Previous Year',
                        'all': 'All Time'
                    };

                    if (hasUserChangedGlobalPeriod && widget.period !== 'today') {
                        const activePeriodName = periodNames[activeGlobalPeriod] || 'Selected Period';
                        if (displayTitle.toLowerCase().includes("this month")) {
                            displayTitle = displayTitle.replace(/this month/i, activePeriodName);
                        }
                    }

                    const cardHtml = `
                        <div class="col-12 col-md-${size} analytics-widget-col" id="widget-col-${widget.id}" draggable="${reorderMode}" data-widget-id="${widget.id}">
                            <div class="analytics-card card">
                                <div class="card-header">
                                    <h6 class="card-title d-flex align-items-center">
                                        <i class="bi bi-grip-vertical card-drag-handle me-1" title="Move component" aria-label="Move component"></i>
                                        ${displayTitle}
                                        <span class="badge bg-light text-muted ms-2 small" style="text-transform: capitalize; font-size:10px;">${widget.module.replace('_', ' ')}</span>
                                    </h6>
                                    <div class="d-flex align-items-center gap-1">
                                        <button class="card-action-btn small" onclick="loadWidgetData('${widget.id}')" title="Refresh Widget">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="card-action-btn small" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item" href="#" onclick="cloneWidget('${widget.id}'); return false;">Clone</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="editWidget('${widget.id}'); return false;">Edit</a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteWidget('${widget.id}'); return false;">Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" id="widget-body-${widget.id}" style="min-height: ${widget.type === 'kpi' ? '105px' : (widget.type === 'target_meter' ? '200px' : (widget.type === 'donut' ? '300px' : '230px'))}; padding: ${widget.type === 'kpi' ? '12px 16px' : (widget.type === 'target_meter' ? '8px 16px' : (widget.type === 'donut' ? '8px 12px' : '16px 20px'))}; justify-content: ${widget.type === 'kpi' ? 'center' : 'flex-start'};">
                                    <div class="card-loader" id="loader-${widget.id}">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    </div>
                                    <div class="widget-content" id="widget-content-${widget.id}"></div>
                                </div>
                            </div>
                        </div>
                    `;
                    grid.append(cardHtml);

                    const widgetColumn = document.getElementById(`widget-col-${widget.id}`);
                    widgetColumn.addEventListener('dragstart', event => {
                        if (!reorderMode) {
                            event.preventDefault();
                            return;
                        }
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', widget.id);
                        grid.addClass('is-drag-over');
                        widgetColumn.querySelector('.analytics-card').classList.add('is-dragging');
                    });
                    widgetColumn.addEventListener('dragend', () => {
                        grid.removeClass('is-drag-over');
                        widgetColumn.querySelector('.analytics-card').classList.remove('is-dragging');
                        document.querySelectorAll('.analytics-card-drop-target').forEach(card => card.classList.remove('analytics-card-drop-target'));
                    });
                    widgetColumn.addEventListener('dragover', event => {
                        if (!reorderMode) return;
                        event.preventDefault();
                        event.dataTransfer.dropEffect = 'move';
                        widgetColumn.querySelector('.analytics-card').classList.add('analytics-card-drop-target');
                    });
                    widgetColumn.addEventListener('dragleave', event => {
                        if (!widgetColumn.contains(event.relatedTarget)) {
                            widgetColumn.querySelector('.analytics-card').classList.remove('analytics-card-drop-target');
                        }
                    });
                    widgetColumn.addEventListener('drop', event => {
                        if (!reorderMode) return;
                        event.preventDefault();
                        event.stopPropagation();
                        const draggedId = event.dataTransfer.getData('text/plain');
                        const targetId = widgetColumn.dataset.widgetId;
                        if (!draggedId || draggedId === targetId) return;

                        const widgets = dashboards[activeDashboard];
                        const draggedIndex = widgets.findIndex(item => item.id === draggedId);
                        const targetIndex = widgets.findIndex(item => item.id === targetId);
                        if (draggedIndex === -1 || targetIndex === -1) return;

                        const [draggedWidget] = widgets.splice(draggedIndex, 1);
                        const adjustedTargetIndex = widgets.findIndex(item => item.id === targetId);
                        const insertIndex = event.clientY > widgetColumn.getBoundingClientRect().top + widgetColumn.offsetHeight / 2
                            ? adjustedTargetIndex + 1
                            : adjustedTargetIndex;
                        widgets.splice(insertIndex, 0, draggedWidget);
                        saveState();
                        renderDashboard();
                    });

                    // Fetch and render the data for this widget
                    loadWidgetData(widget.id);
                });
            }

            // Delete a widget card
            window.deleteWidget = function (id) {
                const removeWidget = () => {
                    const index = dashboards[activeDashboard].findIndex(w => w.id === id);
                    if (index !== -1) {
                        dashboards[activeDashboard].splice(index, 1);
                        saveState();
                        renderDashboard();
                    }
                };

                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        title: 'Remove component?',
                        text: 'This analytics component will be removed from this dashboard.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, remove it',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true
                    }).then(result => {
                        if (result.isConfirmed) {
                            removeWidget();
                            window.Swal.fire({
                                title: 'Removed',
                                text: 'The analytics component was removed.',
                                icon: 'success',
                                timer: 1200,
                                showConfirmButton: false
                            });
                        }
                    });
                    return;
                }

                if (window.confirm('Remove this analytics component?')) {
                    removeWidget();
                }
            };

            // Load actual aggregated data from backend API
            window.loadWidgetData = function (id) {
                const widget = dashboards[activeDashboard].find(w => w.id === id);
                if (!widget) return;

                const loader = $(`#loader-${id}`);
                const container = $(`#widget-content-${id}`);

                loader.show();
                container.empty();

                // Get API data
                $.ajax({
                    url: apiEndpoint,
                    method: "GET",
                    data: {
                        title: widget.title,
                        module: widget.module,
                        metric: widget.metric,
                        dimension: widget.dimension,
                        period: (widget.period === 'today') ? 'today' : ((widget.period && widget.period !== 'all' && !hasUserChangedGlobalPeriod) ? widget.period : activeGlobalPeriod),
                        user_id: activeUserId,
                        type: widget.type,
                        target: widget.type === 'target_meter' ? widget.target : undefined
                    },
                    success: function (response) {
                        loader.hide();
                        if (response.success) {
                            renderWidgetContent(id, widget, response);
                        } else {
                            container.html('<div class="text-danger small text-center">Failed to load data.</div>');
                        }
                    },
                    error: function () {
                        loader.hide();
                        container.html('<div class="text-danger small text-center">Connection error.</div>');
                    }
                });
            };

            // Helper to format KPI values
            function formatValue(value, metric) {
                if (metric === 'sum' || metric === 'avg') {
                    return currencySymbol + ' ' + value.toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
                return value.toLocaleString('en-IN');
            }

            // Render component body based on widget type and data
            function renderWidgetContent(widgetId, widget, data) {
                const container = $(`#widget-content-${widgetId}`);
                container.empty();

                if (widget.type === 'kpi') {
                    const displayVal = formatValue(data.total, widget.metric);
                    const subLabel = widget.metric === 'count' ? 'Total Count' : (widget.metric === 'sum' ? 'Total Amount' : 'Average Amount');

                    let iconClass = 'bi-bar-chart-fill text-primary';
                    if (widget.module === 'deals') iconClass = 'bi-currency-exchange text-success';
                    else if (widget.module === 'converted_leads') iconClass = 'bi-check2-circle text-warning';
                    else if (widget.metric === 'count') iconClass = 'bi-calculator text-info';

                    let displayTitle = widget.title;
                    const periodNames = {
                        'today': 'Today',
                        'this_week': 'This Week',
                        'this_month': 'This Month',
                        'last_month': 'Last Month',
                        '6_months': '6 Months',
                        'this_quarter': 'This Quarter',
                        'this_year': 'This Year',
                        'previous_year': 'Previous Year',
                        'all': 'All Time'
                    };

                    if (hasUserChangedGlobalPeriod && widget.period !== 'today') {
                        const activePeriodName = periodNames[activeGlobalPeriod] || 'Selected Period';
                        if (displayTitle.toLowerCase().includes("this month")) {
                            displayTitle = displayTitle.replace(/this month/i, activePeriodName);
                        }
                    }

                    const html = `
                        <div class="kpi-container d-flex align-items-center justify-content-between">
                            <div>
                                <div class="kpi-value">${displayVal}</div>
                                <div class="kpi-label">${displayTitle}</div>
                                <div class="small text-muted mt-1" style="font-size:11px;">${subLabel}</div>
                            </div>
                            <div class="fs-1 opacity-25">
                                <i class="bi ${iconClass}"></i>
                            </div>
                        </div>
                    `;
                    container.html(html);
                }

                else if (widget.type === 'target_meter') {
                    const target = Number(data.target || 0);
                    const achieved = Number(data.achieved || 0);
                    const percentage = Number(data.percentage || 0);
                    const remaining = Math.max(0, target - achieved);
                    const gaugePercentage = Math.min(100, Math.max(0, percentage));
                    const gaugeAngle = (gaugePercentage * 1.8).toFixed(2);
                    const needleAngle = (-90 + gaugePercentage * 1.8).toFixed(2);
                    const html = `
                        <div class="target-meter-gauge" style="--target-angle: ${gaugeAngle}deg; --needle-angle: ${needleAngle}deg;" role="img" aria-label="${widget.title}: ${formatValue(achieved, 'sum')} achieved of ${formatValue(target, 'sum')} target">
                            <div class="target-meter-track"></div>
                            <div class="target-meter-needle"></div>
                            <span class="target-meter-zero">0</span>
                            <span class="target-meter-target">Target: ${formatValue(target, 'sum')}</span>
                            <div class="target-meter-status">
                                <div class="target-meter-stat target-meter-achieved">
                                    <span class="target-meter-stat-label">Achieved</span>
                                    <span class="target-meter-stat-value">${formatValue(achieved, 'sum')}</span>
                                </div>
                                <div class="target-meter-stat target-meter-remaining">
                                    <span class="target-meter-stat-label">Remaining</span>
                                    <span class="target-meter-stat-value">${formatValue(remaining, 'sum')}</span>
                                </div>
                                <div class="target-meter-stat target-meter-progress">
                                    <span class="target-meter-stat-label">Progress</span>
                                    <span class="target-meter-stat-value">${gaugePercentage.toLocaleString('en-IN', { maximumFractionDigits: 2 })}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                    container.html(html);
                }

                else if (widget.type === 'table') {
                    if (data.labels.length === 0) {
                        container.html('<div class="text-muted text-center py-4 small">No data available</div>');
                        return;
                    }

                    const headerLabel = widget.dimension.charAt(0).toUpperCase() + widget.dimension.slice(1).replace('_', ' ');
                    const headerMetric = widget.metric === 'count' ? 'Count' : 'Total Amount';

                    let rowsHtml = '';
                    data.labels.forEach((label, i) => {
                        const formattedVal = formatValue(data.values[i], widget.metric);
                        rowsHtml += `
                            <tr>
                                <td>${i + 1}. ${label}</td>
                                <td class="text-end font-semibold fw-bold">${formattedVal}</td>
                            </tr>
                        `;
                    });

                    const html = `
                        <div class="widget-table-container">
                            <table class="widget-table table table-sm">
                                <thead>
                                    <tr>
                                        <th>${headerLabel}</th>
                                        <th class="text-end">${headerMetric}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        </div>
                    `;
                    container.html(html);
                }

                else if (widget.type === 'funnel') {
                    if (data.labels.length === 0) {
                        container.html('<div class="text-muted text-center py-4 small">No data available</div>');
                        return;
                    }

                    let funnelHtml = '<div class="funnel-container">';
                    const maxVal = Math.max(...data.values);

                    data.labels.forEach((label, i) => {
                        const val = data.values[i];
                        const formattedVal = formatValue(val, widget.metric);
                        // Funnel width gets progressively smaller (minimum width of 35% for readability)
                        const widthPercent = maxVal > 0 ? Math.max(35, 100 - (i * 8)) : 100;
                        const color = chartPalette[i % chartPalette.length];

                        funnelHtml += `
                            <div class="funnel-stage" style="width: ${widthPercent}%; background-color: ${color};">
                                <span>${label}</span>
                                <span>${formattedVal}</span>
                            </div>
                        `;
                    });
                    funnelHtml += '</div>';
                    container.html(funnelHtml);
                }

                else if (widget.type === 'bar' || widget.type === 'donut') {
                    if (data.labels.length === 0) {
                        container.html('<div class="text-muted text-center py-4 small">No data available</div>');
                        return;
                    }

                    const canvasId = `chart-${widgetId}`;
                    const canvasHeight = widget.type === 'donut' ? 280 : 180;
                    container.html(`<canvas id="${canvasId}" style="width: 100%; height: ${canvasHeight}px; max-height:${canvasHeight}px;"></canvas>`);

                    const ctx = document.getElementById(canvasId).getContext('2d');
                    const colors = widget.type === 'donut' ? chartPalette : chartPalette[1];
                    const donutLabelPlugin = {
                        id: 'donutExternalLabels',
                        afterDraw(chart) {
                            if (widget.type !== 'donut') return;

                            const { ctx, chartArea } = chart;
                            const meta = chart.getDatasetMeta(0);
                            const total = data.values.reduce((sum, value) => sum + Number(value), 0);
                            const centerX = (chartArea.left + chartArea.right) / 2;
                            const centerY = (chartArea.top + chartArea.bottom) / 2;
                            const labelRadius = Math.min(chartArea.width, chartArea.height) * 0.43;
                            const lineStartRadius = Math.min(chartArea.width, chartArea.height) * 0.34;
                            const labelGap = 54;
                            const labelHeight = 34;
                            const labelTop = chartArea.top + labelHeight / 2;
                            const labelBottom = chartArea.bottom - labelHeight / 2;

                            ctx.save();
                            ctx.font = '12px Arial, sans-serif';
                            ctx.fillStyle = '#0f2d5c';
                            ctx.textBaseline = 'middle';
                            ctx.lineWidth = 1;

                            const labelItems = meta.data.map((arc, index) => {
                                const angle = (arc.startAngle + arc.endAngle) / 2;
                                const direction = Math.cos(angle) >= 0 ? 1 : -1;
                                const lineStartX = centerX + Math.cos(angle) * lineStartRadius;
                                const lineStartY = centerY + Math.sin(angle) * lineStartRadius;
                                const elbowX = centerX + direction * labelRadius;
                                const labelX = elbowX + direction * labelGap;
                                const value = Number(data.values[index]);
                                const percentage = total > 0 ? (value / total * 100).toFixed(2) : '0.00';
                                const label = String(data.labels[index]);
                                const valueLabel = `${value.toLocaleString('en-IN')} (${percentage}%)`;

                                return {
                                    angle,
                                    direction,
                                    lineStartX,
                                    lineStartY,
                                    elbowX,
                                    labelX,
                                    labelY: centerY + Math.sin(angle) * labelRadius,
                                    label,
                                    valueLabel,
                                    color: colors[index % colors.length]
                                };
                            });

                            [-1, 1].forEach(direction => {
                                const sideItems = labelItems
                                    .filter(item => item.direction === direction)
                                    .sort((first, second) => first.labelY - second.labelY);

                                sideItems.forEach((item, index) => {
                                    item.labelY = Math.max(labelTop, item.labelY);
                                    if (index > 0) {
                                        item.labelY = Math.max(item.labelY, sideItems[index - 1].labelY + labelHeight);
                                    }
                                });

                                for (let index = sideItems.length - 1; index >= 0; index -= 1) {
                                    const item = sideItems[index];
                                    item.labelY = Math.min(labelBottom, item.labelY);
                                    if (index < sideItems.length - 1) {
                                        item.labelY = Math.min(item.labelY, sideItems[index + 1].labelY - labelHeight);
                                    }
                                }
                            });

                            labelItems.forEach(item => {
                                ctx.strokeStyle = item.color;

                                ctx.beginPath();
                                ctx.moveTo(item.lineStartX, item.lineStartY);
                                ctx.lineTo(item.elbowX, item.labelY);
                                ctx.lineTo(item.labelX - item.direction * 4, item.labelY);
                                ctx.stroke();

                                ctx.textAlign = item.direction === 1 ? 'left' : 'right';
                                ctx.fillText(item.label, item.labelX, item.labelY - 7);
                                ctx.fillText(item.valueLabel, item.labelX, item.labelY + 8);
                            });
                            ctx.restore();
                        }
                    };

                    new Chart(ctx, {
                        type: widget.type === 'donut' ? 'doughnut' : 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: colors,
                                borderColor: widget.type === 'donut' ? '#ffffff' : 'transparent',
                                borderWidth: widget.type === 'donut' ? 2 : 0,
                                barThickness: 24,
                                maxBarThickness: 32,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false,
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 10 }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            return ' ' + context.label + ': ' + formatValue(context.raw, widget.metric);
                                        }
                                    }
                                }
                            },
                            scales: widget.type === 'donut' ? {} : {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        font: { size: 10 },
                                        callback: function (value) {
                                            if (widget.metric === 'sum' || widget.metric === 'avg') {
                                                if (value >= 10000000) return currencySymbol + ' ' + (value / 10000000).toFixed(1) + 'Cr';
                                                if (value >= 100000) return currencySymbol + ' ' + (value / 100000).toFixed(1) + 'L';
                                                if (value >= 1000) return currencySymbol + ' ' + (value / 1000).toFixed(0) + 'k';
                                                return currencySymbol + ' ' + value;
                                            }
                                            return value;
                                        }
                                    },
                                    grid: { color: '#f1f5f9' }
                                },
                                x: {
                                    ticks: { font: { size: 10 } },
                                    grid: { display: false }
                                }
                            }
                        },
                        plugins: widget.type === 'donut' ? [donutLabelPlugin] : []
                    });
                }
            }
        </script>
    @endpush
@endsection