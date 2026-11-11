@extends('layouts.app')

@section('content')
<style>
    :root {
        --calendar-ink: #15233b;
        --calendar-muted: #71809a;
        --calendar-line: #e5ebf4;
        --calendar-blue: #2878f0;
        --calendar-teal: #19b7a2;
        --calendar-surface: #ffffff;
        --calendar-wash: #f4f7fc;
    }

    .calendar-page {
        position: relative;
        min-height: calc(100vh - 74px);
        padding: clamp(20px, 3vw, 36px);
        background:
            radial-gradient(circle at 92% 8%, rgba(40, 120, 240, 0.12), transparent 28%),
            linear-gradient(135deg, #f5f8fd 0%, #edf3fb 100%);
    }

    .calendar-page-content {
        position: relative;
        z-index: 1;
        opacity: 0;
        transition: opacity 220ms ease;
    }

    .calendar-page.is-ready .calendar-page-content {
        opacity: 1;
    }

    .calendar-page-skeleton {
        position: absolute;
        inset: 0;
        z-index: 3;
        padding: clamp(20px, 3vw, 36px);
        background:
            radial-gradient(circle at 92% 8%, rgba(40, 120, 240, 0.12), transparent 28%),
            linear-gradient(135deg, #f5f8fd 0%, #edf3fb 100%);
        transition: opacity 220ms ease, visibility 220ms ease;
    }

    .calendar-page-skeleton.is-hidden {
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
    }

    .calendar-page-skeleton-hero {
        height: clamp(180px, 21vw, 230px);
        margin-bottom: 22px;
        border-radius: 20px;
    }

    .calendar-page-skeleton-card {
        overflow: hidden;
        min-height: 590px;
        border: 1px solid rgba(218, 227, 239, 0.9);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(39, 65, 103, 0.08);
    }

    .calendar-page-skeleton-card-header {
        height: 76px;
        border-bottom: 1px solid var(--calendar-line);
    }

    .calendar-page-skeleton-card-body {
        padding: clamp(14px, 3vw, 28px);
    }

    .calendar-page-skeleton-card-body .calendar-skeleton-grid {
        min-height: 500px;
    }

    .calendar-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
        padding: clamp(24px, 4vw, 42px);
        color: #fff;
        background: linear-gradient(120deg, #173b75 0%, #216ac8 58%, #1aa999 100%);
        border-radius: 20px;
        box-shadow: 0 18px 42px rgba(29, 75, 135, 0.2);
    }

    .calendar-hero::after {
        content: '';
        position: absolute;
        width: 260px;
        height: 260px;
        right: 8%;
        bottom: -170px;
        border: 42px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        pointer-events: none;
    }

    .calendar-hero-content,
    .calendar-hero-actions {
        position: relative;
        z-index: 1;
    }

    .calendar-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .calendar-hero h1 {
        margin: 0 0 8px;
        font-size: clamp(1.8rem, 3vw, 2.7rem);
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .calendar-hero p {
        max-width: 540px;
        margin: 0;
        color: rgba(255, 255, 255, 0.82);
    }

    .calendar-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .calendar-hero-actions .btn {
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 600;
    }

    .calendar-shell {
        overflow: hidden;
        min-height: 666px;
        border: 1px solid rgba(218, 227, 239, 0.9);
        border-radius: 18px;
        background: var(--calendar-surface);
        box-shadow: 0 12px 30px rgba(39, 65, 103, 0.08);
    }

    .calendar-shell-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 24px;
        border-bottom: 1px solid var(--calendar-line);
    }

    .calendar-shell-title {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--calendar-ink);
        font-weight: 700;
    }

    .calendar-shell-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        color: #fff;
        background: linear-gradient(135deg, var(--calendar-blue), var(--calendar-teal));
        border-radius: 11px;
    }

    .calendar-shell-caption {
        margin: 0;
        color: var(--calendar-muted);
        font-size: 0.82rem;
    }

    .calendar-body {
        position: relative;
        min-height: 590px;
        padding: clamp(14px, 3vw, 28px);
    }

    .calendar-loading-skeleton {
        position: absolute;
        inset: clamp(14px, 3vw, 28px);
        z-index: 2;
        padding: 4px;
        background: var(--calendar-surface);
        transition: opacity 220ms ease, visibility 220ms ease;
    }

    .calendar-loading-skeleton.is-hidden {
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
    }

    .calendar-skeleton-block {
        position: relative;
        overflow: hidden;
        background: #edf2f9;
        border-radius: 7px;
    }

    .calendar-skeleton-block::after {
        position: absolute;
        inset: 0;
        content: '';
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.72), transparent);
        transform: translateX(-100%);
        animation: calendar-skeleton-shimmer 1.35s infinite;
    }

    .calendar-skeleton-toolbar {
        display: grid;
        grid-template-columns: 130px 1fr 180px;
        align-items: center;
        gap: 20px;
        height: 42px;
        margin-bottom: 20px;
    }

    .calendar-skeleton-title {
        width: 150px;
        height: 18px;
        justify-self: center;
    }

    .calendar-skeleton-actions {
        display: flex;
        gap: 8px;
    }

    .calendar-skeleton-actions .calendar-skeleton-block {
        width: 38px;
        height: 34px;
    }

    .calendar-skeleton-actions .calendar-skeleton-block:last-child {
        width: 82px;
    }

    .calendar-skeleton-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        grid-template-rows: 36px repeat(5, minmax(74px, 1fr));
        min-height: 410px;
        border: 1px solid var(--calendar-line);
        border-right: 0;
        border-bottom: 0;
    }

    .calendar-skeleton-cell {
        position: relative;
        min-width: 0;
        padding: 10px;
        border-right: 1px solid var(--calendar-line);
        border-bottom: 1px solid var(--calendar-line);
    }

    .calendar-skeleton-cell.header {
        padding: 9px 12px;
    }

    .calendar-skeleton-cell.header .calendar-skeleton-block {
        width: 58%;
        height: 8px;
    }

    .calendar-skeleton-cell.day .calendar-skeleton-block {
        width: 20px;
        height: 8px;
        margin-left: auto;
    }

    .calendar-skeleton-cell.day:nth-child(12) .calendar-skeleton-block,
    .calendar-skeleton-cell.day:nth-child(20) .calendar-skeleton-block,
    .calendar-skeleton-cell.day:nth-child(34) .calendar-skeleton-block {
        width: 64%;
        margin-top: 28px;
    }

    @keyframes calendar-skeleton-shimmer {
        to { transform: translateX(100%); }
    }

    #calendar {
        color: var(--calendar-ink);
        opacity: 0;
        transition: opacity 220ms ease;
    }

    #calendar.is-ready {
        opacity: 1;
    }

    #calendar .fc-toolbar {
        gap: 14px;
        margin-bottom: 22px;
    }

    #calendar .fc-toolbar-title {
        color: var(--calendar-ink);
        font-size: clamp(1.1rem, 2vw, 1.45rem);
        font-weight: 700;
    }

    #calendar .fc-button {
        border: 1px solid #d6e0ee;
        color: #38506f;
        background: #fff;
        box-shadow: none;
        font-size: 0.82rem;
        font-weight: 600;
    }

    #calendar .fc-button:hover,
    #calendar .fc-button:focus {
        border-color: var(--calendar-blue);
        color: var(--calendar-blue);
        background: #f3f7ff;
        box-shadow: 0 0 0 3px rgba(40, 120, 240, 0.12);
    }

    #calendar .fc-button-primary:not(:disabled).fc-button-active,
    #calendar .fc-button-primary:not(:disabled):active {
        border-color: var(--calendar-blue);
        color: #fff !important;
        background: var(--calendar-blue);
    }

    #calendar .fc-button-primary:not(:disabled).fc-button-active:hover,
    #calendar .fc-button-primary:not(:disabled).fc-button-active:focus {
        color: #fff !important;
        background: #1f67d2;
    }

    #calendar .fc-today-button {
        border-color: rgba(25, 183, 162, 0.35);
        color: #118d7e;
        background: #effbf8;
    }

    #calendar .fc-col-header-cell-cushion {
        padding: 10px 4px;
        color: var(--calendar-muted);
        font-size: 0.72rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    #calendar .fc-daygrid-day-number {
        padding: 10px;
        color: #50617a;
        font-size: 0.82rem;
        font-weight: 600;
    }

    #calendar .fc-day-today {
        background: rgba(40, 120, 240, 0.055) !important;
    }

    #calendar .fc-day-today .fc-daygrid-day-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        margin: 4px;
        padding: 0;
        color: #fff;
        background: var(--calendar-blue);
        border-radius: 50%;
    }

    #calendar .fc-event {
        margin: 2px 5px;
        padding: 4px 7px;
        border: 0;
        border-left: 3px solid rgba(255, 255, 255, 0.7);
        border-radius: 6px;
        background: linear-gradient(100deg, var(--calendar-blue), #4295f5);
        box-shadow: 0 3px 8px rgba(40, 120, 240, 0.18);
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    #calendar .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(40, 120, 240, 0.25);
    }

    #calendar .fc-daygrid-more-link {
        color: var(--calendar-blue);
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .calendar-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .calendar-shell-header {
            align-items: flex-start;
            flex-direction: column;
        }

        #calendar .fc-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        #calendar .fc-toolbar-chunk {
            width: 100%;
        }

        #calendar .fc-toolbar-title {
            margin-bottom: 4px;
        }

        #calendar .fc-button-group,
        #calendar .fc-toolbar-chunk:last-child {
            width: 100%;
        }

        #calendar .fc-button-group .fc-button,
        #calendar .fc-toolbar-chunk:last-child .fc-button {
            flex: 1;
        }
    }
