# Expiry and Notifications

## Initial reminder schedule
- 90 days before expiry
- 30 days before expiry
- 7 days before expiry
- On the expiry date

The first implementation sends database notifications to the document owner. Expiry is independent of workflow state: expired documents remain published and are marked expired until an authorized action is taken.

## Idempotency and retry
The document_reminders table has a unique key across document, recipient, reminder type, and expiry date. The command creates due events, then processes pending and failed events. It makes up to five attempts and can reclaim a processing event that has been stuck for more than 15 minutes.

Reminder delivery occurs outside the claim transaction. A database uniqueness constraint prevents duplicate reminder events, and a row lock prevents two scheduler runs from claiming the same event simultaneously. A failure is retained on the reminder record for diagnosis.

## Scheduling
The scheduler is defined in routes/console.php. Production must run the Laravel scheduler every minute; the reminder command itself runs daily at 08:00 in the configured application timezone.

## Remaining hardening
Add administrator-configurable schedules and recipients, next-review reminders, dashboard visibility for failed reminders, and automated tests for duplicate/concurrent processing and timezone boundaries.
