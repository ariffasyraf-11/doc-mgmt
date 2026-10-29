# Development Setup

## Requirements
- PHP 8.3+
- Composer 2
- MySQL
- Filament 5 compatible with Laravel 13

## Setup
1. Clone the repository and run composer install.
2. Copy .env.example to .env.
3. Configure DB_DATABASE, DB_USERNAME, and DB_PASSWORD.
4. For initial local administrator creation, set DMS_ADMIN_EMAIL and DMS_ADMIN_PASSWORD in the local .env file only.
5. Run php artisan key:generate.
6. Run php artisan migrate --seed.
7. Run php artisan serve and open /admin.

The seeder creates starter organization data and an administrator only when both administrator email and password are provided. Do not commit real credentials or keep a bootstrap password configured in production.

## Private file storage
The private disk uses storage/app/private/documents. Do not create a public symbolic link for this directory.

## Scheduled tasks
Run the Laravel scheduler every minute in production. The DMS reminder command is scheduled daily at 08:00 using the configured timezone.

## Before deployment
Set APP_DEBUG=false, use a least-privilege database account, configure TLS and backups, and test backup restoration and private-file recovery.
