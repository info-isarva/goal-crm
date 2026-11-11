# Isarva CRM Developer Guide

## 1. Project Overview
Isarva CRM is a Laravel-based CRM application with modules for leads, deals, organizations, contacts, reporting, and user management.

## 2. Project Structure
- **app/**: Application logic (Controllers, Models, Observers, Providers, etc.)
- **resources/views/**: Blade templates for UI
- **routes/**: Route definitions (web.php, api.php)
- **database/**: Migrations, seeders, factories
- **public/**: Public assets and entry point
- **config/**: Configuration files
- **tests/**: Unit and feature tests

## 3. Setup & Installation
1. Clone the repository
2. Run `composer install` to install PHP dependencies
3. Run `npm install && npm run build` for frontend assets
4. Copy `.env.example` to `.env` and configure database and mail settings
5. Run `php artisan key:generate`
6. Run migrations: `php artisan migrate --seed`
7. Start the server: `php artisan serve`

## 4. Core Modules
- **Leads**: `Lead` model, `LeadsController`, views in `resources/views/leads/`
- **Deals**: `Deal` model, `DealsController`, views in `resources/views/deals/`
- **Organizations**: `Organization` model, `OrganizationsController`
- **Contacts**: `Person` model, `PeopleController`
- **Reports**: `UserReportController`, views in `resources/views/reports/`
- **User Management**: `User` model, `UsersController`, roles and permissions

## 5. Key Concepts
- **Eloquent Relationships**: Models use `belongsTo`, `hasMany`, etc. for data relations
- **Blade Templates**: UI built with Blade, Bootstrap, and custom CSS/JS
- **AJAX**: Used for dynamic popups and filtering in reports
- **Middleware**: Auth and permission checks
- **Validation**: Form requests and controller validation

## 6. Adding Features
- Create new models/controllers with `php artisan make:model` and `php artisan make:controller`
- Add routes in `routes/web.php`
- Create Blade views in `resources/views/`
- Use migrations for database changes
- Write tests in `tests/`

## 7. Deployment
- Use `php artisan config:cache` and `php artisan route:cache` for optimization
- Set up web server (Apache/Nginx) to point to `public/`
- Configure environment variables for production
- Use supervisor for queue workers if needed

## 8. Troubleshooting
- Check logs in `storage/logs/`
- Use `php artisan migrate:status` for migration issues
- Use `php artisan tinker` for quick model testing

## 9. Contribution Guidelines
- Follow PSR standards for PHP code
- Use feature branches and pull requests
- Write tests for new features
- Document code and update guides as needed

## 10. References
- [Laravel Documentation](https://laravel.com/docs)
- [Bootstrap Documentation](https://getbootstrap.com/docs)
- [VS Code](https://code.visualstudio.com/)

---

For further help, contact the lead developer or check the README.md for environment-specific instructions.
