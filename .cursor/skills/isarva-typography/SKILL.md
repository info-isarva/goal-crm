---
name: isarva-typography
description: >-
  Apply and maintain the ISARVA CRM global Inter typography system. Use when
  editing UI styles, adding pages/components, changing fonts, headings, forms,
  tables, badges, sidebar/navbar text, or when the user mentions Inter,
  typography, font family, font weights, or enterprise text styling.
---

# ISARVA CRM Typography (Inter)

## Rule

Use **Inter** for every UI text element. Do not introduce new font families for CRM chrome. Keep monospace only for intentional codes/refs.

## Source of truth

- Global CSS: `public/css/isarva-typography.css`
- Font import: `resources/views/layouts/app.blade.php` (single Google Fonts Inter link)
- Tailwind sans stack: `tailwind.config.js` → `fontFamily.sans: ['Inter', ...]`

Do **not** add per-page Google Fonts Inter `<link>` tags. The layout already loads weights 400/500/600/700/800.

## CSS variables

```css
:root {
  --font-primary: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-size-page-title: 26px;
  --font-size-subtitle: 13px;
  --font-size-card-title: 16px;
  --font-size-section: 14px;
  --font-size-body: 14px;
  --font-size-label: 13px;
  --font-size-input: 14px;
  --font-size-table-header: 12px;
  --font-size-table: 13px;
  --font-size-nav: 13px;
  --font-size-button: 13px;
  --font-size-badge: 11px;
  --font-size-caption: 12px;
  --font-regular: 400;
  --font-medium: 500;
  --font-semibold: 600;
  --font-bold: 700;
  --font-extrabold: 800;
}
```

## Size / weight map

| UI Element | Size | Weight |
|---|---:|---:|
| Main page heading | 26px | 700 |
| Page subtitle | 13px | 400 |
| Card heading | 16px | 600 |
| Section heading | 14px | 600 |
| Form label | 13px | 500 |
| Input text | 14px | 400 |
| Table header | 12px | 600 |
| Table content | 13px | 400 |
| Sidebar / navbar | 13px | 500 |
| Button text | 13px | 600 |
| Status badge | 11px | 600 |
| Supporting text | 12px | 400 |

Prefer the CSS variables above instead of hard-coded sizes when adding new styles.

## When editing UI

1. Inherit Inter via global CSS; do not set competing `font-family` on new components.
2. Use weights from the map; avoid making every value ExtraBold.
3. Keep letter-spacing subtle (`-0.01em` headings, `0.04em` caps/table headers).
4. Preserve existing layouts, colors, fields, and business logic.
5. Exceptions allowed: PDF/email templates that require Arial/DejaVu; monospace for refs/codes; rich-text editor content fonts chosen by the user.

## Coverage checklist

Dashboard, Analytics, VRM, Loan Leads, BM Allocation, Loan Cases / Workspace, Documents, Bank Applications/Queries, Sanction, Disbursement, PMS/Accounts queues, Payouts, Targets, Branch MIS, Revenue Ledger, Org Hierarchy, Company, modals, dropdowns, forms, tables, Select2, DataTables, Flatpickr, toastr.
