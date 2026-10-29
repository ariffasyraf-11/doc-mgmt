# Development Setup

Requirements: PHP 8.3+, Composer 2, Node.js/npm, MySQL, and Filament 5 compatible with Laravel 13.

1. Clone the repository.
2. Run composer install.
3. Copy .env.example to .env and configure the database.
4. Run php artisan key:generate.
5. Run php artisan migrate.
6. Create an administrator account securely.
7. Run php artisan serve and open /admin.

The private disk uses storage/app/private/documents. Do not create a public symbolic link for this directory.

Run the Laravel scheduler every minute in production. Before deployment, set APP_DEBUG=false, use a least-privilege database account, configure TLS and backups, and test backup restoration and private-file recovery.
