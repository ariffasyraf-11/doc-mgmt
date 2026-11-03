# Notification Setup and Activation

This guide explains how to activate the notification processes currently implemented in doc-mgmt.

## 1. Notification types currently supported

| Notification | Trigger | Delivery |
|---|---|---|
| Review assignment | A document review cycle assigns a reviewer | Database notification when the assignment is created |
| Document expiry reminder | A published document reaches a configured reminder offset before expiry or its expiry date | Scheduled database notification |
| Next-review reminder | A published document reaches a configured reminder offset before its next review date or on that date | Scheduled database notification |

Notifications are stored in Laravel's `notifications` table. The `User` model uses Laravel's `Notifiable` trait. Email, SMS, browser push, and external messaging channels are not enabled.

**Current UI limitation:** notification records are persisted in the database, but a complete Filament notification inbox/bell, unread counter, mark-as-read workflow, and notification preferences UI are not yet implemented. Do not interpret successful database delivery as confirmation that a user-facing notification centre is available. This remains tracked in the roadmap.

## 2. Requirements

Before enabling scheduled reminders:

- Configure a working MySQL database in `.env`.
- Set a valid `APP_KEY` and `APP_URL`.
- Set `APP_TIMEZONE=Asia/Kuala_Lumpur` (the application configuration already defaults to this timezone).
- Ensure the server's clock and timezone are synchronized.
- Ensure the Laravel application can write to `storage/` and `bootstrap/cache/`.

The current notification classes use Laravel's database channel directly. They do not require a queue worker for these notifications.

## 3. Apply database migrations

Run this after deploying the Phase 3 branch:

```bash
php artisan migrate --force
```

The initial DMS schema migration creates both `notifications` and `document_reminders` tables. The Phase 3 migration adds reminder configuration columns to `document_types`.

For an existing installation, confirm that the migrations have been applied:

```bash
php artisan migrate:status
```

Do not manually create duplicate notification or reminder tables.

## 4. Configure reminder rules in Filament

1. Sign in to the Admin panel using an active administrator account.
2. Open **Document Types**.
3. Create or edit the relevant document type.
4. Under **Expiry reminders**, select the reminder offsets:
   - 90 days before expiry
   - 30 days before expiry
   - 7 days before expiry
   - On the expiry date
5. Select the recipient groups:
   - Document owner
   - Administrators and document managers
   - Reviewers assigned to the latest review cycle
6. Under **Next review reminders**, select the offsets:
   - 30 days before the next review date
   - 7 days before the next review date
   - On the next review date
7. Select the recipient groups for next-review reminders.
8. Save the document type.

Default settings apply when a document type has no saved configuration:

- Expiry: 90, 30, 7, and 0 days; recipient is the document owner.
- Next review: 30, 7, and 0 days; recipients are the owner and document managers.

Clearing all offsets disables that reminder category. Clearing all recipient groups means no reminder records will be created for that category.

Only published documents are evaluated by the reminder command. A document must have an expiry date or next-review date for the corresponding reminder to be generated.

## 5. Enable the Laravel scheduler

The scheduled task is declared in `routes/console.php`:

```php
Schedule::command('dms:process-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();
```

The schedule uses the application's configured timezone. With the default `Asia/Kuala_Lumpur` setting, the reminder task is scheduled for 08:00 Malaysia time.

### Linux production server

Add the Laravel scheduler cron entry for the operating-system user that owns the application:

```cron
* * * * * cd /path/to/doc-mgmt && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/doc-mgmt` with the absolute project directory. Keep this cron entry running continuously. Laravel checks the schedule every minute and starts the reminder command at its configured time.

### Windows development server

For a Windows environment, create a Windows Task Scheduler task:

- Trigger: repeat every 1 minute, indefinitely.
- Action: start the PHP executable used by the project.
- Arguments: `artisan schedule:run`.
- Start in: the absolute project directory.

Use the same PHP installation and environment variables as the web application.

## 6. Verify activation

Run these commands from the project directory:

```bash
php artisan schedule:list
php artisan dms:process-reminders
```

- `schedule:list` should show `dms:process-reminders` scheduled daily at 08:00.
- The manual command runs reminder evaluation immediately; it does not wait until 08:00.
- Check the command output and Laravel log at `storage/logs/laravel.log` for errors.
- Inspect the `document_reminders` table for generated events and their statuses.
- Inspect the `notifications` table for database notifications addressed to the intended users.

Reminder statuses include `pending`, `processing`, `sent`, `failed`, and `cancelled`. Failed or stale processing attempts are retried by a later command run, up to five attempts.

The reminder event is deduplicated by document, recipient, reminder type, and due date. Running the command more than once for the same date should not create another event for the same key.

## 7. Test with a controlled document

For a safe functional check in a development database:

1. Create or select a document type and configure a short reminder offset, such as 7 days.
2. Create a published test document with an expiry date exactly 7 days from the application's current date.
3. Ensure the document has an active owner and select the owner as a recipient.
4. Run `php artisan dms:process-reminders`.
5. Confirm one matching row is created in `document_reminders` and a corresponding row appears in `notifications`.
6. Run the command again and confirm no duplicate reminder is created.
7. Repeat for `next_review_date` if next-review reminders are being checked.

Use a non-production database and remove test records after verification.

## 8. Operational notes

- Expiry does not automatically unpublish or archive a document.
- Next-review reminders do not automatically start a new review cycle.
- Recipients are resolved when the reminder command runs. Inactive users are excluded.
- Assigned reviewers are resolved from the latest review cycle.
- A scheduled task that is missed while the server is offline is not guaranteed to be replayed for past reminder offsets; keep the scheduler continuously available.
- The current implementation has no dedicated reminder monitoring dashboard. Administrators must inspect database records and application logs until that roadmap item is delivered.
- Before production activation, validate migration status, scheduler execution, timezone, recipient resolution, and notification persistence in the target environment.
