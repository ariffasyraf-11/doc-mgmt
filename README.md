# Document Management System (doc-mgmt)

Internal document management for one organization, built with Laravel 13 and Filament 5.

## Current implementation

- Document metadata management in Filament.
- Department, category, and document-type foundation.
- Private file uploads with SHA-256 checksums and version history.
- Authorized download route for the current published version, with download audit records.
- Sequential/parallel review-cycle service, reviewer decisions, publication, and archive actions.
- Expiry dates and scheduled in-app database reminders.
- Role and visibility policy foundations.
- Initial automated access-policy tests.

This is an active first implementation, not yet production-ready. See docs/ROADMAP.md for remaining work.

## Requirements

- PHP 8.3+
- Composer 2
- MySQL
- Filament 5 compatible with Laravel 13

## Local setup

1. Clone this repository.
2. Run composer install.
3. Copy .env.example to .env.
4. Create the MySQL database configured by DB_DATABASE and update DB_USERNAME / DB_PASSWORD.
5. For the first local setup only, set DMS_ADMIN_EMAIL and DMS_ADMIN_PASSWORD in your local .env file. Do not commit credentials.
6. Run php artisan key:generate.
7. Run php artisan migrate --seed.
8. Run php artisan serve and open /admin.

The seeder creates the default Operations department, General category, Internal Document type, and a sequential review policy. It creates the initial administrator only when DMS_ADMIN_EMAIL and DMS_ADMIN_PASSWORD are provided.

## File security

Document files are stored on the private disk under storage/app/private/documents, outside the public web root. Do not create a public storage link for this directory. Downloads must go through the authenticated, policy-checked route.

## Reminder scheduler

The command is dms:process-reminders. Run the Laravel scheduler every minute in production; the reminder task itself runs daily at 08:00 in the configured application timezone.

## Important

The review UI and first workflow endpoints are implemented, but full CRUD for users/departments/document types, configurable reminder settings, retry monitoring, complete tests, backup/recovery instructions, and production hardening remain in the roadmap.