</style>

<div class="calendar-page" id="calendarPage">
    <div class="calendar-page-skeleton" id="calendarPageSkeleton" role="status" aria-label="Loading calendar layout">
        <div class="calendar-skeleton-block calendar-page-skeleton-hero"></div>
        <div class="calendar-page-skeleton-card">
            <div class="calendar-skeleton-block calendar-page-skeleton-card-header"></div>
            <div class="calendar-page-skeleton-card-body">
                <div class="calendar-skeleton-toolbar">
                    <div class="calendar-skeleton-actions">
                        <div class="calendar-skeleton-block"></div>
                        <div class="calendar-skeleton-block"></div>
                    </div>
                    <div class="calendar-skeleton-block calendar-skeleton-title"></div>
                    <div class="calendar-skeleton-actions">
                        <div class="calendar-skeleton-block"></div>
                        <div class="calendar-skeleton-block"></div>
                    </div>
                </div>
                <div class="calendar-skeleton-grid" aria-hidden="true">
                    @for ($cell = 0; $cell < 42; $cell++)
                        <div class="calendar-skeleton-cell {{ $cell < 7 ? 'header' : 'day' }}">
                            <div class="calendar-skeleton-block"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <div class="calendar-page-content" id="calendarPageContent">
    <section class="calendar-hero" aria-labelledby="calendar-page-title">
        <div class="calendar-hero-content">
            <div class="calendar-kicker"><i class="bi bi-calendar3"></i> Schedule workspace</div>
            <h1 id="calendar-page-title">Important dates, in view.</h1>
            <p>Keep meetings, follow-ups, and key moments visible without losing the rhythm of your week.</p>
        </div>
        <div class="calendar-hero-actions">
            <a href="{{ route('dashboard') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="{{ route('meetings.index') }}" class="btn btn-outline-light">
                <i class="bi bi-people me-1"></i> Meetings
            </a>
        </div>
    </section>

    <section class="calendar-shell" aria-labelledby="calendar-shell-title">
        <div class="calendar-shell-header">
            <div class="calendar-shell-title" id="calendar-shell-title">
                <span class="calendar-shell-title-icon"><i class="bi bi-calendar2-week"></i></span>
                <span>Team calendar</span>
            </div>
            <p class="calendar-shell-caption">Select an event to open its meeting details</p>
        </div>
        <div class="calendar-body">
            <div class="calendar-loading-skeleton" id="calendarLoadingSkeleton" role="status" aria-label="Loading calendar">
                <div class="calendar-skeleton-toolbar">
                    <div class="calendar-skeleton-actions">
                        <div class="calendar-skeleton-block"></div>
                        <div class="calendar-skeleton-block"></div>
                    </div>
                    <div class="calendar-skeleton-block calendar-skeleton-title"></div>
                    <div class="calendar-skeleton-actions">
                        <div class="calendar-skeleton-block"></div>
                        <div class="calendar-skeleton-block"></div>
                    </div>
                </div>
                <div class="calendar-skeleton-grid" aria-hidden="true">
                    @for ($cell = 0; $cell < 42; $cell++)
                        <div class="calendar-skeleton-cell {{ $cell < 7 ? 'header' : 'day' }}">
                            <div class="calendar-skeleton-block"></div>
                        </div>
                    @endfor
                </div>
            </div>
            <div id="calendar"></div>
        </div>
    </section>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarPage = document.getElementById('calendarPage');
    var calendarPageSkeleton = document.getElementById('calendarPageSkeleton');
    var calendarBody = document.querySelector('.calendar-body');
    var calendarSkeleton = document.getElementById('calendarLoadingSkeleton');

    function showCalendarLoading() {
        if (calendarPage) {
            calendarPage.classList.remove('is-ready');
            calendarPage.setAttribute('aria-busy', 'true');
        }
        if (calendarPageSkeleton) {
            calendarPageSkeleton.classList.remove('is-hidden');
            calendarPageSkeleton.setAttribute('aria-hidden', 'false');
        }
        document.body.style.cursor = 'wait';
    }

    function hideCalendarLoading() {
        if (calendarPage) {
            calendarPage.classList.add('is-ready');
            calendarPage.setAttribute('aria-busy', 'false');
        }
        if (calendarPageSkeleton) {
            calendarPageSkeleton.classList.add('is-hidden');
            calendarPageSkeleton.setAttribute('aria-hidden', 'true');
        }
        document.body.style.cursor = '';
    }

    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            day: 'Day'
        },

        events: '/events',

        eventDisplay: 'block',
        height: 'auto',
        contentHeight: 650,
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            showCalendarLoading();
            window.location.href = '/meetings/' + info.event.id;
        },
        themeSystem: 'bootstrap5',
        nowIndicator: true,
        editable: true,
        selectable: true,
        dayMaxEvents: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: true
        }
    });

    calendar.render();
    window.requestAnimationFrame(function () {
        hideCalendarLoading();
        document.getElementById('calendar').classList.add('is-ready');
        if (calendarSkeleton) calendarSkeleton.classList.add('is-hidden');
        if (calendarBody) calendarBody.classList.add('is-ready');
    });
});
</script>
@endsection