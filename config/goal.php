<?php

return [

    /*
    |--------------------------------------------------------------------------
    | REV-01 / payout commercial rates
    |--------------------------------------------------------------------------
    | Revenue and expected payout are recognized at verified disbursement (PD-01).
    | Override via .env without code changes.
    */
    'revenue_rate' => (float) env('GOAL_REVENUE_RATE', 0.0035),
    'payout_rate' => (float) env('GOAL_PAYOUT_RATE', 0.00875),

    /*
    |--------------------------------------------------------------------------
    | VRM daily call target (display / KPI)
    |--------------------------------------------------------------------------
    */
    'daily_call_target' => (int) env('GOAL_DAILY_CALL_TARGET', 200),

    /*
    |--------------------------------------------------------------------------
    | Bank query SLA
    |--------------------------------------------------------------------------
    | Pre-due reminder window (days before due_at) for goal:bank-query-reminders.
    */
    'bank_query_predue_days' => (int) env('GOAL_BANK_QUERY_PREDUE_DAYS', 1),

];
