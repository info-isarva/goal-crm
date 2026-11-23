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

    /*
    |--------------------------------------------------------------------------
    | Set Targets hierarchy
    |--------------------------------------------------------------------------
    | jump_pct: highlight when next-month target exceeds last month by this %.
    | role_weights: default split weights for branch → staff (admin override table).
    */
    'target_jump_pct' => (float) env('GOAL_TARGET_JUMP_PCT', 15),
    'target_unit_label' => env('GOAL_TARGET_UNIT_LABEL', '₹'),
    // Super Admin / Admin / MD / CEO do not receive personal targets — they only distribute.
    'target_exempt_crm_roles' => [0, 1, 4, 5],
    // No personal targets for leadership + non-sales ops (accounts / PMS).
    'target_exempt_job_roles' => [
        'ceo', 'md', 'admin', 'super_admin',
        'accounts', 'account', 'accountant', 'accounts_manager', 'accounts manager',
        'pms', 'pm', 'portfolio_manager', 'portfolio manager',
    ],
    'target_role_weights' => [
        'ssm' => 1.4,
        'senior_sales_manager' => 1.4,
        'sm' => 1.2,
        'sales_manager' => 1.2,
        'jsm' => 1.0,
        'junior_sales' => 1.0,
        'vrm' => 0.8,
    ],

];
