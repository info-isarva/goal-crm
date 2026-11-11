# Project Technology Stack — Isarva CRM

This document summarizes the main technologies, libraries, tools, and where to find them in the codebase for the Isarva CRM project.

**Core**:
- **Framework:** Laravel (see `composer.json` require `laravel/framework` ^12.0)
- **PHP:** ^8.2 (see `composer.json` "php": "^8.2")
- **Dependency Manager:** Composer (project root `composer.json`)

**Backend Packages (selected)**:
- **laravel/fortify:** authentication scaffolding/features
- **laravel/socialite:** social auth integrations
- **maatwebsite/excel:** Excel exports
- **barryvdh/laravel-dompdf:** PDF generation
- **laravel/tinker** for REPL and debugging

Files of interest:
- App code: `app/` (Models, Http/Controllers, Mail, Notifications, Console/Commands)
- Artisan commands: `app/Console/Commands` (custom scheduled tasks & reminder processors)
- Mail templates / mailables: `app/Mail` and `resources/views/emails`
- Blade views: `resources/views` (UI pages, layouts)

**Frontend**:
- **Build tool:** Vite (`package.json` scripts: `dev`, `build`) with `laravel-vite-plugin`
- **Tailwind CSS** (`tailwindcss`, `@tailwindcss/forms`)
- **Alpine.js** for minimal interactivity
- **SweetAlert2** for alerts (`sweetalert2` dependency)
- **Bootstrap 5, jQuery, Select2** used in UI (see `resources/views` and `public/js` usage)

Frontend files and configuration:
- `resources/js/` — project JS entry points
- `resources/css/` — project CSS
- `vite.config.js`, `postcss.config.js`, `tailwind.config.js`
- `public/` — compiled assets, images and vendor files

**Node / NPM**:
- Node / NPM versions: use Node 18.x or newer (see `SOFTWARE_VERSIONS.md`)
- Dev tooling in `package.json`: `vite`, `tailwindcss`, `postcss`, `laravel-vite-plugin`, etc.

**Database**:
- Primary: MySQL (recommended)
- Local dev: SQLite is used by project scripts in `composer.json`
- Migrations: `database/migrations/`
- Factories & Seeders: `database/factories/`, `database/seeders/`

**Queues & Background Jobs**:
- Laravel queue system used in app (check `.env` `QUEUE_CONNECTION`), jobs live in `app/Jobs`
- Example commands: `php artisan queue:work` or `php artisan queue:listen`

**Notifications & Reminders**:
- Notifications: Laravel Notifications (DB notifications are used; check `app/Notifications` and `database/notifications` table)
- Reminder processing: custom artisan command (e.g. `ProcessTaskReminders` in `app/Console/Commands`) — scheduled via scheduler (`app/Console/Kernel.php`)

**Testing & Quality**:
- **PHPUnit**: configured in `phpunit.xml` (phpunit ^11.x in dev deps)
- **Laravel Pint** for code style
- **Faker** for generating test data

**Dev / Local Workflow**:
- Common commands:
  - Install PHP deps: `composer install`
  - Install JS deps: `npm install`
  - Run dev servers: `npm run dev` (Vite) and `php artisan serve` (or `composer dev` script)
  - Build assets: `npm run build`
  - Run migrations: `php artisan migrate`
  - Clear caches: `php artisan view:clear && php artisan cache:clear && php artisan route:clear`
  - Run tests: `php artisan test`

**Environment & Configuration**:
- Check `.env` for required variables: `DB_*`, `MAIL_*`, `QUEUE_CONNECTION`, `VITE_*`, etc.
- Mail driver: check `config/mail.php` and `.env` (SMTP provider details)

**Where to look for version info**:
- PHP & package versions: `composer.json`
- Node & frontend versions: `package.json`
- Recommended/extra versions: `SOFTWARE_VERSIONS.md`

**Notes & Recommendations**:
- The project uses Laravel features extensively: Blade templates, Notifications, Mailables, Queues, and custom artisan commands.
- If you need a canonical source of exact installed versions in your environment, run `composer show` and `npm ls --depth=0` on the server or developer machine.
- For a single place to keep these notes, this `TECH_STACK.md` was added to the project root.

---
Generated: November 21, 2025
